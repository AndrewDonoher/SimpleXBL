<?php
/**********************************************************************************
* remove_settings.php                                                             *
***********************************************************************************
* This file is a simplified installation script to modify an existing SMF         *
* database. It is used to add tables, rows or columns and also add variables to   *
* the smf_settings table. Simply follow the commented sections to find out how to *
* use this file.                                                                  *
**********************************************************************************/
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
   require_once(dirname(__FILE__) . '/SSI.php');
   db_extend('packages');
}
elseif(!defined('SMF'))
   die('<strong>Error:</strong> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

// List settings here
$oldSettings = array(
	'xbl_enable',
	'xbl_items_page',
	'xbl_required_posts',
	'xbl_user_timeout',
	'xbl_show_unranked',
	'xbl_stat_limit',
	'xbl_menu_title',
	'xbl_gtag_image_path',
	'xbl_game_image_path',
	'xbl_gtag_image_url',
	'xbl_game_image_url',
);

$hooks = array(
	'integrate_pre_include' => $sourcedir . '/SimpleXBL.php',
	'integrate_load_theme' => 'sxblLoadTheme',
	'integrate_admin_areas' => 'sxblAdminAreas',
	'integrate_menu_buttons' => 'sxblMenuButtons',
	'integrate_actions' => 'sxblActions',
);

if ( !empty( $smcFunc['db_query'] ) )
{
	$smcFunc['db_query']('', '
		DELETE FROM {db_prefix}settings
		WHERE variable IN ({array_string:settings})',
		array(
			'settings' => $oldSettings,
		)
	);
	
	// Remove hooks
	foreach ( $hooks as $hook => $function )
		remove_integration_function( $hook, $function );
}

?>