<?php
/**
 * Plugin Name: Media Attachment Bulk Move
 * Plugin URI:
 * Description: Displays all media files attached to a post/page in the backend edit screen with bulk move functionality.
 * Version: 1.2.3
 * Author: Sun Phetkong
 * Author URI: https://github.com/Sun-Phetkong
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: media-attachment-bulk-move
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Media_Attachment_Bulk_Move {

    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance (Singleton)
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_attachments_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_mabm_detach_media', array( $this, 'ajax_detach_media' ) );
        add_action( 'wp_ajax_mabm_delete_media', array( $this, 'ajax_delete_media' ) );
        add_action( 'wp_ajax_mabm_move_media', array( $this, 'ajax_move_media' ) );
        add_action( 'wp_ajax_mabm_search_posts', array( $this, 'ajax_search_posts' ) );
    }

    /**
     * Add meta box to post types
     */
    public function add_attachments_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );

        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type ) {
                continue;
            }

            add_meta_box(
                'mabm_media_attachments',
                esc_html__( 'Attached Media Files', 'media-attachment-bulk-move' ),
                array( $this, 'render_attachments_meta_box' ),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Render the meta box content
     */
    public function render_attachments_meta_box( $post ) {
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'posts_per_page' => -1,
            'post_parent'    => $post->ID,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ) );

        wp_nonce_field( 'mabm_media_attachment_action', 'mabm_media_nonce' );

        if ( empty( $attachments ) ) {
            echo '<p class="mabm-no-attachments">' . esc_html__( 'No media files attached to this post.', 'media-attachment-bulk-move' ) . '</p>';
            return;
        }

        echo '<div class="mabm-attachments-container">';

        // Bulk actions bar
        echo '<div class="mabm-bulk-actions-bar">';
        echo '<label class="mabm-select-all-label">';
        echo '<input type="checkbox" id="mabm-select-all" /> ';
        echo esc_html__( 'Select All', 'media-attachment-bulk-move' );
        echo '</label>';

        echo '<div class="mabm-bulk-move-section">';
        printf(
            '<span class="mabm-selected-count">0 %s</span>',
            esc_html__( 'selected', 'media-attachment-bulk-move' )
        );
        echo '<div class="mabm-move-controls">';
        printf(
            '<input type="text" id="mabm-post-search" class="mabm-post-search" placeholder="%s" autocomplete="off" />',
            esc_attr__( 'Search for a post/page...', 'media-attachment-bulk-move' )
        );
        echo '<input type="hidden" id="mabm-target-post-id" value="" />';
        echo '<div id="mabm-search-results" class="mabm-search-results"></div>';
        echo '<button type="button" id="mabm-move-selected" class="button button-primary" disabled>';
        echo '<span class="dashicons dashicons-move"></span> ' . esc_html__( 'Move Selected', 'media-attachment-bulk-move' );
        echo '</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        $count = count( $attachments );
        echo '<p class="mabm-attachment-count">';
        printf(
            /* translators: %d: number of attached files */
            esc_html( _n( '%d attached file', '%d attached files', $count, 'media-attachment-bulk-move' ) ),
            (int) $count
        );
        echo '</p>';

        echo '<div class="mabm-attachments-grid">';

        foreach ( $attachments as $attachment ) {
            $this->render_attachment_item( $attachment );
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Render a single attachment item
     */
    private function render_attachment_item( $attachment ) {
        $attachment_id = $attachment->ID;
        $mime_type     = $attachment->post_mime_type;
        $file_path     = get_attached_file( $attachment_id );
        $file_name     = basename( $file_path );
        $file_url      = wp_get_attachment_url( $attachment_id );
        $edit_link     = get_edit_post_link( $attachment_id );
        $file_size     = file_exists( $file_path ) ? size_format( filesize( $file_path ), 2 ) : esc_html__( 'Unknown', 'media-attachment-bulk-move' );
        $upload_date   = get_the_date( 'Y-m-d H:i', $attachment_id );
        $is_image      = wp_attachment_is_image( $attachment_id );

        echo '<div class="mabm-attachment-item" data-attachment-id="' . esc_attr( $attachment_id ) . '">';

        // Row 1: Checkbox + Filename
        echo '<div class="mabm-attachment-header">';
        echo '<div class="mabm-attachment-checkbox">';
        echo '<input type="checkbox" class="mabm-attachment-select" value="' . esc_attr( $attachment_id ) . '" />';
        echo '</div>';
        echo '<strong class="mabm-file-name">' . esc_html( $file_name ) . '</strong>';
        echo '</div>';

        // Row 2: Thumbnail + Meta + Actions
        echo '<div class="mabm-attachment-body">';

        // Thumbnail or icon
        echo '<div class="mabm-attachment-preview">';
        if ( $is_image ) {
            $thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
            if ( $thumb ) {
                echo '<img src="' . esc_url( $thumb[0] ) . '" alt="' . esc_attr( $file_name ) . '">';
            }
        } else {
            $icon = wp_mime_type_icon( $mime_type );
            echo '<img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $mime_type ) . '" class="mabm-mime-icon">';
        }
        echo '</div>';

        // File meta info
        echo '<div class="mabm-attachment-info">';
        echo '<span class="mabm-file-meta">' . esc_html( $mime_type ) . '</span>';
        echo '<span class="mabm-file-meta">' . esc_html( $file_size ) . '</span>';
        echo '<span class="mabm-file-meta">' . esc_html( $upload_date ) . '</span>';
        echo '</div>';

        // Actions
        echo '<div class="mabm-attachment-actions">';
        printf(
            '<a href="%s" target="_blank" class="button button-small" title="%s"><span class="dashicons dashicons-visibility"></span></a>',
            esc_url( $file_url ),
            esc_attr__( 'View', 'media-attachment-bulk-move' )
        );

        if ( $edit_link ) {
            printf(
                '<a href="%s" class="button button-small" title="%s"><span class="dashicons dashicons-edit"></span></a>',
                esc_url( $edit_link ),
                esc_attr__( 'Edit', 'media-attachment-bulk-move' )
            );
        }

        printf(
            '<button type="button" class="button button-small mabm-detach-btn" data-id="%s" title="%s"><span class="dashicons dashicons-editor-unlink"></span></button>',
            esc_attr( $attachment_id ),
            esc_attr__( 'Detach from post', 'media-attachment-bulk-move' )
        );

        printf(
            '<button type="button" class="button button-small button-link-delete mabm-delete-btn" data-id="%s" title="%s"><span class="dashicons dashicons-trash"></span></button>',
            esc_attr( $attachment_id ),
            esc_attr__( 'Delete permanently', 'media-attachment-bulk-move' )
        );

        echo '</div>';
        echo '</div>'; // .mabm-attachment-body
        echo '</div>'; // .mabm-attachment-item
    }

    /**
     * Enqueue admin styles and scripts
     */
    public function enqueue_admin_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        wp_enqueue_style(
            'media-attachment-bulk-move',
            plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
            array(),
            '1.2.3'
        );

        wp_enqueue_script(
            'media-attachment-bulk-move',
            plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
            array( 'jquery' ),
            '1.2.3',
            true
        );

        wp_localize_script( 'media-attachment-bulk-move', 'mabmManager', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'mabm_media_attachment_action' ),
            'currentPostId' => get_the_ID(),
            'i18n'          => array(
                'confirmDetach' => esc_html__( 'Are you sure you want to detach this file from the post? The file will remain in the Media Library.', 'media-attachment-bulk-move' ),
                'confirmDelete' => esc_html__( 'Are you sure you want to permanently delete this file? This cannot be undone.', 'media-attachment-bulk-move' ),
                'confirmMove'   => esc_html__( 'Are you sure you want to move the selected files to this post?', 'media-attachment-bulk-move' ),
                'selectPost'    => esc_html__( 'Please select a destination post first.', 'media-attachment-bulk-move' ),
                'selectFiles'   => esc_html__( 'Please select at least one file to move.', 'media-attachment-bulk-move' ),
                'searching'     => esc_html__( 'Searching...', 'media-attachment-bulk-move' ),
                'noResults'     => esc_html__( 'No posts found.', 'media-attachment-bulk-move' ),
                'moving'        => esc_html__( 'Moving...', 'media-attachment-bulk-move' ),
                'selected'      => esc_html__( 'selected', 'media-attachment-bulk-move' ),
                'viewTarget'    => esc_html__( 'View target post', 'media-attachment-bulk-move' ),
                'noAttachments' => esc_html__( 'No media files attached to this post.', 'media-attachment-bulk-move' ),
                'attachedFile'  => esc_html__( 'attached file', 'media-attachment-bulk-move' ),
                'attachedFiles' => esc_html__( 'attached files', 'media-attachment-bulk-move' ),
            ),
        ) );
    }

    /**
     * AJAX handler for searching posts
     */
    public function ajax_search_posts() {
        check_ajax_referer( 'mabm_media_attachment_action', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'media-attachment-bulk-move' ) ) );
        }

        $search          = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
        $current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;

        if ( strlen( $search ) < 2 ) {
            wp_send_json_success( array( 'posts' => array() ) );
        }

        $post_types = get_post_types( array( 'public' => true ), 'names' );
        unset( $post_types['attachment'] );

        $posts = get_posts( array(
            'post_type'      => array_values( $post_types ),
            'post_status'    => array( 'publish', 'draft', 'private' ),
            's'              => $search,
            'posts_per_page' => 20,
            'exclude'        => array( $current_post_id ),
            'orderby'        => 'relevance',
        ) );

        $results = array();
        foreach ( $posts as $post ) {
            $post_type_obj = get_post_type_object( $post->post_type );
            $results[]     = array(
                'id'        => $post->ID,
                'title'     => $post->post_title ? $post->post_title : esc_html__( '(no title)', 'media-attachment-bulk-move' ),
                'post_type' => $post_type_obj->labels->singular_name,
                'status'    => $post->post_status,
                'edit_link' => get_edit_post_link( $post->ID, 'raw' ),
            );
        }

        wp_send_json_success( array( 'posts' => $results ) );
    }

    /**
     * AJAX handler for moving media to another post
     */
    public function ajax_move_media() {
        check_ajax_referer( 'mabm_media_attachment_action', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'media-attachment-bulk-move' ) ) );
        }

        $attachment_ids = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', (array) $_POST['attachment_ids'] ) : array();
        $target_post_id = isset( $_POST['target_post_id'] ) ? absint( $_POST['target_post_id'] ) : 0;

        if ( empty( $attachment_ids ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'No attachments selected.', 'media-attachment-bulk-move' ) ) );
        }

        if ( ! $target_post_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid target post.', 'media-attachment-bulk-move' ) ) );
        }

        $target_post = get_post( $target_post_id );
        if ( ! $target_post || 'attachment' === $target_post->post_type ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Target post not found.', 'media-attachment-bulk-move' ) ) );
        }

        $moved  = 0;
        $errors = array();

        foreach ( $attachment_ids as $attachment_id ) {
            $result = wp_update_post( array(
                'ID'          => $attachment_id,
                'post_parent' => $target_post_id,
            ) );

            if ( is_wp_error( $result ) ) {
                $errors[] = $result->get_error_message();
            } else {
                $moved++;
            }
        }

        if ( $moved > 0 ) {
            wp_send_json_success( array(
                'message'    => sprintf(
                    /* translators: %d: number of files moved */
                    esc_html( _n( '%d file moved successfully.', '%d files moved successfully.', $moved, 'media-attachment-bulk-move' ) ),
                    (int) $moved
                ),
                'moved'      => $moved,
                'target_url' => get_edit_post_link( $target_post_id, 'raw' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Failed to move files.', 'media-attachment-bulk-move' ) ) );
        }
    }

    /**
     * AJAX handler for detaching media
     */
    public function ajax_detach_media() {
        check_ajax_referer( 'mabm_media_attachment_action', 'nonce' );

        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'media-attachment-bulk-move' ) ) );
        }

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid attachment ID.', 'media-attachment-bulk-move' ) ) );
        }

        $result = wp_update_post( array(
            'ID'          => $attachment_id,
            'post_parent' => 0,
        ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => esc_html__( 'File detached successfully.', 'media-attachment-bulk-move' ) ) );
    }

    /**
     * AJAX handler for deleting media
     */
    public function ajax_delete_media() {
        check_ajax_referer( 'mabm_media_attachment_action', 'nonce' );

        if ( ! current_user_can( 'delete_posts' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'media-attachment-bulk-move' ) ) );
        }

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid attachment ID.', 'media-attachment-bulk-move' ) ) );
        }

        $result = wp_delete_attachment( $attachment_id, true );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Failed to delete the file.', 'media-attachment-bulk-move' ) ) );
        }

        wp_send_json_success( array( 'message' => esc_html__( 'File deleted successfully.', 'media-attachment-bulk-move' ) ) );
    }
}

// Initialize the plugin
Media_Attachment_Bulk_Move::get_instance();
