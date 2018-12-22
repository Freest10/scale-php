<?php
$uri_massive = \Requests::getUriMassive();

if($uri_massive[4] == 'plugin_api'){
    require_once CURRENT_WORKING_DIR . '/administration/api/main/ProxyApi.php';
    require_once CURRENT_WORKING_DIR . '/administration/api/main/Api.php';
    require_once CURRENT_WORKING_DIR . '/administration/api/section_methods/SectionApi.php';
    require CURRENT_WORKING_DIR . "/plugins/$uri_massive[5]/api/index.php";

    $apiInstance = Api::getInstance();
    $apiInstance->setClassOfApiToSectionTextId('plugin', new \SectionApi\Plugin());
    $apiInstance->reqToPluginApiMethod($uri_massive[6], $uri_massive[7], $uri_massive[5]);
}else{
    require CURRENT_WORKING_DIR . '/administration/api/main/index.php';
}