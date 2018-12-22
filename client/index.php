<?php

use Macros\CustomMacros as CustomMacros;
use Macros\Macros as Macros;
use PageInfo\PageInfo as PageInfo;
use Pug\Pug;

include_once CURRENT_WORKING_DIR . '/vendor/autoload.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/index.php';
include_once CURRENT_WORKING_DIR . '/client/page_info/index.php';
include_once CURRENT_WORKING_DIR . '/client/macros/PugAdapter.php';
include_once CURRENT_WORKING_DIR . '/client/custom_macros/PugAdapter.php';

class ClientTemplate
{
    private $config;
    function __construct()
    {
        $this->config = MainConfiguration::getInstance();
    }

    public function renderClient()
    {
        $cachePath = CURRENT_WORKING_DIR . '/client/templates/cache';
        $pathToIndexPugTemplate = CURRENT_WORKING_DIR . '/client/templates/pug/index.pug';

        if ($this->isProduction()) {
            if ($this->isDeployed()) {
                $pug = new Pug(array(
                    'cache' => $cachePath,
                    'upToDateCheck' => false,
                    'basedir' => CURRENT_WORKING_DIR . '/client/templates/pug',
                ));

                echo $pug->render($pathToIndexPugTemplate, $this->getGlobalVariablesToPug());
            } else {
                $pug = new Pug(array(
                    'cache' => $cachePath
                ));

                list($success, $errors) = $pug->cacheDirectory(CURRENT_WORKING_DIR . '/client/templates/pug');
                $this->config->set("cache_pug", "main", "1");
                echo $pug->render($pathToIndexPugTemplate, $this->getGlobalVariablesToPug());
            }
        } else {
            $pug = new Pug(array(
                'cache' => $cachePath,
                'expressionLanguage' => 'js',
                'basedir' => CURRENT_WORKING_DIR . '/client/templates/pug',
            ));
            $this->config->set("cache_pug", "main", "0");

            echo $pug->render($pathToIndexPugTemplate, $this->getGlobalVariablesToPug());
        }
    }

    private function isProduction()
    {
        return !!$this->config->get("system", "production_mode");
    }

    private function isDeployed()
    {
        return !!$this->config->get("cache_pug", "main");
    }

    private function getGlobalVariablesToPug()
    {
        $macros = new Macros();
        $customMacros = new CustomMacros();
        $macrosPugAdapter = new \Macros\PugAdapter($macros);
        $customMacrosPugAdapter = new \CustomMacros\PugAdapter($customMacros);
        $page_info = new PageInfo();
        $page_info_create = $page_info->getPageInfo();
        $customVariables = array(
            'pageInfo' => $page_info_create,
            'reqParams' => $page_info->getParams(),
            'reqMethod' => $page_info->getRequestMethod()
        );

        return array_merge($customVariables, $macrosPugAdapter->getMacros(), $customMacrosPugAdapter->getCustomMacros());
    }
}