<?php
require __DIR__ . '/../vendor/autoload.php';

// Try to use Google Auth directly to see if it's installed
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

echo "Google Auth loaded successfully!\n";
