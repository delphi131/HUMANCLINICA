<?php

declare(strict_types=1);

error_reporting(E_ALL);

require __DIR__ . '/Config.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/Csrf.php';
require __DIR__ . '/Auth.php';
require __DIR__ . '/UserRepository.php';
require __DIR__ . '/AziendaRepository.php';
require __DIR__ . '/ContractRepository.php';
require __DIR__ . '/ReservationRepository.php';
require __DIR__ . '/ReminderRepository.php';
require __DIR__ . '/MessageRepository.php';
require __DIR__ . '/WhatsApp.php';
require __DIR__ . '/SmtpMailer.php';

date_default_timezone_set(Config::get('app.timezone', 'Europe/Rome'));
ini_set('display_errors', Config::get('app.debug', false) ? '1' : '0');

Auth::start();
