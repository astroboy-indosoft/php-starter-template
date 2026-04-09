# PHP Starter

A minimal, framework-free PHP starter project with a RadixNode router, PDO database wrapper, service container, validator, and view renderer.

## Requirements

- PHP 8.1+
- Apache (with `mod_rewrite`) **or** the built-in PHP dev server
- MySQL / MariaDB (optional — only needed if using the database layer)

---

## Quick start

```bash
# 1. Clone / download the project
cd php-starter

# 2. Copy env file and fill in your values
cp .env.example .env

# 3. Start the dev server (document root = public/)
php -S localhost:8000 -t public
```

Open http://localhost:8000 in your browser.

---

## Project structure

```
php-starter/
├── config/
│   └── app.php            # All config (reads from $_ENV / .env)
├── public/
│   ├── index.php          # Front controller — only entry point
│   ├── .htaccess          # Apache URL rewrite rules
│   └── assets/
│       ├── css/app.css
│       └── js/
├── routes/
│   └── web.php            # All route definitions
├── src/
│   ├── App.php            # Service container + config helper
│   ├── Autoloader.php     # PSR-4-style autoloader (no Composer required)
│   ├── Database.php       # PDO wrapper
│   ├── Http.php           # Response builder + View renderer
│   ├── Logger.php         # File logger
│   ├── Router.php         # RadixNode router
│   ├── Validator.php      # Input validator
│   ├── Controllers/
│   │   └── Controllers.php
│   ├── Middleware/
│   │   └── CommonMiddleware.php
│   └── Models/
│       ├── Model.php      # Base active-record model
│       └── User.php       # Example model
├── views/
│   ├── layouts/
│   │   └── default.php
│   ├── pages/
│   │   ├── home.php
│   │   ├── about.php
│   │   └── dashboard.php
│   └── errors/
│       ├── 404.php
│       └── 405.php
├── storage/
│   ├── logs/              # app.log written here
│   └── cache/
├── .env.example
├── .gitignore
└── README.md
```

---

## Router

Routes are defined in `routes/web.php`. The router uses a **RadixNode tree** so parameter matching is O(log n), not a linear regex scan.

### Route registration

```php
$router->get('/path', handler);
$router->post('/path', handler, [middleware, ...]);
$router->put('/path', handler);
$router->patch('/path', handler);
$router->delete('/path', handler);
$router->any('/path', handler);         // all methods
```

### URL parameters

```php
// Named segment  →  $request['params']['id']
$router->get('/users/{id}', fn($req) => ...);

// Wildcard  →  $request['params']['path']  (captures the rest of the URL)
$router->get('/files/{path*}', fn($req) => ...);
```

### Named routes

```php
$router->name('user.show', '/users/{id}');

// Generate URL
$url = $router->url('user.show', ['id' => 42]);  // → '/users/42'
```

### Middleware

```php
// Global (runs on every request)
$router->use(new CorsMiddleware());

// Per-route
$router->get('/admin', $handler, [new AuthMiddleware()]);
```

A middleware is any callable with the signature:

```php
function (array $request, callable $next): void
{
    // before
    $next();
    // after (optional)
}
```

The `$request` array contains:

| Key       | Type   | Description                        |
|-----------|--------|------------------------------------|
| `method`  | string | HTTP verb                          |
| `uri`     | string | Parsed path                        |
| `params`  | array  | URL parameters from `{name}`       |
| `query`   | array  | `$_GET`                            |
| `body`    | array  | `$_POST` or decoded JSON body      |
| `headers` | array  | HTTP headers (normalized names)    |

---

## Response

```php
use App\Response;

$res = new Response();

$res->json(['data' => $user]);           // 200 JSON
$res->json(['error' => 'nope'], 422);    // 422 JSON
$res->html('<h1>Hello</h1>');
$res->view('pages/home', ['title' => 'Home']);
$res->redirect('/login');
$res->redirect('/dashboard', 301);
```

---

## Views & layouts

Templates live in `views/`. To wrap a page in a layout, set `$layout` at the top of the template:

```php
<?php $layout = 'default'; ?>
<h1><?= \App\View::e($title) ?></h1>
<p>Content here.</p>
```

The layout receives `$content` (the rendered inner template) and any other variables passed to `view()`.

Always escape output with `View::e()`:

```php
<?= \App\View::e($userInput) ?>
```

---

## Database

Register the database in `public/index.php`:

```php
App::bind('db', fn() => new Database(App::config('db')));
```

Then use directly or via a Model:

```php
// Direct
$db    = App::make('db');
$users = $db->all('SELECT * FROM users WHERE active = ?', [1]);
$user  = $db->first('SELECT * FROM users WHERE id = ?', [$id]);
$newId = $db->insert('users', ['name' => 'Alice', 'email' => 'alice@x.com']);
$db->update('users', ['name' => 'Bob'], 'id = ?', [1]);
$db->delete('users', 'id = ?', [1]);

// Transaction
$db->transaction(function ($db) {
    $db->insert('orders', [...]);
    $db->update('inventory', [...], 'sku = ?', ['ABC']);
});
```

---

## Models

```php
namespace App\Models;

class Post extends Model
{
    protected static string $table = 'posts';
}

// Usage
$posts    = Post::all('created_at DESC');
$post     = Post::find(1);
$drafts   = Post::where('status = ?', ['draft']);
$newId    = Post::create(['title' => 'Hello', 'body' => '...']);
Post::update(['title' => 'Updated'], 'id = ?', [1]);
Post::delete('id = ?', [1]);
$total    = Post::count();
```

---

## Validation

```php
use App\Validator;

$v = new Validator($request['body'], [
    'name'  => 'required|min:2|max:120',
    'email' => 'required|email',
    'role'  => 'required|in:admin,editor,viewer',
    'age'   => 'integer|min:0|max:120',
]);

if ($v->fails()) {
    (new Response())->json(['errors' => $v->errors()], 422);
    return;
}
```

Available rules: `required`, `string`, `integer`, `float`, `boolean`, `email`, `url`, `min:{n}`, `max:{n}`, `in:{a,b,c}`

---

## Logging

```php
use App\Logger;

Logger::info('User registered', ['user_id' => $id]);
Logger::warning('Rate limit approached', ['ip' => $_SERVER['REMOTE_ADDR']]);
Logger::error('Payment failed', ['order_id' => $orderId, 'reason' => $e->getMessage()]);
```

Log path is set in `public/index.php` via `Logger::setPath(config('paths.logs'))`.

---

## Apache setup

Point your virtual host document root to `public/`:

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/php-starter/public
    <Directory /path/to/php-starter/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Nginx setup

```nginx
server {
    root /path/to/php-starter/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

---

## Coding standards

- `declare(strict_types=1)` at the top of every file
- Classes → `PascalCase`, methods/variables → `camelCase`, constants → `SCREAMING_SNAKE_CASE`
- Type hints on all parameters and return types
- Prepared statements only — never interpolate user input into SQL
- All user output escaped through `View::e()`
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- Errors logged, never echoed to the user in production
