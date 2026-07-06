<?php
use Core\View;
View::layout('layouts/main', ['title' => 'Registrati']);
?>
<section style="max-width:420px;margin:3rem auto;">
    <h2>Crea account</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;margin:.5rem 0;"><?= View::e($error) ?></p>
    <?php endif ?>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;margin:.5rem 0;">
            <?php foreach ($errors as $field => $msgs): ?>
                <?php foreach ($msgs as $msg): ?>
                    <li><?= View::e($msg) ?></li>
                <?php endforeach ?>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <form method="POST" action="/register" style="display:flex;flex-direction:column;gap:.75rem;margin-top:1rem;">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">

        <label>
            Nome completo
            <input type="text" name="name" required style="display:block;width:100%;padding:.5rem;margin-top:.25rem;">
        </label>

        <label>
            Email
            <input type="email" name="email" required style="display:block;width:100%;padding:.5rem;margin-top:.25rem;">
        </label>

        <label>
            Password
            <input type="password" name="password" required minlength="8"
                   style="display:block;width:100%;padding:.5rem;margin-top:.25rem;">
        </label>

        <button type="submit" style="padding:.6rem 1.2rem;cursor:pointer;">Registrati</button>
    </form>

    <p style="margin-top:1rem;font-size:.9rem;">Hai già un account? <a href="/login">Accedi</a></p>
</section>
