<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../models/Notification.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Helpers\Validator;
use AfriSense\Backend\Models\Notification;

class NotificationController
{
    private Notification $notifications;

    /**
     * Create the notification controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->notifications = new Notification($pdo);
    }

    /**
     * Return notifications for a user.
     */
    public function index(int $userId): array
    {
        return Response::success('Notifications loaded.', [
            'notifications' => $this->notifications->allForUser($userId),
        ]);
    }

    /**
     * Create a notification.
     */
    public function store(array $request): array
    {
        $validator = new Validator();
        $typeField = isset($request['notification_type']) ? 'notification_type' : 'type';

        // Guard this block so it only runs when the required condition is met.
        if (!$validator->validate($request, [
            'user_id' => 'required',
            'title' => 'required|minLength:2|maxLength:150',
            'message' => 'required|minLength:2',
            $typeField => 'required',
        ])) {
            return Response::error('Validation failed.', 422, $validator->getErrors());
        }

        $id = $this->notifications->create($request);

        // Guard this block so it only runs when the required condition is met.
        if ($id === null) {
            return Response::error('Notification could not be created.', 500);
        }

        return Response::success('Notification created.', ['id' => $id], 201);
    }

    /**
     * Mark one notification as read.
     */
    public function markAsRead(int $id, int $userId): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->notifications->markAsRead($id, $userId)) {
            return Response::error('Notification could not be updated.', 400);
        }

        return Response::success('Notification marked as read.');
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): array
    {
        $count = $this->notifications->markAllAsRead($userId);

        return Response::success('Notifications marked as read.', ['updated' => $count]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(int $id, int $userId): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->notifications->delete($id, $userId)) {
            return Response::error('Notification could not be deleted.', 400);
        }

        return Response::success('Notification deleted.');
    }
}
