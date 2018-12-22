<?php

namespace Api;
class TemplatesType
{
    public function get($id)
    {
        if ($id) {
            $this->getTemplatesById($id);
        } else {
            $this->getTemplates();
        }
    }

    public function put()
    {
        $_PUT = \Requests::getPUT();
        $templatesType = new \Controller\TemplatesType;
        $templatesType->addTemplate($_PUT["parent_id"]);
    }

    public function delete()
    {
        $_DELETE = \Requests::getDELETE();
        $templatesType = new \Controller\TemplatesType;
        $templatesType->deleteTemplate($_DELETE["id"]);
    }

    public function set($id)
    {
        $templatesType = new \Controller\TemplatesType;
        $templatesType->templateData = $_POST["templateData"];
        $templatesType->id = $id;
        $templatesType->updateTemplateData();
    }

    private function getTemplates()
    {
        $treeView = new \Tree;
        $treeView->jsTree("SELECT tpid.id, tpid.parent_id, tidn.name as text FROM template_parent_id tpid INNER JOIN template_id_name tidn ON tidn.id = tpid.id  ORDER BY parent_id ASC", "Шаблоны данных", true, true);
        echo $treeView->jsonDecode();
    }

    private function getTemplatesById($id)
    {
        $templatesType = new \Controller\TemplatesType;
        $templatesType->getTemplateById($id);
    }
}