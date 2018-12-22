<?php

class JsonException extends ExceptionMessage
{
    private $typeName;
    private $message;
    private $code;
    private $title;
    private $translated;

    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;
    }

    public function getTypeName()
    {
        return $this->typeName;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setCode($code)
    {
        $this->code = $code ? $code : 400;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getTranslated()
    {
        return $this->translated;
    }

    public function setTranslated($translated)
    {
        $this->translated = $translated;
    }

    public function showMessage()
    {
        header('Content-Type: application/json');
        http_response_code($this->code);
        $result = [];
        $result['description'] = $this->getTranslated() ? $this->getMessage() : $this->langs->getMessage($this->getMessage());
        $result['errorCode'] = $this->getTitle();
        print_r(json_encode($result));
    }
}