<?php

namespace Api;
class ChangeSortOrParentOfPage
{
    public function set()
    {
        $srt_pgs = \ClassesOperations::autoLoadClass('\Controller\SortPages', '/controllers/SortPages.php');
        $srt_pgs->changeSortOrParentOfPage($_POST);
    }
}