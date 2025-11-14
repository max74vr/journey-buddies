<?php
/**
 * Register Custom Taxonomies
 */

if (!defined('ABSPATH')) {
    exit;
}

class CDV_Taxonomies {

    /**
     * Initialize
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomies'));
    }

    /**
     * Register custom taxonomies
     */
    public static function register_taxonomies() {
        self::register_tipo_viaggio();
        self::register_destinazione();
    }

    /**
     * Register 'Tipo Viaggio' taxonomy
     */
    private static function register_tipo_viaggio() {
        $labels = array(
            'name'              => _x('Tipi di Viaggio', 'taxonomy general name', 'compagni-di-viaggi'),
            'singular_name'     => _x('Tipo di Viaggio', 'taxonomy singular name', 'compagni-di-viaggi'),
            'search_items'      => __('Cerca Tipi', 'compagni-di-viaggi'),
            'all_items'         => __('Tutti i Tipi', 'compagni-di-viaggi'),
            'parent_item'       => __('Tipo Genitore', 'compagni-di-viaggi'),
            'parent_item_colon' => __('Tipo Genitore:', 'compagni-di-viaggi'),
            'edit_item'         => __('Modifica Tipo', 'compagni-di-viaggi'),
            'update_item'       => __('Aggiorna Tipo', 'compagni-di-viaggi'),
            'add_new_item'      => __('Aggiungi Nuovo Tipo', 'compagni-di-viaggi'),
            'new_item_name'     => __('Nuovo Tipo di Viaggio', 'compagni-di-viaggi'),
            'menu_name'         => __('Tipi di Viaggio', 'compagni-di-viaggi'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'tipo-viaggio'),
        );

        register_taxonomy('tipo_viaggio', array('viaggio'), $args);

        // Add default terms
        if (!term_exists('Avventura', 'tipo_viaggio')) {
            wp_insert_term('Avventura', 'tipo_viaggio', array('slug' => 'avventura'));
        }
        if (!term_exists('Mare', 'tipo_viaggio')) {
            wp_insert_term('Mare', 'tipo_viaggio', array('slug' => 'mare'));
        }
        if (!term_exists('Montagna', 'tipo_viaggio')) {
            wp_insert_term('Montagna', 'tipo_viaggio', array('slug' => 'montagna'));
        }
        if (!term_exists('Città d\'Arte', 'tipo_viaggio')) {
            wp_insert_term('Città d\'Arte', 'tipo_viaggio', array('slug' => 'citta-arte'));
        }
        if (!term_exists('Cultura', 'tipo_viaggio')) {
            wp_insert_term('Cultura', 'tipo_viaggio', array('slug' => 'cultura'));
        }
        if (!term_exists('Relax', 'tipo_viaggio')) {
            wp_insert_term('Relax', 'tipo_viaggio', array('slug' => 'relax'));
        }
        if (!term_exists('Food & Wine', 'tipo_viaggio')) {
            wp_insert_term('Food & Wine', 'tipo_viaggio', array('slug' => 'food-wine'));
        }
        if (!term_exists('Sport', 'tipo_viaggio')) {
            wp_insert_term('Sport', 'tipo_viaggio', array('slug' => 'sport'));
        }
        if (!term_exists('Zaino in Spalla', 'tipo_viaggio')) {
            wp_insert_term('Zaino in Spalla', 'tipo_viaggio', array('slug' => 'zaino-spalla'));
        }
    }

    /**
     * Register 'Destinazione' taxonomy
     */
    private static function register_destinazione() {
        $labels = array(
            'name'              => _x('Destinazioni', 'taxonomy general name', 'compagni-di-viaggi'),
            'singular_name'     => _x('Destinazione', 'taxonomy singular name', 'compagni-di-viaggi'),
            'search_items'      => __('Cerca Destinazioni', 'compagni-di-viaggi'),
            'all_items'         => __('Tutte le Destinazioni', 'compagni-di-viaggi'),
            'parent_item'       => __('Destinazione Genitore', 'compagni-di-viaggi'),
            'parent_item_colon' => __('Destinazione Genitore:', 'compagni-di-viaggi'),
            'edit_item'         => __('Modifica Destinazione', 'compagni-di-viaggi'),
            'update_item'       => __('Aggiorna Destinazione', 'compagni-di-viaggi'),
            'add_new_item'      => __('Aggiungi Nuova Destinazione', 'compagni-di-viaggi'),
            'new_item_name'     => __('Nuova Destinazione', 'compagni-di-viaggi'),
            'menu_name'         => __('Destinazioni', 'compagni-di-viaggi'),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'destinazione'),
        );

        register_taxonomy('destinazione', array('viaggio'), $args);
    }
}
