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

define('CLOUD_RSA_LOCAL_PRIVATE_KEY', file_get_contents(__DIR__ . '/server.key'));

define('CLOUD_RSA_REMOTE_PUBLIC_KEY', file_get_contents(__DIR__ . '/server.pub'));
