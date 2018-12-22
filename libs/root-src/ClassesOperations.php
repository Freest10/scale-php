<?php
	class ClassesOperations {
		static function autoLoadClass($className, $pathToClass){
			if (!class_exists($className)) {
                include_once CURRENT_WORKING_DIR . $pathToClass;
            }
            $newClassName = new $className;
            return $newClassName;
        }
	}