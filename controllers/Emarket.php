<?php

namespace Controller;
class Emarket
{

    public $order;
    private $id;
    private $userIdOfOrder;
    private $totalAmount;
    private $totalPrice;
    private $langs;
    private $orderFieldValues;
    private $basket;
    private $struct_data;

    function __construct()
    {
        $this->langs = \Langs::getInstance();
    }

    public function getOrders()
    {
        $dataToJsonLimit = [
            'selectResponse' => 'SELECT order_id as id, date FROM `orders` ORDER BY order_id DESC',
            'countResponse' => 'orders',
            'templatesData' => [
                'id' => true,
                'date' => true,
            ],
            'begin' => $_GET['begin'],
            'limit' => $_GET['limit'],
            'type' => 'order'
        ];
        \JsonOperations::createLimitJson($dataToJsonLimit);
    }

    public function getOrderById($id)
    {
        $this->order = [];
        $this->id = $id;
        $this->order['name'] = $this->langs->getMessage("backend.emarket.order") . ' №' . $id;
        $this->userIdOfOrder = $this->getUserOfOrder();
        $structData = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $structData->structId = $id;
        $structData->structTemplateId = 4;
        $structData->structTypeId = 4;
        $structData->generateStructData();
        if ($this->userIdOfOrder) {
            $this->order['groups'] = [$structData->createGeneralGroup()];
            $this->order['groups'] = array_merge($this->order['groups'], $structData->structData['groups']);
        } else {
            $this->order['groups'] = $structData->structData['groups'];
        }
        if (!is_array($this->order["fields"])) $this->order["fields"] = [];
        $this->getUserInfo();
        $this->order['fields'] = array_merge($this->order['fields'], $structData->structData['fields']);
        $this->order['table'] = $this->tableCreator();

        $eventsModel = \ClassesOperations::autoLoadClass('\Controller\Events', '/controllers/Events.php');
        $eventsModel->newEvent($id, "order_id", "orders", 0);
        \JsonOperations::printJsonFromPhp($this->order);
    }

    private function getUserOfOrder()
    {
        return \DataBase::queryToDataBase("SELECT user_id FROM `orders` WHERE order_id=" . $this->id)['user_id'];
    }

    private function getUserInfo()
    {
        if ($this->userIdOfOrder > 0) {
            $fieldNum = count($this->order["fields"]);
            $this->order["fields"][$fieldNum]["id"] = -401;
            $this->order["fields"][$fieldNum]["parentId"] = 0;
            $this->order["fields"][$fieldNum]["typeId"] = -1;
            $this->order["fields"][$fieldNum]["necessarily"] = 0;
            $this->order["fields"][$fieldNum]["name"] = $this->langs->getMessage("backend.emarket.users_login");
            $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
            $this->order["fields"][$fieldNum]["value"] = $users->getUserName($this->userIdOfOrder);
        }
    }

    public function setOrder($id)
    {
        $struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $struct_data->structId = $id;
        $struct_data->structTypeId = 4;
        $struct_data->setFieldsData($_POST);
        \Response::goodResponse();
    }

    public function tableCreator()
    {
        $table = [];
        $table['columnsName'] = [[
            'name' => $this->langs->getMessage("backend.emarket.name"),
            'price' => $this->langs->getMessage("backend.emarket.price"),
            'amount' => $this->langs->getMessage("backend.emarket.amount")
        ]];

        $table['columnsValue'] = [];
        $table['tableName'] = $this->langs->getMessage("backend.emarket.tableName");
        $table['totalPrice'] = 0;

        $groupsToDbQuery = \DataBase::justQueryToDataBase("SELECT product_id, price, amount, product_name as name FROM `order_info` WHERE id = " . $this->id);

        $tableNum = 0;
        while ($responseFromDb = \DataBase::responseFromDataBase($groupsToDbQuery)) // перебор строк таблицы с начала до конца
        {
            $table['columnsValue'][$tableNum] = [];
            $table['columnsValue'][$tableNum]["name"] = $responseFromDb['name'];
            $table['columnsValue'][$tableNum]["price"] = $responseFromDb['price'];
            $table['columnsValue'][$tableNum]["amount"] = $responseFromDb['amount'];
            $table['total'] += $responseFromDb['price'] * $responseFromDb['amount'];
            $tableNum++;
        }

        return $table;
    }

    public function getOrdersForUsers($options)
    {
        $ids = $options["ids"];
        $props = $options["props"];
        $groups = $options["groups"];
        $idsToSql = "";
        if (count($ids) > 0) {
            foreach ($ids as $key => $value) {
                if ($key != 0) {
                    $idsToSql .= " OR";
                }
                $idsToSql .= " users.user_id  = " . $value;
            }
        }
        $typesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT orders.order_id as id, orders.user_id as user_id, users.login as login
				FROM orders
				INNER JOIN users 
				ON orders.user_id = users.user_id 
				WHERE " . $idsToSql
        );
        $orders = [];
        $this->totalAmount = 0;
        $this->totalPrice = 0;
        while ($responseFromDb = \DataBase::responseFromDataBase($typesQueryToDbQuery)) {
            $data = [];
            $dataId = $responseFromDb["id"];
            $data["userId"] = $responseFromDb["user_id"];
            $data["login"] = $responseFromDb["login"];
            $data["products"] = $this->getProducts($dataId);
            if (count($props) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTemplateId = 4;
                $data["props"] = $this->struct_data->getFieldsByPageIdForClient($props);
            }
            if (count($groups) > 0) {
                if (!$this->struct_data) {
                    $this->connectStructData();
                }
                $this->struct_data->structId = $dataId;
                $this->struct_data->structTemplateId = 4;
                $data["groups"] = $this->struct_data->getGroupsByPageIdForClient($groups);
            }
            $data["totalPrice"] = $this->totalPrice;
            $data["totalAmount"] = $this->totalAmount;
            $orders[$dataId] = $data;
        }
        return $orders;
    }

    private function getProducts($id)
    {
        $typesQueryToDbQuery = \DataBase::justQueryToDataBase("
				SELECT *
				FROM order_info
				LEFT JOIN page_id_uri as piu
				ON piu.page_id=order_info.product_id
				WHERE id=$id"
        );
        $products = [];
        while ($responseFromDb = \DataBase::responseFromDataBase($typesQueryToDbQuery)) {
            $data = [];
            $data["url"] = $responseFromDb["full_path"];
            $data["productId"] = $responseFromDb["product_id"];
            $data["price"] = $responseFromDb["price"];
            $data["amount"] = $responseFromDb["amount"];
            $data["productName"] = $responseFromDb["product_name"];
            $data["currency"] = $responseFromDb["currency"];
            $this->totalPrice += $data["price"] * $data["amount"];
            $this->totalAmount += $data["amount"];
            $products[] = $data;
        }
        return $products;
    }

    private function connectStructData()
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->struct_data->structTypeId = 4;
    }

    private function setOrderFieldValues($values)
    {
        $this->orderFieldValues = $values;
    }

    public function setOrderClient($values)
    {
        $sessions = \ClassesOperations::autoLoadClass('\Session', '/libs/root-src/session.php');
        $this->basket = \ClassesOperations::autoLoadClass('\Controller\Basket', '/controllers/Basket.php');
        $sessionId = $sessions::getSessionId();
        $this->setOrderFieldValues($values);
        $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
        $userReqId = $users->getUserIdBySessionId($sessionId);
        if ($userReqId > 0) {
            $userId = $userReqId;
            $basket = $this->basket->getBasket();
        } else {
            $userId = $users->getIdOfCreatedRandomUser();
            $cookieBasket = $this->basket->getBasketToCookie();
            $this->basket->setProductForUser($userId, $cookieBasket);
            $this->basket->clearCookieBasket();
            $basket = json_decode($cookieBasket, true);
        }

        if ($basket && count($basket) > 0) {
            include_once CURRENT_WORKING_DIR . '/controllers/StructData.php';
            $this->basket = \ClassesOperations::autoLoadClass('\Controller\Basket', '/controllers/Basket.php');
            $result = $this->setOrderUser($userId, $basket);
            $this->basket->deleteBasketToUser($userId);
            include_once CURRENT_WORKING_DIR . '/client/email/emarket/index.php';
        } else {
            $result = ["errorCode" => "BASKET_IS_EMPTY",
                "description" => $this->langs->getMessage("backend.errors.basket_is_empty")];
        }

        return $result;
    }

    private function setOrderUser($userId, $basket)
    {
        $templateType = \ClassesOperations::autoLoadClass('\Controller\TemplatesType', '/controllers/TemplatesType.php');
        $templateType->getTemplateFields(4);
        $messagesModel = \ClassesOperations::autoLoadClass('\Controller\Messages', '/controllers/Messages.php');
        if ($messagesModel->isHaveAllNecessarilyFields($templateType->template["fields"], $this->orderFieldValues)) {
            $this->createOrder($userId);
            $orderId = $this->getLastOrderId();
            $this->setOrderFieldValuesForOrder($orderId, $this->orderFieldValues, $templateType->template["fields"]);
            $this->setOrderInfo($orderId, $basket);
            return true;
        } else {
            return $templateType->langs->getMessage("backend.errors.required_fields");
        }
    }

    private function setOrderInfo($orderId, $basket)
    {
        $fields = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
        $priceTableName = $fields->getTableNameByTypeField(13);
        foreach ($basket as $key => $value) {
            $price = $this->getPriceProduct($key, $priceTableName);
            $product_name = Page::getPageName($key);
            $amount = $value["amount"];
            $sql = "INSERT order_info SET id = $orderId, product_id = $key, amount=$amount, product_name='$product_name', price = $price, currency=1";
            \DataBase::justQueryToDataBase($sql);
        }
    }

    private function getPriceProduct($id, $tableName)
    {
        if (!$tableName) {
            $fields = \ClassesOperations::autoLoadClass('\Controller\Fields', '/controllers/Fields.php');
            $tableName = $fields->getTableNameByTypeField(13);
        }

        return \DataBase::queryToDataBase("SELECT value FROM " . $tableName . " WHERE page_id=$id AND type=1")["value"];
    }

    private function setOrderFieldValuesForOrder($orderId, $fieldValues, $templateFields)
    {
        $this->struct_data = \ClassesOperations::autoLoadClass('\Controller\StructData', '/controllers/StructData.php');
        $this->struct_data->structId = $orderId;
        $this->struct_data->structTemplateId = 4;
        $this->struct_data->structTypeId = 4;
        $this->struct_data->setFieldsDataByTemplate($fieldValues, $templateFields);
    }

    private function createOrder($userId)
    {
        $today = date("Y-m-d H:i:s");
        $sql = "INSERT orders SET user_id = $userId, date = '" . $today . "', new_event = 1";
        \DataBase::justQueryToDataBase($sql);
    }

    private function getLastOrderId()
    {
        return \DataBase::queryToDataBase("SELECT order_id FROM orders ORDER BY order_id DESC LIMIT 1")["order_id"];
    }

    public function deleteOrder($id)
    {
        $pageModel = new Page();
        $pageModel->deleteAllFieldsByPage($id, 4);
        $this->deleteOrderFromDb($id);
        $this->deleteOrderInfoFromDb($id);
        \Response::goodResponse();
    }

    private function deleteOrderFromDb($id)
    {
        \DataBase::queryToDataBase("DELETE FROM orders WHERE order_id =" . $id);
    }

    private function deleteOrderInfoFromDb($id)
    {
        \DataBase::queryToDataBase("DELETE FROM order_info WHERE id =" . $id);
    }

    public function getLastOrder()
    {
        $result = [];
        $lastOdrderId = $this->getLastOrderId();
        $result["orderId"] = $lastOdrderId;
        $result["products"] = $this->getProducts($lastOdrderId);
        $result["totalPrice"] = $this->totalPrice;
        $result["totalAmount"] = $this->totalAmount;
        return $result;
    }
}