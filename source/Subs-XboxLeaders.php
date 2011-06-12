<?php
/**********************************************************************************
* Subs-XboxLeaders.php                                                            *
* =============================================================================== *
* Software:                   SimpleXBL                                           *
* Software Version:           1.0.4                                               *
* Software Compatible:        SMF 2.0 RC5                                         *
* Software by:                XboxLeaders (http://xboxleaders.com)                *
* Copyright 2010-2011 by:     XboxLeaders (http://xboxleaders.com)                *
* Support, News, Updates at:  http://xboxleaders.com/community/                   *
***********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by XboxLeaders.                  *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "simplexbl-license.txt" file for details of the XboxLeaders license.    *
* The latest version can always be found at http://xboxleaders.com/download/.     *
**********************************************************************************/

if (!defined('SMF'))
	die('Hacking attempt...');

$context['xbl_version'] = '1.0.4';

// !!! This function is important!
// This cleans the nasties out of the game titles
function clean_string($string)
{
	global $context;

	if (!function_exists('iconv'))
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
	global $sourcedir, $context;

	$player['existing'] = $member;

	// Now we need to extract the necessary data
	preg_match_all('~<div class="XbcGamercard">.*?<div class="Header">.*?<div class="Gamertag">.*?<a href=".*?">.*?<span class="(.*?)">(.*?)</span>.*?</a>.*?</div>.*?</div>.*?<div class="Body">.*?<a href=".*?">.*?<img class="GamerPic" width="64" height="64" src="(.*?)" alt=".*?" title=".*?"/>.*?</a>.*?<div class="Stats">.*?<div style="height:30px; line-height:30px;">.*?<div class="Stat">Rep</div>.*?<div class="Star (.*?)"></div>.*?<div class="Star (.*?)"></div>.*?<div class="Star (.*?)"></div>.*?<div class="Star (.*?)"></div>.*?<div class="Star (.*?)"></div>.*?</div>.*?<div style="height:30px; line-height:30px;">.*?<div class="Stat">.*?<div class="GSIcon"></div>.*?<div class="Stat">(.*?)</div>.*?</div>.*?</div>.*?</div>.*?</div>.*?<div class="Footer">(.*?)</div>.*?</div>~si', $string, $matches, PREG_SET_ORDER);

	/**
	 *	1. Account Status: Silver/Gold
	 *	2. Gamertag
	 *	3. Avatar
	 *	4. Reputation1
	 *	5. Reputation2
	 *	6. Reputation3
	 *	7. Reputation4
	 *	8. Reputation5
	 *	9. Gamerscore
	 *	10. Games Played
	 */

	if (!isset($matches[0]))
	{
		log_error('getXboxLiveData(): Unable to retrieve gamercard information.', 'general');
		return false;
	}
	elseif (trim($matches[0][2] != ''))
	{
		$r = array(
			'empty' => 0,
			'quarter' => 1,
			'half' => 2,
			'threequarter' => 3,
			'full' => 4,
		);
		$rep = array();
		$rep[1] = strtolower($matches[0][4]);
		$rep[2] = strtolower($matches[0][5]);
		$rep[3] = strtolower($matches[0][6]);
		$rep[4] = strtolower($matches[0][7]);
		$rep[5] = strtolower($matches[0][8]);

		$player['id']					= $player['existing']['id_member'];
		$player['gold']					= $matches[0][1] == 'Gold' ? 1 : 0;
		$player['gamertag']				= urldecode($matches[0][2]);
		$player['avatar']				= $matches[0][3];
		$player['gamerscore']			= intval($matches[0][9]);
		$player['reputation']			= $r[$rep[1]] + $r[$rep[2]] + $r[$rep[3]] + $r[$rep[4]] + $r[$rep[5]];

		// We need to do something special to get the games
		if ($matches[0][10])
		{
			preg_match_all('~<a href="(.*?)">.*?<img class="Game" width="32" height="32" src="(.*?)" alt="(.*?)" title=".*?" />.*?</a>~si', $matches[0][10], $lastplayed, PREG_SET_ORDER);

			$player['games'] = array();

			if (is_array($lastplayed))
			{
				foreach ($lastplayed as $key => $val)
				{
					$player['games'][$key]['title'] 	= clean_string($val[3]);
					$player['games'][$key]['link'] 		= 'http://gamercard.xbox.com' . $val[1];
					$player['games'][$key]['image'] 	= $val[2];
				}
					
				$player['lastplayed'] = serialize($player['games']);
			}
			else
			{
				$player['games'] = false;
				$player['lastplayed'] = false;
			}
		}

		return $player;
	}
	else
		return false;
}

// Update the xbox_leaders table with new data
function xblUpdateMember($player)
{
	global $context, $smcFunc;

	// Make sure we have a valid gamertag
	if ($player['avatar'] != '/xweb/lib/images/QuestionMark64x64.jpg')
		$player_exists = true;
	else
		$player_exists = false;

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
				'gold'				=> $player['gold'],
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
			SUM(xbl.gold) AS gold
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
			position ASC,
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
			mem.last_login, xbl.gold, xbl.gamertag, xbl.avatar,
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