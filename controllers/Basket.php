<?php

namespace Controller;
class Basket
{
    private $options;
    private $sessionId;

    public function addToBasket($options)
    {
        $this->setSessionId();
        $this->setOptions($options);
        if ($this->sessionId) {
            $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
            $userId = $users->getUserIdBySessionId($this->sessionId);
            if ($userId > 0) {
                $this->setBasketToUser($userId);
            } else {
                $this->setBasketToCookie();
            }
        } else {
            $this->setBasketToCookie();
        }
    }

    public function updateBasket($options)
    {
        include_once CURRENT_WORKING_DIR . '/libs/root-src/session.php';
        $this->sessionId = \Session::getSessionId();
        $this->setOptions($options);
        if ($this->sessionId) {
            $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
            $userId = $users->getUserIdBySessionId($this->sessionId);
            if ($userId > 0) {
                $this->updateBasketToUser($userId);
            } else {
                $this->updateBasketToCookie();
            }
        } else {
            $this->updateBasketToCookie();
        }
    }

    public function updateBasketToUser($userId)
    {
        if (count($this->options["products"]) < 1) {
            $this->deleteBasketToUser($userId);
            return false;
        }

        $productsArr = $this->renderProductArrFromUpdate($this->options["products"]);
        $jsonResult = \DataBase::realEscapeString(json_encode($productsArr));
        \DataBase::justQueryToDataBase("UPDATE basket SET products='" . $jsonResult . "' WHERE user_id=$userId");
    }

    public function deleteBasketToUser($userId){
        \DataBase::justQueryToDataBase("DELETE FROM basket WHERE user_id=$userId");
    }

    public function getBasket()
    {
        $this->setSessionId();
        $result = NULL;
        if ($this->sessionId) {
            $users = \ClassesOperations::autoLoadClass('\Controller\Users', '/controllers/Users.php');
            $userId = $users->getUserIdBySessionId($this->sessionId);
            $cookieBasket = $this->getBasketToCookie();
            if ($userId > 0) {
                $result = $this->getBasketOfUser($userId);
                if ($cookieBasket) {
                    $decodedCookieBasket = json_decode($cookieBasket, true);
                    if ($result) {
                        $decodedUserBasket = json_decode($result, true);
                        $assignedBasket = $this->getAssignedProductBaskets($decodedUserBasket, $decodedCookieBasket);
                        $this->updateProductsOfUserFrom($userId, $assignedBasket);
                        $this->clearCookieBasket();
                        return $assignedBasket;
                    } else {
                        $this->setProductForUser($userId, $decodedCookieBasket);
                        $this->clearCookieBasket();
                        return $decodedCookieBasket;
                    }
                }
            } else {
                $result = $cookieBasket;
            }
        } else {
            $result = $this->getBasketToCookie();
        }

        return json_decode($result, true);
    }

    public function getBasketToCookie()
    {
        return $_COOKIE["basket"];
    }

    public function clearCookieBasket()
    {
        $this->setBasketCookie(NULL, time() - 3600);
    }

    public function setProductForUser($userId, $products)
    {
        $jsonResult = \DataBase::realEscapeString(json_encode($products));
        \DataBase::justQueryToDataBase("INSERT INTO basket (user_id,products) VALUES($userId,'" . $jsonResult . "')");
    }

    private function getBasketOfUser($userId)
    {
        return \DataBase::queryToDataBase("SELECT products FROM basket WHERE user_id = $userId")["products"];
    }

    private function updateBasketToCookie()
    {
        if (count($this->options["products"]) < 1) {
            $this->setBasketCookie(NULL, time() - 3600);
        } else if ($_COOKIE["basket"]) {
            $resultCookie = $this->renderProductArrFromUpdate($this->options["products"]);
            if (!is_null($resultCookie)) {
                $this->setBasketCookie($resultCookie);
            }
        }
    }

    private function setSessionId()
    {
        $session = \ClassesOperations::autoLoadClass('\Session', '/libs/root-src/session.php');
        $this->sessionId = $session::getSessionId();
    }

    private function renderProductArrFromUpdate($productsToDelete)
    {
        $productsArr = [];
        $today = date("Y-m-d H:i:s");
        foreach ($productsToDelete as $key => $value) {
            $productId = $value["productId"];
            $productsArr[$productId] = [];
            $productsArr[$productId]["amount"] = $value['amount'];
            $productsArr[$productId]["date"] = $today;
        }

        return $productsArr;
    }

    private function setOptions($options)
    {
        $this->options = $options;
    }

    private function setBasketToCookie()
    {
        if ($_COOKIE["basket"]) {
            $resultCookie = $this->renderProductArr($this->options["products"], json_decode($_COOKIE["basket"]));
        } else {
            $resultCookie = $this->renderJsonOfProducts($this->options['products']);
        }

        if (count($resultCookie) > 0) {
            $this->setBasketCookie($resultCookie);
        }
    }

    public function setBasketCookie($arr, $time = NULL)
    {
        if (!$time) {
            $time = time() + 3600;
        }

        setcookie("basket", strval(json_encode($arr)), $time, '/');
    }

    private function setBasketToUser($userId)
    {
        $productForUser = $this->isHaveProductsForUser($userId);
        if ($productForUser > 0) {
            $this->updateProductsOfUserFromOptionProducts($userId);
        } else {
            $this->setProductForUserFromOptionProducts($userId);
        }
    }

    private function updateProductsOfUserFromOptionProducts($userId)
    {
        $currentProducts = json_decode($this->getProductsForUser($userId), true);
        $productsArr = $this->renderProductArr($this->options["products"], $currentProducts);
        if (count($productsArr) > 0) {
            $this->updateProductsOfUserFrom($userId, $productsArr);
        }
    }

    private function updateProductsOfUserFrom($userId, $products)
    {
        $jsonResult = \DataBase::realEscapeString(json_encode($products));
        \DataBase::justQueryToDataBase("UPDATE basket SET products='" . $jsonResult . "' WHERE user_id=$userId");
    }

    private function renderProductArr($products, $currentProducts)
    {
        $productsSystemFormat = $this->getProductsInSystemFormat($products);
        return $this->getAssignedProductBaskets($productsSystemFormat, $currentProducts);
    }

    private function getProductsInSystemFormat($products)
    {
        $productsArr = [];
        $today = date("Y-m-d H:i:s");
        foreach ($products as $key => $value) {
            $productId = $value["productId"];
            $productsArr[$productId] = [];
            $productsArr[$productId]["amount"] = $value["amount"];
            $productsArr[$productId]["date"] = $today;
        }

        return $productsArr;
    }

    private function getAssignedProductBaskets($productBasket1, $productBasket2)
    {
        $resultProducts = [];
        $today = date("Y-m-d H:i:s");
        foreach ($productBasket1 as $key => $value) {
            $resultProducts[$key] = ['amount' => $productBasket2[$key] ? ($productBasket2[$key]["amount"] + $value["amount"]) : $value["amount"], 'date' => $today];
        }

        foreach ($productBasket2 as $key => $value) {
            if (!$productBasket1[$key]) {
                $resultProducts[$key] = ['amount' => $value["amount"], 'date' => $today];
            }
        }

        return $resultProducts;
    }

    private function getProductsForUser($userId)
    {
        return \DataBase::queryToDataBase("SELECT products FROM basket WHERE user_id=$userId")["products"];
    }

    public function isHaveProductsForUser($userId)
    {
        $resultQuery = \DataBase::queryToDataBase("SELECT count(*) FROM basket WHERE user_id = $userId");
        if (isset($resultQuery)) {
            return $resultQuery["count(*)"];
        }
        return false;
    }

    private function setProductForUserFromOptionProducts($userId)
    {
        $toJsonProducts = $this->renderJsonOfProducts( $this->options['products']);
        $this->setProductForUser($userId, $toJsonProducts);
    }

    private function renderJsonOfProducts($products)
    {
        $today = date("Y-m-d H:i:s");
        $productsArr = [];
        foreach ($products as $key => $value) {
            if ($value["amount"] > 0) {
                $productId = $value["productId"];
                $productsArr[$productId] = [];
                $productsArr[$productId]["amount"] = $value["amount"];
                $productsArr[$productId]["date"] = $today;
            }
        }

        return $productsArr;
    }
}