<?php
use Core\View;
View::layout('layouts/main', ['title' => 'Home', 'user' => $user ?? null]);
?>
<section>
    <h1>Benvenuto in pachy</h1>
    <p>Un framework architetturale minimale per PHP 8.5.</p>

    <?php if (!empty($user)): ?>
        <p>Sei autenticato come utente <strong>#<?= View::e($user['id']) ?></strong> (ruolo: <em><?= View::e($user['role']) ?></em>).</p>
    <?php else: ?>
        <p><a href="/login">Accedi</a> per continuare.</p>
    <?php endif ?>
</section>
