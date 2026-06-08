<?php
/**
 * Configuration loader
 * Loads runtime config or falls back to defaults.
 */

$runtimeConfig = __DIR__ . '/runtime.php';
if (file_exists($runtimeConfig)) {
    require_once $runtimeConfig;
}

// Local defaults. Runtime config may override any of these values.
defined('DB_HOST')   || define('DB_HOST',   '127.0.0.1');
defined('DB_PORT')   || define('DB_PORT',   3306);
defined('DB_NAME')   || define('DB_NAME',   'clinic_db');
defined('DB_USER')   || define('DB_USER',   'root');
defined('DB_PASS')   || define('DB_PASS',   '');

define('APP_NAME',    'ClinicCare');
define('APP_VERSION', '1.0.0');

$baseUrl = '';
if (php_sapi_name() !== 'cli' && !empty($_SERVER['SCRIPT_NAME'])) {
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $segments = array_filter(explode('/', trim($scriptName, '/')));
    $publicIndex = array_search('public', $segments, true);
    if ($publicIndex !== false) {
        $baseUrl = '/' . implode('/', array_slice($segments, 0, $publicIndex + 1));
    } else {
        $baseUrl = $scriptName === '/' ? '' : rtrim($scriptName, '/');
    }
}
define('BASE_URL', $baseUrl);
define('UPLOAD_DIR',  __DIR__ . '/../public/uploads/');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}