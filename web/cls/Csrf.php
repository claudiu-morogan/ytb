<?php

class Csrf {
    /**
     * Generate a CSRF token and store it in the session
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token from POST request
     */
    public static function validateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }

    /**
     * Get the CSRF token input field HTML
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate token or die with error
     */
    public static function validateOrDie() {
        if (!self::validateToken()) {
            error_log("CSRF token validation failed");
            http_response_code(403);
            die("Invalid security token. Please try again.");
        }
    }
}
