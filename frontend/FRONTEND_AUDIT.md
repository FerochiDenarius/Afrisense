# AfriSense Frontend Audit

## Current Navigation Model

- Public pages use `layouts/public_layout.php` and do not show a side menu.
- Customer pages use `layouts/customer_layout.php` and show `components/customer_sidebar.php` after login.
- Admin pages should use `layouts/admin_layout.php` and show `components/admin_sidebar.php` after login.
- `admin/dashboard.php` is still a standalone legacy dashboard, but it is now auth-protected and has a working logout link.

## Wired Auth Flow

- `auth/register.php` now creates a backend `users` account and a matching `customers` row.
- Registration creates a `Customer` role in the existing `roles` table if it does not already exist.
- `auth/login.php` now authenticates through the backend `Auth` model.
- Customer users are redirected to `customer/dashboard.php`.
- Staff/admin users are redirected to `admin/dashboard.php`.
- `auth/logout.php` destroys the session and redirects to login.

## Side Menu Status

- Customer side menu is available after customer login.
- Admin side menu is available on pages using `layouts/admin_layout.php`, such as `admin/foods.php`.
- Public pages like home, menu, services, booking, contact, order, and about intentionally do not show a side menu.

## Implemented Public Pages

- `landing/index.php`
- `landing/menu.php`
- `landing/services.php`
- `landing/booking.php`
- `landing/contact.php`
- `landing/order.php`
- `landing/payment.php`
- `landing/about.php`
- `landing/privacy.php`
- `landing/terms.php`
- `landing/enquiries.php`
- `landing/gallery.php`

## Implemented Auth Pages

- `auth/register.php`
- `auth/login.php`
- `auth/logout.php`
- `auth/forgot-password.php` exists but is not backend-wired.
- `auth/reset-password.php` exists but is not backend-wired.

## Implemented Customer Pages

- `customer/dashboard.php`
- `customer/my-orders.php` placeholder with protected customer layout.
- `customer/my-bookings.php` placeholder with protected customer layout.
- `customer/notifications.php` placeholder with protected customer layout.
- `customer/profile.php` placeholder with protected customer layout.

## Admin Pages

- `admin/foods.php` is implemented and uses the shared admin layout.
- `admin/dashboard.php` is implemented but still uses an older standalone layout.
- These admin files are currently empty and need implementation:
  - `admin/booking.php`
  - `admin/customers.php`
  - `admin/enquiries.php`
  - `admin/gallery.php`
  - `admin/notifications.php`
  - `admin/orders.php`
  - `admin/remarks.php`
  - `admin/reports.php`
  - `admin/roles.php`
  - `admin/services.php`
  - `admin/settings.php`
  - `admin/users.php`

## Main Gaps

- Customer pages need real backend data for orders, bookings, notifications, and profile editing.
- Admin pages need to be rebuilt on `layouts/admin_layout.php` for consistency.
- Password recovery forms need backend wiring.
- Public booking, contact, and enquiries forms are still frontend-only and need submit handlers.
- Order/payment flow is still mostly static and needs cart/session/database wiring.
- Admin dashboard should be migrated from its standalone markup to the shared admin layout.
