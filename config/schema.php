<?php
/**
 * Logical -> physical column mapping.
 *
 * These table/column names were reconstructed by scanning the raw bytes of
 * the humanClinica.bak SQL Server backup for embedded catalog/text strings
 * (system_sql_modules, syscolpars, etc.), NOT by querying a live database —
 * this session had no network access to the production SQL Server.
 *
 * Confidence:
 *  - users.username / users.password_legacy / reservations table+columns / messages
 *    table+columns: HIGH — found as literal, unambiguous strings (including a
 *    stored SQL snippet "SELECT * FROM tReservations where id_Azienda=..." and
 *    "SELECT value FROM tMessages where name=@name and type=@type and LANG=@lang").
 *  - Exact column set for tUsers (id_azienda, Nome, cognome, typeProfile) and
 *    tReservations (day, settore, sottosettore, beatyadvisor, link-meet, ...):
 *    MEDIUM — names were found close together in memory but their exact table
 *    grouping is inferred, not certain.
 *
 * ACTION REQUIRED: after filling in config.php, run:
 *     php tools/check_schema.php
 * from a machine that can reach the real database. It queries
 * INFORMATION_SCHEMA.COLUMNS and reports any mapped column below that does not
 * actually exist, so you can correct this file in one place before relying on
 * it. Nothing else in the app hardcodes column names outside this file.
 */
return [
    'users' => [
        'table'            => 'tUsers',
        'pk'                => 'Id',
        'username'          => 'username',
        // Legacy reversible-cipher password used by the existing ASP.NET app.
        // The PHP panel never reads/writes this column.
        'password_legacy'   => 'Password',
        // New column this app adds (see db/migrations/001_add_password_hash_column.sql)
        // to store a bcrypt hash for PHP-panel logins, without touching the
        // legacy column or the ASP.NET app.
        'password_hash'     => 'PasswordPHP',
        'nome'              => 'Nome',
        'cognome'           => 'cognome',
        'email'             => 'email',
        'telephone'         => 'telephone',
        'id_azienda'        => 'id_azienda',
        'type_profile'      => 'typeProfile',
        // Value of type_profile that grants full admin rights (Gestione Utenti, etc.)
        // Anything else is treated as a Beauty Advisor. typeProfile is
        // tinyint on the real DB; confirmed 3 = admin (both existing
        // accounts, including the "AMMINISTRATORE SISTEMA" one, have this
        // value). Adjust if a non-admin account turns out to also be 3.
        'type_profile_admin_value' => 3,
        // Best guess for the non-admin (Beauty Advisor) role value — not yet
        // confirmed against a real account with this role. Adjust once you
        // create/see one, or just type the raw number in the Utenti form.
        'type_profile_beauty_advisor_value' => 1,
    ],

    // UNVERIFIED against the live DB — reconstructed from .bak strings, like
    // the rest of this file originally was. Run:
    //   php tools/list_columns.php tAzienda
    // and correct below before relying on the Aziende page.
    'azienda' => [
        'table'          => 'tAzienda',
        'pk'              => 'id_Azienda',
        'nome'            => 'Nome',
        'piva'            => 'piva',
        'sede'            => 'sede',
        'citta'           => 'citta',
        'cap'             => 'cap',
        'rappresentante'  => 'Rappresentante',
        'telephone'       => 'telephone',
        'email'           => 'email',
        'contratto'       => 'contratto',
        'enabled'         => 'enabled',
    ],

    // New table this app adds — see db/migrations/002_add_azienda_contratti_table.sql
    'azienda_contratti' => [
        'table'      => 'tAziendaContratti',
        'pk'          => 'id',
        'id_azienda'  => 'id_Azienda',
        'source'      => 'source',
        'content'     => 'content',
        'file_name'   => 'file_name',
        'file_path'   => 'file_path',
        'created_at'  => 'created_at',
        'created_by'  => 'created_by',
    ],

    'reservations' => [
        'table'       => 'tReservations',
        'pk'           => 'id',
        'nome'         => 'nome',
        'cognome'      => 'cognome',
        'email'        => 'email',
        'telefono'     => 'telefono',
        'settore'      => 'settore',
        'sottosettore' => 'sottosettore',
        // FK/assignment to the beauty advisor handling this request.
        'beauty_advisor' => 'beatyadvisor',
        'status'       => 'status',
        // Appointment date/time shown on the calendar.
        'day'          => 'day',
        'price'        => 'price',
        'pacchetto'    => 'pacchetto',
        'package_id'   => 'packageID',
        'payment_id'   => 'paymentid',
        'token'        => 'token',
        'link_meet'    => 'link-meet',
    ],

    'messages' => [
        'table'  => 'tMessages',
        'pk'      => 'id',
        'name'    => 'NAME',
        'type'    => 'TYPE',
        'lang'    => 'LANG',
        'value'   => 'VALUE',
        'id_ref'  => 'id_ref',
    ],

    // Template names used by the app's "azioni" buttons. Adjust to match what
    // already exists in tMessages (seen in the ASP.NET panel: CONFERMA_PRENOTAZIONE, ACCOUNT).
    'template_names' => [
        'booking_confirmation' => 'CONFERMA_PRENOTAZIONE',
        'taken_in_charge'      => 'PRESA_IN_CARICO',
        'collaboration_contract' => 'CONTRATTO_COLLABORAZIONE',
    ],

    // Config rows this app stores in tMessages (TYPE = 'CONFIG') for SMTP settings,
    // editable from the Messaggi page instead of hardcoding secrets in config.php.
    'smtp_config_names' => [
        'host'       => 'SMTP_HOST',
        'port'       => 'SMTP_PORT',
        'encryption' => 'SMTP_ENCRYPTION',
        'username'   => 'SMTP_USER',
        'password'   => 'SMTP_PASS',
        'from_email' => 'SMTP_FROM_EMAIL',
        'from_name'  => 'SMTP_FROM_NAME',
    ],
];
