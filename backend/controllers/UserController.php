<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/User.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Validator;
use AfriSense\Backend\Models\User;

class UserController
{
    private User $users;

    /**
     * Create the user controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->users = new User($pdo);
    }

    /**
     * Return all users.
     */
    public function index(): array
    {
        $users = array_map(
            static function (array $user): array {
                unset($user['password']);

                return $user;
            },
            $this->users->all()
        );

        return Response::success('Users loaded.', ['users' => $users]);
    }

    /**
     * Return one user.
     */
    public function show(int $id): array
    {
        $user = $this->users->findById($id);

        if ($user === null) {
            return Response::error('User not found.', 404);
        }

        unset($user['password']);

        return Response::success('User loaded.', ['user' => $user]);
    }

    /**
     * Create a user.
     */
    public function store(array $request): array
    {
        $validator = new Validator();
        $phoneField = isset($request['phonenumber']) ? 'phonenumber' : 'phone';

        if (!$validator->validate($request, [
            'fullname' => 'required|minLength:2|maxLength:150',
            'username' => 'required|minLength:2|maxLength:100',
            'email' => 'required|email',
            $phoneField => 'required|phone',
            'password' => 'required|password',
            'role_id' => 'required',
        ])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        $id = $this->users->create($request);

        if ($id === null) {
            return Response::error('User could not be created.', 500);
        }

        return Response::success('User created.', ['id' => $id], 201);
    }

    /**
     * Update a user.
     */
    public function update(int $id, array $request): array
    {
        $validator = new Validator();

        if (isset($request['email']) && !$validator->validate($request, ['email' => 'email'])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        if (!$this->users->update($id, $request)) {
            return Response::error('User could not be updated.', 400);
        }

        return Response::success('User updated.');
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id): array
    {
        if (!$this->users->delete($id)) {
            return Response::error('User could not be deleted.', 400);
        }

        return Response::success('User deleted.');
    }
}
