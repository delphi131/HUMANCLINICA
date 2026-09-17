<?php

declare(strict_types=1);

final class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('humanclinica_admin');
            session_start();
        }
    }

    public static function attempt(string $username, string $password): array
    {
        $users = new UserRepository();
        $user = $users->findByUsername($username);

        if ($user === null) {
            return ['ok' => false, 'error' => 'Credenziali non valide.'];
        }

        $map = Config::schema()['users'];
        $hash = $user[$map['password_hash']] ?? null;

        if (empty($hash)) {
            return [
                'ok' => false,
                'error' => 'Nessuna password impostata per questo pannello. '
                    . 'Un amministratore deve eseguire: php tools/set_password.php ' . $username . ' <nuova-password>',
            ];
        }

        if (!password_verify($password, $hash)) {
            return ['ok' => false, 'error' => 'Credenziali non valide.'];
        }

        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user[$map['pk']];
        $_SESSION['username'] = $user[$map['username']];
        $_SESSION['display_name'] = $users->displayName($user);
        $_SESSION['is_admin'] = $users->isAdmin($user);

        return ['ok' => true];
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (empty($_SESSION['is_admin'])) {
            http_response_code(403);
            die('Accesso riservato agli amministratori.');
        }
    }

    public static function user(): array
    {
        self::start();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'display_name' => $_SESSION['display_name'] ?? null,
            'is_admin' => $_SESSION['is_admin'] ?? false,
        ];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
