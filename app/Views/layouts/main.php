<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \Core\View::e($title ?? 'pachy App') ?></title>
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f4f6f9; color: #1a1a2e; }
        header { background: #1a1a2e; color: #fff; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #a8d8ea; text-decoration: none; margin-left: 1rem; }
        main { max-width: 960px; margin: 2rem auto; padding: 0 1rem; }
        footer { text-align: center; padding: 2rem; color: #666; font-size: .85rem; }
    </style>
</head>
<body>
<header>
    <a href="/" style="font-weight:bold;font-size:1.2rem;color:#fff;">pachy</a>
    <nav>
        <?php if (!empty($user)): ?>
            <span>Ciao, #<?= \Core\View::e($user['id']) ?></span>
            <a href="/logout">Esci</a>
        <?php else: ?>
            <a href="/login">Accedi</a>
        <?php endif ?>
    </nav>
</header>
<main>
    <?= $content ?? '' ?>
</main>
<footer>pachy &mdash; Minimal PHP 8.5 Architecture Framework</footer>
</body>
</html>

