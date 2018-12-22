<?php

namespace Controller;
class Messages
{
    public function getMessages($begin, $limit)
    {
        $dataToJsonLimit = [
            'selectResponse' => 'SELECT msgs.id as "id", msgs.date as "date", tin.name as "name" from messages msgs LEFT JOIN template_id_name tin ON msgs.template_id = tin.id ORDER BY msgs.date DESC',
            'countResponse' => 'LangsInterface',
            'templatesData' => [
                'id' => true,
                'name' => true,
                'date' => true
            ],
            'begin' => $begin,
            'limit' => $limit
        ];
        \JsonOperations::createLimitJson($dataToJsonLimit);
    }

    public function getMessage($id)
    {
        $dataToJsonLimit = [
            'selectResponse' => 'SELECT msgs.id as "id", msgs.date as "date", msgs.ip as "ip", msgs.message as "message", rfd.name as "address" from messages msgs LEFT JOIN reference_data rfd ON msgs.address_id = rfd.reference_data_id WHERE id=' . $id,
            'templatesData' => [
                'id' => true,
                'date' => true,
                'ip' => true,
                'message' => true,
                'address' => true
            ]
        ];
        $eventsModel = \ClassesOperations::autoLoadClass('\Controller\Events', '/controllers/Events.php');
        $eventsModel->newEvent($id, "id", "messages", 0);
        \JsonOperations::createLimitJson($dataToJsonLimit);
    }

    public function deleteMessages($ids)
    {
        if (count($ids) > 0) {
            foreach ($ids as $key => $value) {
                $this->deleteMessage($value);
            }
        }
        \Response::goodResponse();
    }

    private function deleteMessage($id)
    {
        \DataBase::justQueryToDataBase("DELETE FROM messages WHERE id =" . $id);
    }

    public function getClientMessages($id)
    {
        $templateType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
        return $templateType->getClientTemplateById($id);
    }

    public function sendClientMessages($id, $value)
    {
        $templateType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
        $templateType->getTemplateFields($id);
        if ($this->isHaveAllNecessarilyFields($templateType->template["fields"], $value)) {
            $value = $this->getValuesForMessage($templateType->template["fields"], $value);
            $this->sendMessage($id, $value);
        } else {
            return $templateType->langs->getMessage("backend.errors.required_fields");
        }
    }

    public function getValuesForMessage($templateFields, $values)
    {
        $resultValues = [];
        $references = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
        foreach ($templateFields as $key => $value) {
            //file or photo
            if ($templateFields[$key]["typeId"] == 8 && $_FILES[$templateFields[$key]["textId"]]) {
                $resultValues[$templateFields[$key]["textId"]] = $_FILES[$templateFields[$key]["textId"]];
                //select
            } else if ($templateFields[$key]["typeId"] == 4) {
                $textId = $templateFields[$key]["textId"];
                $resultValues[$templateFields[$key]["textId"]] = $references->getReferenceDataValue($templateFields[$key]["referenceId"], $values[$textId]);
                //multi select
            } else if ($templateFields[$key]["typeId"] == 5) {
                $textId = $templateFields[$key]["textId"];
                $resultValues[$templateFields[$key]["textId"]] = $references->getReferenceMultiDataValue($templateFields[$key]["referenceId"], $values[$textId]);
            } else {
                $resultValues[$templateFields[$key]["textId"]] = $values[$templateFields[$key]["textId"]];
            }
        }
        return $resultValues;
    }

    public function isHaveAllNecessarilyFields($necessarilyFields, $values)
    {
        foreach ($necessarilyFields as $key => $value) {
            if ($necessarilyFields[$key]['typeId'] == 8 && $necessarilyFields[$key]["necessarily"] == 1 && $_FILES[$necessarilyFields[$key]["textId"]]) {
                return false;
            } else if ($necessarilyFields[$key]["necessarily"] == 1 && empty($values[$necessarilyFields[$key]["textId"]])) {
                return false;
            }
        }

        return true;
    }

    private function sendMessage($id, $values)
    {
        $pageController = \ClassesOperations::autoLoadClass('\Controller\Page', '/controllers/Page.php');
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->struct_data->structId = $pageController->getFirstPageIdByType($id);
        $this->struct_data->structTemplateId = 7;
        $this->struct_data->structTypeId = 6;
        $this->struct_data->getStructFieldsForTemplate($this->struct_data->structTemplateId);
        $this->createMessageToSendFromStructData($id, $this->struct_data->structData["fields"], $values);
    }

    private function createMessageToSendFromStructData($id, $fields, $values)
    {
        $referencesModel = \ClassesOperations::autoLoadClass('\Controller\References', '/controllers/References.php');
        $addressesSendString = NULL;
        $nameFrom = NULL;
        $addressFrom = NULL;
        $message = NULL;
        $addressesId = NULL;
        foreach ($fields as $key => $value) {
            if ($fields[$key]["textId"] == "mail_template_address") {
                $addressesSendString = $referencesModel->getReferenceDataFieldValue($fields[$key]["value"], 76);
                $addressesId = $fields[$key]["value"];
            } else if ($fields[$key]["textId"] == "mail_template_name_from") {
                $nameFrom = $fields[$key]["value"];
            } else if ($fields[$key]["textId"] == "mail_template_address_from") {
                $addressFrom = $fields[$key]["value"];
            } else if ($fields[$key]["textId"] == "mail_template_mail_templ") {
                $message = $this->getMessageTextFromTemplate($fields[$key]["value"], $values);
            }
        }
        $this->setMessageToDb($id, $addressesId, $message);
        $this->doSendMail($addressesSendString, $nameFrom, $addressFrom, $message, $values);
    }

    private function setMessageToDb($id, $addressesId, $message)
    {
        $date = date("Y-m-d H:i:s");
        $ip = $_SERVER['REMOTE_ADDR'];
        \DataBase::justQueryToDataBase("INSERT messages SET address_id=" . $addressesId . ", template_id=" . $id . ", new_event=1, message='" . $message . "', date='" . $date . "', ip='" . $ip . "'");
    }

    public function mail_attachment($mailto, $from_name, $replyto, $message, $values)
    {
        $EOL = "\r\n"; // ограничитель строк, некоторые почтовые сервера требуют \n - подобрать опытным путём
        $boundary = "--" . md5(uniqid(time()));  // любая строка, которой не будет ниже в потоке данных.
        $headers = "MIME-Version: 1.0;$EOL";
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"$EOL";
        $multipart = "--$boundary$EOL";
        $multipart .= "Content-Type: text/html; charset=utf-8$EOL";
        $multipart .= "Content-Transfer-Encoding: base64$EOL";
        $multipart .= $EOL; // раздел между заголовками и телом html-части
        $multipart .= chunk_split(base64_encode($message));

        $multipart .= $this->getMessageFilesFromValues($values, $boundary);
        $multipart .= "$EOL--$boundary--$EOL";
        if (!mail($mailto, $from_name, $multipart, $headers)) {
            return false;
        } else {
            return true;
        }
        exit;
    }

    private function getMessageFilesFromValues($values, $boundary)
    {
        $filesValues = "";
        $EOL = "\r\n";
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $path = $value['tmp_name'];
                if ($value["size"] > 20971520) {
                    $langs = \Langs::getInstance();
                    $stringError = $key;
                    $stringError .= " - ";
                    $stringError .= $langs->getMessage("backend.errors.limit_file");
                    echo($stringError);
                    exit();
                }
                if ($path) {
                    $fp = fopen($value['tmp_name'], "rb");
                    if (!$fp) {
                        print "Cannot open file";
                        exit();
                    }

                    $file = fread($fp, filesize($path));
                    fclose($fp);
                    $name = $value['name'];
                    $filesValues .= "$EOL--$boundary$EOL";
                    $filesValues .= "Content-Type: application/octet-stream; name=\"$name\"$EOL";
                    $filesValues .= "Content-Transfer-Encoding: base64$EOL";
                    $filesValues .= "Content-Disposition: attachment; filename=\"$name\"$EOL";
                    $filesValues .= $EOL; // раздел между заголовками и телом прикрепленного файла
                    $filesValues .= chunk_split(base64_encode($file));
                }
            }
        }
        return $filesValues;
    }

    public function doSendMail($to, $nameFrom, $addressFrom, $message, $values)
    {
        $pos = stripos($to, ',');
        if ($pos > 0) {
            $tos = explode(",", $to);
            foreach ($tos as $key => $value) {
                $addresses = trim($value);
                $this->attachMail($addresses, $nameFrom, $addressFrom, $message, $values);
            }
        } else {
            $this->attachMail($to, $nameFrom, $addressFrom, $message, $values);
        }
    }

    public function getMessageTextFromTemplate($template, $values)
    {
        $message = html_entity_decode($template);
        foreach ($values as $key => $value) {
            $templateValue = "&lt;%" . $key . "%&gt;";
            $valueToPush = "";
            if (is_array($value)) {
                if ($value["tmp_name"]) {
                    $valueToPush = $value["name"];
                }
            } else {
                $valueToPush = $values[$key];
            }
            $message = str_replace($templateValue, $valueToPush, $message);
        }

        $pattern = '/&lt;%(.*?)%&gt;/i';
        $replacement = '';
        $message = preg_replace($pattern, $replacement, $message);
        return $message;
    }

    private function attachMail($address, $nameFrom, $addressFrom, $message, $values)
    {
        $this->mail_attachment($address, $nameFrom, $addressFrom, $message, $values);
    }
}
