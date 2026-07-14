<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/Role.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Validator;
use AfriSense\Backend\Models\Role;

class RoleController
{
    private Role $roles;

    /**
     * Create the role controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->roles = new Role($pdo);
    }

    /**
     * Return all roles.
     */
    public function index(): array
    {
        return Response::success('Roles loaded.', ['roles' => $this->roles->all()]);
    }

    /**
     * Return one role.
     */
    public function show(int $id): array
    {
        $role = $this->roles->findById($id);

        if ($role === null) {
            return Response::error('Role not found.', 404);
        }

        return Response::success('Role loaded.', ['role' => $role]);
    }

    /**
     * Create a role.
     */
    public function store(array $request): array
    {
        $validator = new Validator();
        $nameField = isset($request['rolename']) ? 'rolename' : 'name';

        if (!$validator->validate($request, [$nameField => 'required|minLength:2|maxLength:100'])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        $id = $this->roles->create($request);

        if ($id === null) {
            return Response::error('Role could not be created.', 500);
        }

        return Response::success('Role created.', ['id' => $id], 201);
    }

    /**
     * Update a role.
     */
    public function update(int $id, array $request): array
    {
        if (!$this->roles->update($id, $request)) {
            return Response::error('Role could not be updated.', 400);
        }

        return Response::success('Role updated.');
    }

    /**
     * Delete a role.
     */
    public function destroy(int $id): array
    {
        if (!$this->roles->delete($id)) {
            return Response::error('Role could not be deleted.', 400);
        }

        return Response::success('Role deleted.');
    }

    /**
     * Assign a permission to a role.
     */
    public function assignPermission(int $roleId, int $permissionId): array
    {
        if (!$this->roles->assignPermission($roleId, $permissionId)) {
            return Response::error('Permission could not be assigned.', 400);
        }

        return Response::success('Permission assigned.');
    }

    /**
     * Remove a permission from a role.
     */
    public function removePermission(int $roleId, int $permissionId): array
    {
        if (!$this->roles->removePermission($roleId, $permissionId)) {
            return Response::error('Permission could not be removed.', 400);
        }

        return Response::success('Permission removed.');
    }
}
