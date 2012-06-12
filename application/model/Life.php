<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 03.06.12
 * Time: 16:43
 */
require_once 'LifeCoords.php';
require_once 'LifeGeneration.php';
require_once 'LifeHelper.php';


class Life
{
    const RULE_B_MIN        = 3;
    const RULE_B_MAX        = 3;
    const RULE_S_MIN        = 2;
    const RULE_S_MAX        = 3;

    const STATUS_LIVING     = 1;
    const STATUS_STABILIZED = 10;
    const STATUS_DEAD       = -1;

    /**
     * @var int     Life's database ID.
     */
    private $_dbId;
    /**
     * @var int     Current iteration.
     */
    private $_currentIteration;
    /**
     * @var int     Universe's rows.
     */
    private $_rows;
    /**
     * @var int     Universe's columns.
     */
    private $_cols;
    /**
     * @var array   Full map of the universe (2-dimensional array).
     */
    private $_bitmap;
    /**
     * @var int     One of (STATUS_LIVING, STATUS_STABILIZED, STATUS_DEAD).
     */
    private $_status;
    /**
     * @var array   List of living cells.
     */
    private $_living;
    /**
     * @var array   List of changes from previous generation.
     */
    private $_changeList;

    public function __construct($id, $currentIteration, $bitmap)
    {
        assert(!empty($id));
        assert(!empty($currentIteration));
        assert(!empty($bitmap));

        $this->_dbId                = $id;
        $this->_currentIteration    = $currentIteration;

        $this->_rows                = count($bitmap);
        $this->_cols                = count($bitmap[0]);
        assert($this->_cols > 0);

        $this->_bitmap              = $bitmap;

        $this->_status              = self::STATUS_LIVING;
    }

    //******************************************************************************************************************
    // Static.
    //******************************************************************************************************************

    public static function factory($bitmap)
    {
        assert(!empty($bitmap));

        $rows = count($bitmap);
        $cols = count($bitmap[0]);
        assert($cols > 0);

        // Persist life.
        $dbId = LifeDb::getInstance()->insertLife(self::STATUS_LIVING);
        assert(null != $dbId);

        // Persist 1st generation.
        LifeDb::getInstance()->insertGeneration(
            $dbId,
            $iteration = 1,
            $rows,
            $cols,
            LifeHelper::bitmapToDbFormat($bitmap),
            $tweaked = false
        );

        return new self($dbId, 1, $bitmap);
    }

    //******************************************************************************************************************
    // Getters.
    //******************************************************************************************************************

    public function getId()
    {
        return $this->_dbId;
    }

    public function getCurrentIteration()
    {
        return $this->_currentIteration;
    }

    public function getRows()
    {
        return $this->_rows;
    }

    public function getCols()
    {
        return $this->_cols;
    }

    public function getBitmap()
    {
        return $this->_bitmap;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getLiving()
    {
        if (!isset($this->_living)) {
            $this->_evalLivingFromBitmap();
        }

        return $this->_living;
    }

    //******************************************************************************************************************
    // Actions.
    //******************************************************************************************************************

    /**
     * @param array $changes
     * @return void
     */
    public function tweak($changes)
    {
        assert(!empty($changes));

        // Apply the changes.
        $this->_applyChanges($changes);

        if (empty($this->_living)) {
            // All cells have been killed. The population has died out.
            $this->_onDied();
        }

        // Increment the iteration counter.
        $this->_currentIteration++;

        // Save to state DB.
        LifeDb::getInstance()->insertGeneration(
            $this->_dbId,
            $this->_currentIteration,
            $this->_rows,
            $this->_cols,
            LifeHelper::bitmapToDbFormat($this->_bitmap),
            true
        );
    }

    /**
     * @return array    List of changes (<coordsHash> => <changeStatus>).
     */
    public function nextGeneration()
    {
        if ($this->getStatus() == self::STATUS_STABILIZED ||
            $this->getStatus() == self::STATUS_DEAD) {
            // Game is over. There is no next generation.
            return null;
        }


        // Next.
        if ($this->_next()) {
            // Next generation exists.
            // Increment the iteration counter.
            $this->_currentIteration++;

            // Save state to DB.
            LifeDb::getInstance()->insertGeneration(
                $this->_dbId,
                $this->_currentIteration,
                $this->_rows,
                $this->_cols,
                LifeHelper::bitmapToDbFormat($this->_bitmap),
                false
            );
        }

        return $this->_changeList;
    }

    /**
     * @throws Exception
     * @param  $iteration
     * @return LifeGeneration|null
     */
    public function getPastGeneration($iteration)
    {
        if ($iteration > $this->_currentIteration) {
            throw new Exception(
                "Action not allowed: iteration $iteration is greater than the current one $this->_currentIteration.");
        }

        $fetched = LifeDb::getInstance()->fetchGeneration($this->_dbId, $iteration);
        if (!$fetched) {
            // Nothing fetched.
            return null;
        }

        // Fetched.
        // Convert the bitmap.
        $bitmap = LifeHelper::dbFormatToBitmap($fetched->rows, $fetched->cols, $fetched->bitmap);

        return new LifeGeneration(
            $iteration,
            $fetched->tweaked,
            $bitmap
        );
    }

    //******************************************************************************************************************
    // Evolution.
    //******************************************************************************************************************

    const CHSTATUS_ADD      = 1;
    const CHSTATUS_KILL     = -1;
    const CHSTATUS_NONE     = 0;

    private function _evalLivingFromBitmap()
    {
        if (empty($this->_bitmap)) {
            // The bitmap is empty. Nothing to evaluate.
            $this->_living = null;
            return;
        }


        foreach ($this->_bitmap as $y => $row) {
            foreach ($row as $x => $element) {
                if ($element) {
                    $coords = new LifeCoords($y, $x);
                    $coordsHash = $coords->getHash();

                    $this->_living[$coordsHash] = self::CHSTATUS_ADD;
                }
            }
        }
    }

    /**
     * @return bool     Whether the next generation exists or not.
     */
    private function _next()
    {
        // Initialize the list of living cells if needed.
        if (!isset($this->_living)) {
            $this->_evalLivingFromBitmap();
        }

        if (empty($this->_living)) {
            // The list of living cells is empty. The population has died out.
            $this->_onDied();

            return false;
        }


        // Walk through all living cells to make the change list.
        $this->_changeList = array();

        // A list of elements that have been analyzed. Used to avoid duplicate analyzing.
        // Tests have shown that for populations that are big enough this hashtable somewhat speeds up the procedure.
        $processedHashtable = array();

        foreach ($this->_living as $coordsHash => $lastChangeStatus) {
            $cellCoords = LifeCoords::parseHash($coordsHash);

            // Evaluate neighbour coordinates -> a list of elements to analyze.
            $elementsToAnalyze = $this->_getNeighboursCoords($cellCoords->y, $cellCoords->x);
            assert(count($elementsToAnalyze) == 8);

            // The cell itself.
            $elementsToAnalyze[] = $cellCoords;

            // Walk through all 9 elements and evaulate their fates.
            foreach ($elementsToAnalyze as $elCoords) {
                $elCoordsHash = $elCoords->getHash();

                if (array_key_exists($elCoordsHash, $processedHashtable)) {
                    // This element has been analized yet. Skip.
                    continue;
                }


                // Evaluate element's fate.
                $changeStatus = $this->_evalElementChangeStatus($elCoords->y, $elCoords->x);
                if ($changeStatus != self::CHSTATUS_NONE) {
                    // Element's state must be changed. Add to the change list.
                    $this->_changeList[$elCoordsHash] = $changeStatus;
                }

                // Add to the list of processed elements.
                $processedHashtable[$elCoordsHash] = $changeStatus;
            }
        }

        if (empty($this->_changeList)) {
            // No changes have been detected. The population has stabilized.
            $this->_onStabilized();

            return false;
        }

        // Apply the changes.
        $this->_applyChanges($this->_changeList);

        if (empty($this->_living)) {
            // All cells have been killed. The population has died out.
            $this->_onDied();

            return false;
        }

        return true;
    }

    private function _applyChanges($changes)
    {
        // Walk through the change list and apply the changes to _bitmap and _living simultaneously.
        foreach ($changes as $coordsHash => $changeStatus) {
            $coords = LifeCoords::parseHash($coordsHash);

            if ($changeStatus == self::CHSTATUS_ADD) {
                // Add new.
                $this->_bitmap[$coords->y][$coords->x] = 1;
                $this->_living[$coordsHash] = self::CHSTATUS_ADD;
            } elseif ($changeStatus == self::CHSTATUS_KILL) {
                // Kill.
                $this->_bitmap[$coords->y][$coords->x] = 0;
                unset($this->_living[$coordsHash]);
            }
        }
    }

    private function _getNeighboursCoords($y, $x)
    {
        // Evaluate neighbour coordinates toroidally.
        $northY = ($y == 0) ? $this->_rows - 1 : $y - 1;
        $southY = ($y == $this->_rows - 1) ? 0 : $y + 1;

        $westX = ($x == 0) ? $this->_cols - 1 : $x - 1;
        $eastX = ($x == $this->_cols - 1) ? 0 : $x + 1;

        return array(
            new LifeCoords($northY, $westX),
            new LifeCoords($northY, $x),
            new LifeCoords($northY, $eastX),
            new LifeCoords($y,      $westX),
            new LifeCoords($y,      $eastX),
            new LifeCoords($southY, $westX),
            new LifeCoords($southY, $x),
            new LifeCoords($southY, $eastX),
        );
    }

    private function _getNeighboursCount($y, $x)
    {
        // Evaluate neighbour coordinates.
        $neighbours = $this->_getNeighboursCoords($y, $x);
        assert(count($neighbours) == 8);

        // Count living neighbours.
        $count = 0;

        foreach ($neighbours as $coords) {
            $count += $this->_bitmap[$coords->y][$coords->x];
        }

        return $count;
    }

    private function _evalElementChangeStatus($y, $x)
    {
        $isAlive = $this->_bitmap[$y][$x];

        // Get amount of neighbours.
        $neighbours = $this->_getNeighboursCount($y, $x);

        if ($isAlive) {
            if ($neighbours < self::RULE_S_MIN || $neighbours > self::RULE_S_MAX) {
                // Kill.
                return self::CHSTATUS_KILL;
            }
        } else {
            if ($neighbours >= self::RULE_B_MIN && $neighbours <= self::RULE_B_MAX) {
                // Add.
                return self::CHSTATUS_ADD;
            }
        }

        // No change.
        return self::CHSTATUS_NONE;
    }

    private function _onStabilized()
    {
        $this->_status = self::STATUS_STABILIZED;

        // Update life's status.
        LifeDb::getInstance()->updateLifeStatus(
            $this->_dbId,
            self::STATUS_STABILIZED
        );
    }

    private function _onDied()
    {
        $this->_status = self::STATUS_DEAD;

        // Update life's status.
        LifeDb::getInstance()->updateLifeStatus(
            $this->_dbId,
            self::STATUS_DEAD
        );
    }

}
