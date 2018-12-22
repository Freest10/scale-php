<?php

abstract class ExceptionMessage
{
    protected $langs;
    function __construct($typeName)
    {
        $this->setTypeName($typeName);
        $this->langs = \Langs::getInstance();
    }

    abstract public function setCode($code);

    abstract public function setMessage($message);

    abstract public function getMessage();

    abstract public function setTypeName($typeName);

    abstract public function setTitle($title);

    abstract public function setTranslated($translated);

    abstract public function getTranslated();

    abstract public function getTitle();

    abstract public function getTypeName();

    abstract public function showMessage();
}