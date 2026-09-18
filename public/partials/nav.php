<?php
/** @var array $__user Provided by including page via Auth::user() */
$__user = Auth::user();
$__current = basename($_SERVER['SCRIPT_NAME']);

$__name = trim((string)($__user['display_name'] ?? ''));
$__parts = $__name !== '' ? preg_split('/\s+/', $__name) : [];
$__initials = '';
if (!empty($__parts)) {
    $__initials = mb_strtoupper(mb_substr($__parts[0], 0, 1));
    if (count($__parts) > 1) {
        $__initials .= mb_strtoupper(mb_substr(end($__parts), 0, 1));
    }
}
?>
<header class="topbar">
    <a href="dashboard.php" class="topbar-brand"><img src="assets/img/logo.webp" alt="Human Clinica"></a>
    <nav class="topbar-nav">
        <a href="dashboard.php" class="<?= $__current === 'dashboard.php' ? 'active' : '' ?>">Calendario</a>
        <a href="messaggi.php" class="<?= $__current === 'messaggi.php' ? 'active' : '' ?>">Messaggi</a>
        <?php if (!empty($__user['is_admin'])): ?>
        <a href="utenti.php" class="<?= in_array($__current, ['utenti.php', 'utente_form.php'], true) ? 'active' : '' ?>">Utenti</a>
        <a href="aziende.php" class="<?= in_array($__current, ['aziende.php', 'azienda_form.php', 'azienda_contratto.php'], true) ? 'active' : '' ?>">Aziende</a>
        <?php endif; ?>
    </nav>
    <div class="topbar-user">
        <span class="avatar"><?= htmlspecialchars($__initials) ?></span>
        <span><?= htmlspecialchars($__name) ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Esci</a>
    </div>
</header>
