<?php

declare(strict_types=1);

$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Delivery Management | AfriSense';
$adminTitle = 'Delivery Management';
$adminSearchPlaceholder = 'Search orders, customers, riders, phone...';
$activeAdminPage = 'delivery_management';
$deliveryCssPath = __DIR__ . '/../assets/css/admin-delivery.css';
$extraStyles = [
    $frontendBase . '/assets/css/admin-menu.css',
    $frontendBase . '/assets/css/admin-users-settings.css',
];

// Guard this block so it only runs when the required condition is met.
if (is_file($deliveryCssPath)) {
    $extraStyles[] = $frontendBase . '/assets/css/admin-delivery.css?v=' . filemtime($deliveryCssPath);
}

require_once __DIR__ . '/../auth/auth_bootstrap.php';
require_once __DIR__ . '/../includes/public_settings.php';

$authUser = afrisense_require_admin();
$adminUserId = (int) ($authUser['id'] ?? 0);
$itemsPerPage = min(10, afrisense_admin_items_per_page());

/**
 * Delivery management links orders to riders and payment-on-delivery records.
 *
 * The table is created defensively so older local databases can use the page
 * without a manual migration.
 */
function afrisense_delivery_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `delivery_assignments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL UNIQUE,
            `rider_user_id` INT NULL,
            `delivery_status` ENUM("Pending","Assigned","Picked Up","In Transit","Delivered","Returned","Cancelled") NOT NULL DEFAULT "Pending",
            `delivery_type` VARCHAR(40) NOT NULL DEFAULT "Standard Delivery",
            `amount_collected` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `change_given` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `assigned_at` DATETIME NULL,
            `picked_up_at` DATETIME NULL,
            `delivered_at` DATETIME NULL,
            `notes` TEXT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_delivery_order` (`order_id`),
            INDEX `idx_delivery_rider` (`rider_user_id`),
            INDEX `idx_delivery_status` (`delivery_status`),
            CONSTRAINT `fk_delivery_order`
                FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

// Defines the afrisense_delivery_status_class helper used by this module.
function afrisense_delivery_status_class(string $status): string
{
    return match (strtolower($status)) {
        'assigned' => 'assigned',
        'picked up' => 'picked',
        'in transit' => 'transit',
        'delivered' => 'delivered',
        'returned' => 'returned',
        'cancelled' => 'cancelled',
        default => 'pending',
    };
}

// Defines the afrisense_delivery_payment_class helper used by this module.
function afrisense_delivery_payment_class(string $method): string
{
    return match (strtolower($method)) {
        'mobile money' => 'momo',
        'card' => 'card',
        default => 'cash',
    };
}

// Defines the afrisense_delivery_money helper used by this module.
function afrisense_delivery_money(float $value): string
{
    return 'GH₵ ' . number_format($value, 2);
}

// Defines the afrisense_delivery_effective_status_sql helper used by this module.
function afrisense_delivery_effective_status_sql(): string
{
    // Orders created before a delivery assignment still need to appear in the
    // list, so derive a display status from the order status when no assignment
    // row exists yet.
    return 'COALESCE(
        da.`delivery_status`,
        CASE
            WHEN o.`order_status` = "Delivered" THEN "Delivered"
            WHEN o.`order_status` = "Out for Delivery" THEN "In Transit"
            WHEN o.`order_status` = "Ready" THEN "Assigned"
            WHEN o.`order_status` = "Cancelled" THEN "Cancelled"
            ELSE "Pending"
        END
    )';
}

// Defines the afrisense_delivery_url helper used by this module.
function afrisense_delivery_url(array $overrides = [], string $anchor = ''): string
{
    // Preserve filters/search/page state when opening details or paging.
    $params = $_GET;

    // Iterate through the data needed for this block.
    foreach ($overrides as $key => $value) {
        // Guard this block so it only runs when the required condition is met.
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }

    $query = http_build_query($params);

    return 'delivery-management.php' . ($query !== '' ? '?' . $query : '') . $anchor;
}

// Defines the afrisense_delivery_notify helper used by this module.
function afrisense_delivery_notify(PDO $pdo, int $userId, string $title, string $message, string $actionUrl, int $createdBy): void
{
    // Guard this block so it only runs when the required condition is met.
    if ($userId <= 0) {
        return;
    }

    // Delivery notifications are written to the same notifications table used
    // by orders, so riders and customers see updates through existing UI.
    $statement = $pdo->prepare(
        'INSERT INTO `notifications`
            (`user_id`, `title`, `message`, `notification_type`, `action_url`, `created_by`)
         VALUES
            (:user_id, :title, :message, :notification_type, :action_url, :created_by)'
    );
    $statement->execute([
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'notification_type' => 'Order',
        'action_url' => $actionUrl,
        'created_by' => $createdBy > 0 ? $createdBy : null,
    ]);
}

// Defines the afrisense_delivery_customer_user_id helper used by this module.
function afrisense_delivery_customer_user_id(PDO $pdo, int $orderId): ?int
{
    // Guest orders may not map to a user account; registered customers are
    // matched by the customer contact attached to the order.
    $statement = $pdo->prepare(
        'SELECT u.`id`
         FROM `orders` o
         INNER JOIN `customers` c ON c.`id` = o.`customer_id`
         INNER JOIN `users` u
            ON u.`email` = c.`email`
            OR REPLACE(u.`phonenumber`, " ", "") = REPLACE(c.`phone_number`, " ", "")
         WHERE o.`id` = :order_id
         ORDER BY u.`id` ASC
         LIMIT 1'
    );
    $statement->execute(['order_id' => $orderId]);
    $userId = $statement->fetchColumn();

    return $userId !== false ? (int) $userId : null;
}

// Defines the afrisense_delivery_assignment helper used by this module.
function afrisense_delivery_assignment(PDO $pdo, int $orderId, ?int $riderId, string $status, string $deliveryType, float $amountCollected, float $changeGiven, string $notes): void
{
    // One assignment row belongs to one order. Reassigning or updating delivery
    // status uses ON DUPLICATE KEY UPDATE so admin actions stay idempotent.
    $statement = $pdo->prepare(
        'INSERT INTO `delivery_assignments`
            (`order_id`, `rider_user_id`, `delivery_status`, `delivery_type`, `amount_collected`, `change_given`, `assigned_at`, `picked_up_at`, `delivered_at`, `notes`)
         VALUES
            (:order_id, :rider_user_id, :delivery_status, :delivery_type, :amount_collected, :change_given,
             CASE WHEN :assigned_status IN ("Assigned","Picked Up","In Transit","Delivered") THEN NOW() ELSE NULL END,
             CASE WHEN :picked_status IN ("Picked Up","In Transit","Delivered") THEN NOW() ELSE NULL END,
             CASE WHEN :delivered_status = "Delivered" THEN NOW() ELSE NULL END,
             :notes)
         ON DUPLICATE KEY UPDATE
            `rider_user_id` = VALUES(`rider_user_id`),
            `delivery_status` = VALUES(`delivery_status`),
            `delivery_type` = VALUES(`delivery_type`),
            `amount_collected` = VALUES(`amount_collected`),
            `change_given` = VALUES(`change_given`),
            `assigned_at` = COALESCE(`assigned_at`, VALUES(`assigned_at`)),
            `picked_up_at` = CASE WHEN VALUES(`picked_up_at`) IS NOT NULL THEN COALESCE(`picked_up_at`, VALUES(`picked_up_at`)) ELSE `picked_up_at` END,
            `delivered_at` = CASE WHEN VALUES(`delivered_at`) IS NOT NULL THEN COALESCE(`delivered_at`, VALUES(`delivered_at`)) ELSE `delivered_at` END,
            `notes` = VALUES(`notes`),
            `updated_at` = NOW()'
    );
    $statement->execute([
        'order_id' => $orderId,
        'rider_user_id' => $riderId !== null && $riderId > 0 ? $riderId : null,
        'delivery_status' => $status,
        'delivery_type' => $deliveryType !== '' ? $deliveryType : 'Standard Delivery',
        'amount_collected' => $amountCollected,
        'change_given' => $changeGiven,
        'assigned_status' => $status,
        'picked_status' => $status,
        'delivered_status' => $status,
        'notes' => $notes,
    ]);
}

// Defines the afrisense_delivery_order_status_for_delivery helper used by this module.
function afrisense_delivery_order_status_for_delivery(string $deliveryStatus, string $currentOrderStatus): string
{
    // Keep the order page and delivery page aligned: delivery progress updates
    // the parent order status, but neutral statuses keep the current order state.
    return match ($deliveryStatus) {
        'Assigned', 'Picked Up', 'In Transit' => 'Out for Delivery',
        'Delivered' => 'Delivered',
        'Returned', 'Cancelled' => 'Cancelled',
        default => $currentOrderStatus,
    };
}

$validDeliveryStatuses = ['Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered', 'Returned', 'Cancelled'];
$validDeliveryTypes = ['Standard Delivery', 'Express Delivery', 'Scheduled Delivery'];
$statusFilter = (string) ($_GET['status'] ?? '');
$paymentFilter = (string) ($_GET['payment'] ?? '');
$riderFilter = (int) ($_GET['rider'] ?? 0);
$rangeFilter = (string) ($_GET['range'] ?? 'month');
$search = trim((string) ($_GET['search'] ?? ''));
$viewOrderId = (int) ($_GET['view'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $itemsPerPage;
$flashMessage = '';
$flashType = 'success';

// Run database/action work inside a guarded block so the page can fail gracefully.
try {
    $pdo = afrisense_pdo();
    afrisense_delivery_tables($pdo);

    // Guard this block so it only runs when the required condition is met.
    if (($_GET['export'] ?? '') === 'csv') {
        $exportRows = $pdo->query(
            'SELECT
                o.`id`,
                c.`fullname`,
                c.`phone_number`,
                o.`delivery_address`,
                COALESCE(r.`fullname`, "Unassigned") AS rider_name,
                ' . afrisense_delivery_effective_status_sql() . ' AS delivery_status,
                o.`payment_method`,
                o.`total_price`,
                o.`ordered_at`
             FROM `orders` o
             INNER JOIN `customers` c ON c.`id` = o.`customer_id`
             LEFT JOIN `delivery_assignments` da ON da.`order_id` = o.`id`
             LEFT JOIN `users` r ON r.`id` = da.`rider_user_id`
             ORDER BY o.`ordered_at` DESC, o.`id` DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="afrisense-deliveries-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        // Guard this block so it only runs when the required condition is met.
        if ($output !== false) {
            fputcsv($output, ['Order ID', 'Customer', 'Phone', 'Address', 'Rider', 'Delivery Status', 'Payment Method', 'Amount', 'Ordered At']);
            // Iterate through the data needed for this block.
            foreach ($exportRows as $row) {
                fputcsv($output, [
                    'AFR' . str_pad((string) ($row['id'] ?? 0), 4, '0', STR_PAD_LEFT),
                    (string) ($row['fullname'] ?? ''),
                    (string) ($row['phone_number'] ?? ''),
                    (string) ($row['delivery_address'] ?? ''),
                    (string) ($row['rider_name'] ?? ''),
                    (string) ($row['delivery_status'] ?? ''),
                    (string) ($row['payment_method'] ?? ''),
                    number_format((float) ($row['total_price'] ?? 0), 2, '.', ''),
                    (string) ($row['ordered_at'] ?? ''),
                ]);
            }
        }
        exit;
    }

    // Guard this block so it only runs when the required condition is met.
    if (($_GET['invoice'] ?? '') !== '') {
        $invoiceOrderId = (int) $_GET['invoice'];
        $invoiceStatement = $pdo->prepare(
            'SELECT
                o.*,
                c.`fullname`,
                c.`email`,
                c.`phone_number`,
                f.`food_name`,
                COALESCE(da.`delivery_type`, "Standard Delivery") AS delivery_type,
                COALESCE(da.`amount_collected`, 0) AS amount_collected,
                COALESCE(da.`change_given`, 0) AS change_given,
                COALESCE(r.`fullname`, "Unassigned") AS rider_name
             FROM `orders` o
             INNER JOIN `customers` c ON c.`id` = o.`customer_id`
             INNER JOIN `foods` f ON f.`id` = o.`food_id`
             LEFT JOIN `delivery_assignments` da ON da.`order_id` = o.`id`
             LEFT JOIN `users` r ON r.`id` = da.`rider_user_id`
             WHERE o.`id` = :id
             LIMIT 1'
        );
        $invoiceStatement->execute(['id' => $invoiceOrderId]);
        $invoice = $invoiceStatement->fetch(PDO::FETCH_ASSOC);

        // Guard this block so it only runs when the required condition is met.
        if ($invoice !== false) {
            header('Content-Type: text/plain; charset=UTF-8');
            header('Content-Disposition: attachment; filename="afrisense-invoice-' . str_pad((string) $invoiceOrderId, 5, '0', STR_PAD_LEFT) . '.txt"');
            echo "AfriSense Delivery Invoice\n";
            echo "Order: #AFR" . str_pad((string) $invoiceOrderId, 4, '0', STR_PAD_LEFT) . "\n";
            echo "Customer: " . (string) $invoice['fullname'] . "\n";
            echo "Email: " . (string) $invoice['email'] . "\n";
            echo "Phone: " . (string) $invoice['phone_number'] . "\n";
            echo "Item: " . (string) $invoice['food_name'] . " x " . (int) $invoice['quantity'] . "\n";
            echo "Delivery Type: " . (string) $invoice['delivery_type'] . "\n";
            echo "Rider: " . (string) $invoice['rider_name'] . "\n";
            echo "Payment: " . (string) $invoice['payment_method'] . " / " . (string) $invoice['payment_status'] . "\n";
            echo "Total: " . afrisense_delivery_money((float) $invoice['total_price']) . "\n";
            echo "Collected: " . afrisense_delivery_money((float) $invoice['amount_collected']) . "\n";
            echo "Change: " . afrisense_delivery_money((float) $invoice['change_given']) . "\n";
            exit;
        }
    }

    $ridersStatement = $pdo->prepare(
        'SELECT u.`id`, u.`fullname`, u.`email`, u.`phonenumber`, r.`rolename`
         FROM `users` u
         INNER JOIN `roles` r ON r.`id` = u.`role_id`
         WHERE LOWER(r.`rolename`) <> "customer"
         ORDER BY
            CASE
                WHEN LOWER(r.`rolename`) IN ("delivery rider", "rider", "delivery") THEN 0
                WHEN LOWER(r.`rolename`) IN ("manager", "cashier") THEN 1
                ELSE 2
            END,
            u.`fullname` ASC'
    );
    $ridersStatement->execute();
    $riders = $ridersStatement->fetchAll(PDO::FETCH_ASSOC);

    // Handle submitted form actions before rendering the page.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['action'] ?? '');
        $orderId = (int) ($_POST['order_id'] ?? 0);

        $orderStatement = $pdo->prepare('SELECT * FROM `orders` WHERE `id` = :id LIMIT 1');
        $orderStatement->execute(['id' => $orderId]);
        $targetOrder = $orderStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        // Guard this block so it only runs when the required condition is met.
        if ($targetOrder === null) {
            $flashType = 'error';
            $flashMessage = 'Delivery order could not be found.';
        } elseif ($action === 'assign_delivery') {
            $riderId = (int) ($_POST['rider_user_id'] ?? 0);
            $deliveryType = (string) ($_POST['delivery_type'] ?? 'Standard Delivery');
            $notes = trim((string) ($_POST['notes'] ?? ''));

            // Guard this block so it only runs when the required condition is met.
            if ($riderId <= 0 || !in_array($deliveryType, $validDeliveryTypes, true)) {
                $flashType = 'error';
                $flashMessage = 'Choose a rider and valid delivery type.';
            } else {
                afrisense_delivery_assignment($pdo, $orderId, $riderId, 'Assigned', $deliveryType, 0.00, 0.00, $notes);
                // Guard this block so it only runs when the required condition is met.
                if (!in_array((string) $targetOrder['order_status'], ['Delivered', 'Cancelled'], true)) {
                    $update = $pdo->prepare('UPDATE `orders` SET `order_status` = :status, `updated_at` = NOW() WHERE `id` = :id');
                    $update->execute(['status' => 'Out for Delivery', 'id' => $orderId]);
                }
                afrisense_delivery_notify($pdo, $riderId, 'Delivery Assigned', 'Order #AFR' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT) . ' has been assigned to you.', '/Afrisense/frontend/admin/delivery-management.php?view=' . $orderId, $adminUserId);
                $flashMessage = 'Delivery assigned to rider.';
                $viewOrderId = $orderId;
            }
        } elseif ($action === 'update_delivery_status') {
            $nextStatus = (string) ($_POST['delivery_status'] ?? '');
            $riderId = (int) ($_POST['rider_user_id'] ?? 0);
            $amountCollected = max(0.00, (float) ($_POST['amount_collected'] ?? 0));
            $changeGiven = max(0.00, (float) ($_POST['change_given'] ?? 0));
            $deliveryType = (string) ($_POST['delivery_type'] ?? 'Standard Delivery');
            $notes = trim((string) ($_POST['notes'] ?? ''));

            // Guard this block so it only runs when the required condition is met.
            if (!in_array($nextStatus, $validDeliveryStatuses, true)) {
                $flashType = 'error';
                $flashMessage = 'Delivery status could not be updated.';
            } else {
                // Guard this block so it only runs when the required condition is met.
                if ($riderId <= 0) {
                    $riderLookup = $pdo->prepare('SELECT `rider_user_id` FROM `delivery_assignments` WHERE `order_id` = :order_id LIMIT 1');
                    $riderLookup->execute(['order_id' => $orderId]);
                    $riderId = (int) ($riderLookup->fetchColumn() ?: 0);
                }

                // Guard this block so it only runs when the required condition is met.
                if ($nextStatus === 'Delivered' && (string) $targetOrder['payment_method'] === 'Cash' && $amountCollected <= 0) {
                    $amountCollected = (float) $targetOrder['total_price'];
                }

                afrisense_delivery_assignment($pdo, $orderId, $riderId > 0 ? $riderId : null, $nextStatus, $deliveryType, $amountCollected, $changeGiven, $notes);
                $nextOrderStatus = afrisense_delivery_order_status_for_delivery($nextStatus, (string) $targetOrder['order_status']);
                $nextPaymentStatus = $nextStatus === 'Delivered' && (string) $targetOrder['payment_method'] === 'Cash' ? 'Paid' : (string) ($targetOrder['payment_status'] ?? 'Pending');
                $update = $pdo->prepare('UPDATE `orders` SET `order_status` = :order_status, `payment_status` = :payment_status, `updated_at` = NOW() WHERE `id` = :id');
                $update->execute(['order_status' => $nextOrderStatus, 'payment_status' => $nextPaymentStatus, 'id' => $orderId]);

                $customerUserId = afrisense_delivery_customer_user_id($pdo, $orderId);
                // Guard this block so it only runs when the required condition is met.
                if ($customerUserId !== null) {
                    afrisense_delivery_notify($pdo, $customerUserId, 'Delivery Status Updated', 'Your order #AFR' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT) . ' is now ' . $nextStatus . '.', '/Afrisense/frontend/customer/my-orders.php?view=' . $orderId . '#order-details', $adminUserId);
                }

                $flashMessage = 'Delivery status updated to ' . $nextStatus . '.';
                $viewOrderId = $orderId;
            }
        }
    }

    $statusExpression = afrisense_delivery_effective_status_sql();
    $where = [];
    $params = [];

    // Guard this block so it only runs when the required condition is met.
    if ($search !== '') {
        $where[] = '(CAST(o.`id` AS CHAR) LIKE :search OR c.`fullname` LIKE :search OR c.`phone_number` LIKE :search OR c.`email` LIKE :search OR o.`delivery_address` LIKE :search OR r.`fullname` LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }

    // Guard this block so it only runs when the required condition is met.
    if (in_array($statusFilter, $validDeliveryStatuses, true)) {
        $where[] = $statusExpression . ' = :status';
        $params['status'] = $statusFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if (in_array($paymentFilter, ['Cash', 'Mobile Money', 'Card'], true)) {
        $where[] = 'o.`payment_method` = :payment';
        $params['payment'] = $paymentFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($riderFilter > 0) {
        $where[] = 'da.`rider_user_id` = :rider_id';
        $params['rider_id'] = $riderFilter;
    }

    // Guard this block so it only runs when the required condition is met.
    if ($rangeFilter === 'today') {
        $where[] = 'DATE(o.`ordered_at`) = CURDATE()';
    } elseif ($rangeFilter === 'week') {
        $where[] = 'o.`ordered_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($rangeFilter === 'month') {
        $where[] = 'o.`ordered_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }

    $from = ' FROM `orders` o
        INNER JOIN `customers` c ON c.`id` = o.`customer_id`
        INNER JOIN `foods` f ON f.`id` = o.`food_id`
        LEFT JOIN `delivery_assignments` da ON da.`order_id` = o.`id`
        LEFT JOIN `users` r ON r.`id` = da.`rider_user_id`';
    $whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

    $countStatement = $pdo->prepare('SELECT COUNT(*) ' . $from . $whereSql);
    $countStatement->execute($params);
    $totalDeliveries = (int) $countStatement->fetchColumn();
    $totalPages = max(1, (int) ceil($totalDeliveries / max(1, $itemsPerPage)));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $itemsPerPage;

    $sql = 'SELECT
            o.`id`,
            o.`quantity`,
            o.`total_price`,
            o.`delivery_address`,
            o.`special_instructions`,
            o.`payment_method`,
            o.`payment_status`,
            o.`order_status`,
            o.`ordered_at`,
            c.`fullname`,
            c.`email`,
            c.`phone_number`,
            f.`food_name`,
            ' . $statusExpression . ' AS delivery_status,
            COALESCE(da.`delivery_type`, "Standard Delivery") AS delivery_type,
            COALESCE(da.`amount_collected`, 0) AS amount_collected,
            COALESCE(da.`change_given`, 0) AS change_given,
            da.`assigned_at`,
            da.`picked_up_at`,
            da.`delivered_at`,
            da.`notes`,
            da.`rider_user_id`,
            COALESCE(r.`fullname`, "Unassigned") AS rider_name,
            r.`phonenumber` AS rider_phone
        ' . $from . $whereSql . '
        ORDER BY o.`ordered_at` DESC, o.`id` DESC
        LIMIT ' . $itemsPerPage . ' OFFSET ' . $offset;
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $deliveries = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Guard this block so it only runs when the required condition is met.
    if ($viewOrderId <= 0 && $deliveries !== []) {
        $viewOrderId = (int) $deliveries[0]['id'];
    }

    $selectedDelivery = null;
    // Guard this block so it only runs when the required condition is met.
    if ($viewOrderId > 0) {
        $selectedStatement = $pdo->prepare(
            'SELECT
                o.`id`,
                o.`quantity`,
                o.`total_price`,
                o.`delivery_address`,
                o.`special_instructions`,
                o.`payment_method`,
                o.`payment_status`,
                o.`order_status`,
                o.`ordered_at`,
                c.`fullname`,
                c.`email`,
                c.`phone_number`,
                f.`food_name`,
                ' . $statusExpression . ' AS delivery_status,
                COALESCE(da.`delivery_type`, "Standard Delivery") AS delivery_type,
                COALESCE(da.`amount_collected`, 0) AS amount_collected,
                COALESCE(da.`change_given`, 0) AS change_given,
                da.`assigned_at`,
                da.`picked_up_at`,
                da.`delivered_at`,
                da.`notes`,
                da.`rider_user_id`,
                COALESCE(r.`fullname`, "Unassigned") AS rider_name,
                r.`phonenumber` AS rider_phone
             ' . $from . '
             WHERE o.`id` = :id
             LIMIT 1'
        );
        $selectedStatement->execute(['id' => $viewOrderId]);
        $selectedDelivery = $selectedStatement->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $metricRows = $pdo->query(
        'SELECT
            COUNT(*) AS total_count,
            SUM(CASE WHEN o.`order_status` = "Delivered" THEN 1 ELSE 0 END) AS delivered_count,
            SUM(CASE WHEN o.`order_status` = "Out for Delivery" THEN 1 ELSE 0 END) AS transit_count,
            SUM(CASE WHEN o.`payment_method` = "Cash" THEN o.`total_price` ELSE 0 END) AS cash_total,
            SUM(CASE WHEN o.`payment_method` = "Mobile Money" THEN o.`total_price` ELSE 0 END) AS momo_total,
            SUM(CASE WHEN o.`payment_method` = "Cash" THEN 1 ELSE 0 END) AS cash_orders,
            SUM(CASE WHEN o.`payment_method` = "Mobile Money" THEN 1 ELSE 0 END) AS momo_orders
         FROM `orders` o'
    )->fetch(PDO::FETCH_ASSOC) ?: [];
    $loadError = '';
} catch (Throwable $exception) {
    $riders = [];
    $deliveries = [];
    $selectedDelivery = null;
    $metricRows = [];
    $totalDeliveries = 0;
    $totalPages = 1;
    $loadError = 'Delivery management could not be loaded. Check that MySQL is running.';
}

$totalCount = (int) ($metricRows['total_count'] ?? 0);
$completedCount = (int) ($metricRows['delivered_count'] ?? 0);
$transitCount = (int) ($metricRows['transit_count'] ?? 0);
$cashTotal = (float) ($metricRows['cash_total'] ?? 0);
$momoTotal = (float) ($metricRows['momo_total'] ?? 0);
$cashOrders = (int) ($metricRows['cash_orders'] ?? 0);
$momoOrders = (int) ($metricRows['momo_orders'] ?? 0);
$selectedDeliveryStatus = (string) ($selectedDelivery['delivery_status'] ?? 'Pending');
$selectedRiderId = (int) ($selectedDelivery['rider_user_id'] ?? 0);
$selectedDeliveryType = (string) ($selectedDelivery['delivery_type'] ?? 'Standard Delivery');

ob_start();
?>
<!-- Page section for this part of the AfriSense interface. -->
<section class="af-admin-delivery-page">
    <!-- Header block for this interface section. -->
    <header class="af-admin-page-heading af-delivery-heading">
        <div>
            <h1>Food Delivery Management</h1>
            <p>Track, manage and monitor all food deliveries in real-time.</p>
        </div>
        <div class="af-delivery-heading-actions">
            <a class="af-delivery-light-btn" href="<?php echo htmlspecialchars(afrisense_delivery_url(['export' => 'csv']), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-download" aria-hidden="true"></i>
                Export
            </a>
            <a class="af-add-menu-btn" href="#delivery-assignment-panel">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Assign Delivery
            </a>
        </div>
    </header>

    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($loadError !== ''): ?>
        <div class="af-admin-alert error"><?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php // Render this conditional/dynamic template block. ?>
    <?php if ($flashMessage !== ''): ?>
        <div class="af-admin-alert <?php echo htmlspecialchars($flashType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-delivery-metrics" aria-label="Delivery summary">
        <article>
            <span class="green"><i class="bi bi-truck" aria-hidden="true"></i></span>
            <div><small>Total Deliveries</small><strong><?php echo htmlspecialchars((string) $totalCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>All order deliveries</p></div>
        </article>
        <article>
            <span class="gold"><i class="bi bi-check2-circle" aria-hidden="true"></i></span>
            <div><small>Completed</small><strong><?php echo htmlspecialchars((string) $completedCount, ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo $totalCount > 0 ? htmlspecialchars(number_format(($completedCount / $totalCount) * 100, 1), ENT_QUOTES, 'UTF-8') : '0.0'; ?>% of total</p></div>
        </article>
        <article>
            <span class="blue"><i class="bi bi-truck-front" aria-hidden="true"></i></span>
            <div><small>In Transit</small><strong><?php echo htmlspecialchars((string) $transitCount, ENT_QUOTES, 'UTF-8'); ?></strong><p>On the road</p></div>
        </article>
        <article>
            <span class="orange"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <div><small>Cash on Delivery</small><strong><?php echo htmlspecialchars(afrisense_delivery_money($cashTotal), ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars((string) $cashOrders, ENT_QUOTES, 'UTF-8'); ?> orders</p></div>
        </article>
        <article>
            <span class="purple"><i class="bi bi-phone" aria-hidden="true"></i></span>
            <div><small>MoMo on Delivery</small><strong><?php echo htmlspecialchars(afrisense_delivery_money($momoTotal), ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars((string) $momoOrders, ENT_QUOTES, 'UTF-8'); ?> orders</p></div>
        </article>
    </section>

    <!-- Page section for this part of the AfriSense interface. -->
    <section class="af-delivery-layout">
        <!-- Page section for this part of the AfriSense interface. -->
        <section class="af-delivery-main">
            <!-- Form block that submits this page workflow. -->
            <form class="af-delivery-filters" action="delivery-management.php" method="get">
                <label class="af-delivery-search">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by order ID, customer, phone...">
                </label>
                <label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($validDeliveryStatuses as $status): ?>
                            <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <select name="payment" onchange="this.form.submit()">
                        <option value="">All Payment Methods</option>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach (['Cash', 'Mobile Money', 'Card'] as $paymentMethod): ?>
                            <option value="<?php echo htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $paymentFilter === $paymentMethod ? 'selected' : ''; ?>><?php echo htmlspecialchars(afrisense_public_payment_method_label($paymentMethod), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <select name="rider" onchange="this.form.submit()">
                        <option value="">All Riders</option>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($riders as $rider): ?>
                            <option value="<?php echo (int) $rider['id']; ?>" <?php echo $riderFilter === (int) $rider['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $rider['fullname'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <select name="range" onchange="this.form.submit()">
                        <option value="">All Dates</option>
                        <option value="today" <?php echo $rangeFilter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $rangeFilter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $rangeFilter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    </select>
                </label>
                <button type="submit" title="Apply filters"><i class="bi bi-funnel" aria-hidden="true"></i> Filters</button>
            </form>

            <div class="af-delivery-table-wrap">
                <!-- Table block for displaying structured records. -->
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Rider</th>
                            <th>Status</th>
                            <th>Payment on Delivery</th>
                            <th>Amount</th>
                            <th>Delivery Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php if ($deliveries === []): ?>
                            <tr><td colspan="11"><div class="af-empty-state">No deliveries found.</div></td></tr>
                        <?php endif; ?>
                        <?php // Render this conditional/dynamic template block. ?>
                        <?php foreach ($deliveries as $index => $delivery): ?>
                            <?php
                            $deliveryId = (int) ($delivery['id'] ?? 0);
                            $deliveryStatus = (string) ($delivery['delivery_status'] ?? 'Pending');
                            $paymentMethod = (string) ($delivery['payment_method'] ?? 'Cash');
                            $deliveredAt = (string) ($delivery['delivered_at'] ?? '');
                            $orderedAt = strtotime((string) ($delivery['ordered_at'] ?? '')) ?: time();
                            $deliveryDetailUrl = afrisense_delivery_url(['view' => (string) $deliveryId], '#delivery-details');
                            ?>
                            <tr
                                class="af-clickable-delivery-row <?php echo $viewOrderId === $deliveryId ? 'is-selected' : ''; ?>"
                                id="delivery-row-<?php echo $deliveryId; ?>"
                                data-delivery-href="<?php echo htmlspecialchars($deliveryDetailUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                tabindex="0"
                                role="link"
                                title="Open delivery details and assign rider"
                                aria-label="Open delivery details for order AFR<?php echo htmlspecialchars(str_pad((string) $deliveryId, 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <td><?php echo htmlspecialchars((string) ($offset + $index + 1), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong>#AFR<?php echo htmlspecialchars(str_pad((string) $deliveryId, 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars((string) ($delivery['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($delivery['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($delivery['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($delivery['rider_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="af-delivery-status <?php echo htmlspecialchars(afrisense_delivery_status_class($deliveryStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($deliveryStatus, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><span class="af-delivery-payment <?php echo htmlspecialchars(afrisense_delivery_payment_class($paymentMethod), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($paymentMethod === 'Mobile Money' ? 'MoMo' : ($paymentMethod === 'Cash' ? 'Cash' : 'Card'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars(afrisense_delivery_money((float) ($delivery['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $deliveredAt !== '' ? htmlspecialchars(date('d M, h:i A', strtotime($deliveredAt)), ENT_QUOTES, 'UTF-8') : '<span class="af-muted">—</span>'; ?></td>
                                <td>
                                    <div class="af-row-actions af-delivery-row-actions">
                                        <a href="<?php echo htmlspecialchars($deliveryDetailUrl, ENT_QUOTES, 'UTF-8'); ?>" title="View delivery details" aria-label="View delivery details"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                        <!-- Form block that submits this page workflow. -->
                                        <form action="<?php echo htmlspecialchars(afrisense_delivery_url(['view' => (string) $deliveryId], '#delivery-details'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                                            <input type="hidden" name="action" value="update_delivery_status">
                                            <input type="hidden" name="order_id" value="<?php echo $deliveryId; ?>">
                                            <input type="hidden" name="rider_user_id" value="<?php echo (int) ($delivery['rider_user_id'] ?? 0); ?>">
                                            <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars((string) ($delivery['delivery_type'] ?? 'Standard Delivery'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="delivery_status" value="Delivered">
                                            <input type="hidden" name="amount_collected" value="<?php echo htmlspecialchars((string) ($delivery['total_price'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" title="Mark delivered" aria-label="Mark delivered"><i class="bi bi-check2-circle" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer block for this interface section. -->
            <footer class="af-delivery-pagination">
                <p>Showing <?php echo htmlspecialchars((string) ($totalDeliveries > 0 ? $offset + 1 : 0), ENT_QUOTES, 'UTF-8'); ?> to <?php echo htmlspecialchars((string) min($offset + count($deliveries), $totalDeliveries), ENT_QUOTES, 'UTF-8'); ?> of <?php echo htmlspecialchars((string) $totalDeliveries, ENT_QUOTES, 'UTF-8'); ?> deliveries</p>
                <!-- Navigation links for this interface. -->
                <nav aria-label="Delivery pagination">
                    <a class="<?php echo $page <= 1 ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page <= 1 ? '#' : afrisense_delivery_url(['page' => (string) ($page - 1)]), ENT_QUOTES, 'UTF-8'); ?>" title="Previous page" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php for ($number = max(1, $page - 1); $number <= min($totalPages, $page + 1); $number++): ?>
                        <a class="<?php echo $number === $page ? 'is-active' : ''; ?>" href="<?php echo htmlspecialchars(afrisense_delivery_url(['page' => (string) $number]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $number; ?></a>
                    <?php endfor; ?>
                    <?php // Render this conditional/dynamic template block. ?>
                    <?php if ($totalPages > $page + 1): ?><span>...</span><a href="<?php echo htmlspecialchars(afrisense_delivery_url(['page' => (string) $totalPages]), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $totalPages; ?></a><?php endif; ?>
                    <a class="<?php echo $page >= $totalPages ? 'is-disabled' : ''; ?>" href="<?php echo htmlspecialchars($page >= $totalPages ? '#' : afrisense_delivery_url(['page' => (string) ($page + 1)]), ENT_QUOTES, 'UTF-8'); ?>" title="Next page" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </nav>
            </footer>
        </section>

        <!-- Side panel with supporting information and actions. -->
        <aside class="af-delivery-details" id="delivery-details">
            <?php // Render this conditional/dynamic template block. ?>
            <?php if ($selectedDelivery === null): ?>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-delivery-side-card"><h2>Delivery Details</h2><p>Select a delivery to view details.</p></section>
            <?php else: ?>
                <?php
                $selectedOrderId = (int) ($selectedDelivery['id'] ?? 0);
                $subtotal = max(0.00, (float) ($selectedDelivery['total_price'] ?? 0) - afrisense_public_delivery_fee((float) ($selectedDelivery['total_price'] ?? 0), (string) ($selectedDelivery['delivery_address'] ?? '')));
                $deliveryFee = max(0.00, (float) ($selectedDelivery['total_price'] ?? 0) - $subtotal);
                ?>
                <!-- Page section for this part of the AfriSense interface. -->
                <section class="af-delivery-side-card">
                    <!-- Header block for this interface section. -->
                    <header>
                        <span><i class="bi bi-receipt" aria-hidden="true"></i></span>
                        <button type="button" title="Close details" onclick="window.location.href='delivery-management.php'"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </header>
                    <h2 class="af-delivery-details-title">Delivery Details</h2>
                    <h2>Order #AFR<?php echo htmlspecialchars(str_pad((string) $selectedOrderId, 4, '0', STR_PAD_LEFT), ENT_QUOTES, 'UTF-8'); ?> <span class="af-delivery-status <?php echo htmlspecialchars(afrisense_delivery_status_class($selectedDeliveryStatus), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($selectedDeliveryStatus, ENT_QUOTES, 'UTF-8'); ?></span></h2>
                    <time><?php echo htmlspecialchars(date('d M Y • h:i A', strtotime((string) ($selectedDelivery['ordered_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></time>

                    <!-- Navigation links for this interface. -->
                    <nav class="af-delivery-detail-tabs" aria-label="Delivery detail sections">
                        <a href="#delivery-overview">Overview</a>
                        <a href="#delivery-items">Items</a>
                        <a href="#delivery-timeline">Timeline</a>
                        <a href="#delivery-history">History</a>
                    </nav>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section id="delivery-overview">
                        <h3><i class="bi bi-person" aria-hidden="true"></i> Customer Information</h3>
                        <strong><?php echo htmlspecialchars((string) ($selectedDelivery['fullname'] ?? 'Customer'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><?php echo htmlspecialchars((string) ($selectedDelivery['phone_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><?php echo htmlspecialchars((string) ($selectedDelivery['delivery_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section id="delivery-assignment-panel">
                        <h3><i class="bi bi-truck" aria-hidden="true"></i> Delivery Information</h3>
                        <!-- Form block that submits this page workflow. -->
                        <form class="af-delivery-assign-form" action="<?php echo htmlspecialchars(afrisense_delivery_url(['view' => (string) $selectedOrderId], '#delivery-details'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                            <input type="hidden" name="action" value="assign_delivery">
                            <input type="hidden" name="order_id" value="<?php echo $selectedOrderId; ?>">
                            <label>
                                <span>Rider</span>
                                <select name="rider_user_id" required>
                                    <option value="">Choose rider</option>
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php foreach ($riders as $rider): ?>
                                        <option value="<?php echo (int) $rider['id']; ?>" <?php echo $selectedRiderId === (int) $rider['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string) $rider['fullname'] . ' • ' . (string) $rider['rolename'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Delivery Type</span>
                                <select name="delivery_type">
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php foreach ($validDeliveryTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedDeliveryType === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Notes</span>
                                <input type="text" name="notes" value="<?php echo htmlspecialchars((string) ($selectedDelivery['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Optional delivery note">
                            </label>
                            <button type="submit"><i class="bi bi-person-plus" aria-hidden="true"></i> Assign Rider</button>
                        </form>
                        <dl>
                            <div><dt>Current Rider</dt><dd><?php echo htmlspecialchars((string) ($selectedDelivery['rider_name'] ?? 'Unassigned'), ENT_QUOTES, 'UTF-8'); ?><small><?php echo htmlspecialchars((string) ($selectedDelivery['rider_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></dd></div>
                            <div><dt>Assigned At</dt><dd><?php echo !empty($selectedDelivery['assigned_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['assigned_at'])), ENT_QUOTES, 'UTF-8') : '—'; ?></dd></div>
                            <div><dt>Picked Up At</dt><dd><?php echo !empty($selectedDelivery['picked_up_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['picked_up_at'])), ENT_QUOTES, 'UTF-8') : '—'; ?></dd></div>
                            <div><dt>Delivered At</dt><dd><?php echo !empty($selectedDelivery['delivered_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['delivered_at'])), ENT_QUOTES, 'UTF-8') : '—'; ?></dd></div>
                        </dl>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section>
                        <h3><i class="bi bi-cash-coin" aria-hidden="true"></i> Payment on Delivery</h3>
                        <!-- Form block that submits this page workflow. -->
                        <form class="af-delivery-status-form" action="<?php echo htmlspecialchars(afrisense_delivery_url(['view' => (string) $selectedOrderId], '#delivery-details'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
                            <input type="hidden" name="action" value="update_delivery_status">
                            <input type="hidden" name="order_id" value="<?php echo $selectedOrderId; ?>">
                            <input type="hidden" name="rider_user_id" value="<?php echo $selectedRiderId; ?>">
                            <input type="hidden" name="delivery_type" value="<?php echo htmlspecialchars($selectedDeliveryType, ENT_QUOTES, 'UTF-8'); ?>">
                            <label>
                                <span>Status</span>
                                <select name="delivery_status">
                                    <?php // Render this conditional/dynamic template block. ?>
                                    <?php foreach ($validDeliveryStatuses as $status): ?>
                                        <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selectedDeliveryStatus === $status ? 'selected' : ''; ?>><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label><span>Amount Collected</span><input type="number" name="amount_collected" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format((float) ($selectedDelivery['amount_collected'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                            <label><span>Change Given</span><input type="number" name="change_given" min="0" step="0.01" value="<?php echo htmlspecialchars(number_format((float) ($selectedDelivery['change_given'] ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"></label>
                            <button type="submit"><i class="bi bi-save" aria-hidden="true"></i> Update Delivery</button>
                        </form>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section id="delivery-items">
                        <h3><i class="bi bi-basket" aria-hidden="true"></i> Order Summary</h3>
                        <dl>
                            <div><dt>Item</dt><dd><?php echo htmlspecialchars((string) ($selectedDelivery['food_name'] ?? 'Food item'), ENT_QUOTES, 'UTF-8'); ?> x <?php echo htmlspecialchars((string) ($selectedDelivery['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div><dt>Subtotal</dt><dd><?php echo htmlspecialchars(afrisense_delivery_money($subtotal), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div><dt>Delivery Fee</dt><dd><?php echo htmlspecialchars(afrisense_delivery_money($deliveryFee), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                            <div><dt>Total Amount</dt><dd><strong><?php echo htmlspecialchars(afrisense_delivery_money((float) ($selectedDelivery['total_price'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong></dd></div>
                            <div><dt>Payment</dt><dd><?php echo htmlspecialchars((string) ($selectedDelivery['payment_method'] ?? 'Cash'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($selectedDelivery['payment_status'] ?? 'Pending'), ENT_QUOTES, 'UTF-8'); ?></dd></div>
                        </dl>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section id="delivery-timeline">
                        <h3><i class="bi bi-clock-history" aria-hidden="true"></i> Timeline</h3>
                        <ul class="af-delivery-timeline">
                            <li class="is-done">Order placed <small><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime((string) ($selectedDelivery['ordered_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></small></li>
                            <li class="<?php echo !empty($selectedDelivery['assigned_at']) ? 'is-done' : ''; ?>">Assigned <small><?php echo !empty($selectedDelivery['assigned_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['assigned_at'])), ENT_QUOTES, 'UTF-8') : 'Pending'; ?></small></li>
                            <li class="<?php echo !empty($selectedDelivery['picked_up_at']) ? 'is-done' : ''; ?>">Picked up <small><?php echo !empty($selectedDelivery['picked_up_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['picked_up_at'])), ENT_QUOTES, 'UTF-8') : 'Pending'; ?></small></li>
                            <li class="<?php echo !empty($selectedDelivery['delivered_at']) ? 'is-done' : ''; ?>">Delivered <small><?php echo !empty($selectedDelivery['delivered_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime((string) $selectedDelivery['delivered_at'])), ENT_QUOTES, 'UTF-8') : 'Pending'; ?></small></li>
                        </ul>
                    </section>

                    <!-- Page section for this part of the AfriSense interface. -->
                    <section id="delivery-history">
                        <h3><i class="bi bi-journal-text" aria-hidden="true"></i> History</h3>
                        <p><?php echo trim((string) ($selectedDelivery['notes'] ?? '')) !== '' ? htmlspecialchars((string) $selectedDelivery['notes'], ENT_QUOTES, 'UTF-8') : 'No delivery notes recorded.'; ?></p>
                    </section>

                    <!-- Footer block for this interface section. -->
                    <footer>
                        <button type="button" onclick="window.print()" title="Print receipt"><i class="bi bi-printer" aria-hidden="true"></i> Print Receipt</button>
                        <a href="<?php echo htmlspecialchars(afrisense_delivery_url(['invoice' => (string) $selectedOrderId]), ENT_QUOTES, 'UTF-8'); ?>" title="Download invoice"><i class="bi bi-download" aria-hidden="true"></i> Download Invoice</a>
                    </footer>
                </section>
            <?php endif; ?>
        </aside>
    </section>
</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.af-clickable-delivery-row[data-delivery-href]').forEach((row) => {
        const openRow = () => {
            const href = row.getAttribute('data-delivery-href');
            if (href) {
                window.location.href = href;
            }
        };

        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, textarea, form')) {
                return;
            }

            openRow();
        });

        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openRow();
        });
    });
});
</script>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
