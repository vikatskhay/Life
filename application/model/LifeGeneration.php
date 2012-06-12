<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 04.06.12
 * Time: 1:02
 */

class LifeGeneration
{
    public $iteration;
    public $tweaked;
    public $bitmap;

    function __construct($iteration, $tweaked, $bitmap)
    {
        $this->iteration    = $iteration;
        $this->tweaked      = $tweaked;
        $this->bitmap       = $bitmap;
    }
}
