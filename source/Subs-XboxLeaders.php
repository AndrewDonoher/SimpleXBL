<?php
/**********************************************************************************
* Subs-XboxLeaders.php                                                            *
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

$context['xbl_version'] = '1.0.4';

// !!! This function is important!
// This cleans the nasties out of the game titles
function clean_string($string)
{
	global $context;

	if ($context['character_set'] === 'UTF-8')
		return $string;
	elseif (!function_exists('iconv'))
		trigger_error('iconv(): Function does not exist and is required.', E_USER_ERROR);
	else
	{
		$clean = iconv('UTF-8', !empty($context['character_set']) ? $context['character_set'] . '//IGNORE' : 'ISO-8859-1//IGNORE', $string);
		return $clean;
	}
}

// This function grabs all the data from the Xbox.com website and makes it all nice and pretty for later use.
function getXboxLiveData($string, $member)
{
	global $sourcedir, $context, $modSettings, $scripturl;

	$player['existing'] = $member;

	// Now we need to extract the necessary data
	$data = json_decode($string, true);

	if (!is_array($data) || $data === false)
	{
		log_error('getXboxLiveData(): Unable to retrieve gamercard information.', 'general');
		return false;
	}

	else
	{
		/**
		 *	1. Forum Member ID
		 *	2. Account Status
		 *	3. Gender
		 *	4. Profile Link
		 *	5. Gamertag
		 *	6. Avatar
		 *	7. Reputation
		 *	8. Gamerscore
		 *	9. Location
		 *	10. Motto
		 *	11. Name
		 *	12. Bio
		 *	13. Recent Games
		*/
		$player['id']				= $player['existing']['id_member'];
		$player['account_status']	= (string)$data['user']['account_status'];
		$player['gender']			= (string)$data['user']['gender'];
		$player['is_cheater']		= (int)$data['user']['is_cheater'];
		$player['link']				= (string)$data['user']['profile_link'];
		$player['gamertag']			= (string)$data['user']['gamertag'];
		$player['avatar']			= (string)!xblSaveImage($data['user']['avatar'], null, $data['user']['gamertag']) ? $data['user']['avatars']['gamer_tile'] : $modSettings['xbl_gtag_image_url'] . '/' . str_replace(' ', '%20', $data['user']['gamertag']) . '.png';
		$player['reputation']		= (int)$data['user']['reputation'];
		$player['gamerscore']		= (int)$data['user']['gamerscore'];
		$player['location']			= (string)$data['user']['location'];
		$player['motto']			= (string)$data['user']['motto'];
		$player['name']				= (string)$data['user']['name'];
		$player['bio']				= (string)$data['user']['bio'];

		xblSaveImage($player['avatar'], null, $player['gamertag']);

		// We need to do something special to get the games
		if (!empty($data['user']['recent_games']))
		{
			$player['games'] = array();

			foreach ($data['user']['recent_games'] as $key => $val)
			{
				$val['tid']											= getGameTid($val['link']);
				$val['last_played']									= strtotime($val['last_played']);

				$player['games'][$key]['tid']						= (int)$val['tid'];
				$player['games'][$key]['link']						= (string)$scripturl . '?action=xboxleaders;sa=games;id=' . $val['tid'];
				$player['games'][$key]['image']						= !xblSaveImage($val['tile'], $val['tid'], null) ? $val['tile'] : $modSettings['xbl_game_image_url'] . '/' . $val['tid'] . '.png';
				$player['games'][$key]['title']						= (string)$val['title'];
				$player['games'][$key]['last_played']				= (int)$val['last_played'];
				$player['games'][$key]['earned_gamerscore']			= (int)$val['earned_gamerscore'];
				$player['games'][$key]['available_gamerscore']		= (int)$val['available_gamerscore'];
				$player['games'][$key]['earned_achievements']		= (int)$val['earned_achievements'];
				$player['games'][$key]['available_achievements']	= (int)$val['available_achievements'];
				$player['games'][$key]['percentage_complete']		= (int)$val['percentage_complete'];
			}
				
			$player['lastplayed'] = serialize($player['games']);
		}
		else
		{
			$player['games'] = false;
			$player['lastplayed'] = false;
		}

		return $player;
	}
}

// Update the xbox_leaders table with new data
function xblUpdateMember($player)
{
	global $context, $smcFunc;

	// Make sure we have a valid gamertag
	$player_exists = $player['is_valid'] === 1 ? true : false;

	// OK, so he exists. Now what?
	if ($player_exists)
	{
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}xbox_leaders
			SET
				gold = {int:gold},
				gamertag = {string:gamertag},
				avatar = {string:avatar},
				reputation = {string:reputation},
				gamerscore = {string:gamerscore},
				updated = {int:updated}
			WHERE id_member = {int:member}',
			array(
				'member'			=> $player['id'],
				'account_status'	=> $player['account_status'],
				'gamertag'			=> $player['gamertag'],
				'avatar'			=> $player['avatar'],
				'reputation'		=> $player['reputation'],
				'gamerscore'		=> $player['gamerscore'],
				'updated'			=> time(),
			)
		);

		// If there are games to insert, do it
		if ($player['games'] && $player['lastplayed'])
		{
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}xbox_leaders
				SET last_played = {string:lastplayed}
				WHERE id_member = {int:member}',
				array(
					'lastplayed'	=> $player['lastplayed'],
					'member'		=> $player['id'],
				)
			);

			// Remove the games before we update it
			@$smcFunc['db_query']('', '
				DELETE FROM {db_prefix}xbox_games
				WHERE id_member = {int:id_member}',
				array(
					'id_member' => $player['id'],
				)
			);

			// Update the games list too!
			foreach ($player['games'] as $key => $game)
			{
				$smcFunc['db_insert']('ignore',
					'{db_prefix}xbox_games',
					array(
						'id_member' 	=> 'int',
						'position' 		=> 'int',
						'title' 		=> 'string',
						'link' 			=> 'string',
						'image' 		=> 'string',
						'updated' 		=> 'int',
					),
					array(
						$player['id'],
						$key,
						clean_string($game['title']),
						$game['link'],
						$game['image'],
						time(),
					),
					array('position')
				);

				// Might as well update the archive
				$smcFunc['db_insert']('ignore',
					'{db_prefix}xbox_games_list',
					array(
						'tid' => 'string',
						'title' => 'string',
						'image' => 'string',
					),
					array(
						getGameTid($game['link']),
						$game['title'],
						$game['image'],
					),
					array('tid')
				);
			}
		}
	}

	return true;
}

// Remove a member and all their data
function xblDeleteMember($member)
{
	global $context, $smcFunc;

	if (is_numeric($member))
	{
		// Remove them from the leaders table
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}xbox_leaders
			WHERE id_member = {int:member}',
			array(
				'member' => $member,
			)
		);

		// Also remove them from the games table
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}xbox_games
			WHERE id_member = {int:member}',
			array(
				'member' => $member,
			)
		);

		// Might as well remove from the members table too
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}members
			SET gamertag = \'\'
			WHERE id_member = {int:member}',
			array(
				'member' => $member,
			)
		);
	}
	else
		return false;

	return true;
}

// Saves an image to the cache
function xblSaveImage($url, $tid = null, $gamertag = null)
{
	global $modSettings, $settings, $scripturl;

	if ($tid != null)
	{
		$im = @imagecreatefromjpeg($url);
		imagepng($im, $modSettings['xbl_game_image_path'] . '/' . $tid . '.png', 8);
		imagedestroy($im);
	}
	elseif ($gamertag != null)
	{
		$im = @imagecreatefrompng($url);
		imagepng($im, $modSettings['xbl_gtag_image_path'] . '/' . $gamertag . '.png');
		imagedestroy($im);
	}
	else
		return false;
}

// Load data for a given gamer
function xblLoadGamerData($mid)
{
	global $smcFunc, $modSettings, $settings, $txt;

	$request = $smcFunc['db_query']('', '
		SELECT xbl.*, xbg.*
		FROM {db_prefix}xbox_leaders AS xbl
			LEFT JOIN {db_prefix}xbox_games AS xbg ON (xbg.id_member = xbl.id_member)
		WHERE xbl.id_member = {int:id_member}
		ORDER BY xbg.last_played DESC',
		array(
			'id_member' => $mid
		)
	);
	$gamer_data = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$gamer_data[] = array(
			'id' => $row['id_member'],
			'gamertag' => $row['gamertag'],
			'gamerscore' => $row['gamerscore'],
			'reputation' => '<img src="' . $settings['images_url'] . '/xbl/' . $row['reputation'] . '.png" alt="' . $row['reputation'] . '" title="' . $row['reputation'] . '" />',
			'gold' => $row['account_status'],
			'zone' => 'N/A',
			'avatar' => '<img src="' . $modSettings['xbl_gtag_image_dir'] . '/' . $row['gamertag'] . '.png" width="32px" height="32px" alt="" />',
			'location' => '<a href="http://maps.google.com/maps?q=' . urlencode(strtolower($row['location'])) . '">' . $row['location'] . '</a>',
			'motto' => $row['motto'],
			'name' => $row['name'],
			'bio' => parse_bbc($row['bio'])
		);

		$gamer_data['games'][$row['tid']] = array(
			'tid' => $row['tid'],
			'title' => $row['title'],
			'tile' => $modSettings['xbl_game_image_dir'] . '/' . $row['tid'] . '.png',
			'egscore' => $row['earned_gamerscore'],
			'agscore' => $row['available_gamerscore'],
			'echeevo' => $row['earned_achievements'],
			'acheevo' => $row['available_achievements'],
			'per_com' => $row['percentage_complete'],
			'last_played' => date('F j, Y', $row['last_played'])
		);
	}
	$smcFunc['db_free_result']($request);

	return $gamer_data;
}

// Load up all of a members data, for post display maybe?
function getXboxMemberData($member)
{
	global $smcFunc;

	$request = $smcFunc['db_query']('', '
		SELECT
			xbl.*
		FROM {db_prefix}members AS mem
		WHERE id_member = {int:member}',
		array(
			'member' => $member,
		)
	);
	$member = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$member[] = $row;
	$smcFunc['db_free_result']($request);

	return $member;
}

// Get the game's unique ID from the Xbox site
function getGameTid($string)
{
	$tid = parse_url($string);
	$tid = explode('&', html_entity_decode($tid['query']));
	$tid = explode('=', $tid['0']);
	
	return $tid['1'];
}

// Get some nice stats going
function getXboxStatCount()
{
	global $smcFunc, $modSettings;

	// Overall
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(mem.gamertag) AS usercount,
			SUM(xbl.gamerscore) AS gamerscore,
			SUM(xbl.reputation) AS reputation,
			SUM(xbl.account_status) AS gold
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (mem.id_member = xbl.id_member)
		WHERE mem.gamertag != \'\'
			AND mem.posts >= {int:required_posts}
			AND mem.last_login >= {int:user_timeout}
			AND xbl.gamerscore >= {int:show_unranked}',
		array(
			'required_posts' => !empty($modSettings['xbl_required_posts']) ? $modSettings['xbl_required_posts'] : 0,
			'user_timeout' => time() - ($modSettings['xbl_user_timeout'] * 86400),
			'show_unranked' => !empty($modSettings['xbl_show_unranked']) ? 0 : 1,
		)
	);
	$count = array();
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$count['members'] 			= comma_format($row['usercount']);
	$count['score'] 			= comma_format($row['gamerscore']);
	$count['reputation'] 		= $row['reputation'] != 0 ? ceil($row['reputation'] / $row['usercount']) : 0;
	$count['silver'] 			= comma_format($row['usercount'] - $row['gold']);
	$count['gold'] 				= comma_format($row['gold']);

	// Games
	$request = $smcFunc['db_query']('', '
		SELECT COUNT(DISTINCT title) AS gamescount
		FROM {db_prefix}xbox_games AS xbg
			LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (xbg.id_member = xbl.id_member)',
		array()
	);
	$row = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$count['gamescount'] 		= comma_format($row['gamescount']);

	return $count;
}

// Top Avatars
function getXblTopAvatars()
{
	global $smcFunc, $modSettings;

	$filtered_avatars = array('http://tiles.xbox.com/tiles/8y/ov/0Wdsb2JhbC9EClZWVEoAGAFdL3RpbGUvMC8yMDAwMAAAAAAAAAD+ACrT.jpg','/xweb/lib/images/QuestionMark64x64.jpg');

	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.real_name, mem.posts, mem.last_login,
			xbl.avatar, xbl.gamerscore, COUNT(*) AS count
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (mem.id_member = xbl.id_member)
		WHERE mem.gamertag != \'\'
			AND mem.posts >= {int:required_posts}
			AND mem.last_login >= {int:user_timeout}
			AND xbl.gamerscore >= {int:show_unranked}
			AND xbl.avatar NOT IN ({string:exclude})
		GROUP BY xbl.avatar
		ORDER BY
			count DESC,
			xbl.gamerscore ASC
		LIMIT {int:limit}',
		array(
			'required_posts' => !empty($modSettings['xbl_required_posts']) ? $modSettings['xbl_required_posts'] : 0,
			'user_timeout' => time() - ($modSettings['xbl_user_timeout'] * 86400),
			'show_unranked' => !empty($modSettings['xbl_show_unranked']) ? 0 : 1,
			'exclude' => implode('\', \'', array_values($filtered_avatars)),
			'limit' => !empty($modSettings['xbl_stats_limit']) ? $modSettings['xbl_stats_limit'] : 5,
		)
	);
	$avatars = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$avatars[] = $row;
	$smcFunc['db_free_result']($request);

	return $avatars;
}

// Top Players
function getXblTopPlayers()
{
	global $smcFunc, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT
			mem.id_member, mem.real_name, mem.posts, mem.last_login,
			xbl.gamertag, xbl.gamerscore, COUNT(*) AS count
		FROM {db_prefix}members AS mem
			LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (mem.id_member = xbl.id_member)
		WHERE mem.gamertag != \'\'
			AND mem.posts >= {int:required_posts}
			AND mem.last_login >= {int:user_timeout}
			AND xbl.gamerscore >= {int:show_unranked}
		GROUP BY mem.id_member
		ORDER BY
			xbl.gamerscore DESC,
			xbl.gamertag ASC
		LIMIT {int:limit}',
		array(
			'required_posts' => !empty($modSettings['xbl_required_posts']) ? $modSettings['xbl_required_posts'] : 0,
			'user_timeout' => time() - ($modSettings['xbl_user_timeout'] * 86400),
			'show_unranked' => !empty($modSettings['xbl_show_unranked']) ? 0 : 1,
			'limit' => !empty($modSettings['xbl_stats_limit']) ? $modSettings['xbl_stats_limit'] : 5,
		)
	);
	$players = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$players[] = $row;
	$smcFunc['db_free_result']($request);

	return $players;
}

function getXblTopGames()
{
	global $smcFunc, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT xbg.title, xbg.link, xbg.image,
			COUNT(*) AS count
		FROM {db_prefix}xbox_games AS xbg
			LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (xbg.id_member = xbl.id_member)
		WHERE xbl.gamerscore >= {int:show_unranked}
		GROUP BY title
		ORDER BY 
			count DESC,
			title ASC
		LIMIT {int:limit}',
		array(
			'show_unranked' => !empty($modSettings['xbl_show_unranked']) ? 0 : 1,
			'limit' => !empty($modSettings['xbl_stats_limit']) ? $modSettings['xbl_stats_limit'] : 5,
		)
	);
	$games = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$games[] = array(
			'link' => preg_replace('~&amp;compareTo=(.*)~i', '', $row['link']),
			'title' => $row['title'],
			'image' => $row['image'],
			'count' => $row['count'],
		);
	}
	$smcFunc['db_free_result']($request);

	return $games;
}

function list_getXboxMembers($start, $items_per_page, $sort)
{
	global $smcFunc, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT
			xbl.id_member, mem.id_member, mem.real_name, mem.posts,
			mem.last_login, xbl.account_status, xbl.gamertag, xbl.avatar,
			xbl.reputation, xbl.gamerscore, xbl.last_played, xbl.updated
		FROM {db_prefix}xbox_leaders AS xbl
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = xbl.id_member)
		WHERE mem.gamertag != \'\'
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'per_page' => $items_per_page,
		)
	);

	$members = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
		$members[] = $row;
	$smcFunc['db_free_result']($request);

	return $members;
}

function list_getNumXboxMembers()
{
	global $smcFunc, $modSettings;

	$request = $smcFunc['db_query']('', '
		SELECT COUNT(*)
		FROM {db_prefix}xbox_leaders AS xbl
			LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = xbl.id_member)
		WHERE mem.gamertag != \'\'',
		array(
		)
	);
	list ($num_members) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	return $num_members;
}

?>