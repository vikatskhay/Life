<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 03.06.12
 * Time: 16:11
 */

class LifeDb
{
    private $_db;

    function __construct($config)
    {
        assert(array_key_exists('url',      $config));
        assert(array_key_exists('username', $config));
        assert(array_key_exists('password', $config));

        $this->_db = new PDO($config['url'], $config['username'], $config['password']);
    }

    /**
     * @var LifeDb
     */
    private static $_instance;

    public static function init($config)
    {
        self::$_instance = new self($config);
    }

    /**
     * @var Memcache
     */
    private static $_memcache;
    private static $_memcachePrefix;

    public static function initMemcache($config)
    {
        if (extension_loaded('memcache')) {
            assert(array_key_exists('host',     $config));
            assert(array_key_exists('port',     $config));
            assert(array_key_exists('prefix',   $config));

            self::$_memcache = new Memcache();
            self::$_memcache->connect($config['host'], $config['port']);

            self::$_memcachePrefix = $config['prefix'];
        }
    }

    public static function getInstance()
    {
		if (!self::$_instance) {
            throw new Exception('The Db singleton must be initialized.');
		}

        return self::$_instance;
    }

    private function _cacheGet($key)
    {
        if (self::$_memcache) {
            $key = self::$_memcachePrefix . $key;

            return self::$_memcache->get($key);
        }

        return false;
    }

    private function _cacheSet($key, $val)
    {
        if (self::$_memcache) {
            $key = self::$_memcachePrefix . $key;

            self::$_memcache->set($key, $val);
        }
    }

    /**
     * @return int  Inserted id.
     */
    public function insertLife($status)
    {
        $stmt = $this->_db->prepare('
            INSERT INTO life (status) VALUES (?) RETURNING id
        ');

        $stmt->execute(array($status));

        $result = $stmt->fetchObject();
        return $result->id;
    }

    /**
     * @param  $id
     * @param  $status
     * @return bool Returns true on success or false on failure.
     */
    public function updateLifeStatus($id, $status)
    {
        $stmt = $this->_db->prepare('
            UPDATE life SET status = ? WHERE id = ?
        ');

        return $stmt->execute(array(
                                   $status,
                                   $id
                              ));
    }

    /**
     * @param  $lifeId
     * @param  $iteration
     * @param  $rows
     * @param  $cols
     * @param  $packedBitmap
     * @return bool Returns true on success or false on failure.
     */
    public function insertGeneration($lifeId, $iteration, $rows, $cols, $packedBitmap, $tweaked)
    {
        assert(isset($lifeId));
        assert(isset($iteration));
        assert(isset($rows));
        assert(isset($cols));
        assert(isset($packedBitmap));
        assert(isset($tweaked));

        $stmt = $this->_db->prepare('
            INSERT INTO generation (life_id, iteration, "rows", cols, bitmap, tweaked)
            VALUES (?, ?, ?, ?, ?, ?)
        ');

        return $stmt->execute(array(
                                   $lifeId,
                                   $iteration,
                                   $rows,
                                   $cols,
                                   $packedBitmap,
                                   intval($tweaked)
                              ));
    }

    /**
     * @param  $lifeId
     * @param  $iteration
     * @return stdClass|null      An object with fields:
     *                              - rows
     *                              - cols
     *                              - tweaked
     *                              - bitmap
     */
    public function fetchGeneration($lifeId, $iteration)
    {
        assert(isset($lifeId));
        assert(isset($iteration));

        // Check cache.
        $cacheKey = __FUNCTION__ . '_' . implode('_', func_get_args());

        $fromCache = $this->_cacheGet($cacheKey);
        if ($fromCache !== false) {
            // Return from cache.
            return $fromCache;
        }

        $stmt = $this->_db->prepare('
            SELECT tweaked, "rows", cols, bitmap
            FROM generation
            WHERE
                life_id = ? AND
                iteration = ?
        ');
        $stmt->execute(array(
                            $lifeId,
                            $iteration
                       ));

        $result = $stmt->fetchObject();

        $this->_cacheSet($cacheKey, $result);

        return $result;
    }

    /**
     * @param  $lifeId
     * @return stdClass|null      An object with fields:
     *                              - iteration
     *                              - tweaked
     *                              - rows
     *                              - cols
     *                              - bitmap
     */
    public function fetchLastGeneration($lifeId)
    {
        assert(isset($lifeId));

        $stmt = $this->_db->prepare('
            SELECT iteration, tweaked, "rows", cols, bitmap
            FROM generation
            WHERE life_id = ?
            ORDER BY iteration DESC
            LIMIT 1
        ');
        $stmt->execute(array($lifeId));

        return $stmt->fetchObject();
    }

}