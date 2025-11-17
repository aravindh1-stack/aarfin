<?php
// -- DATABASE CREDENTIALS --
define('DB_HOST', 'localhost');
define('DB_NAME', 'aarfin');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default: no password

// -- APP CONFIGURATION --
// App Root (For file includes like 'require_once')
define('APP_ROOT', dirname(dirname(__FILE__))); 
// URL Root (For links like <a href="...">)
define('URL_ROOT', 'http://localhost/paycircle'); // Local XAMPP URL

// -- TIMEZONE --
date_default_timezone_set('Asia/Kolkata');