<?php
/**********************************************************************************
* XboxLeaders.php                                                                 *
* =============================================================================== *
* Software:                   SimpleXBL                                           *
* Software Version:           1.0.4                                               *
* Software Compatible:        SMF 2.0 RC5                                         *
* Software by:                XboxLeaders (http://xboxleaders.com)                *
* Copyright 2010-2011 by:     XboxLeaders (http://xboxleaders.com)                *
* Support, News, Updates at:  http://community.xboxleaders.com/                   *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by XboxLeaders.                  *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "simplexbl-license.txt" file for details of the XboxLeaders license.    *
* The latest version can always be found at                                       *
* http://xboxleaders.com/products/simplexbl/                                      *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

function XboxLeaders()
{
	global $context, $txt, $smcFunc, $scripturl, $sourcedir, $settings;

	// Load the template
	loadTemplate('XboxLeaders');

	$subActions = array(
		'main' => 'XboxLeaderboard',
		'delete' => 'XboxDeleteMember',
	);

	// Default to main
	if (!isset($_REQUEST['sa']) || !isset($subActions[$_REQUEST['sa']]))
		$subAction = $subActions['main'];
	else
		$subAction = $subActions[$_REQUEST['sa']];

	$subAction();
}

function XboxLeaderboard()
{
	global $context, $txt, $smcFunc, $scripturl, $sourcedir, $settings, $modSettings;

	// Load up the overlib js
	$context['html_headers'] = '
		<script type="text/javascript" src="' . $settings['default_theme_url'] . '/scripts/overlib/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>
		<script type="text/javascript">
			function xbl_setcursor(type) {
				document.getElementsByTagName(\'body\')[0].style.cursor = typeof type == \'undefined\' ? \'default\' : type;
				links = document.getElementsByTagName(\'a\');
				for(i = 0; i < links.length; i++) links.item(i).style.cursor = typeof type == \'undefined\' ? \'pointer\' : type;
			}
			function xbl_show_gamer( type, gamertag) {
				if(type == \'gamercard\') {
					xbl_setcursor(\'progress\');
					var html = \'<iframe onload="xbl_setcursor();" src="http://gamercard.xbox.com/\'+gamertag+\'.card" scrolling="no" frameBorder="0" height="135" width="200">\'+gamertag+\'</iframe>\';
					return overlib(html, BORDER, 0, FULLHTML, WIDTH, 204, HEIGHT, 140, VAUTO, HAUTO);
				}

				if(type == \'nxeavatar\') {
					xbl_setcursor(\'progress\');
					var html = \'<iframe onload="xbl_setcursor();" src="http://avatar.xboxlive.com/avatar/\'+gamertag+\'/avatar-body.png" scrolling="no" frameBorder="0" height="300" width="150">\'+gamertag+\'</iframe>\';
					return overlib(html, BORDER, 0, FULLHTML, WIDTH, 150, HEIGHT, 300, VAUTO, HAUTO);
				}
			}
			function xbl_hide() {
				return nd();
			}
		</script>';

	// Make sure we're allowed to be here
	isAllowedTo('xbl_access_lb');

	$listOptions = array(
		'id' => 'xbl_leaders',
		'title' => $context['forum_name'] . '\'s ' . $txt['xbox_leaders'],
		'base_href' => $scripturl . '?action=xboxleaders',
		'items_per_page' => !empty($modSettings['xbl_items_page']) ? $modSettings['xbl_items_page'] : 20,
		'default_sort_col' => 'gamerscore',
		'default_sort_dir' => 'desc',
		'no_items_label' => $txt['xbl_no_data'],
		'no_items_align' => 'center',
		'get_items' => array(
			'file' => $sourcedir . '/Subs-XboxLeaders.php',
			'function' => 'list_getXboxMembers',
		),
		'get_count' => array(
			'file' => $sourcedir . '/Subs-XboxLeaders.php',
			'function' => 'list_getNumXboxMembers',
		),
		'columns' => array(
			'member' => array(
				'header' => array(
					'value' => $txt['xbl_header_member'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . strtr($scripturl, array('%' => '%%')) . '?action=profile;u=%1$d">%2$s</a>',
						'params' => array(
							'id_member' => false,
							'real_name' => false,
						),
					),
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'real_name',
					'reverse' => 'real_name DESC',
				),
			),
			'gamertag' => array(
				'header' => array(
					'value' => $txt['xbl_header_gamertag'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						$gamertag = \'<a href="http://live.xbox.com/member/\' . str_replace(\' \', \'%20\', $rowData[\'gamertag\']) . \'">\' . $rowData[\'gamertag\'] . \'</a>\';
						return $gamertag;
					'),
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'gamertag',
					'reverse' => 'gamertag DESC',
				),
			),
			'avatar' => array(
				'header' => array(
					'value' => $txt['xbl_header_avatar'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a target="_blank" href="%1$s" onmouseover="xbl_show_gamer(\'gamercard\',\'%2$s\');" onmouseout="xbl_hide();">
								<img height="32" width="32" src="%s" alt="" /> 
							</a> 
							<a target="_blank" href="#" onmouseover="xbl_show_gamer(\'nxeavatar\',\'%2$s\');" onmouseout="xbl_hide();">
									<img height="32" width="32" src="%1$s" alt="" />
							</a>',
						'params' => array(
							'avatar' => false,
							'gamertag' => false,
						),
					),
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'avatar',
					'reverse' => 'avatar DESC',
				),
			),
			'gamerscore' => array(
				'header' => array(
					'value' => $txt['xbl_header_gamerscore'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $settings;
						$gamerscore = \'<img src="\' . $settings[\'images_url\'] . \'/xbl/gs.png" height="10" width="10" alt="" /> \' . comma_format($rowData[\'gamerscore\']);
						return $gamerscore;
					'),
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'gamerscore',
					'reverse' => 'gamerscore DESC',
				),
			),
			'reputation' => array(
				'header' => array(
					'value' => $txt['xbl_header_reputation'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<img src="' . $settings['images_url'] . '/xbl/%1$s.png" alt="" title="' . $txt['xbl_header_reputation'] . ': %1$s" />',
						'params' => array(
							'reputation' => false,
						),
					),
					'style' => 'text-align: center',
				),
				'sort' => array(
					'default' => 'reputation',
					'reverse' => 'reputation DESC',
				),
			),
			'last_played' => array(
				'header' => array(
					'value' => $txt['xbl_header_lastplayed'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						$player = unserialize($rowData[\'last_played\']);
						$games = \'\';
						if (empty($player))
							$games .= $txt[\'xbl_privacy_settings\'];
						else
						{
							foreach ($player as $game)
								$games .= \'<a target="_blank" href="\' . $game[\'link\'] . \'"><img height="32" width="32" style="border: 1px black solid;" src="\' . $game[\'image\'] . \'" alt="" title="\' . $game[\'title\'] . \'" /></a> \';
						}
						return $games;
					'),
					'style' => 'text-align: center',
				),
			),
			'contact' => array(
				'header' => array(
					'value' => $txt['xbl_header_contact'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl, $settings, $user_info, $txt, $context;

						$buttons = \'<a target="_blank" href="http://live.xbox.com/en-US/MyXbox/Profile?gamertag=\' . $rowData[\'gamertag\'] . \'" title="\' . $txt[\'xbl_view_profile\'] . \'">
								<img src="\' . $settings[\'images_url\'] . \'/xbl/user.png" alt="" />
							</a> <a target="_blank" href="http://live.xbox.com/en-US/MessageCenter/Compose?gamertag=\' . $rowData[\'gamertag\'] . \'" title="\' . $txt[\'xbl_send_msg\'] . \'">
								<img src="\' . $settings[\'images_url\'] . \'/xbl/message.png" alt="" />
							</a> \' . ($user_info[\'is_admin\'] ? \'<a href="\' . $scripturl . \'?action=xboxleaders;sa=delete;id=\' . $rowData[\'id_member\'] . \';\' . $context[\'session_var\'] . \'=\' . $context[\'session_id\'] . \'" title="\' . $txt[\'xbl_delete\'] . \'">
								<img src="\' . $settings[\'images_url\'] . \'/xbl/delete.png" alt="" />
							</a>\' : \'\');

						return $buttons;
					'),
					'style' => 'text-align: center',
				),
			),
		),
		'additional_rows' => array(
			array(
				'position' => 'above_column_headers',
				'value' => '<a href="' . $scripturl . '?action=profile;area=forumprofile" title="' . $txt['xbl_add_gamertag'] . '">
					<img src="' . $settings['images_url'] . '/xbl/add.png" alt="" /> <strong>' . $txt['xbl_add_gamertag'] . '</strong></a>',
			),
		),
	);

	// Make the list!
	require_once($sourcedir . '/Subs-List.php');
	createList($listOptions);

	$context['page_title'] = $txt['xbox_leaders'];
	$context['sub_template'] = 'leaderboard';
}

function XboxDeleteMember()
{
	global $sourcedir;

	checkSession('request');

	require_once($sourcedir . '/Subs-XboxLeaders.php');

	if (isset($_REQUEST['id']))
		xblDeleteMember((int) $_REQUEST['id']);

	redirectexit('action=xboxleaders');
}

?>