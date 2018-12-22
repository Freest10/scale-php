<?php

//It is a template email message after success order. It send to admin email.
namespace Pug;

use Macros\CustomMacros as CustomMacros;
use Macros\Macros as Macros;
use PageInfo\PageInfo as PageInfo;
use SystemMacros\SystemMacros as SystemMacros;

include_once CURRENT_WORKING_DIR . '/vendor/autoload.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/page_info/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/PugAdapter.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/PugAdapter.php';
include_once CURRENT_WORKING_DIR . '/client/system_macros/index.php';

$config = \MainConfiguration::getInstance();
$isProduction = $config->get("system", "production_mode");
$isDeployed = !!$config->get("cache_pug", "mail");

$cachePath = CURRENT_WORKING_DIR . '/client/templates/cache/';
$baseDir = CURRENT_WORKING_DIR . '/client/templates/pug';
if ($isProduction) {
    if ($isDeployed) {
        $pug = new Pug(array(
            'cache' => $cachePath,
            'upToDateCheck' => false,
            'basedir' => $baseDir
        ));
    } else {
        $pug = new Pug(array(
            'cache' => $cachePath
        ));

        list($success, $errors) = $pug->cacheDirectory(CURRENT_WORKING_DIR . '/client/templates/pug');
        $config->set("cache_pug", "mail", "1");
    }
} else {
    $pug = new Pug(array(
        'cache' => $cachePath,
        'expressionLanguage' => 'js',
        'basedir' => $baseDir
    ));

    $config->set("cache_pug", "mail", "0");
}

function getGlobalVariablesToPug()
{
    $macros = new Macros();
    $customMacros = new CustomMacros();
    $macrosPugAdapter = new \Macros\PugAdapter($macros);
    $customMacrosPugAdapter = new \CustomMacros\PugAdapter($customMacros);
    $pageInfo = new PageInfo();
    $pageInfoCreate = $pageInfo->getPageInfo();
    $customVariables = array(
        'pageInfo' => $pageInfoCreate,
        'reqParams' => $pageInfo->getParams()
    );

    return array_merge($customVariables, $macrosPugAdapter->getMacros(), $customMacrosPugAdapter->getCustomMacros());
}

$mailResult = $pug->render(CURRENT_WORKING_DIR . '/client/email/emarket/pug/index.pug', getGlobalVariablesToPug());

$systemMacros = new SystemMacros();
$systemMacros->sendAdminEmail($mailResult);