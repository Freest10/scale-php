<?php
include_once CURRENT_WORKING_DIR . '/libs/root-src/exception/exception_handler/ExceptionMessage.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/exception/exception_handler/JsonException.php';
include_once CURRENT_WORKING_DIR . '/libs/root-src/exception/SystemException.php';
require_once CURRENT_WORKING_DIR . '/libs/root-src/exception/exception_handler/ModalException.php';

\SystemException::pushType(new JsonExceptionRoot("json"));
\SystemException::pushType(new ModalException("modal"));
