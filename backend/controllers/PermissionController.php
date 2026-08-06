<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/Permission.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Validator;
use AfriSense\Backend\Models\Permission;

class PermissionController
{
    private Permission $permissions;

    /**
     * Create the permission controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->permissions = new Permission($pdo);
    }

    /**
     * Return all permissions.
     */
    public function index(): array
    {
        return Response::success('Permissions loaded.', ['permissions' => $this->permissions->all()]);
    }

    /**
     * Return one permission.
     */
    public function show(int $id): array
    {
        $permission = $this->permissions->findById($id);

        // Guard this block so it only runs when the required condition is met.
        if ($permission === null) {
            return Response::error('Permission not found.', 404);
        }

        return Response::success('Permission loaded.', ['permission' => $permission]);
    }

    /**
     * Create a permission.
     */
    public function store(array $request): array
    {
        $validator = new Validator();

        // Guard this block so it only runs when the required condition is met.
        if (!$validator->validate($request, ['name' => 'required|minLength:2|maxLength:100'])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        $id = $this->permissions->create($request);

        // Guard this block so it only runs when the required condition is met.
        if ($id === null) {
            return Response::error('Permission could not be created.', 500);
        }

        return Response::success('Permission created.', ['id' => $id], 201);
    }

    /**
     * Update a permission.
     */
    public function update(int $id, array $request): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->permissions->update($id, $request)) {
            return Response::error('Permission could not be updated.', 400);
        }

        return Response::success('Permission updated.');
    }

    /**
     * Delete a permission.
     */
    public function destroy(int $id): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->permissions->delete($id)) {
            return Response::error('Permission could not be deleted.', 400);
        }

        return Response::success('Permission deleted.');
    }
}
