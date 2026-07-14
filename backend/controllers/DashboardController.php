<?php

declare(strict_types=1);

namespace AfriSense\Backend\Controllers;

use PDO;

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/Dashboard.php';

use AfriSense\Backend\Helpers\Response;
use AfriSense\Backend\Models\Dashboard;

class DashboardController
{
    private Dashboard $dashboard;

    /**
     * Create the dashboard controller.
     */
    public function __construct(PDO $pdo)
    {
        $this->dashboard = new Dashboard($pdo);
    }

    /**
     * Return dashboard summary data.
     */
    public function index(?int $userId = null): array
    {
        return Response::success('Dashboard loaded.', $this->dashboard->summary($userId));
    }
}
