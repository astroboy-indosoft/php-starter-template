<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\View::e($title ?? 'App') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/dashboard">Dashboard</a>
    </nav>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <p>&copy; <?= date('Y') ?></p>
    </footer>
</body>
</html>
