<?php
/**********************************************************************************
* install.php                                                                     *
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
   die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

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

// xbox_leaders table
$xbox_leaders_columns[] = array('name' => 'id_member', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'gold', 'auto' => false, 'default' => 0, 'type' => 'tinyint', 'size' => 2, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'gamertag', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 30, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'avatar', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'reputation', 'auto' => false, 'default' => 0, 'type' => 'tinyint', 'size' => 2, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'gamerscore', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);
$xbox_leaders_columns[] = array('name' => 'last_played', 'auto' => false, 'default' => '', 'type' => 'text', 'size' => '', 'null' => false);
$xbox_leaders_columns[] = array('name' => 'updated', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);

$xbox_leaders_indexes[] = array('columns' => array('id_member'), 'type' => 'primary');

$smcFunc['db_create_table']('{db_prefix}xbox_leaders', $xbox_leaders_columns, $xbox_leaders_indexes, array(), 'update');

// xbox_games table
$xbox_games_columns[] = array('name' => 'id_member', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);
$xbox_games_columns[] = array('name' => 'position', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);
$xbox_games_columns[] = array('name' => 'title', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);
$xbox_games_columns[] = array('name' => 'link', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);
$xbox_games_columns[] = array('name' => 'image', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);
$xbox_games_columns[] = array('name' => 'updated', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 11, 'null' => false);

$xbox_games_indexes[] = array('columns' => array('id_member', 'title'), 'type' => 'primary');

$smcFunc['db_create_table']('{db_prefix}xbox_games', $xbox_games_columns, $xbox_games_indexes, array(), 'update');

// xbox_games_list table
//$smcFunc['db_query']('', 'IF EXISTS {db_prefix}xbox_games_list TRUNCATE {db_prefix}xbox_games_list', array());

$xbox_games_list_columns[] = array('name' => 'game_id', 'auto' => true, 'default' => '', 'type' => 'int', 'size' => 12, 'null' => false);
$xbox_games_list_columns[] = array('name' => 'tid', 'auto' => false, 'default' => 0, 'type' => 'int', 'size' => 10, 'null' => false);
$xbox_games_list_columns[] = array('name' => 'title', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);
$xbox_games_list_columns[] = array('name' => 'image', 'auto' => false, 'default' => '', 'type' => 'varchar', 'size' => 255, 'null' => false);

$xbox_games_list_indexes[] = array('columns' => array('game_id'), 'type' => 'primary');
$xbox_games_list_indexes[] = array('columns' => array('tid'), 'type' => 'unique');

$smcFunc['db_create_table']('{db_prefix}xbox_games_list', $xbox_games_list_columns, $xbox_games_list_indexes, array(), 'update');

// Scheduled Task
$smcFunc['db_insert']('replace',
	'{db_prefix}scheduled_tasks',
	array('next_time' => 'int', 'time_offset' => 'int', 'time_regularity' => 'int', 'time_unit' => 'string', 'disabled' => 'int', 'task' => 'string'),
	array(time() + 15, 0, 15, 'm', 0, 'update_gamertags'),
	array('id_task')
);

// Add a column to the members table
$smcFunc['db_add_column']('{db_prefix}members', array('name' => 'gamertag', 'type' => 'varchar', 'size' => 30, 'null' => false, 'default' => ''));


// Are we done?
if (SMF == 'SSI')
   echo 'Database changes are complete!';
?>