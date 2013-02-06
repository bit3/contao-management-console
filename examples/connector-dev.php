<?php

chdir(dirname(__FILE__));

define('CONTAO_MANAGEMENT_API_LOG', dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.log');
define('CONTAO_MANAGEMENT_API_LOG_LEVEL', 100);
define('CONTAO_MANAGEMENT_API_CONTAO_PATH', dirname(dirname(__FILE__)));
define('CONTAO_MANAGEMENT_API_RSA_LOCAL_PRIVATE_KEY', file_get_contents('/path/to/contao-management-api/test/client.key'));
define('CONTAO_MANAGEMENT_API_RSA_REMOTE_PUBLIC_KEY', file_get_contents('/path/to/contao-management-api/test/server.pub'));

require('/path/to/contao-management-api/vendor/autoload.php');
require('/path/to/contao-management-api/scripts/error_handler.php');
require('/path/to/contao-management-api/scripts/connect.php');
