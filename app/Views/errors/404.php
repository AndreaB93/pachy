<?php
use Core\View;
View::layout('layouts/main', ['title' => 'Pagina non trovata']);
?>
<section style="text-align:center;padding:4rem 0;">
    <h1 style="font-size:6rem;color:#ccc;">404</h1>
    <h2>Pagina non trovata</h2>
    <p style="margin:1rem 0;color:#666;">La pagina che cerchi non esiste o è stata spostata.</p>
    <a href="/" style="display:inline-block;padding:.6rem 1.4rem;background:#1a1a2e;color:#fff;text-decoration:none;border-radius:4px;">
        Torna alla home
    </a>
</section>
