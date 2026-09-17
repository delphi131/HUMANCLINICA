<?php
/**
 * Copy this file to config.php and fill in real values.
 * config.php is gitignored — never commit real credentials.
 */
return [
    'db' => [
        // 'sqlsrv' (Microsoft Drivers for PHP, typical on Windows/IIS hosting)
        // or 'dblib' (FreeTDS, typical on Linux hosting)
        'driver'   => 'sqlsrv',
        'host'     => '127.0.0.1',
        'port'     => 1433,
        'database' => 'HumanClinica',
        'user'     => 'sa',
        'password' => '',
    ],

    'app' => [
        // Used for session cookie name / CSRF salt, change per deployment.
        'name' => 'HumanClinica Admin',
        // Set to true only while developing locally.
        'debug' => false,
        // Base timezone for the calendar.
        'timezone' => 'Europe/Rome',
    ],

    // Fallback SMTP used only if no ACCOUNT/SMTP_* rows exist yet in tMessages.
    // Once the Messaggi page is used to save settings, DB values take over.
    'smtp_fallback' => [
        'host' => '',
        'port' => 587,
        'encryption' => 'tls', // tls | ssl | none
        'username' => '',
        'password' => '',
        'from_email' => '',
        'from_name' => 'Human Clinica',
    ],
];
