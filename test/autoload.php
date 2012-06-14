<?php

$cwd = getcwd();

chdir(__DIR__ . '/../vendor/phpseclib');

require_once('Crypt/RSA.php');
require_once('Crypt/AES.php');

chdir($cwd);

require_once('../src/ConnectionEncryption.php');
require_once('../src/CloudServer.php');
require_once('../src/CloudConnector.php');
