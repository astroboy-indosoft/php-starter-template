<?php $layout = 'default'; ?>

<h1><?= \App\View::e($heading ?? 'Welcome') ?></h1>
<p>Your native PHP starter project is running.</p>

<h2>Try these routes</h2>
<ul>
    <li><a href="/about">/about</a></li>
    <li><a href="/api/users">/api/users</a> — JSON list</li>
    <li><a href="/api/users/42">/api/users/42</a> — JSON single ({id} param)</li>
    <li><a href="/dashboard">/dashboard</a> — auth-protected</li>
</ul>
