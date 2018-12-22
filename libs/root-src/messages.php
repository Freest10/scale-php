<?php
    class systemMessage{
        private $langs;

        public function  __construct()
        {
            $this->langs = \Langs::getInstance();
        }

        public function errorMessage($message){
            $title = $this->langs->getMessage("backend.errors.error");
            $this->renderMessage($title, $message);
        }

        public function renderMessage($title, $message){
                header('Content-Type: text/html; charset=UTF-8');
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
                            <div class="head"><h3>'.$title.'</h3></div>
                                <div class="cont">'.$message.'</div>
                        </div>
                    </div>
                </div>
                ';
                exit();
        }
    }
