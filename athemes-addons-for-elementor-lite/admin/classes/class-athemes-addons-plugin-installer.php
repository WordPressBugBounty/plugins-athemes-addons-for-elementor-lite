<?php

/**
 * Plugin Installer.
 * This class is responsible for installing and activating plugins. This could be from wp.org and external sources.
 * 
 * @package aThemes_Addons
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'aThemes_Addons_Plugin_Installer' ) ) {

    class aThemes_Addons_Plugin_Installer {

        /**
         * Constructor.
         * 
         */
        public function __construct() {
            add_action( 'admin_init', array( $this, 'init' ) );
        }

        /**
         * Initialize the class.
         * 
         * @return void
         */
        public function init() {
            if ( ! is_admin() ) {
                return;
            }

            if ( ! current_user_can( 'install_plugins' ) ) {
                return;
            }

            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_action( 'wp_ajax_addons_install_plugin', array( $this, 'install_plugin' ) );
            add_action( 'wp_ajax_addons_install_external_plugin', array( $this, 'install_external_plugin' ) );
        }

        /**
         * Enqueue scripts.
         * 
         * @return void
         */
        public function enqueue_scripts() {
            wp_enqueue_script( 'addons-plugin-installer', ATHEMES_AFE_URI . '/assets/js/admin/plugin-installer.min.js', array( 'jquery' ), ATHEMES_AFE_VERSION, true );

            wp_localize_script( 'addons-plugin-installer', 'addonsPluginInstallerConfig', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'addons_plugin_installer_nonce' ),
                'i18n' => array(
                    'defaultText' => esc_html__( 'Install and Activate', 'athemes-addons-elementor' ),
                    'installingText' => esc_html__( 'Installing...', 'athemes-addons-elementor' ),
                    'activatingText' => esc_html__( 'Activating...', 'athemes-addons-elementor' ),
                ),
            ) );
        }

        /**
         * Install plugin.
         * This method is responsible for installing plugins from the wp.org.
         * 
         * @return void
         */
        public function install_plugin() {
            // TODO: Implement this method.
        }

        /**
         * Install external plugin.
         * This method is responsible for installing plugins from external sources.
         * 
         * @return void
         */
        public function install_external_plugin() {
            check_ajax_referer( 'addons_plugin_installer_nonce', 'nonce' );

            if ( ! current_user_can( 'install_plugins' ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to install plugins.', 'athemes-addons-elementor' ) ) );
            }

            if ( empty( $_POST['url'] ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'Plugin URL is required.', 'athemes-addons-elementor' ) ) );
            }

            $url = esc_url_raw( $_POST['url'] );
            
            if ( empty( $_POST['plugin_name'] ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'Plugin name is required.', 'athemes-addons-elementor' ) ) );
            }

            $plugin_name = sanitize_text_field( wp_unslash( $_POST['plugin_name'] ) );

            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ATHEMES_AFE_DIR . 'admin/classes/class-athemes-addons-silent-upgrader-skin.php';
            
            $upgrader = new Plugin_Upgrader( new aThemes_Addons_Silent_Upgrader_Skin() );
            $install = $upgrader->install( $url );       

            if ( is_wp_error( $install ) ) {
                wp_send_json_error( array( 'message' => $install->get_error_message() ) );
            }

            $activate = activate_plugin( $plugin_name );

            if ( is_wp_error( $activate ) ) {
                wp_send_json_error( array( 'message' => $activate->get_error_message() ) );
            }

            wp_send_json_success( 
                array( 
                    'message' => esc_html__( 'Plugin activated successfully.', 'athemes-addons-elementor' ), 
                ) 
            );
        }
    }

    new aThemes_Addons_Plugin_Installer();
}