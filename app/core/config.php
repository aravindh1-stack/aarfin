<?php
// -- DATABASE CREDENTIALS --
define('DB_HOST', 'localhost');
define('DB_NAME', 'aarfin');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty

// -- APP CONFIGURATION --
// App Root (For file includes like 'require_once')
define('APP_ROOT', dirname(dirname(__FILE__))); 
// URL Root (For links like <a href="...">)
define('URL_ROOT', 'https://paycircle.wuaze.com');// Unga website oda correct URL podunga

// -- TIMEZONE --
date_default_timezone_set('Asia/Kolkata');