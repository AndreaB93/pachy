<?php
use Core\View;
View::layout('layouts/main', ['title' => 'Accedi']);
?>
<section style="max-width:400px;margin:3rem auto;">
    <h2>Accedi</h2>

    <?php if (!empty($error)): ?>
        <p style="color:red;margin:.5rem 0;"><?= View::e($error) ?></p>
    <?php endif ?>

    <form method="POST" action="/login" style="display:flex;flex-direction:column;gap:.75rem;margin-top:1rem;">
        <input type="hidden" name="_csrf" value="<?= View::e($csrf ?? '') ?>">

        <label>
            Email
            <input type="email" name="email" required autocomplete="email"
                   style="display:block;width:100%;padding:.5rem;margin-top:.25rem;">
        </label>

        <label>
            Password
            <input type="password" name="password" required autocomplete="current-password"
                   style="display:block;width:100%;padding:.5rem;margin-top:.25rem;">
        </label>

        <button type="submit" style="padding:.6rem 1.2rem;cursor:pointer;">Accedi</button>
    </form>
</section>
