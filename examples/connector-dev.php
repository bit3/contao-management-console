<?php

chdir(dirname(__FILE__));

define('CONTAO_CONNECTOR_LOG', dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.log');
define('CONTAO_CONNECTOR_LOG_LEVEL', 100);
define('CONTAO_CONNECTOR_CONTAO_PATH', dirname(dirname(__FILE__)));
define('CONTAO_CONNECTOR_RSA_LOCAL_PRIVATE_KEY', file_get_contents('/path/to/contao-cloud-connector/test/client.key'));
define('CONTAO_CONNECTOR_RSA_REMOTE_PUBLIC_KEY', file_get_contents('/path/to/contao-cloud-connector/test/server.pub'));

require('/path/to/contao-cloud-connector/vendor/autoload.php');
require('/path/to/contao-cloud-connector/scripts/error_handler.php');
require('/path/to/contao-cloud-connector/scripts/connector.php');
