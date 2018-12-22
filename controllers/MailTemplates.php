<?php

namespace Controller;
class MailTemplates
{

    private $id;
    private $templatesData;
    private $elementData;

    public function getMailTemplates($begin, $limit)
    {
        $dataToJsonLimit = [
            'selectResponse' => 'SELECT tpid.id, tidn.name from template_parent_id tpid INNER JOIN template_id_name tidn ON tpid.id = tidn.id  WHERE tpid.parent_id=6',
            'countResponse' => 'template_parent_id WHERE parent_id = 6',
            'templatesData' => [
                'id' => true
            ],
            'begin' => $begin,
            'limit' => $limit,
            'type' => 'reference'
        ];
        \JsonOperations::createLimitJson($dataToJsonLimit);
    }

    public function getMailTemplate($id)
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->templatesData = [];
        $pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $pageId = $pageController->getFirstPageIdByType($id);
        $this->struct_data->structId = $pageId;
        $this->struct_data->structTemplateId = 7;
        $this->struct_data->structTypeId = 6;
        $this->struct_data->generateStructData();
        $this->templatesData = $this->struct_data->structData;
        $this->templatesData['name'] = TemplatesType::getTemplateName($id);
        \JsonOperations::printJsonFromPhp($this->templatesData);
    }

    public function setElementData($elementData)
    {
        $this->elementData = $elementData;
    }

    public function setMailTemplate($id)
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $pageId = $pageController->getFirstPageIdByType($id);
        if (!$pageId) {
            $templatesTypeController = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
            $mailTemplateName = $templatesTypeController->getTemplateName($id);
            $pageController->createPageName($mailTemplateName);
            $createdPageId = $pageController->getIdLastPage();
            $pageId = $createdPageId;
            $pageController->createTypePageById($id, $createdPageId);
        }

        $this->struct_data->structId = $pageId;
        $this->struct_data->structTypeId = 6;
        $this->struct_data->setFieldsData($this->elementData);
        \Response::goodResponse();
    }

}