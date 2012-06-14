<?php

set_error_handler(function($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function(Exception $exception) {
	header("HTTP/1.0 500 Internal Server Error");
	echo $exception->getMessage() . "\n";
	echo $exception->getTraceAsString();
	exit;
});

require_once('autoload.php');

$rsaKeys = array (
  'server' =>
  array (
    'privatekey' => '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDQ6pAtV74+WBobh7Pe7jUZY/iB39HyGoN+ZiVPAE1hD7jYwC4YWDsTeIK0Fnva
yNef1rKNfnnVw+Lz3dv7qgBhsgizAGqZhLDYNAU/k4wZ5Bjb38kgzjHMrsnEOrNPrOXUBGy+zbdE
+MEgx0rwOiea65TJeTCDcwjBxKSa4cIIRQIDAQABAoGANxJeR1Wl9NFMsI0pQU8R+Et+Kt5Rfby3
hQ5wL08pFrkKRTkD7a70g8F00vxKUfY3wQ7bqqj4fP1SSm7lmoXJ630HXuXowbJnwlgMxFsKudr0
t64IwN4me85RJCwy8xyEhUUjibf/Tpn8v+Qfhs26hiIs92eBIDdyUbVU9Q++9NECQQDhYCyOc3Uy
A4ptUEelt8xJtrs0KPB6F2epwnDjs3H6c5tmxFATLXIE3MNSfl1QPHdYUEyQs23B61aLos7nAdDv
AkEA7U3Wrowo9WtDOnrvKNHM5YDZ6r67CyXOKHW21t1Cw+/po8BG9qA//u0rPTWuR32p785VXbkD
i7xA2QErKVbSCwJAR9QV/0SO6mS0fohifU9pvWiOm14c3lyNPk5pGjj7r32e3o7cknAecaxGWAlM
BiFvS+czr75v0akGiTRXSsqKYwJBAMxdVJ1Np5hzn5oldQG6bLLZnNJFH+Ah1sGRXrz8IBuN6bBU
TsjmiTaKGcrFCCoZVthm4a2tQBh/L3mzP/CCDLECQQCjvVFnAXWREBZABDzpWIKuhs00qyZDXcpW
QxvRuWW2G3JazhERI5PA/Yu/2D32EtmMX6xDjskDe7/c8XVU7e/G
-----END RSA PRIVATE KEY-----',
    'publickey' => '-----BEGIN PUBLIC KEY-----
MIGJAoGBANDqkC1Xvj5YGhuHs97uNRlj+IHf0fIag35mJU8ATWEPuNjALhhYOxN4grQWe9rI15/W
so1+edXD4vPd2/uqAGGyCLMAapmEsNg0BT+TjBnkGNvfySDOMcyuycQ6s0+s5dQEbL7Nt0T4wSDH
SvA6J5rrlMl5MINzCMHEpJrhwghFAgMBAAE=
-----END PUBLIC KEY-----',
    'partialkey' => false,
  ),
  'client' =>
  array (
    'privatekey' => '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQCG3IhBj8Z0nMQjKTlm6HDY1JBb4UVab80p9AR/AL4LpxOkconFbDYQfdtCT3po
ogsSOCNhpIiuHUCnJ9a0KB8MIqhTN/I56lubQda42uBQFfEKMGX9K6vqaCqvXULdo78tKSU5VCOQ
VZysIQZRJSYZ62bLCUXd8FVKrVB+LpRI8wIDAQABAoGAQvDjl3t43DhWaUeUrR6SqSZ1042Roc7e
DUWJF8t0Cg+wQc/yz/KjMXNvas5NqfNJQI1qDpUfnSF7Dp3JRpqlseRyKzvEvmvwbsHtiRVQFkfV
OOeCxYGo+SFO4DN6WT3ajvu5LLaiNqb2F9QcY7qiHTs46/85vIra1rFRzEDHACECQQCnt/kMjSfe
j/mKNUQvOQqVqVKwMsFNT2j+nOYnL1J4m79sUCPlp/YJ4sUVnRnCWZOYkZCe9c2BhNoZQU48paCh
AkEAzdkQmgRfPqtRXdI7JjpdPXzHe2LzXvsyRyj1G9G5muUwlMt6vNYhspxPjuzM2xcI2FajFz11
X+0NkwtSoSM9EwJAO9FDxBQ8Ggbji0WAMg94FPS1Bx6zDq251sWC2IrqMRXraegTRX9oIxJ8FD9Z
xpVILOwN1oP4ba9CUiuWic7QwQJAc/d6s9oMsmifgUSj0AiHaNF4LFn1k6fejlpTo+WGM+40bU8p
CWN1PoNzCqj7S95xPDeqz7fu/Si2QgXZ+i+5AwJAQdnnIUpre4YNskjJN48trE7HzcPcU7+FbX5q
fGJhOlhKU6LnZ0j8SIS/htMsvqGgT5kMeZygSsn6RinTLNJ+Mg==
-----END RSA PRIVATE KEY-----',
    'publickey' => '-----BEGIN PUBLIC KEY-----
MIGJAoGBAIbciEGPxnScxCMpOWbocNjUkFvhRVpvzSn0BH8AvgunE6RyicVsNhB920JPemiiCxI4
I2GkiK4dQKcn1rQoHwwiqFM38jnqW5tB1rja4FAV8QowZf0rq+poKq9dQt2jvy0pJTlUI5BVnKwh
BlElJhnrZssJRd3wVUqtUH4ulEjzAgMBAAE=
-----END PUBLIC KEY-----',
    'partialkey' => false,
  ),
);
