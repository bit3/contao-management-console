<!DOCTYPE html>
<html lang="de">
<head>
	<meta charset="utf-8"/>
	<title>HTML5 Layout</title>
</head>
<body>
<pre>
<?php

require_once('init.php');

$strUrl = 'http://localhost/~tristan/workspace/cloud/connection-encryption/test/connector.php';

$objServer = new CloudServer(
	$rsaKeys['server']['privatekey'],
	$rsaKeys['client']['publickey'],
	true
);

if (!$objServer->getSessionID() && !$objServer->doHandshake($strUrl)) {
	throw new Exception('Handshake failed!');
}
echo "Handshake successfull!\n";

$hello = new stdClass();
$hello->do = 'hello';

$response = $objServer->sendCommand($strUrl, $hello);

?>
</pre>
<?php echo $response; ?>
</body>
</html>
