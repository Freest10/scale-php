<?php

class SystemException extends Exception
{
    private $type;
    private $title;
    private $translated;
    private static $types = [];

    function __construct($title = '', $message = '', $type = null, $code = 400, Throwable $previous = null, $translated = false)
    {
        parent::__construct($message, $code, $previous);
        $this->setType($type);
        $this->setTranslated($translated);
        $this->setTitle($title);
    }

    public static function pushType(ExceptionMessage $exception)
    {
        self::$types[$exception->getTypeName()] = $exception;
    }

    public function showMessage()
    {
        $exceptionByType = self::$types[$this->type];
        if (isset($exceptionByType)) {
            $exceptionByType->setCode($this->getCode());
            $exceptionByType->setTitle($this->getTitle());
            $exceptionByType->setMessage($this->getMessage());
            $exceptionByType->setTranslated($this->getTranslated());
            $exceptionByType->showMessage();
        } else {
            $this->defaultMessage();
        }
    }

    private function defaultMessage()
    {
        echo $this->getMessage();
    }

    private function setTitle($title)
    {
        $this->title = $title;
    }

    private function getTitle()
    {
        return $this->title;
    }

    private function setType($type)
    {
        $this->type = $type;
    }

    private function getTranslated()
    {
        return $this->translated;
    }

    private function setTranslated($translated)
    {
        $this->translated = $translated;
    }
}