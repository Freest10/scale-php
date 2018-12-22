<?php
require_once CURRENT_WORKING_DIR . '/administration/api/main/ProxyApi.php';
require_once CURRENT_WORKING_DIR . '/administration/api/main/Api.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/SectionApi.php';

require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Events.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Structure.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/References.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Emarket.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Feedback.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Users.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Settings.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Templates.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/BackUps.php';
require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/Plugins.php';

use \SectionApi\Events as Events;
use \SectionApi\Structure as Structure;
use \SectionApi\References as References;
use \SectionApi\Emarket as Emarket;
use \SectionApi\Feedback as Feedback;
use \SectionApi\Users as Users;
use \SectionApi\Settings as Settings;
use \SectionApi\Templates as Templates;
use \SectionApi\BackUps as BackUps;
use \SectionApi\Plugins as Plugins;

$apiInstance = Api::getInstance();
$apiInstance->addMethodToNoProxy('Sections');
$apiInstance->addMethodToNoProxy('Langs');
$apiInstance->addMethodToNoProxy('LogOut');
$apiInstance->addMethodToNoProxy('ActiveLang');
$apiInstance->addMethodToNoProxy('SubDomains');
$apiInstance->addMethodToNoProxy('UploadContainer');
$apiInstance->addMethodToNoProxy('UploadItem');
$apiInstance->addMethodToNoProxy('FilemanagerLanguage');
$apiInstance->addMethodToNoProxy('Connectors');

$apiInstance->setClassOfApiToSectionTextId('events', new Events());
$apiInstance->setClassOfApiToSectionTextId('structure', new Structure());
$apiInstance->setClassOfApiToSectionTextId('templates', new Templates());
$apiInstance->setClassOfApiToSectionTextId('references', new References());
$apiInstance->setClassOfApiToSectionTextId('orders', new Emarket());
$apiInstance->setClassOfApiToSectionTextId('feedback', new Feedback());
$apiInstance->setClassOfApiToSectionTextId('users', new Users());
$apiInstance->setClassOfApiToSectionTextId('backups', new BackUps());
$apiInstance->setClassOfApiToSectionTextId('settings', new Settings());
$apiInstance->setClassOfApiToSectionTextId('plugins', new Plugins());

$apiInstance->reqToMainApiMethod();