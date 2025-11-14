<?php
/**
 * REST API endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_REST_API {

    /**
     * Initialize
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public static function register_routes() {
        $namespace = 'cdv/v1';

        // Travels endpoints
        register_rest_route($namespace, '/travels', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_travels'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($namespace, '/travels/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_travel'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($namespace, '/travels/(?P<id>\d+)/join', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'join_travel'),
            'permission_callback' => array(__CLASS__, 'is_user_logged_in'),
        ));

        register_rest_route($namespace, '/travels/(?P<id>\d+)/participants', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_travel_participants'),
            'permission_callback' => '__return_true',
        ));

        // Chat endpoints
        register_rest_route($namespace, '/chats/(?P<id>\d+)/messages', array(
            array(
                'methods' => 'GET',
                'callback' => array(__CLASS__, 'get_chat_messages'),
                'permission_callback' => array(__CLASS__, 'can_access_chat'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array(__CLASS__, 'send_chat_message'),
                'permission_callback' => array(__CLASS__, 'can_access_chat'),
            ),
        ));

        // Reviews endpoints
        register_rest_route($namespace, '/reviews', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'add_review'),
            'permission_callback' => array(__CLASS__, 'is_user_logged_in'),
        ));

        register_rest_route($namespace, '/users/(?P<id>\d+)/reviews', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_reviews'),
            'permission_callback' => '__return_true',
        ));

        // User profile endpoints
        register_rest_route($namespace, '/users/(?P<id>\d+)/profile', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_profile'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($namespace, '/users/(?P<id>\d+)/badges', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_user_badges'),
            'permission_callback' => '__return_true',
        ));

        // Dashboard endpoints
        register_rest_route($namespace, '/dashboard/my-travels', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_my_travels'),
            'permission_callback' => array(__CLASS__, 'is_user_logged_in'),
        ));
    }

    /**
     * Get travels list
     */
    public static function get_travels($request) {
        $args = array(
            'post_type' => 'viaggio',
            'post_status' => 'publish',
            'posts_per_page' => $request->get_param('per_page') ?: 12,
            'paged' => $request->get_param('page') ?: 1,
        );

        // Filters
        if ($request->get_param('tipo_viaggio')) {
            $args['tax_query'][] = array(
                'taxonomy' => 'tipo_viaggio',
                'field' => 'slug',
                'terms' => $request->get_param('tipo_viaggio'),
            );
        }

        if ($request->get_param('destinazione')) {
            $args['tax_query'][] = array(
                'taxonomy' => 'destinazione',
                'field' => 'slug',
                'terms' => $request->get_param('destinazione'),
            );
        }

        if ($request->get_param('search')) {
            $args['s'] = $request->get_param('search');
        }

        $query = new WP_Query($args);

        $travels = array();
        foreach ($query->posts as $post) {
            $travels[] = self::format_travel($post);
        }

        return new WP_REST_Response(array(
            'travels' => $travels,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ), 200);
    }

    /**
     * Get single travel
     */
    public static function get_travel($request) {
        $post = get_post($request['id']);

        if (!$post || $post->post_type !== 'viaggio') {
            return new WP_Error('not_found', 'Viaggio non trovato', array('status' => 404));
        }

        return new WP_REST_Response(self::format_travel($post), 200);
    }

    /**
     * Join travel
     */
    public static function join_travel($request) {
        $travel_id = $request['id'];
        $user_id = get_current_user_id();
        $message = $request->get_param('message');

        $result = CDV_Participants::request_join($travel_id, $user_id, $message);

        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array('success' => true, 'id' => $result), 200);
    }

    /**
     * Get travel participants
     */
    public static function get_travel_participants($request) {
        $participants = CDV_Participants::get_participants($request['id'], 'accepted');

        $formatted = array();
        foreach ($participants as $participant) {
            $user = get_user_by('id', $participant->user_id);
            $formatted[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
                'reputation' => get_user_meta($user->ID, 'cdv_reputation_score', true),
            );
        }

        return new WP_REST_Response($formatted, 200);
    }

    /**
     * Get chat messages
     */
    public static function get_chat_messages($request) {
        $messages = CDV_Chat::get_messages($request['id'], 50);

        $formatted = array();
        foreach ($messages as $message) {
            $user = get_user_by('id', $message->user_id);
            $formatted[] = array(
                'id' => $message->id,
                'user' => array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'avatar' => get_avatar_url($user->ID),
                ),
                'message' => $message->message,
                'created_at' => $message->created_at,
            );
        }

        return new WP_REST_Response(array_reverse($formatted), 200);
    }

    /**
     * Send chat message
     */
    public static function send_chat_message($request) {
        $chat_group_id = $request['id'];
        $user_id = get_current_user_id();
        $message = $request->get_param('message');

        $result = CDV_Chat::send_message($chat_group_id, $user_id, $message);

        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array('success' => true, 'id' => $result), 200);
    }

    /**
     * Add review
     */
    public static function add_review($request) {
        $travel_id = $request->get_param('travel_id');
        $reviewed_id = $request->get_param('reviewed_id');
        $reviewer_id = get_current_user_id();
        $scores = array(
            'punctuality' => $request->get_param('punctuality'),
            'group_spirit' => $request->get_param('group_spirit'),
            'respect' => $request->get_param('respect'),
            'adaptability' => $request->get_param('adaptability'),
        );
        $comment = $request->get_param('comment');

        $result = CDV_Reviews::add_review($travel_id, $reviewer_id, $reviewed_id, $scores, $comment);

        if (is_wp_error($result)) {
            return new WP_Error($result->get_error_code(), $result->get_error_message(), array('status' => 400));
        }

        return new WP_REST_Response(array('success' => true, 'id' => $result), 200);
    }

    /**
     * Get user reviews
     */
    public static function get_user_reviews($request) {
        $reviews = CDV_Reviews::get_user_reviews($request['id']);

        $formatted = array();
        foreach ($reviews as $review) {
            $reviewer = get_user_by('id', $review->reviewer_id);
            $formatted[] = array(
                'reviewer' => array(
                    'id' => $reviewer->ID,
                    'name' => $reviewer->display_name,
                    'avatar' => get_avatar_url($reviewer->ID),
                ),
                'scores' => array(
                    'punctuality' => $review->punctuality,
                    'group_spirit' => $review->group_spirit,
                    'respect' => $review->respect,
                    'adaptability' => $review->adaptability,
                ),
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            );
        }

        return new WP_REST_Response($formatted, 200);
    }

    /**
     * Get user profile
     */
    public static function get_user_profile($request) {
        $user = get_user_by('id', $request['id']);

        if (!$user) {
            return new WP_Error('not_found', 'Utente non trovato', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'avatar' => get_avatar_url($user->ID, array('size' => 200)),
            'bio' => get_user_meta($user->ID, 'cdv_bio', true),
            'city' => get_user_meta($user->ID, 'cdv_city', true),
            'country' => get_user_meta($user->ID, 'cdv_country', true),
            'languages' => get_user_meta($user->ID, 'cdv_languages', true),
            'travel_styles' => get_user_meta($user->ID, 'cdv_travel_styles', true),
            'verified' => get_user_meta($user->ID, 'cdv_verified', true) === '1',
            'reputation' => get_user_meta($user->ID, 'cdv_reputation_score', true),
            'total_reviews' => get_user_meta($user->ID, 'cdv_total_reviews', true),
        ), 200);
    }

    /**
     * Get user badges
     */
    public static function get_user_badges($request) {
        return new WP_REST_Response(CDV_Badges::get_user_badges($request['id']), 200);
    }

    /**
     * Get user's travels (organized and participating)
     */
    public static function get_my_travels($request) {
        $user_id = get_current_user_id();

        // Organized travels
        $organized = get_posts(array(
            'post_type' => 'viaggio',
            'author' => $user_id,
            'posts_per_page' => -1,
        ));

        // Participating travels
        global $wpdb;
        $table_participants = $wpdb->prefix . 'cdv_travel_participants';
        $participating_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT travel_id FROM $table_participants WHERE user_id = %d AND status = 'accepted'",
            $user_id
        ));

        $participating = array();
        if (!empty($participating_ids)) {
            $participating = get_posts(array(
                'post_type' => 'viaggio',
                'post__in' => $participating_ids,
                'posts_per_page' => -1,
            ));
        }

        return new WP_REST_Response(array(
            'organized' => array_map(array(__CLASS__, 'format_travel'), $organized),
            'participating' => array_map(array(__CLASS__, 'format_travel'), $participating),
        ), 200);
    }

    /**
     * Format travel post for API
     */
    private static function format_travel($post) {
        $author = get_user_by('id', $post->post_author);

        return array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => get_the_excerpt($post),
            'image' => get_the_post_thumbnail_url($post->ID, 'large'),
            'organizer' => array(
                'id' => $author->ID,
                'name' => $author->display_name,
                'avatar' => get_avatar_url($author->ID),
                'reputation' => get_user_meta($author->ID, 'cdv_reputation_score', true),
            ),
            'start_date' => get_post_meta($post->ID, 'cdv_start_date', true),
            'end_date' => get_post_meta($post->ID, 'cdv_end_date', true),
            'destination' => get_post_meta($post->ID, 'cdv_destination', true),
            'country' => get_post_meta($post->ID, 'cdv_country', true),
            'budget' => get_post_meta($post->ID, 'cdv_budget', true),
            'max_participants' => get_post_meta($post->ID, 'cdv_max_participants', true),
            'current_participants' => CDV_Participants::get_participant_count($post->ID),
            'status' => get_post_meta($post->ID, 'cdv_travel_status', true) ?: 'open',
            'tipo_viaggio' => wp_get_post_terms($post->ID, 'tipo_viaggio', array('fields' => 'names')),
            'created_at' => $post->post_date,
        );
    }

    /**
     * Permission callback: check if user is logged in
     */
    public static function is_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Permission callback: check if user can access chat
     */
    public static function can_access_chat($request) {
        if (!is_user_logged_in()) {
            return false;
        }

        return CDV_Chat::can_user_access_chat($request['id'], get_current_user_id());
    }
}
