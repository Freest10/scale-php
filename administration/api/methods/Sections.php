<?php

namespace Api;
class Sections
{
    private $sectionsController;

    function __construct()
    {
        require_once CURRENT_WORKING_DIR . '/controllers/Sections.php';
        $this->sectionsController = new \Controller\Sections();
    }

    public function get()
    {
        $this->sectionsController->getSections();
    }
}