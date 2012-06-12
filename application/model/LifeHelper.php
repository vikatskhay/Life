<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 11.06.12
 * Time: 2:23
 */
require_once 'Life.php';
require_once 'LifeCoords.php';
require_once 'LifeDb.php';


class LifeHelper
{

    public static function tweaksToBitmap($rows, $cols, $tweaks)
    {
        // Validate.
        assert(filter_var($rows, FILTER_VALIDATE_INT) > 0);
        assert(filter_var($cols, FILTER_VALIDATE_INT) > 0);
        assert(!empty($tweaks));

        // Make a bitmap.
        for ($y = 0; $y < $rows; $y++) {
            for ($x = 0; $x < $cols; $x++) {
                $bitmap[$y][$x] = 0;
            }
        }

        foreach ($tweaks as $coordsHash => $chStatus) {
            if ($chStatus == Life::CHSTATUS_ADD) {
                // Add a living cell.
                $coords = LifeCoords::parseHash($coordsHash);
                $bitmap[$coords->y][$coords->x] = 1;
            }
        }

        return $bitmap;
    }

    public static function bitmapToDbFormat($bitmap)
    {
        $toCompress = call_user_func_array('array_merge', $bitmap);
        $toCompress = implode('', $toCompress);

        $binary = gzcompress($toCompress);

        // Binary to Hex.
        return bin2hex($binary);
    }

    public static function dbFormatToBitmap($rows, $cols, $dbBitmap)
    {
        // Hex to binary.
        $dbBitmap = pack('H*', $dbBitmap);

        $data = gzuncompress($dbBitmap);
        $data = str_split($data, 1);
        $data = array_chunk($data, $cols);

        assert(count($data) == $rows);

        return $data;
    }

    /**
     * @static
     * @param  $id
     * @return Life|null
     */
    public static function loadFromDb($id)
    {
        $lastGen = LifeDb::getInstance()->fetchLastGeneration($id);

        if (!$lastGen) {
            // Nothing found.
            return null;
        }

        // Found.
        // Convert the bitmap.
        $bitmap = LifeHelper::dbFormatToBitmap($lastGen->rows, $lastGen->cols, $lastGen->bitmap);

        $life = new Life($id, $lastGen->iteration, $bitmap);
        return $life;
    }

}
