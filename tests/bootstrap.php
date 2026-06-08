<?php

// Suppress E_DEPRECATED from vendor packages (e.g. PDO::MYSQL_ATTR_SSL_CA in PHP 8.5).
// Only our own deprecations (in app/) should cause failures.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';
