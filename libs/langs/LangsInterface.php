<?php
interface LangsInterface
{
    public function getAllLangs();
    public function getActiveLang();
    public function setActiveLang($id);
    public function setActiveLangByTextId($textId);
    public function getMessage($key, $nameSpace);
}