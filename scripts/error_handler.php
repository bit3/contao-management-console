<?php

/**
 * Management Console for Contao Open Source CMS
 *
 * Copyright (C) 2013 Tristan Lins
 *
 * PHP version 5
 *
 * @copyright  bit3 UG 2013
 * @author     Tristan Lins <tristan.lins@bit3.de>
 * @package    contao-management-console
 * @license    LGPL-3.0+
 * @filesource
 */

set_error_handler(
	function ($errno, $errstr, $errfile, $errline) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	},
	E_ALL ^ E_NOTICE
);

set_exception_handler(
	function (Exception $e) {
		while (ob_end_clean()) {
		}
		header("HTTP/1.0 500 Internal Server Error");
		header("Status: 500 Internal Server Error");
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Exception [' . get_class($e) . ']: ' . $e->getMessage() . "\n";
		echo $e->getTraceAsString();
		exit;
	}
);
