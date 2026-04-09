<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Response;

/**
 * HomeController — handles the root and misc public pages.
 */
class HomeController
{
    public function index(array $request): void
    {
        (new Response())->view('pages/home', [
            'title'   => 'Welcome',
            'heading' => 'PHP Starter',
        ]);
    }

    public function about(array $request): void
    {
        (new Response())->view('pages/about', ['title' => 'About']);
    }
}

/**
 * UserController — example resource controller with URL parameters.
 */
class UserController
{
    /** GET /users */
    public function index(array $request): void
    {
        // Replace with real DB query
        $users = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        (new Response())->json(['data' => $users]);
    }

    /** GET /users/{id} */
    public function show(array $request): void
    {
        $id = (int) $request['params']['id'];

        // Replace with real DB lookup
        $user = ['id' => $id, 'name' => 'Alice'];

        (new Response())->json(['data' => $user]);
    }

    /** POST /users */
    public function store(array $request): void
    {
        $body = $request['body'];

        // Validate
        if (empty($body['name'])) {
            (new Response())->json(['error' => 'name is required'], 422);
            return;
        }

        // Persist → return created resource
        $created = ['id' => 3, 'name' => $body['name']];
        (new Response())->json(['data' => $created], 201);
    }

    /** PUT /users/{id} */
    public function update(array $request): void
    {
        $id   = (int) $request['params']['id'];
        $body = $request['body'];

        $updated = ['id' => $id, 'name' => $body['name'] ?? 'Unknown'];
        (new Response())->json(['data' => $updated]);
    }

    /** DELETE /users/{id} */
    public function destroy(array $request): void
    {
        $id = (int) $request['params']['id'];
        // Perform deletion...
        (new Response())->json(['message' => "User {$id} deleted"]);
    }
}
