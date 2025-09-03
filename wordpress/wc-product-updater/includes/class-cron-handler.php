<?php
class WC_Product_Updater_Cron_Handler {
    
    public function __construct() {
        add_action('wc_product_updater_cron', array($this, 'run_scheduled_update'));
        add_filter('cron_schedules', array($this, 'add_cron_schedule'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function add_cron_schedule($schedules) {
        $schedules['six_hours'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours')
        );
        return $schedules;
    }
    
    public function activate() {
        if (!wp_next_scheduled('wc_product_updater_cron')) {
            wp_schedule_event(time(), 'six_hours', 'wc_product_updater_cron');
        }
    }
    
    public function deactivate() {
        $timestamp = wp_next_scheduled('wc_product_updater_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'wc_product_updater_cron');
        }
    }
    
    public function run_scheduled_update() {
        $api_handler = new WC_Product_Updater_API_Handler();
        $result = $api_handler->update_products();
        
        // Log the result
        error_log('WC Product Updater Cron: ' . print_r($result, true));
    }
}