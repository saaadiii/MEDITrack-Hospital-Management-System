<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width,initial-scale=1.0"
    >
    <title>MEDITrack - <?= esc(
        $title ?? 'Dashboard'
    ) ?></title>
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20260923-final"
    >
</head>
<body class="dashboard-page">
    <aside class="sidebar">
        <div class="sidebar-brand">MEDI<span>Track</span></div>
        <nav>
            <?php foreach ($nav as $item): ?><a
                class="nav-link <?= active_page(
                    $item[0],
                    $current
                ) ?>"
                href="<?= $item[1] ?>"
            ><?= $item[2] ?></a><?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom"><a
                href="index.php?page=logout<?= !empty($logoutRole) ? '&amp;role=' . urlencode($logoutRole) : '' ?>"
                class="signout-link"
            >Sign out</a></div>
    </aside>
    <main class="main-content">
        <header class="dashboard-header">
            <div>
                <h1><?= esc(
                    $heading ?? 'MEDITrack'
                ) ?></h1>
                <p><?= esc(
                    $subheading ?? 'Hospital patient, appointment & resource management'
                ) ?></p>
            </div>
        </header>
        <?php if (!empty($message)): ?>
        <div class="form-message <?= esc(
            $type ?? ($message_type ?? 'success')
        ) ?>"><?= esc($message) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?>
        <div class="form-message error"><?= esc($error) ?></div><?php endif; ?>
