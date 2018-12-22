<?php

class Security
{

    private $filteredParams;

    public function filterParams($params)
    {
        $this->filteredParams = $params;
        if (count($this->filteredParams) > 0) {
            $this->recursiveParams($this->filteredParams);
        }
        return $this->filteredParams;
    }


    public function recursiveParams($params)
    {
        if (count($params) > 0) {
            foreach ($params as $key => $paramVal) {
                if (is_array($paramVal)) {
                    $this->recursiveParams($paramVal);
                } else {
                    $params[$key] = $this->filterParamVal($paramVal);
                }
            }
        }
    }

    public function filterParamVal($paramVal)
    {
        $filteredParamVal = strip_tags($paramVal);
        $filteredParamVal = htmlentities($filteredParamVal, ENT_QUOTES, "UTF-8");
        $filteredParamVal = htmlspecialchars($filteredParamVal, ENT_QUOTES);
        $filteredParamVal = addslashes($filteredParamVal);
        return $filteredParamVal;
    }

    public function secureReq()
    {
        $_SERVER = $this->filterParams($_SERVER);
        $_GET = $this->filterParams($_GET);
        $_POST = $this->filterParams($_POST);
    }

}