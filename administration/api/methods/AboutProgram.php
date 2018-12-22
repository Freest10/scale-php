<?php

namespace Api {
    class AboutProgram
    {

        public function get()
        {
            require_once CURRENT_WORKING_DIR . '/controllers/AboutProgram.php';
            $aboutProgram = new \Controller\AboutProgram();// \ClassesOperations::autoLoadClass('AboutProgramController', '/controllers/about_program.php');
            $aboutProgram->getInfo();
        }

    }
}