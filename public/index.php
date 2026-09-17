<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

header('Location: ' . (Auth::check() ? 'dashboard.php' : 'login.php'));
exit;
