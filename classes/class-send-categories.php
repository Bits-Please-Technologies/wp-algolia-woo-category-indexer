<?php

/**
 * Algolia Woo Indexer class for sending products
 * Called from main plugin file algolia-woo-indexer.php
 *
 * @package algolia-woo-category-indexer
 */

namespace AlgowooCats;

use \AlgowooCats\Algolia_Check_Requirements as Algolia_Check_Requirements;

/**
 * Abort if this file is called directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Include plugin file if function is_plugin_active does not exist
 */
if (!function_exists('is_plugin_active')) {
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

/**
 * Define the plugin version and the database table name
 */
define('ALGOWOOCAT_DB_OPTION', '_algolia_woo_category_indexer');
define('ALGOWOOCAT_CURRENT_DB_VERSION', '0.3');

/**
 * Define application constants
 */
define('CHANGE_ME', 'change me');

/**
 * Database table names
 */
define('INDEX_NAME', '_index_name');
define('AUTOMATICALLY_SEND_NEW_CATEGORIES', '_automatically_send_new_categories');
define('ALGOLIA_APP_ID', '_application_id');
define('ALGOLIA_API_KEY', '_admin_api_key');

if (!class_exists('Algolia_Send_Categories')) {
    /**
     * Algolia WooIndexer main class
     */
    
    class Algolia_Send_Categories
    {
        const PLUGIN_NAME      = 'Algolia Woo Category Indexer';
        const PLUGIN_TRANSIENT = 'algowoocat-plugin-notice';

        /**
         * The Algolia instance
         *
         * @var \Algolia\AlgoliaSearch\SearchClient
         */
        private static $algolia = null;

        /**
         * Check if we can connect to Algolia, if not, handle the exception, display an error and then return
         */
        public static function can_connect_to_algolia()
        {
            try {
                self::$algolia->listApiKeys();
            } catch (\Algolia\AlgoliaSearch\Exceptions\UnreachableException $error) {
                add_action(
                    'admin_notices',
                    function () {
                        echo '<div class="error notice">
							  <p>' . esc_html__('An error has been encountered. Please check your application ID and API key. ', 'algolia-woo-category-indexer') . '</p>
							</div>';
                    }
                );
                return;
            }
        }

        /**
         * Get sale price or regular price based on product type
         *
         * @param  mixed $product Product to check   
         * @return array ['sale_price' => $sale_price,'regular_price' => $regular_price] Array with regular price and sale price
         */
        public static function get_product_type_price($product)
        {
            $sale_price = 0;
            $regular_price = 0;
            if ($product->is_type('simple')) {
                $sale_price     =  $product->get_sale_price();
                $regular_price  =  $product->get_regular_price();
            } elseif ($product->is_type('variable')) {
                $sale_price     =  $product->get_variation_sale_price('min', true);
                $regular_price  =  $product->get_variation_regular_price('max', true);
            }
            return array(
                'sale_price' => $sale_price,
                'regular_price' => $regular_price
            );
        }

        /**
         * Send WooCommerce products to Algolia
         *
         * @param Int $id Product to send to Algolia if we send only a single product
         * @return void
         */
        public static function send_categories_to_algolia($id = '')
        {
            /**
             * Remove classes from plugin URL and autoload Algolia with Composer
             */

            $base_plugin_directory = str_replace('classes', '', dirname(__FILE__));
            require_once $base_plugin_directory . '/vendor/autoload.php';

            /**
             * Fetch the required variables from the Settings API
             */

            $algolia_application_id = get_option(ALGOWOOCAT_DB_OPTION . ALGOLIA_APP_ID);
            $algolia_application_id = is_string($algolia_application_id) ? $algolia_application_id : CHANGE_ME;

            $algolia_api_key        = get_option(ALGOWOOCAT_DB_OPTION . ALGOLIA_API_KEY);
            $algolia_api_key        = is_string($algolia_api_key) ? $algolia_api_key : CHANGE_ME;

            $algolia_index_name     = get_option(ALGOWOOCAT_DB_OPTION . INDEX_NAME);
            $algolia_index_name        = is_string($algolia_index_name) ? $algolia_index_name : CHANGE_ME;

            /**
             * Display admin notice and return if not all values have been set
             */

            Algolia_Check_Requirements::check_algolia_input_values($algolia_application_id, $algolia_api_key, $algolia_index_name);

            /**
             * Initiate the Algolia client
             */
            self::$algolia = \Algolia\AlgoliaSearch\SearchClient::create($algolia_application_id, $algolia_api_key);

            /**
             * Check if we can connect, if not, handle the exception, display an error and then return
             */
            self::can_connect_to_algolia();

            /**
             * Initialize the search index and set the name to the option from the database
             */
            $index = self::$algolia->initIndex($algolia_index_name);

            /**
             * Setup arguments for sending all categories to Algolia
             *
             * Limit => -1 means we send all categories
             */
            $arguments = array(
                'status'   => 'publish',
                'limit'    => -1,
                'paginate' => false,
            );

            /**
             * Setup arguments for sending only a single category
             */
            if (isset($id) && '' !== $id) {
                $arguments = array(
                    'status'   => 'publish',
                    'include'  => array($id),
                    'paginate' => false,
                );
            }

            $categories = get_terms( ['taxonomy' => 'product_cat'] );

            if (empty($categories)) {
                return;
            }
            $records = array();
            $record  = array();

            foreach ($categories as $category) {

                $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                $image = wp_get_attachment_url( $thumbnail_id );

                $record['objectID'] = $category->term_id;
                $record['slug'] = $category->slug;
                $record['name'] = $category->name; 
                $record['image_url'] = $image;
                $records[] = $record;
            }

            wp_reset_postdata();

            /**
             * Send the information to Algolia and save the result
             * If result is NullResponse, print an error message
             */
            $result = $index->saveObjects($records);

            if ('Algolia\AlgoliaSearch\Response\NullResponse' === get_class($result)) {
                wp_die(esc_html__('No response from the server. Please check your settings and try again', 'algolia_woo_category_indexer_settings'));
            }

            /**
             * Display success message
             */
            echo '<div class="notice notice-success is-dismissible">
					 	<p>' . esc_html__('Categories sent to Algolia.', 'algolia-woo-category-indexer') . '</p>
				  		</div>';
        }
    }
}
