<?php
/**
 * Bootstrap — loaded at the top of every page.
 * Includes config (which starts the session) and auto-requires all classes.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';

$srcDir = dirname(__DIR__, 2) . '/src/';
$classes = ['Database', 'Auth', 'User', 'Doctor', 'Appointment', 'XmlHandler'];

foreach ($classes as $class) {
    require_once $srcDir . $class . '.php';
}
