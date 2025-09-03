<?php
class WC_Product_Updater_API_Handler {
    
    private $api_url;
    private $api_key;
    
    public function __construct() {
        $this->api_url = get_option('wc_product_updater_api_url', '');
        $this->api_key = get_option('wc_product_updater_api_key', '');
        
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_routes() {
        register_rest_route('wc-product-updater/v1', '/update-products', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_update_request'),
            'permission_callback' => array($this, 'verify_request')
        ));
    }
    
    public function verify_request($request) {
        $api_key = $request->get_header('X-API-Key');
        return $api_key === $this->api_key;
    }
    
    public function handle_update_request($request) {
        $data = $request->get_json_params();
        
        if (empty($data['products'])) {
            return new WP_Error('no_data', 'No product data provided', array('status' => 400));
        }
        
        $results = array();
        $updated_count = 0;
        
        foreach ($data['products'] as $product_data) {
            $result = $this->update_product($product_data);
            $results[] = $result;
            
            if ($result['status'] === 'success') {
                $updated_count++;
            }
        }
        
        update_option('wc_product_updater_last_sync', current_time('mysql'));
        
        return array(
            'success' => true,
            'updated' => $updated_count,
            'total' => count($results),
            'results' => $results
        );
    }
    
    public function update_product($data) {
        try {
            if (empty($data['id'])) {
                return array(
                    'status' => 'error',
                    'message' => 'Product ID missing'
                );
            }
            
            $product = wc_get_product($data['id']);
            
            if (!$product) {
                return array(
                    'product_id' => $data['id'],
                    'status' => 'error',
                    'message' => 'Product not found'
                );
            }
            
            // Update product fields
            if (isset($data['price'])) {
                $product->set_price($data['price']);
                $product->set_regular_price($data['price']);
            }
            
            if (isset($data['stock_quantity'])) {
                $product->set_stock_quantity($data['stock_quantity']);
                $product->set_manage_stock(true);
            }
            
            if (isset($data['name'])) {
                $product->set_name(sanitize_text_field($data['name']));
            }
            
            if (isset($data['description'])) {
                $product->set_description(sanitize_textarea_field($data['description']));
            }
            
            // Save changes
            $product_id = $product->save();
            
            return array(
                'product_id' => $product_id,
                'status' => 'success',
                'message' => 'Product updated successfully'
            );
            
        } catch (Exception $e) {
            return array(
                'product_id' => $data['id'],
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }
    
    public function register_settings() {
        register_setting('wc_product_updater_settings', 'wc_product_updater_api_url');
        register_setting('wc_product_updater_settings', 'wc_product_updater_api_key');
        
        add_settings_section(
            'wc_product_updater_api_section',
            'API Settings',
            array($this, 'render_api_section'),
            'wc_product_updater_settings'
        );
        
        add_settings_field(
            'wc_product_updater_api_url',
            'API Endpoint URL',
            array($this, 'render_api_url_field'),
            'wc_product_updater_settings',
            'wc_product_updater_api_section'
        );
        
        add_settings_field(
            'wc_product_updater_api_key',
            'API Key',
            array($this, 'render_api_key_field'),
            'wc_product_updater_settings',
            'wc_product_updater_api_section'
        );
    }
    
    public function render_api_section() {
        echo '<p>Configure the external API connection settings</p>';
    }
    
    public function render_api_url_field() {
        $value = get_option('wc_product_updater_api_url', '');
        echo '<input type="url" name="wc_product_updater_api_url" value="' . esc_attr($value) . '" class="regular-text">';
    }
    
    public function render_api_key_field() {
        $value = get_option('wc_product_updater_api_key', '');
        echo '<input type="password" name="wc_product_updater_api_key" value="' . esc_attr($value) . '" class="regular-text">';
    }
    
    public function update_products() {
        // This method would be called by the cron handler
        if (empty($this->api_url)) {
            return array(
                'success' => false,
                'message' => 'API URL not configured'
            );
        }
        
        // In a real implementation, you would fetch from the external API
        // For this example, we'll simulate a response
        $mock_products = array(
            array(
                'id' => 101,
                'name' => 'Sample Product 1',
                'price' => 29.99,
                'stock_quantity' => 50
            ),
            array(
                'id' => 102,
                'name' => 'Sample Product 2',
                'price' => 49.99,
                'stock_quantity' => 25
            )
        );
        
        // Simulate API request
        $response = array(
            'success' => true,
            'products' => $mock_products
        );
        
        // Process the products
        $results = array();
        $updated_count = 0;
        
        foreach ($response['products'] as $product_data) {
            $result = $this->update_product($product_data);
            $results[] = $result;
            
            if ($result['status'] === 'success') {
                $updated_count++;
            }
        }
        
        update_option('wc_product_updater_last_sync', current_time('mysql'));
        
        return array(
            'success' => true,
            'updated' => $updated_count,
            'total' => count($results),
            'results' => $results
        );
    }
}