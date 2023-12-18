<?php
/**
 * Plugin Name:     Algolia WooCommerce Category Indexer
 * Description:     Index WooCommerce product categories into Algolia
 * Text Domain:     algolia-woo-category-indexer
 * Author:          BPT
 * Requires at least: 6.0
 * Tested up to: 6.2.0
 * Requires PHP: 8.1
 * WC requires at least: 7.0.0
 * WC tested up to: 7.4.0
 * Version:         1.0.7
 *
 * @package         algolia-woo-category-indexer
 * @license         GNU version 3
 */

/**
 * Abort if this file is called directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class file
 */
require_once plugin_dir_path( __FILE__ ) . '/classes/class-algolia-woo-category-indexer.php';

/**
 * Class for checking plugin requirements
 */
require_once plugin_dir_path( __FILE__ ) . '/classes/class-check-requirements.php';

/**
 * Class for verifying nonces
 */
require_once plugin_dir_path( __FILE__ ) . '/classes/class-verify-nonces.php';

/**
 * Class for sending products
 */
require_once plugin_dir_path( __FILE__ ) . '/classes/class-send-categories.php';

$algowooindexer = \AlgowooCats\Algolia_Woo_Category_Indexer::get_instance();

register_activation_hook( __FILE__, array( $algowooindexer, 'activate_plugin' ) );
register_deactivation_hook( __FILE__, array( $algowooindexer, 'deactivate_plugin' ) );
