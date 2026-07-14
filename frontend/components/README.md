# AfriSense Frontend Components

All reusable frontend UI lives in `frontend/components`, shared layouts live in `frontend/layouts`, and shared CSS/JS lives in `frontend/assets`.

## Component Files

- `components/navbar.php` - public website header
- `components/footer.php` - public website footer
- `components/admin_sidebar.php` - admin dashboard sidebar
- `components/admin_header.php` - admin dashboard header
- `components/customer_sidebar.php` - customer dashboard sidebar
- `components/customer_header.php` - customer dashboard header
- `components/alerts.php` - reusable PHP alert helper and alert stack
- `components/modal.php` - reusable confirmation modal helper
- `components/loader.php` - shared loading overlay

Compatibility wrappers:

- `components/alert.php` loads `alerts.php`
- `components/sidebar.php` loads `admin_sidebar.php`

## Shared Assets

- `assets/css/main.css` imports the shared CSS system
- `assets/css/variables.css` contains AfriSense color, spacing, shadow and font variables
- `assets/css/components.css` contains reusable component styles
- `assets/css/responsive.css` contains shared responsive rules
- `assets/css/dashboard.css` remains available for dashboard page-specific content
- `assets/js/main.js` initializes shared UI helpers
- `assets/js/navbar.js` controls the public mobile navbar
- `assets/js/sidebar.js` controls admin/customer sidebar collapse and mobile slide-out
- `assets/js/modal.js` controls reusable modals
- `assets/js/alerts.js` controls dismissible alerts

## Layout Strategy

Future pages should use layouts instead of duplicating navbars, footers, sidebars or headers.

Public page example:

```php
<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Home | AfriSense';
$activePage = 'home';
$contentView = __DIR__ . '/home_content.php';
require __DIR__ . '/../layouts/public_layout.php';
```

Admin page example:

```php
<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'Dashboard | AfriSense';
$adminTitle = 'Dashboard';
$activeAdminPage = 'dashboard';
$contentView = __DIR__ . '/dashboard_content.php';
require __DIR__ . '/../layouts/admin_layout.php';
```

Customer page example:

```php
<?php
$frontendBase = '/Afrisense/frontend';
$pageTitle = 'My Orders | AfriSense';
$customerTitle = 'My Orders';
$activeCustomerPage = 'orders';
$contentView = __DIR__ . '/my_orders_content.php';
require __DIR__ . '/../layouts/customer_layout.php';
```

If a page is small, it can pass `$content` directly instead of `$contentView`.

## Active States

Public navbar active keys:

- `home`
- `menu`
- `catering`
- `booking`
- `about`
- `contact`

Admin sidebar active keys:

- `dashboard`
- `orders`
- `bookings`
- `enquiries`
- `menu`
- `customers`
- `staff`
- `roles`
- `permissions`
- `settings`
- `emails`
- `analytics`
- `reports`

Customer sidebar active keys:

- `dashboard`
- `orders`
- `bookings`
- `wishlist`
- `notifications`
- `profile`
- `settings`
