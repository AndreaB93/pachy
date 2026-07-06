<!-- app/Views/partials/flash.php -->
<?php use Core\View; ?>
<?php if (!empty($flash)): ?>
    <div style="padding:.75rem 1rem;margin-bottom:1rem;border-radius:4px;
                background:<?= $flash['type'] === 'error' ? '#fdecea' : '#e8f5e9' ?>;
                color:<?= $flash['type'] === 'error' ? '#c62828' : '#2e7d32' ?>;">
        <?= View::e($flash['message']) ?>
    </div>
<?php endif ?>
