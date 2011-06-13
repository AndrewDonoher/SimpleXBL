<?php
/**********************************************************************************
* add_settings.php                                                                *
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

/* Pre-Install Check */
pre_install_check();

/* Integration Hooks */
if ( !empty( $smcFunc['db_query'] ) )
{
	$hooks = array(
		'integrate_pre_include' => $sourcedir . '/SimpleXBL.php',
		'integrate_load_theme' => 'sxblLoadTheme',
		'integrate_admin_areas' => 'sxblAdminAreas',
		'integrate_menu_buttons' => 'sxblMenuButtons',
		'integrate_actions' => 'sxblActions',
	);

	foreach ( $hooks as $key => $val )
		add_integration_function( $key, $val, true );
}

function pre_install_check()
{
	global $modSettings, $txt;

	if (version_compare(PHP_VERSION, '5.2.0', '<'))
		fatal_error('<strong>PHP 5.2 or geater is required to install SimpleXBL.  Please advise your host that PHP4 is no longer maintained and ask that they upgrade you to PHP5.</strong><br />');

	$char_set = empty($modSettings['global_character_set']) ? $txt['lang_character_set'] : $modSettings['global_character_set'];
	if ($char_set != 'ISO-8859-1' && $char_set != 'UTF-8' && !function_exists('iconv') && !function_exists('mb_convert_encoding') && !function_exists('unicode_decode'))
		fatal_error('<strong>You are currently using the ' . $char_set . ' character set and your server does not have functions available to convert to UTF-8.  In order to use this mod, you will either need to convert your board to UTF-8 or ask your host to recompile PHP with with the Iconv or Multibyte String extensions.</strong>');
}

// $modSettings variables
$sxbl_settings = array(
	'xbl_enable' => '1',
	'xbl_items_page' => '20',
	'xbl_required_posts' => '1',
	'xbl_user_timeout' => '30',
	'xbl_show_unranked' => '1',
	'xbl_stat_limit' => '5',
);
updateSettings($sxbl_settings);

// Are we done?
if (SMF == 'SSI')
   echo 'Database changes are complete!';

?>