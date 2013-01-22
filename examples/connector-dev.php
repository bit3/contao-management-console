<?php

chdir(dirname(__FILE__));

define('CLOUD_CONTAO_PATH', dirname(dirname(__FILE__)));
define('CLOUD_RSA_LOCAL_PRIVATE_KEY', null);
define('CLOUD_RSA_REMOTE_PUBLIC_KEY', null);

require('/path/to/contao-cloud-connector/vendor/autoload.php');
require('/path/to/contao-cloud-connector/examples/connector.php');
