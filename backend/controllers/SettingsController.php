<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/SystemSettings.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Models\SystemSettings;

class SettingsController
{
    private SystemSettings $settings;

    /**
     * Create the settings controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->settings = new SystemSettings($pdo);
    }

    /**
     * Return all settings.
     */
    public function index(): array
    {
        return Response::success('Settings loaded.', ['settings' => $this->settings->all()]);
    }

    /**
     * Return one setting.
     */
    public function show(string $key): array
    {
        return Response::success('Setting loaded.', ['value' => $this->settings->get($key)]);
    }

    /**
     * Create or update one setting.
     */
    public function update(string $key, mixed $value): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->settings->set($key, $value)) {
            return Response::error('Setting could not be saved.', 400);
        }

        return Response::success('Setting saved.');
    }

    /**
     * Delete one setting.
     */
    public function destroy(string $key): array
    {
        // Guard this block so it only runs when the required condition is met.
        if (!$this->settings->delete($key)) {
            return Response::error('Setting could not be deleted.', 400);
        }

        return Response::success('Setting deleted.');
    }
}
