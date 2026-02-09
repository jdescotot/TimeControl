<?php
/**
 * vendor/autoload.php - Autoloader manual para PHPMailer
 * 
 * Use this file if Composer is not available on your hosting.
 * 
 * Download PHPMailer from: https://github.com/PHPMailer/PHPMailer
 * Extract to: vendor/phpmailer/phpmailer/
 */

// Define autoloader function
spl_autoload_register(function ($class) {
    // PHPMailer namespace
    if (strpos($class, 'PHPMailer\\PHPMailer\\') === 0) {
        $class_name = str_replace('PHPMailer\\PHPMailer\\', '', $class);
        $file = __DIR__ . '/phpmailer/phpmailer/src/' . $class_name . '.php';
        
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// Explicitly require PHPMailer main classes
$phpmailer_dir = __DIR__ . '/phpmailer/phpmailer/src';

if (is_dir($phpmailer_dir)) {
    require_once $phpmailer_dir . '/PHPMailer.php';
    require_once $phpmailer_dir . '/SMTP.php';
    require_once $phpmailer_dir . '/Exception.php';
}

// Flag to indicate this is loaded
define('PHPMAILER_LOADED', true);
