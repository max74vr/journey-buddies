<?php
/**
 * JWT Authentication for mobile apps
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_JWT_Auth {

    /**
     * Initialize
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
        add_filter('determine_current_user', array(__CLASS__, 'determine_current_user'), 20);
    }

    /**
     * Register JWT auth routes
     */
    public static function register_routes() {
        register_rest_route('cdv/v1', '/auth/login', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'login'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('cdv/v1', '/auth/register', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'register'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('cdv/v1', '/auth/validate', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'validate_token'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('cdv/v1', '/auth/refresh', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'refresh_token'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Login endpoint
     */
    public static function login($request) {
        $username = $request->get_param('username');
        $password = $request->get_param('password');

        if (empty($username) || empty($password)) {
            return new WP_Error('missing_credentials', 'Username e password sono obbligatori', array('status' => 400));
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error('invalid_credentials', 'Credenziali non valide', array('status' => 401));
        }

        $token = self::generate_token($user->ID);

        return new WP_REST_Response(array(
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ),
        ), 200);
    }

    /**
     * Register endpoint
     */
    public static function register($request) {
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $display_name = $request->get_param('display_name');

        if (empty($username) || empty($email) || empty($password)) {
            return new WP_Error('missing_fields', 'Tutti i campi sono obbligatori', array('status' => 400));
        }

        // Validate email
        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Email non valida', array('status' => 400));
        }

        // Check if username or email already exists
        if (username_exists($username)) {
            return new WP_Error('username_exists', 'Username già in uso', array('status' => 400));
        }

        if (email_exists($email)) {
            return new WP_Error('email_exists', 'Email già in uso', array('status' => 400));
        }

        // Create user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update display name
        if ($display_name) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name,
            ));
        }

        // Award early adopter badge
        CDV_Badges::award_badge($user_id, 'early_adopter');

        $token = self::generate_token($user_id);
        $user = get_user_by('id', $user_id);

        return new WP_REST_Response(array(
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ),
        ), 201);
    }

    /**
     * Validate token endpoint
     */
    public static function validate_token($request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return new WP_Error('missing_token', 'Token mancante', array('status' => 400));
        }

        $user_id = self::verify_token($token);

        if (!$user_id) {
            return new WP_Error('invalid_token', 'Token non valido o scaduto', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        return new WP_REST_Response(array(
            'valid' => true,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ),
        ), 200);
    }

    /**
     * Refresh token endpoint
     */
    public static function refresh_token($request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return new WP_Error('missing_token', 'Token mancante', array('status' => 400));
        }

        $user_id = self::verify_token($token);

        if (!$user_id) {
            return new WP_Error('invalid_token', 'Token non valido o scaduto', array('status' => 401));
        }

        $new_token = self::generate_token($user_id);

        return new WP_REST_Response(array(
            'token' => $new_token,
        ), 200);
    }

    /**
     * Determine current user from JWT token
     */
    public static function determine_current_user($user_id) {
        // Skip if already authenticated
        if ($user_id) {
            return $user_id;
        }

        // Get token from Authorization header
        $auth_header = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';

        if (empty($auth_header)) {
            return $user_id;
        }

        // Extract token
        list($token) = sscanf($auth_header, 'Bearer %s');

        if (empty($token)) {
            return $user_id;
        }

        // Verify token
        $verified_user_id = self::verify_token($token);

        if ($verified_user_id) {
            return $verified_user_id;
        }

        return $user_id;
    }

    /**
     * Generate JWT token
     */
    private static function generate_token($user_id) {
        $secret = get_option('cdv_jwt_secret');
        $issued_at = time();
        $expiration = $issued_at + (60 * 60 * 24 * 30); // 30 days

        $payload = array(
            'iss' => get_bloginfo('url'),
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user_id,
        );

        return self::jwt_encode($payload, $secret);
    }

    /**
     * Verify JWT token
     */
    private static function verify_token($token) {
        $secret = get_option('cdv_jwt_secret');

        try {
            $payload = self::jwt_decode($token, $secret);

            if (!isset($payload->user_id)) {
                return false;
            }

            // Check expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                return false;
            }

            return $payload->user_id;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Simple JWT encode (base64url)
     */
    private static function jwt_encode($payload, $secret) {
        $header = array('typ' => 'JWT', 'alg' => 'HS256');

        $segments = array();
        $segments[] = self::base64url_encode(json_encode($header));
        $segments[] = self::base64url_encode(json_encode($payload));

        $signing_input = implode('.', $segments);

        $signature = hash_hmac('sha256', $signing_input, $secret, true);
        $segments[] = self::base64url_encode($signature);

        return implode('.', $segments);
    }

    /**
     * Simple JWT decode
     */
    private static function jwt_decode($token, $secret) {
        $segments = explode('.', $token);

        if (count($segments) != 3) {
            throw new Exception('Invalid token format');
        }

        list($header_b64, $payload_b64, $signature_b64) = $segments;

        // Verify signature
        $signing_input = $header_b64 . '.' . $payload_b64;
        $signature = self::base64url_decode($signature_b64);
        $expected_signature = hash_hmac('sha256', $signing_input, $secret, true);

        if ($signature !== $expected_signature) {
            throw new Exception('Invalid signature');
        }

        $payload = json_decode(self::base64url_decode($payload_b64));

        if (!$payload) {
            throw new Exception('Invalid payload');
        }

        return $payload;
    }

    /**
     * Base64 URL encode
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
