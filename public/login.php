<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid($_POST['csrf_token'] ?? null);

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Inserisci username e password.';
    } else {
        $result = Auth::attempt($username, $password);
        if ($result['ok']) {
            header('Location: dashboard.php');
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Accedi — <?= htmlspecialchars(Config::get('app.name', 'HumanClinica Admin')) ?></title>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body">
<div class="login-card">
    <h1 class="brand">humanclinica</h1>
    <p class="brand-sub">Pannello prenotazioni</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <?= Csrf::field() ?>
        <label>Username
            <input type="text" name="username" autocomplete="username" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit" class="btn btn-primary btn-block">Accedi</button>
    </form>
</div>
</body>
</html>
