<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 04.06.12
 * Time: 0:34
 */

class Template
{
    private $_vars = array();

    /**
     * @var Template
     */
    private static $_instance;

    public static function getInstance()
    {
		if (null == self::$_instance) {
            self::$_instance = new self();
		}

        return self::$_instance;
    }


    public function __set($varName, $varValue)
    {
        $this->_vars[$varName] = $varValue;
    }

    public function render($template)
    {
        $path = APPLICATION_PATH . "/view/$template.phtml";

        if (!file_exists($path)) {
            throw new Exception("Template '$template' not found.");
        }


        // Prepare vars.
        if (!empty($this->_vars)) {
            foreach ($this->_vars as $varName => $varValue) {
                $$varName = $varValue;
            }
        }

        $template = $path;

        include APPLICATION_PATH . "/view/layout/layout.phtml";
    }

}