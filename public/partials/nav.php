<?php
/** @var array $__user Provided by including page via Auth::user() */
$__user = Auth::user();
$__current = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="topbar">
    <div class="topbar-brand">humanclinica</div>
    <nav class="topbar-nav">
        <a href="dashboard.php" class="<?= $__current === 'dashboard.php' ? 'active' : '' ?>">Calendario</a>
        <a href="messaggi.php" class="<?= $__current === 'messaggi.php' ? 'active' : '' ?>">Messaggi</a>
    </nav>
    <div class="topbar-user">
        <span><?= htmlspecialchars($__user['display_name'] ?? '') ?></span>
        <a href="logout.php" class="btn btn-outline btn-sm">Esci</a>
    </div>
</header>
