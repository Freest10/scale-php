<?php

class ModalException extends ExceptionMessage
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

    public function getMessage()
    {
        return $this->message;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    private function getCode()
    {
        return $this->code;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getTranslated()
    {
        return $this->translated;
    }

    public function setTranslated($translated)
    {
        $this->translated = $translated;
    }

    public function showMessage(){
        header('Content-Type: text/html; charset=UTF-8');
        http_response_code($this->getCode());
        echo '
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <canvas></canvas>
                <link href="/css/administration/auth.css" rel="stylesheet">
                <link href="/css/administration/bootstrap.css" rel="stylesheet">
                <link href="/css/administration/style.css" rel="stylesheet">
                <script src="/js/administration/canvas_animation/zepto.min.js"></script>
                <script src="/js/administration/canvas_animation/index.js"></script>
                <script src="/js/administration/install.js"></script>
                <div id="auth_wrap">
                    <div id="auth_wrap_flex">
                        <div id="install">
                            <div class="head"><h3>'.$this->getTranslated() ? $this->getTitle() : $this->langs->getMessage($this->getTitle()).'</h3></div>
                                <div class="cont">'.$this->getTranslated() ? $this->getTitle() : $this->langs->getMessage($this->getMessage()).'</div>
                        </div>
                    </div>
                </div>
                ';
        exit();
    }
}