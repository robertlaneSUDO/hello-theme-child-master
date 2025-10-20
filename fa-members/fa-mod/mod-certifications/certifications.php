<?php
/**
 * Certifications Module Loader
 * 
 * This file loads the Reports and Notifications modules.
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Reports module
if (file_exists(__DIR__ . '/reports.php')) {
    require_once __DIR__ . '/reports.php';
}

// Notifications module
if (file_exists(__DIR__ . '/notifications.php')) {
    require_once __DIR__ . '/notifications.php';
}

// User Certifications shortcode (UI)
if (file_exists(__DIR__ . '/user-certifications.php')) {
    require_once __DIR__ . '/user-certifications.php';
}

// (Optional) Additional AJAX Shortcode if it's a separate file
if (file_exists(__DIR__ . '/user-certifications-ajax.php')) {
    require_once __DIR__ . '/user-certifications-ajax.php';
}

// AJAX Handlers
if (file_exists(__DIR__ . '/ajax-handlers.php')) {
    require_once __DIR__ . '/ajax-handlers.php';
}

// Shortcode (frontend UI)
if (file_exists(__DIR__ . '/user-certifications-shortcode.php')) {
    require_once __DIR__ . '/user-certifications-shortcode.php';
}