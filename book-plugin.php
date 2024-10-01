<?php
/*
Plugin Name: Book Info Plugin
Description: A plugin to manage books and their ISBN numbers.
Version: 1.0
Author: Nadia Golboo
*/

if (!defined('ABSPATH')) {
    exit;
}

use BaseService\Services\QueryBuilder\QueryBuilder;
define('QUERY_BUILDER', BASE_CONTAINER->get(QueryBuilder::class)->get());

register_activation_hook(__FILE__, 'book_plugin_create_table');
function book_plugin_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'book_infos';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        isbn VARCHAR(13) NOT NULL,
        PRIMARY KEY (ID)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


add_action('init', 'book_plugin_register_taxonomies');
function book_plugin_register_taxonomies(): void
{
    register_taxonomy('publisher', 'book', array(
        'label' => __('Publisher', 'book-plugin'),
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ));
    register_taxonomy('author', 'book', array(
        'label' => __('Authors', 'book-plugin'),
        'public' => true,
        'hierarchical' => false,
        'show_in_rest' => true,
    ));
}

add_action('init', 'book_plugin_register_post_type');
function book_plugin_register_post_type()
{
    $args = array(
        'label' => __('Books', 'book-plugin'),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'taxonomies' => array( 'publisher','author', 'post_tag' ),
        'show_in_rest' => true,
    );
    register_post_type('book', $args);
}

add_action('add_meta_boxes', 'book_plugin_add_meta_box');
function book_plugin_add_meta_box()
{
    add_meta_box(
        'isbn_meta_box',
        __('ISBN Number', 'book-plugin'),
        'book_plugin_render_meta_box',
        'book',
        'side',
        'default'
    );
}

function book_plugin_render_meta_box($post)
{
    $value = get_post_meta($post->ID, '_isbn_meta_key', true);
    echo '<label for="book_isbn">' . __('ISBN', 'book-plugin') . '</label>';
    echo '<input type="text" id="book_isbn" name="book_isbn" value="' . esc_attr($value) . '" />';
}

add_action('save_post', 'book_plugin_save_isbn_meta');
function book_plugin_save_isbn_meta($post_id)
{
    if (array_key_exists('book_isbn', $_POST) && get_post_type($post_id) == 'book') {

        update_post_meta($post_id, '_isbn_meta_key', sanitize_text_field($_POST['book_isbn']));

        QUERY_BUILDER->table('wp_book_infos')->updateOrInsert(
            ['post_id' => $post_id],
            ['isbn' => sanitize_text_field($_POST['book_isbn'])]
        );
    }
}

add_action('admin_menu', 'book_plugin_admin_menu');
function book_plugin_admin_menu()
{
    add_menu_page(
        __('Book Info Table', 'book-plugin'),
        __('Book Infos', 'book-plugin'),
        'manage_options',
        'book-infos',
        'book_plugin_render_admin_page',
        'dashicons-book',
        6
    );
}

function book_plugin_render_admin_page()
{
    $results = QUERY_BUILDER->table('wp_book_infos')->get();
    TEMPLATE->setViewDir(__DIR__ . '/views');
    TEMPLATE->setView('book-infos');
    TEMPLATE->share(['results' => $results]);
    echo TEMPLATE->render();
}

add_action('admin_enqueue_scripts', 'book_plugin_enqueue_scripts');
function book_plugin_enqueue_scripts($hook) {
    if ('post.php' == $hook && get_post_type() == 'book') {
        wp_enqueue_script(
            'book-plugin-js',
            plugin_dir_url(__FILE__) . 'assets/js/book-plugin.js',
            array('jquery'),
            '1.0',
            true
        );
    }
}

add_action('wp_ajax_save_isbn', 'book_plugin_save_isbn_via_ajax');
function book_plugin_save_isbn_via_ajax()
{
    if (isset($_POST['isbn']) && isset($_POST['post_id'])) {
        $isbn = sanitize_text_field($_POST['isbn']);
        $post_id = intval($_POST['post_id']);
        update_post_meta($post_id, '_isbn_meta_key', $isbn);
        QUERY_BUILDER->table('wp_book_infos')->updateOrInsert(
            ['post_id' => $post_id],
            ['isbn' => $isbn]
        );
        wp_send_json_success('ISBN saved successfully.');
    } else {
        wp_send_json_error('Invalid data.');
    }
    wp_die();
}


