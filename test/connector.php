<?php

require_once('init.php');

$objConnector = new CloudConnector(
	$rsaKeys['client']['privatekey'],
	$rsaKeys['server']['publickey'],
	true
);

$objConnector->handleRequest();
