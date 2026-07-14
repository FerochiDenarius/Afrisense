# AfriSense Backend Architecture

## Dependency Diagram

```text
public/index.php
  -> config/database.php
  -> routes/api.php or routes/web.php

public/login.php
  -> config/database.php
  -> controllers/AuthController.php
  -> helpers/Security.php

public/logout.php
  -> config/database.php
  -> controllers/AuthController.php

public/dashboard.php
  -> config/database.php
  -> middleware/AuthMiddleware.php
  -> controllers/DashboardController.php
  -> helpers/Security.php

routes/api.php
  -> helpers/Response.php
  -> controllers/*
  -> middleware/AuthMiddleware.php
  -> middleware/AdminMiddleware.php

routes/web.php
  -> helpers/Response.php
  -> public/login.php or public/dashboard.php

controllers/AuthController.php
  -> models/Auth.php
  -> helpers/Response.php
  -> helpers/Validator.php

controllers/UserController.php
  -> models/User.php
  -> helpers/Response.php
  -> helpers/Validator.php

controllers/RoleController.php
  -> models/Role.php
  -> helpers/Response.php
  -> helpers/Validator.php

controllers/PermissionController.php
  -> models/Permission.php
  -> helpers/Response.php
  -> helpers/Validator.php

controllers/NotificationController.php
  -> models/Notification.php
  -> helpers/Response.php

controllers/SettingsController.php
  -> models/SystemSettings.php
  -> helpers/Response.php

controllers/DashboardController.php
  -> models/Dashboard.php
  -> helpers/Response.php

middleware/AuthMiddleware.php
  -> models/Auth.php
  -> helpers/Response.php

middleware/AdminMiddleware.php
  -> middleware/AuthMiddleware.php
  -> models/User.php
  -> helpers/Response.php

middleware/PermissionMiddleware.php
  -> middleware/AuthMiddleware.php
  -> models/User.php
  -> helpers/Response.php

models/Auth.php
  -> models/User.php
  -> models/AuditLog.php
  -> helpers/Response.php
  -> helpers/Security.php
  -> helpers/Session.php

models/Dashboard.php
  -> models/BaseModel.php
  -> models/AuditLog.php
  -> models/Notification.php

models/User.php
  -> models/BaseModel.php
  -> helpers/Security.php

models/AuditLog.php
  -> models/BaseModel.php
  -> helpers/Security.php

models/Role.php, Permission.php, Notification.php, SystemSettings.php
  -> models/BaseModel.php

helpers/Security.php
  -> helpers/Session.php
```

## Backend File Checklist

- [x] config/database.php
- [x] models/BaseModel.php
- [x] models/User.php
- [x] models/Auth.php
- [x] models/AuditLog.php
- [x] models/Role.php
- [x] models/Permission.php
- [x] models/Notification.php
- [x] models/SystemSettings.php
- [x] models/Dashboard.php
- [x] helpers/Validator.php
- [x] helpers/Response.php
- [x] helpers/Session.php
- [x] helpers/Security.php
- [x] helpers/Upload.php
- [x] controllers/AuthController.php
- [x] controllers/UserController.php
- [x] controllers/RoleController.php
- [x] controllers/PermissionController.php
- [x] controllers/NotificationController.php
- [x] controllers/SettingsController.php
- [x] controllers/DashboardController.php
- [x] middleware/AuthMiddleware.php
- [x] middleware/AdminMiddleware.php
- [x] middleware/PermissionMiddleware.php
- [x] routes/web.php
- [x] routes/api.php
- [x] uploads/
- [x] logs/
- [x] public/index.php
- [x] public/login.php
- [x] public/logout.php
- [x] public/dashboard.php

## Public Methods

### Config

- Database: `__construct()`, `getConnection()`

### Models

- BaseModel: `__construct()`
- User: `__construct()`, `findById()`, `findByEmail()`, `getUserByEmail()`, `all()`, `create()`, `update()`, `delete()`, `updateLastLogin()`, `hasPermission()`, `getRole()`
- Auth: `__construct()`, `login()`, `logout()`, `isLoggedIn()`, `getCurrentUser()`, `requireLogin()`
- AuditLog: `__construct()`, `create()`, `findById()`, `all()`, `deleteOlderThan()`
- Role: `__construct()`, `all()`, `findById()`, `findByName()`, `create()`, `update()`, `delete()`, `assignPermission()`, `removePermission()`, `getPermissions()`
- Permission: `__construct()`, `all()`, `findById()`, `findByName()`, `create()`, `update()`, `delete()`
- Notification: `__construct()`, `allForUser()`, `create()`, `markAsRead()`, `markAllAsRead()`, `delete()`, `unreadCount()`
- SystemSettings: `__construct()`, `all()`, `get()`, `set()`, `delete()`
- Dashboard: `__construct()`, `summary()`, `countTable()`

### Helpers

- Validator: `validate()`, `getErrors()`
- Response: `success()`, `error()`, `json()`
- Session: `start()`, `set()`, `get()`, `has()`, `remove()`, `regenerate()`, `destroy()`
- Security: `hashPassword()`, `verifyPassword()`, `sanitizeString()`, `generateCsrfToken()`, `validateCsrfToken()`, `currentIp()`, `userAgent()`
- Upload: `moveUploadedFile()`, `delete()`

### Controllers

- AuthController: `__construct()`, `login()`, `logout()`, `currentUser()`
- UserController: `__construct()`, `index()`, `show()`, `store()`, `update()`, `destroy()`
- RoleController: `__construct()`, `index()`, `show()`, `store()`, `update()`, `destroy()`, `assignPermission()`, `removePermission()`
- PermissionController: `__construct()`, `index()`, `show()`, `store()`, `update()`, `destroy()`
- NotificationController: `__construct()`, `index()`, `store()`, `markAsRead()`, `markAllAsRead()`, `destroy()`
- SettingsController: `__construct()`, `index()`, `show()`, `update()`, `destroy()`
- DashboardController: `__construct()`, `index()`

### Middleware

- AuthMiddleware: `__construct()`, `handle()`
- AdminMiddleware: `__construct()`, `handle()`
- PermissionMiddleware: `__construct()`, `handle()`

## File Responsibilities

- config/database.php: Creates a secure PDO connection factory with exception mode and prepared statement support.
- models/BaseModel.php: Provides shared prepared-query, schema-introspection, insert, update, delete, and fetch helpers for models.
- models/User.php: Owns user persistence, password hashing on writes, login timestamp updates, role lookup, and permission checks.
- models/Auth.php: Owns authentication state, login, logout, current-user lookup, session regeneration, and audit logging.
- models/AuditLog.php: Writes and reads audit events with request IP and user agent metadata.
- models/Role.php: Manages roles and role-permission assignments.
- models/Permission.php: Manages permission records.
- models/Notification.php: Manages user notifications, unread counts, read state, and deletion.
- models/SystemSettings.php: Reads, writes, and clears settings using the existing row-based `system_settings` columns.
- models/Dashboard.php: Aggregates counts and recent audit logs for dashboard views.
- helpers/Validator.php: Validates request data using required, email, password, phone, minLength, and maxLength rules.
- helpers/Response.php: Standardizes success, error, and JSON HTTP responses.
- helpers/Session.php: Starts, reads, writes, regenerates, and securely destroys PHP sessions.
- helpers/Security.php: Handles password verification, CSRF tokens, safe output encoding, IP detection, and user-agent capture.
- helpers/Upload.php: Validates, stores, and deletes uploaded files.
- controllers/AuthController.php: Coordinates authentication requests and responses.
- controllers/UserController.php: Coordinates user CRUD requests and strips password hashes from responses.
- controllers/RoleController.php: Coordinates role CRUD and role-permission assignment requests.
- controllers/PermissionController.php: Coordinates permission CRUD requests.
- controllers/NotificationController.php: Coordinates notification listing, creation, read-state updates, and deletion.
- controllers/SettingsController.php: Coordinates system settings requests.
- controllers/DashboardController.php: Coordinates dashboard summary requests.
- middleware/AuthMiddleware.php: Requires an authenticated session before continuing.
- middleware/AdminMiddleware.php: Requires the authenticated user to have an administrator role.
- middleware/PermissionMiddleware.php: Requires the authenticated user to have a named permission.
- routes/web.php: Dispatches simple web routes to public PHP pages.
- routes/api.php: Dispatches JSON API requests to controllers and applies authentication/admin middleware.
- public/index.php: Bootstraps the database and dispatches API or web requests.
- public/login.php: Renders and processes the login form with CSRF validation.
- public/logout.php: Logs out the current user and redirects to login.
- public/dashboard.php: Renders a protected dashboard summary page.
- uploads/: Stores uploaded files.
- logs/: Stores runtime logs generated by the application or deployment environment.
- ARCHITECTURE.md: Documents backend dependencies, checklist, public methods, and file responsibilities.
