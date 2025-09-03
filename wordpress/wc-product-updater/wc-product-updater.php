<?php
/* 
Plugin Name: WooCommerce Product Updater
Description: Updates product data from external API endpoint
Version: 1.0.0
Author: Your Name
*/

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('WC_PRODUCT_UPDATER_VERSION', '1.0.0');
define('WC_PRODUCT_UPDATER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_PRODUCT_UPDATER_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include necessary files
require_once WC_PRODUCT_UPDATER_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once WC_PRODUCT_UPDATER_PLUGIN_DIR . 'includes/class-cron-handler.php';

class WC_Product_Updater {
    
    private $api_handler;
    private $cron_handler;
    
    public function __construct() {
        $this->api_handler = new WC_Product_Updater_API_Handler();
        $this->cron_handler = new WC_Product_Updater_Cron_Handler();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wc_product_updater_manual_sync', array($this, 'handle_manual_sync'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Product Updater',
            'Product Updater',
            'manage_options',
            'wc-product-updater',
            array($this, 'render_admin_page')
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WooCommerce Product Updater</h1>
            <div class="card">
                <h2>Product Synchronization</h2>
                <p>Products are automatically synchronized with the external API 4 times per day.</p>
                <p>Last sync: <span id="last-sync-time"><?php echo get_option('wc_product_updater_last_sync', 'Never'); ?></span></p>
                <button id="manual-sync" class="button button-primary">Sync Now</button>
                <span id="sync-status"></span>
            </div>
            <div class="card">
                <h2>Settings</h2>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wc_product_updater_settings');
                    do_settings_sections('wc_product_updater_settings');
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_wc-product-updater') {
            return;
        }
        
        wp_enqueue_script(
            'wc-product-updater-admin',
            WC_PRODUCT_UPDATER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WC_PRODUCT_UPDATER_VERSION,
            true
        );
        
        wp_localize_script('wc-product-updater-admin', 'wc_product_updater', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_product_updater_nonce')
        ));
    }
    
    public function handle_manual_sync() {
        check_ajax_referer('wc_product_updater_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = $this->api_handler->update_products();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}

// Initialize the plugin
new WC_Product_Updater();