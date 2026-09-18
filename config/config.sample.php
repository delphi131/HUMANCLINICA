<?php
/**
 * Copy this file to config.php and fill in real values.
 * config.php is gitignored — never commit real credentials.
 */
return [
    'db' => [
        // 'sqlsrv' (Microsoft Drivers for PHP, typical on Windows/IIS hosting)
        // 'dblib'  (FreeTDS, typical on Linux hosting)
        // 'odbc'   (system ODBC Driver 17/18 for SQL Server, via PDO_ODBC —
        //           use this if your PHP version is newer than the latest
        //           official sqlsrv/pdo_sqlsrv release and `php -m` shows no
        //           sqlsrv/pdo_sqlsrv module; PDO_ODBC is usually already
        //           built into official Windows PHP builds)
        'driver'   => 'sqlsrv',
        'host'     => '127.0.0.1',
        'port'     => 1433,
        'database' => 'HumanClinica',
        'user'     => 'sa',
        'password' => '',
        // Only used when driver = 'odbc': must match the exact name of the
        // installed ODBC driver, as shown by "ODBC Data Sources (64-bit)" ->
        // Drivers tab on the server (e.g. 'ODBC Driver 17 for SQL Server' or
        // 'ODBC Driver 18 for SQL Server').
        'odbc_driver_name' => 'ODBC Driver 17 for SQL Server',
        // Only used when driver = 'odbc': extra raw DSN attributes appended
        // as-is. With ODBC Driver 18 (which defaults to Encrypt=yes) you'll
        // often need: 'TrustServerCertificate=yes'
        'odbc_extra' => '',
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
