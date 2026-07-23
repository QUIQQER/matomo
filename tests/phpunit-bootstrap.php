<?php

if (!defined('QUIQQER_SYSTEM')) {
    define('QUIQQER_SYSTEM', true);
}

if (!defined('QUIQQER_AJAX')) {
    define('QUIQQER_AJAX', true);
}

require_once __DIR__ . '/../../../../bootstrap.php';
require_once __DIR__ . '/stubs/GdprCookieInterface.php';
require_once __DIR__ . '/stubs/GdprCookieCollection.php';
require_once __DIR__ . '/stubs/GdprCookieProviderInterface.php';
require_once __DIR__ . '/stubs/FrontendUsersRegistrarInterface.php';
