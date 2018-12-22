<?php

namespace SystemMacros;

use \Controller\Messages as Messages;
use \Controller\Settings as Settings;

include_once CURRENT_WORKING_DIR . '/controllers/Messages.php';
include_once CURRENT_WORKING_DIR . '/controllers/Settings.php';

class SystemMacros
{

    public function sendAdminEmail($message)
    {
        $settings = new Settings();
        $adminEmail = $settings->getAdminEmailSettings();
        $messages = new Messages();
        $messages->doSendMail($adminEmail, NULL, NULL, $message, NULL);
    }

    public function sendEmail($message, $mailTo, $fromName)
    {
        $messages = new Messages();
        $messages->doSendMail($mailTo, $fromName, NULL, $message, NULL);
    }
}
