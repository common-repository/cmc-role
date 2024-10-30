<?php

/* 
 * package: cmc-role
 * file: default.php
 */
if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$GLOBALS['cmcrm_settings_default'] = array(
	'allowed_roles' =>'',
    'del_opt_uninstall' => 0,
);