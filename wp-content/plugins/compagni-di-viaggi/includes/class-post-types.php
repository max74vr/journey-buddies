<?php
/**
 * Register Custom Post Types
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Post_Types {

    /**
     * Initialize
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
    }

    /**
     * Register custom post types
     */
    public static function register_post_types() {
        self::register_viaggio();
    }

    /**
     * Register 'Viaggio' post type
     */
    private static function register_viaggio() {
        $labels = array(
            'name'                  => _x('Viaggi', 'Post Type General Name', 'compagni-di-viaggi'),
            'singular_name'         => _x('Viaggio', 'Post Type Singular Name', 'compagni-di-viaggi'),
            'menu_name'            => __('Viaggi', 'compagni-di-viaggi'),
            'name_admin_bar'       => __('Viaggio', 'compagni-di-viaggi'),
            'archives'             => __('Archivio Viaggi', 'compagni-di-viaggi'),
            'attributes'           => __('Attributi Viaggio', 'compagni-di-viaggi'),
            'parent_item_colon'    => __('Viaggio Genitore:', 'compagni-di-viaggi'),
            'all_items'            => __('Tutti i Viaggi', 'compagni-di-viaggi'),
            'add_new_item'         => __('Aggiungi Nuovo Viaggio', 'compagni-di-viaggi'),
            'add_new'              => __('Aggiungi Nuovo', 'compagni-di-viaggi'),
            'new_item'             => __('Nuovo Viaggio', 'compagni-di-viaggi'),
            'edit_item'            => __('Modifica Viaggio', 'compagni-di-viaggi'),
            'update_item'          => __('Aggiorna Viaggio', 'compagni-di-viaggi'),
            'view_item'            => __('Visualizza Viaggio', 'compagni-di-viaggi'),
            'view_items'           => __('Visualizza Viaggi', 'compagni-di-viaggi'),
            'search_items'         => __('Cerca Viaggio', 'compagni-di-viaggi'),
            'not_found'            => __('Nessun viaggio trovato', 'compagni-di-viaggi'),
            'not_found_in_trash'   => __('Nessun viaggio trovato nel cestino', 'compagni-di-viaggi'),
            'featured_image'       => __('Immagine di Copertina', 'compagni-di-viaggi'),
            'set_featured_image'   => __('Imposta immagine di copertina', 'compagni-di-viaggi'),
            'remove_featured_image'=> __('Rimuovi immagine di copertina', 'compagni-di-viaggi'),
            'use_featured_image'   => __('Usa come immagine di copertina', 'compagni-di-viaggi'),
            'insert_into_item'     => __('Inserisci nel viaggio', 'compagni-di-viaggi'),
            'uploaded_to_this_item'=> __('Caricato in questo viaggio', 'compagni-di-viaggi'),
            'items_list'           => __('Lista viaggi', 'compagni-di-viaggi'),
            'items_list_navigation'=> __('Navigazione lista viaggi', 'compagni-di-viaggi'),
            'filter_items_list'    => __('Filtra lista viaggi', 'compagni-di-viaggi'),
        );

        $args = array(
            'label'               => __('Viaggio', 'compagni-di-viaggi'),
            'description'         => __('Viaggi della community', 'compagni-di-viaggi'),
            'labels'              => $labels,
            'supports'            => array('title', 'editor', 'thumbnail', 'author', 'comments', 'revisions'),
            'taxonomies'          => array('tipo_viaggio', 'destinazione'),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-palmtree',
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'capability_type'     => 'post',
            'show_in_rest'        => true,
            'rest_base'           => 'viaggi',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
            'rewrite'             => array('slug' => 'viaggi'),
        );

        register_post_type('viaggio', $args);
    }
}
