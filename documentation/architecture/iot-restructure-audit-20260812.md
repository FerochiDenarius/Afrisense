# AfriSense IoT Restructure Audit - 2026-08-12

## Purpose

AfriSense is changing from a restaurant-first project into a broader technology/services platform. The existing restaurant and food-service system must continue working while the application is prepared for Smart Agriculture / IoT.

This document records Stage 1 and Stage 2 of the restructure:

1. Audit the current application before moving files.
2. Propose the move map that should be reviewed before large structural changes.

No restaurant files have been moved as part of this audit.

## Current Folder Structure Summary

Important current folders:

- `backend/config`: database, environment, and mail configuration.
- `backend/controllers`: shared API-style controllers for auth, dashboard, notifications, permissions, roles, settings, and users.
- `backend/models`: shared models for auth, users, roles, permissions, notifications, dashboard, audit logs, settings, and base model.
- `backend/helpers`: response, security, session, upload, and validation helpers.
- `backend/middleware`: auth, admin, and permission middleware.
- `backend/routes`: API and web routing for the backend/public area.
- `frontend/landing`: current public restaurant-focused pages.
- `frontend/customer`: logged-in customer restaurant/account pages.
- `frontend/admin`: admin pages for restaurant operations plus shared administration.
- `frontend/auth`: shared registration, login, logout, password reset, and email verification.
- `frontend/components`: shared navbar, footer, admin sidebar/header, customer sidebar/header, alerts, modal, loader.
- `frontend/includes`: shared public settings, theme, support, and remarks helpers.
- `frontend/layouts`: public, admin, and customer layout wrappers.
- `frontend/assets`: global CSS, JS, images, audio, uploads, and food images.
- `frontend/support`: live support endpoint.
- `sql`: main AfriSense database schema.
- `documentation`: project documentation.

The current project has no root `index.php`. The active public entry is `frontend/landing/index.php`.

## Current Database Design Surface

Existing restaurant/shared database tables in `sql/afrisense_db_schema.sql`:

- Shared/core: `users`, `roles`, `permissions`, `role_permissions`, `notifications`, `audit_logs`, `system_settings`, `website_settings`, `company_information`, `newsletter_subscribers`.
- Restaurant/customer: `customers`, `foods`, `food_categories`, `orders`, `bookings`, `services`, `service_categories`, `delivery_assignments`, `gallery`, `customer_remarks`, `testimonials`, `enquiries`.
- Support: `support_conversations`, `support_messages`, `support_attachments`.
- Content: `about_us`.

Planned agriculture tables are not present and should not be created yet:

- `farms`
- `device_categories`
- `devices`
- `sensor_readings`
- `alerts`
- `device_logs`

## File Categorization

### A. Shared Core

Keep these shared. Do not move into restaurant or agriculture modules:

- `backend/config/database.php`
- `backend/config/env.php`
- `backend/config/mail.php`
- `backend/helpers/Response.php`
- `backend/helpers/Security.php`
- `backend/helpers/Session.php`
- `backend/helpers/Upload.php`
- `backend/helpers/Validator.php`
- `backend/middleware/AdminMiddleware.php`
- `backend/middleware/AuthMiddleware.php`
- `backend/middleware/PermissionMiddleware.php`
- `backend/models/BaseModel.php`
- `backend/models/Auth.php`
- `backend/models/User.php`
- `backend/models/Role.php`
- `backend/models/Permission.php`
- `backend/models/Notification.php`
- `backend/models/AuditLog.php`
- `backend/models/SystemSettings.php`
- `backend/models/Dashboard.php`
- `backend/controllers/AuthController.php`
- `backend/controllers/DashboardController.php`
- `backend/controllers/NotificationController.php`
- `backend/controllers/PermissionController.php`
- `backend/controllers/RoleController.php`
- `backend/controllers/SettingsController.php`
- `backend/controllers/UserController.php`
- `frontend/auth/*`
- `frontend/includes/public_settings.php`
- `frontend/includes/theme.php`
- `frontend/includes/support_helpers.php`
- `frontend/includes/support_view.php`
- `frontend/layouts/*`
- `frontend/components/alerts.php`
- `frontend/components/alert.php`
- `frontend/components/modal.php`
- `frontend/components/loader.php`
- `frontend/assets/css/main.css`
- `frontend/assets/css/components.css`
- `frontend/assets/css/variables.css`
- `frontend/assets/js/main.js`
- `frontend/assets/js/alerts.js`
- `frontend/assets/js/modal.js`
- `frontend/assets/js/navbar.js`
- `frontend/assets/js/sidebar.js`
- `frontend/assets/js/support.js`
- `frontend/assets/audio/*`

### B. Restaurant Module

These are restaurant/food-service focused and are candidates for the restaurant module:

- `frontend/landing/menu.php`
- `frontend/landing/order.php`
- `frontend/landing/cart.php`
- `frontend/landing/checkout.php`
- `frontend/landing/payment.php`
- `frontend/landing/booking.php`
- `frontend/landing/services.php`
- `frontend/landing/gallery.php`
- `frontend/landing/remarks.php`
- `frontend/landing/enquiries.php`
- `frontend/landing/enquiry_helpers.php`
- `frontend/customer/orders.php`
- `frontend/customer/cart.php`
- `frontend/customer/payment.php`
- `frontend/customer/my-orders.php`
- `frontend/customer/my-bookings.php`
- `frontend/customer/enquiries.php`
- `frontend/customer/remarks.php`
- `frontend/assets/css/menu-services.css`
- `frontend/assets/css/order-payment.css`
- `frontend/assets/css/orders.css`
- `frontend/assets/css/booking-contact.css`
- `frontend/assets/css/customer-bookings.css`
- `frontend/assets/css/customer-my-orders.css`
- `frontend/assets/css/public-gallery.css`
- `frontend/assets/css/public-remarks.css`
- `frontend/assets/css/enquiries.css`
- `frontend/assets/js/order.js`
- `frontend/assets/js/orders.js`
- `frontend/assets/js/payment-method.js`
- `frontend/assets/js/booking-contact.js`
- `frontend/assets/js/enquiries.js`
- `frontend/assets/js/gallery.js`
- `frontend/assets/images/foods/*`
- `frontend/uploads/foods/*`

### C. Public / Landing

These should remain or become corporate platform landing pages:

- `frontend/landing/index.php`
- `frontend/landing/about.php`
- `frontend/landing/contact.php`
- `frontend/landing/privacy.php`
- `frontend/landing/terms.php`
- `frontend/components/navbar.php`
- `frontend/components/footer.php`
- `frontend/layouts/public_layout.php`
- `frontend/assets/css/index.css`
- `frontend/assets/js/index.js`

New public pages to prepare:

- `frontend/landing/solutions.php`
- `frontend/landing/smart-agriculture.php`
- `frontend/landing/restaurant.php`

### D. Admin

Shared admin pages:

- `frontend/admin/dashboard.php`
- `frontend/admin/users.php`
- `frontend/admin/roles.php`
- `frontend/admin/permissions.php`
- `frontend/admin/notifications.php`
- `frontend/admin/settings.php`
- `frontend/admin/settings/*`
- `frontend/admin/support.php`
- `frontend/admin/reports.php`
- `frontend/components/admin_header.php`
- `frontend/components/admin_sidebar.php`
- `frontend/layouts/admin_layout.php`

Restaurant admin pages:

- `frontend/admin/orders.php`
- `frontend/admin/new-order.php`
- `frontend/admin/bookings.php`
- `frontend/admin/booking.php`
- `frontend/admin/delivery-management.php`
- `frontend/admin/foods.php`
- `frontend/admin/gallery.php`
- `frontend/admin/services.php`
- `frontend/admin/customers.php`
- `frontend/admin/enquiries.php`
- `frontend/admin/remarks.php`

### E. Authentication

Authentication must remain shared:

- `frontend/auth/auth_bootstrap.php`
- `frontend/auth/login.php`
- `frontend/auth/register.php`
- `frontend/auth/logout.php`
- `frontend/auth/forgot-password.php`
- `frontend/auth/reset-password.php`
- `frontend/auth/verify-email.php`

The same `users`, `roles`, `permissions`, and `role_permissions` tables should support restaurant, agriculture, admin, support agents, and delivery riders.

### F. Smart Agriculture / New Module

These folders/pages do not exist yet and should be prepared as placeholders only:

- `frontend/agriculture/dashboard.php`
- `frontend/agriculture/farms/index.php`
- `frontend/agriculture/farms/create.php`
- `frontend/agriculture/farms/edit.php`
- `frontend/agriculture/farms/view.php`
- `frontend/agriculture/devices/index.php`
- `frontend/agriculture/devices/create.php`
- `frontend/agriculture/devices/view.php`
- `frontend/agriculture/monitoring/index.php`
- `frontend/agriculture/alerts/index.php`
- `backend/controllers/agriculture/`
- `backend/models/agriculture/`
- `backend/services/agriculture/`
- `backend/api/agriculture/`
- `frontend/admin/agriculture/`

Do not create agriculture database tables or backend logic until Smart Agriculture Phase 1 starts.

### G. Unknown / Needs Review

These appear legacy or duplicate and should be reviewed before moving:

- `classes/Auth.php`
- `classes/user.php`
- `config/config.php`
- `config/constants.php`
- `config/database.php`
- `models/AuditLog.php`
- `assets/`
- `includes/`
- `uploads/`
- `database/`
- `backend/public/*`
- `backend/routes/*`
- `test_connection.php`

## Hard-Coded Path Risks

The application currently has many hard-coded references to:

- `/Afrisense/frontend`
- `/Afrisense/frontend/landing`
- `/Afrisense/frontend/customer`
- `/Afrisense/frontend/admin`
- `/Afrisense/frontend/auth`

High-risk files include:

- `frontend/auth/auth_bootstrap.php`
- `frontend/includes/public_settings.php`
- `frontend/includes/support_helpers.php`
- `frontend/includes/remarks_helpers.php`
- `frontend/components/navbar.php`
- `frontend/components/footer.php`
- `frontend/components/admin_sidebar.php`
- `frontend/components/customer_sidebar.php`
- `frontend/layouts/admin_layout.php`
- `frontend/layouts/customer_layout.php`
- `frontend/layouts/public_layout.php`
- `frontend/admin/orders.php`
- `frontend/admin/bookings.php`
- `frontend/admin/delivery-management.php`
- `frontend/admin/notifications.php`
- `frontend/customer/orders.php`
- `frontend/customer/payment.php`
- `frontend/customer/my-orders.php`
- `frontend/customer/my-bookings.php`
- `frontend/customer/notifications.php`
- `frontend/landing/order.php`
- `frontend/landing/payment.php`
- `frontend/landing/booking.php`

Before moving pages, introduce or strengthen central URL helpers for:

- frontend base URL
- public landing URLs
- restaurant public URLs
- restaurant customer URLs
- admin shared URLs
- admin restaurant URLs
- auth URLs
- support live endpoint URL
- upload/image URLs

## Existing API / AJAX Endpoints

Current live support endpoint:

- `frontend/support/live.php`

Current JS that depends on that endpoint:

- `frontend/assets/js/support.js`

The support JS uses `data-support-live-url`, `fetch(...)`, and redirects to `support.php?view=...`. If support pages are moved, the data attribute and redirect logic must be made module-aware.

Backend API routes exist in:

- `backend/routes/api.php`
- `backend/routes/web.php`
- `backend/public/index.php`

The restaurant pages currently do not use a clean backend restaurant API. Most restaurant actions are handled inside page-level PHP scripts.

## Proposed Target Folder Additions

Create these folders before moving files:

- `frontend/restaurant/landing/`
- `frontend/restaurant/customer/`
- `frontend/restaurant/components/`
- `frontend/agriculture/farms/`
- `frontend/agriculture/devices/`
- `frontend/agriculture/monitoring/`
- `frontend/agriculture/alerts/`
- `frontend/admin/shared/`
- `frontend/admin/restaurant/`
- `frontend/admin/agriculture/`
- `backend/controllers/shared/`
- `backend/controllers/restaurant/`
- `backend/controllers/agriculture/`
- `backend/models/shared/`
- `backend/models/restaurant/`
- `backend/models/agriculture/`
- `backend/services/shared/`
- `backend/services/restaurant/`
- `backend/services/agriculture/`
- `backend/api/restaurant/`
- `backend/api/agriculture/`
- `documentation/architecture/`
- `documentation/database/`
- `documentation/screenshots/`
- `documentation/research/`
- `tests/`

## Proposed Move Map

### Public Restaurant Pages

| Current File | Proposed New Location |
| --- | --- |
| `frontend/landing/menu.php` | `frontend/restaurant/landing/menu.php` |
| `frontend/landing/order.php` | `frontend/restaurant/landing/order.php` |
| `frontend/landing/cart.php` | `frontend/restaurant/landing/cart.php` |
| `frontend/landing/checkout.php` | `frontend/restaurant/landing/checkout.php` |
| `frontend/landing/payment.php` | `frontend/restaurant/landing/payment.php` |
| `frontend/landing/booking.php` | `frontend/restaurant/landing/booking.php` |
| `frontend/landing/services.php` | `frontend/restaurant/landing/services.php` |
| `frontend/landing/gallery.php` | `frontend/restaurant/landing/gallery.php` |
| `frontend/landing/remarks.php` | `frontend/restaurant/landing/remarks.php` |
| `frontend/landing/enquiries.php` | `frontend/restaurant/landing/enquiries.php` |
| `frontend/landing/enquiry_helpers.php` | `frontend/restaurant/landing/enquiry_helpers.php` |

### Customer Restaurant Pages

| Current File | Proposed New Location |
| --- | --- |
| `frontend/customer/orders.php` | `frontend/restaurant/customer/orders.php` |
| `frontend/customer/cart.php` | `frontend/restaurant/customer/cart.php` |
| `frontend/customer/payment.php` | `frontend/restaurant/customer/payment.php` |
| `frontend/customer/my-orders.php` | `frontend/restaurant/customer/my-orders.php` |
| `frontend/customer/my-bookings.php` | `frontend/restaurant/customer/my-bookings.php` |
| `frontend/customer/enquiries.php` | `frontend/restaurant/customer/enquiries.php` |
| `frontend/customer/remarks.php` | `frontend/restaurant/customer/remarks.php` |

Keep these customer files shared for now:

| Current File | Reason |
| --- | --- |
| `frontend/customer/dashboard.php` | Will become account/service dashboard, not only restaurant. |
| `frontend/customer/notifications.php` | Shared notifications across modules. |
| `frontend/customer/profile.php` | Shared user/customer profile. |
| `frontend/customer/settings.php` | Shared account settings. |
| `frontend/customer/support.php` | Shared support chat across modules. |

### Restaurant Admin Pages

| Current File | Proposed New Location |
| --- | --- |
| `frontend/admin/orders.php` | `frontend/admin/restaurant/orders.php` |
| `frontend/admin/new-order.php` | `frontend/admin/restaurant/new-order.php` |
| `frontend/admin/bookings.php` | `frontend/admin/restaurant/bookings.php` |
| `frontend/admin/booking.php` | `frontend/admin/restaurant/booking.php` |
| `frontend/admin/delivery-management.php` | `frontend/admin/restaurant/delivery-management.php` |
| `frontend/admin/foods.php` | `frontend/admin/restaurant/foods.php` |
| `frontend/admin/gallery.php` | `frontend/admin/restaurant/gallery.php` |
| `frontend/admin/services.php` | `frontend/admin/restaurant/services.php` |
| `frontend/admin/customers.php` | `frontend/admin/restaurant/customers.php` initially, but may later become shared CRM. |
| `frontend/admin/enquiries.php` | `frontend/admin/restaurant/enquiries.php` initially, but may later become shared enquiries. |
| `frontend/admin/remarks.php` | `frontend/admin/restaurant/remarks.php` |

### Shared Admin Pages

| Current File | Proposed New Location |
| --- | --- |
| `frontend/admin/dashboard.php` | Keep as `frontend/admin/dashboard.php`, or later move to `frontend/admin/shared/dashboard.php` with compatibility wrapper. |
| `frontend/admin/users.php` | `frontend/admin/shared/users.php` |
| `frontend/admin/roles.php` | `frontend/admin/shared/roles.php` |
| `frontend/admin/permissions.php` | `frontend/admin/shared/permissions.php` |
| `frontend/admin/notifications.php` | `frontend/admin/shared/notifications.php` |
| `frontend/admin/support.php` | `frontend/admin/shared/support.php` |
| `frontend/admin/reports.php` | `frontend/admin/shared/reports.php` initially; later split by module. |
| `frontend/admin/settings.php` | `frontend/admin/shared/settings.php` or keep compatibility wrapper. |
| `frontend/admin/settings/*` | `frontend/admin/shared/settings/*` only after links are updated. |

### Public Corporate Pages

| Current File | Proposed Action |
| --- | --- |
| `frontend/landing/index.php` | Rewrite into corporate technology platform homepage. |
| `frontend/landing/about.php` | Update wording from restaurant-only to platform/company. |
| `frontend/landing/contact.php` | Keep shared contact page. |
| `frontend/landing/privacy.php` | Keep shared legal page. |
| `frontend/landing/terms.php` | Keep shared legal page. |
| `frontend/landing/solutions.php` | Create new corporate solutions page. |
| `frontend/landing/smart-agriculture.php` | Create new Smart Agriculture informational page. |
| `frontend/landing/restaurant.php` | Create entry page that routes to restaurant services/module. |

### Backend

At this stage, do not move existing backend controllers/models unless route bootstrapping is refactored first. They are shared and currently required by exact `__DIR__` paths.

Recommended backend organization after a safer compatibility layer exists:

| Current File | Proposed New Location |
| --- | --- |
| `backend/controllers/AuthController.php` | `backend/controllers/shared/AuthController.php` |
| `backend/controllers/DashboardController.php` | `backend/controllers/shared/DashboardController.php` |
| `backend/controllers/NotificationController.php` | `backend/controllers/shared/NotificationController.php` |
| `backend/controllers/PermissionController.php` | `backend/controllers/shared/PermissionController.php` |
| `backend/controllers/RoleController.php` | `backend/controllers/shared/RoleController.php` |
| `backend/controllers/SettingsController.php` | `backend/controllers/shared/SettingsController.php` |
| `backend/controllers/UserController.php` | `backend/controllers/shared/UserController.php` |
| `backend/models/Auth.php` | `backend/models/shared/Auth.php` later only if autoloading is introduced. |
| `backend/models/User.php` | `backend/models/shared/User.php` later only if autoloading is introduced. |
| `backend/models/Role.php` | `backend/models/shared/Role.php` later only if autoloading is introduced. |
| `backend/models/Permission.php` | `backend/models/shared/Permission.php` later only if autoloading is introduced. |
| `backend/models/Notification.php` | `backend/models/shared/Notification.php` later only if autoloading is introduced. |
| `backend/models/AuditLog.php` | `backend/models/shared/AuditLog.php` later only if autoloading is introduced. |
| `backend/models/SystemSettings.php` | `backend/models/shared/SystemSettings.php` later only if autoloading is introduced. |

Restaurant backend models/controllers do not currently exist as separate classes. Creating them should be a later refactor after the page move is stable.

## Compatibility Strategy Before Moving

To avoid breaking existing links, use compatibility wrappers:

1. Move the real page into the new module path.
2. Leave a small file at the old path.
3. The old file should `require` the new file or redirect safely.
4. Update shared navigation to point to the new module paths.
5. Update notification `action_url` values gradually.

Example:

- Real file: `frontend/restaurant/landing/order.php`
- Compatibility wrapper: `frontend/landing/order.php`

This allows old links, emails, notifications, bookmarks, and form redirects to keep working during the transition.

## Recommended Execution Order

1. Add central route helpers to `frontend/includes/public_settings.php`.
2. Create destination folders.
3. Create `frontend/landing/smart-agriculture.php`, `frontend/landing/solutions.php`, and `frontend/landing/restaurant.php`.
4. Rewrite `frontend/landing/index.php` into the new corporate/IoT landing page.
5. Update `frontend/components/navbar.php` and `frontend/components/footer.php` for platform navigation.
6. Prepare empty agriculture placeholder pages.
7. Move public restaurant landing pages one group at a time and leave wrappers.
8. Move customer restaurant pages one group at a time and leave wrappers.
9. Move restaurant admin pages one group at a time and leave wrappers.
10. Update admin/customer sidebars to point to new locations.
11. Run PHP syntax checks on all touched files.
12. Run browser/manual regression for homepage, restaurant pages, auth, cart, orders, bookings, support, admin, settings, notifications, CSS, JS, and images.

## Architectural Issues Discovered

1. The project has no root `index.php`, so `/Afrisense/` does not currently act as the platform homepage.
2. Restaurant SQL logic is mostly inside frontend PHP pages, not backend restaurant models/controllers.
3. Hard-coded `/Afrisense/frontend` paths are widespread.
4. Existing settings seed text still brands the platform as `AfriSense Food Services`.
5. `frontend/components/navbar.php` and `frontend/components/footer.php` are restaurant-focused.
6. `frontend/components/admin_sidebar.php` is mostly restaurant/admin mixed; it should be grouped into Platform, Restaurant, Agriculture, Administration, and Reports.
7. Support is shared, but some labels and redirects assume the current page path.
8. The old `classes/`, `config/`, `models/`, `assets/`, `includes/`, `uploads/`, and `database/` folders may be legacy duplicates and need a separate cleanup audit.

## Checkpoint Recommendation

Do not move files until this move map is approved. The safest next implementation stage is:

SMART PLATFORM RESTRUCTURE PHASE 1:

- Add route/base URL helpers.
- Create new folders.
- Add corporate platform pages.
- Add agriculture placeholders.
- Leave old restaurant paths working with wrappers.

After that, begin:

SMART AGRICULTURE PHASE 1:

- Database design for farms.
- Farm model.
- Farm controller.
- Register Farm.
- View Farms.
- Edit Farm.
- Farm Details.
