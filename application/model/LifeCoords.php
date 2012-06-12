<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 03.06.12
 * Time: 17:21
 */

class LifeCoords
{
    public $y;
    public $x;

    function __construct($y, $x)
    {
        $this->y = $y;
        $this->x = $x;
    }

    public function getHash()
    {
        return "{$this->y}_{$this->x}";
    }

    public static function parseHash($hash)
    {
        assert(!empty($hash));

        list($y, $x) = explode('_', $hash);

        return new self($y, $x);
    }
}
