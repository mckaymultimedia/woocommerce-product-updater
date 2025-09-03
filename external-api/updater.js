// updater.js
const axios = require('axios');
const cron = require('node-cron');

class WooCommerceUpdater {
    constructor(config) {
        this.wpApiUrl = config.wpApiUrl;
        this.apiKey = config.apiKey;
        this.externalEndpoint = config.externalEndpoint;
    }

    async updateProducts() {
        try {
            console.log('Fetching data from external API...');
            
            // 1. Fetch data from external endpoint
            const externalResponse = await axios.get(this.externalEndpoint, {
                timeout: 30000,
                headers: {
                    'Accept': 'application/json'
                }
            });

            const productData = externalResponse.data;

            // 2. Send update to WordPress
            const wpResponse = await axios.post(this.wpApiUrl, {
                products: productData
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': this.apiKey
                },
                timeout: 45000
            });

            console.log('Update successful:', wpResponse.data);
            return wpResponse.data;

        } catch (error) {
            console.error('Update failed:', error.response?.data || error.message);
            throw error;
        }
    }

    // Method to run updates on schedule
    startScheduledUpdates() {
        console.log('Starting scheduled product updates (4 times daily)...');
        
        // Run every 6 hours (4 times daily)
        cron.schedule('0 */6 * * *', () => {
            console.log('Running scheduled product update...');
            this.updateProducts();
        });
    }
}

// Config - to be moved to config.js in production
const config = {
    wpApiUrl: 'https://your-wordpress-site.com/wp-json/wc-product-updater/v1/update-products',
    apiKey: 'your-secret-api-key',
    externalEndpoint: 'https://external-api.com/products/update'
};

// Initialize and start
const updater = new WooCommerceUpdater(config);
updater.startScheduledUpdates();

// For immediate testing
// updater.updateProducts();

module.exports = WooCommerceUpdater;