<?php
/**
 * Delete the saved options freom the database when we uninstall the plugin
 * We do not do it when we deactivate the plugin, in case we want to maintain the data
 *
 * @package         algolia-woo-indexer
 */

/*
*  If uninstall was not called from WordPress, then exit
*/
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

define( 'ALGOWOOCAT_DB_OPTION', '_algolia_woo_category_indexer' );

delete_option( ALGOWOOCAT_DB_OPTION . '_application_id' );
delete_option( ALGOWOOCAT_DB_OPTION . '_admin_api_key' );
delete_option( ALGOWOOCAT_DB_OPTION . '_index_name' );
delete_option( ALGOWOOCAT_DB_OPTION . '_index_in_stock' );
delete_option( ALGOWOOCAT_DB_OPTION . '_automatically_send_new_categories' );
