<?php

class ProxyApi
{
    private $noProxyClassNames = [];
    private $sectionApisToSectionTextId = [];

    protected function proxyCallMethod($className, $typeMethod, $data = null)
    {
        if (in_array($className, $this->noProxyClassNames, true) || $this->hasUserAccess($className, $typeMethod)) {
            $this->callMethod($className, $typeMethod, $data);
        } else {
            throw new SystemException('', 'backend.errors.access_denied', 'json');
        }
    }

    protected function proxyCallPluginMethod($className, $typeMethod, $pluginName, $data = null)
    {
        if (in_array($className, $this->noProxyClassNames, true) || $this->hasUserAccess($className, $typeMethod, $pluginName)) {
            $this->callPluginMethod($className, $typeMethod, $pluginName, $data);
        } else {
            throw new SystemException('', 'backend.errors.access_denied', 'json');
        }
    }

    protected function callMethod($className, $typeMethod, $data = null)
    {
        $classNameWithNameSpace = '\Api\\' . $className;
        $classInstance = \ClassesOperations::autoLoadClass($classNameWithNameSpace, '/administration/api/methods/' . $className . '.php');
        $classInstance->$typeMethod($data);
    }

    protected function callPluginMethod($className, $typeMethod, $pluginName, $data = null)
    {
        $classNameWithNameSpace = '\PluginApi\\' . $className;
        $classInstance = \ClassesOperations::autoLoadClass($classNameWithNameSpace, "/plugins/$pluginName/api/" . $className . '.php');
        $classInstance->$typeMethod($data);
    }

    public function hasUserAccess($className, $typeMethod, $pluginName = null)
    {
        $sectionName = $this->getSectionForApiClassName($className);
        if(!$sectionName){
            throw new SystemException('', 'backend.errors.could_find_section_for_api_method', 'json');
        }

        $usersInstance = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
        $userId = $usersInstance->getUserId();
        $userApiAccess = $sectionName === 'plugin' ? $usersInstance->getUserAccessesForPlugin($userId, $pluginName) : $usersInstance->getUserAccessesForSection($userId, $sectionName);

        return !!$userApiAccess[$typeMethod];
    }

    public function addMethodToNoProxy($classMethodName)
    {
        $this->noProxyClassNames[] = $classMethodName;
    }

    public function setClassOfApiToSectionTextId($textId, $apiClass)
    {
        $this->sectionApisToSectionTextId[$textId] = $apiClass;
    }

    private function getSectionForApiClassName($className)
    {
        $sectionName = null;
        foreach ($this->sectionApisToSectionTextId as $key => $value){
            if(in_array($className, $value->getArrayOfApiClassNames(), true)){
                $sectionName = $key;
                break;
            }
        }

        return $sectionName;
    }

}