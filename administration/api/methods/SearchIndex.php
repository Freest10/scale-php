<?php
namespace Api;
	class SearchIndex {
		public function get(){
			$search = \ClassesOperations::autoLoadClass('\Controller\Search', '/controllers/Search.php');
			$search->index_search();
		}
	}