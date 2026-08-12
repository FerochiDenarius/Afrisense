<?php

declare(strict_types=1);

// Central application paths used by PHP includes and browser-facing URLs.
if (!defined('AFRISENSE_PROJECT_ROOT')) {
    define('AFRISENSE_PROJECT_ROOT', dirname(__DIR__, 2));
}

if (!defined('AFRISENSE_FRONTEND_ROOT')) {
    define('AFRISENSE_FRONTEND_ROOT', dirname(__DIR__));
}

if (!defined('AFRISENSE_APP_ROOT_URL')) {
    define('AFRISENSE_APP_ROOT_URL', '/Afrisense');
}

if (!defined('AFRISENSE_FRONTEND_URL')) {
    define('AFRISENSE_FRONTEND_URL', AFRISENSE_APP_ROOT_URL . '/frontend');
}

if (!defined('AFRISENSE_LANDING_URL')) {
    define('AFRISENSE_LANDING_URL', AFRISENSE_FRONTEND_URL . '/landing');
}

if (!defined('AFRISENSE_AUTH_URL')) {
    define('AFRISENSE_AUTH_URL', AFRISENSE_FRONTEND_URL . '/auth');
}

if (!defined('AFRISENSE_CUSTOMER_URL')) {
    define('AFRISENSE_CUSTOMER_URL', AFRISENSE_FRONTEND_URL . '/customer');
}

if (!defined('AFRISENSE_RESTAURANT_URL')) {
    define('AFRISENSE_RESTAURANT_URL', AFRISENSE_FRONTEND_URL . '/restaurant');
}

if (!defined('AFRISENSE_RESTAURANT_LANDING_URL')) {
    define('AFRISENSE_RESTAURANT_LANDING_URL', AFRISENSE_RESTAURANT_URL . '/landing');
}

if (!defined('AFRISENSE_RESTAURANT_CUSTOMER_URL')) {
    define('AFRISENSE_RESTAURANT_CUSTOMER_URL', AFRISENSE_RESTAURANT_URL . '/customer');
}

if (!defined('AFRISENSE_ADMIN_URL')) {
    define('AFRISENSE_ADMIN_URL', AFRISENSE_FRONTEND_URL . '/admin');
}

if (!defined('AFRISENSE_ADMIN_SHARED_URL')) {
    define('AFRISENSE_ADMIN_SHARED_URL', AFRISENSE_ADMIN_URL . '/shared');
}

if (!defined('AFRISENSE_RESTAURANT_ADMIN_URL')) {
    define('AFRISENSE_RESTAURANT_ADMIN_URL', AFRISENSE_ADMIN_URL . '/restaurant');
}

if (!defined('AFRISENSE_AGRICULTURE_URL')) {
    define('AFRISENSE_AGRICULTURE_URL', AFRISENSE_FRONTEND_URL . '/agriculture');
}

if (!defined('AFRISENSE_AGRICULTURE_ADMIN_URL')) {
    define('AFRISENSE_AGRICULTURE_ADMIN_URL', AFRISENSE_ADMIN_URL . '/agriculture');
}

if (!defined('AFRISENSE_ASSETS_URL')) {
    define('AFRISENSE_ASSETS_URL', AFRISENSE_FRONTEND_URL . '/assets');
}

if (!defined('AFRISENSE_UPLOADS_URL')) {
    define('AFRISENSE_UPLOADS_URL', AFRISENSE_FRONTEND_URL . '/uploads');
}

if (!defined('AFRISENSE_SUPPORT_LIVE_URL')) {
    define('AFRISENSE_SUPPORT_LIVE_URL', AFRISENSE_FRONTEND_URL . '/support/live.php');
}

// Joins URL fragments without duplicate slashes while keeping the leading app slash.
function afrisense_join_url(string $base, string $path = ''): string
{
    $base = '/' . trim($base, '/');
    $path = trim(str_replace('\\', '/', $path), '/');

    if ($path === '') {
        return $base;
    }

    return $base . '/' . $path;
}

function afrisense_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_APP_ROOT_URL, $path);
}

function afrisense_frontend_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_FRONTEND_URL, $path);
}

function afrisense_landing_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_LANDING_URL, $path);
}

function afrisense_auth_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_AUTH_URL, $path);
}

function afrisense_customer_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_CUSTOMER_URL, $path);
}

function afrisense_restaurant_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_RESTAURANT_URL, $path);
}

function afrisense_restaurant_landing_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_RESTAURANT_LANDING_URL, $path);
}

function afrisense_restaurant_customer_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_RESTAURANT_CUSTOMER_URL, $path);
}

function afrisense_admin_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_ADMIN_URL, $path);
}

function afrisense_admin_shared_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_ADMIN_SHARED_URL, $path);
}

function afrisense_restaurant_admin_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_RESTAURANT_ADMIN_URL, $path);
}

function afrisense_agriculture_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_AGRICULTURE_URL, $path);
}

function afrisense_agriculture_admin_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_AGRICULTURE_ADMIN_URL, $path);
}

function afrisense_asset_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_ASSETS_URL, $path);
}

function afrisense_upload_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_UPLOADS_URL, $path);
}

function afrisense_support_live_url(string $path = ''): string
{
    return afrisense_join_url(AFRISENSE_SUPPORT_LIVE_URL, $path);
}
