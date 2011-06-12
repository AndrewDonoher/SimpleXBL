<?php
/**
 * SimpleXBL
 *
 * @package SMF
 * @author Jason Clemons <jclemons@jblaze.net>
 * @file SimpleXBL.php
 * @copyright 2011 XboxLeaders <http://xboxleaders.com/>
 * @license MIT <http://xboxleaders.com/license/>
 *
 * @version 1.0.5
 */

if (!defined('SMF'))
	die('Hacking attempt...');

class SimpleXBL
{
	/**
	 * @var string App version
	 */
	public static $version = '1.0.5';

	/**
	 * The main function for SimpleXBL
	 * Loads all necessary subactions and templates
	 *
	 * @global array $context
	 * @return void
	 */
	public function __construct()
	{
		global $context;

		$subActions = array(
			'main' => 'self::leaderboard',
			'delete' => 'self::deleteMember',
		);

		if ( !isset( $_REQUEST['sa'] ) || !isset( $subActions[$_REQUEST['sa']] ) )
			$subAction = $subActions['main'];
		else
			$subAction = $subActions[$_REQUEST['sa']];

		$subAction();
	}

	/**
	 * Implements integrate_menu_buttons
	 * Adds some SimpleXBL settings to the main menu under the admin menu
	 *
	 * @global string $scripturl
	 * @global array $txt
	 * @global array $modSettings
	 * @param array $menu_buttons Array of menu buttons, post processed
	 * @return void
	 */
	public static function menuButtons( &$menu_buttons )
	{
		global $scripturl, $txt, $modSettings;

		$buttons['simplexbl'] = array(
			'title' => $txt['simplexbl'],
			'href' => $scripturl . '?action=simplexbl',
			'show' => allowedTo( 'xbl_access_lb' ),
			'sub_buttons' => array(
			),
		);

		$menu_buttons = array_merge( $menu_buttons, $buttons );
	}

	/**
	 * Implements integrate_admin_areas
	 * Adds SimpleXBL options to the admin panel
	 *
	 * @global array $txt
	 * @global array $modSettings
	 * @param array $admin_areas
	 */
	public static function adminAreas( &$admin_areas )
	{
		global $txt, $modSettings;

		// We insert it after Features and Options
		$counter = 0;
		foreach ( $admin_areas['config']['areas'] as $area => $dummy )
			if ( ++$counter && $area == 'featuresettings' )
				break;

		$admin_areas['config']['areas'] = array_merge(
			array_slice( $admin_areas['config']['areas'], 0, $counter, TRUE ),
			array( 'simplexbl' => array(
				'label' => $txt['simplexbl'],
				'function' => create_function( NULL, 'SimpleXBL::ModifySimpleXBLSettings();' ),
				'icon' => 'search.gif',
				'subsections' => array(
				),
			) ),
			array_slice( $admin_areas['config']['areas'], $counter, NULL, TRUE )
		);
	}

	/**
	 * Sets the simplexbl action
	 *
	 * @param array &$actionArray
	 */
	public static function actions( &$actionArray )
	{
		$actionArray['simplexbl'] = array( 'SimpleXBL.php', 'SimpleXBL::__construct' );
	}

	/**
	 * Directs the admin to the proper page of settings for SimpleXBL
	 *
	 * @global array $txt
	 * @global array $context
	 * @global string $sourcedir
	 */
	public static function ModifySimpleXBLSettings()
	{
		global $txt, $context, $sourcedir;

		require_once($sourcedir . '/ManageSettings.php');

		$context['page_title'] = $txt['simplexbl'];

		$subActions = array(
			'basic' => array( 'SimpleXBL', 'ModifyBasicSettings' ),
		);

		loadGeneralSettingParameters( $subActions, 'basic' );

		// Load up all the tabs...
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['simpelxbl'],
			'description' => $txt['simplexbl_desc'],
			'tabs' => array(
				'basic' => array(
				),
			),
		);

		call_user_func( $subActions[$_REQUEST['sa']] );
	}

	/**
	 * Modify SimpleXBL-related settings
	 *
	 * @global array $txt
	 * @global string $scripturl
	 * @global array $context
	 * @global array $modSettings
	 * @global array $sourcedir
	 * @param bool $return_config
	 * @return void
	 */
	public static function ModifyBasicSettings( $return_config = false )
	{
		global $txt, $scripturl, $context, $modSettings, $sourcedir;

		require_once( $sourcedir . '/ManageSettings.php' );

		isAllowedTo( 'xbl_admin' );

		$config_vars = array(
			array( 'check', 'xbl_enable' ),
			array( 'int', 'xbl_items_page', 'subtext' => $txt['xbl_items_page_sub'] ),
			array( 'int', 'xbl_required_posts', 'subtext' => $txt['xbl_required_posts_sub'] ),
			array( 'int', 'xbl_user_timeout', 'subtext' => $txt['xbl_user_timeout_sub'] ),
			array( 'check', 'xbl_show_unranked', 'subtext' => $txt['xbl_show_unranked_sub'] ),
			array( 'int', 'xbl_stat_limit', 'subtext' => $txt['xbl_stat_limit_sub'] ),
		'',
			array( 'text', 'xbl_gtag_image_path', 'subtext' => $txt['xbl_gtag_image_path_sub'] ),
			array( 'text', 'xbl_game_image_path', 'subtext' => $txt['xbl_game_image_path_sub'] ),
			array( 'text', 'xbl_gtag_image_url', 'subtext' => $txt['xbl_gtag_image_url_sub'] ),
			array( 'text', 'xbl_game_image_url', 'subtext' => $txt['xbl_game_image_url_sub'] ),
		);

		if ( $return_config )
			return $config_vars;

		$context['post_url'] = $scripturl . '?action=admin;area=simplexbl;save';
		$context['settings_title'] = $txt['mods_cat_modifications_misc'];

		if ( empty( $config_vars ) )
		{
			$context['settings_save_dont_show'] = true;
			$context['settings_message'] = '<div class="centertext">' . $txt['modification_no_misc_settings'] . '</div>';

			return prepareDBSettingContext($config_vars);
		}

		if ( isset( $_GET['save'] ) )
		{
			checkSession();
			$save_vars = $config_vars;
			saveDBSettings( $save_vars );
			redirectexit( 'action=admin;area=simplexbl' );
		}
		prepareDBSettingContext( $config_vars );
	}

	/**
	 * Loads our necessary language files
	 *
	 * @return void
	 */
	public static function loadTheme()
	{
		loadLanguage( 'SimpleXBL' );
	}

	/**
	 * Loads up the main leaderboard
	 *
	 * @global array $context
	 * @global array $txt
	 * @global string $scripturl
	 * @global string $sourcedir
	 * @global array $settings
	 * @global array $modSettings
	 * @return void
	 */
	public static function leaderboard()
	{
		global $context, $txt, $scripturl, $sourcedir, $settings, $modSettings;

		isAllowedTo( 'xbl_access_lb' );
		loadTemplate( 'SimpleXBL' );

		/* Stats */
		$context['xbl_stats_basic'] = self::stats_basic();
		$context['xbl_stats_avatars'] = self::stats_top_avatars();
		$context['xbl_stats_players'] = self::stats_top_players();
		$context['xbl_stats_games'] = self::stats_top_games();

		$listOptions = array(
			'id' => 'xbl_leaders',
			'title' => $context['forum_name'] . '\'s ' . $txt['xbox_leaders'],
			'base_href' => $scripturl . '?action=xbox',
			'items_per_page' => !empty($modSettings['xbl_items_page']) ? $modSettings['xbl_items_page'] : 20,
			'default_sort_col' => 'gamerscore',
			'default_sort_dir' => 'desc',
			'no_items_label' => $txt['xbl_no_data'],
			'no_items_align' => 'center',
			'get_items' => array(
				'file' => $sourcedir . '/Subs-SimpleXBL.php',
				'function' => 'list_getXboxMembers',
			),
			'get_count' => array(
				'file' => $sourcedir . '/Subs-SimpleXBL.php',
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
							'format' => '<a target="_blank" href="%1$s">
									<img height="32" width="32" src="%s" alt="" /> 
								</a> 
								<a target="_blank" href="#">
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
		require_once( $sourcedir . '/Subs-List.php' );
		createList( $listOptions );

		$context['page_title'] = $txt['simplexbl'];
		$context['sub_template'] = 'leaderboard';
	}

	/**
	 * Remove a member from the leaderboard and entire app
	 *
	 * @global string $sourcedir
	 * @return void
	 */
	public static function deleteMember()
	{
		global $sourcedir;

		loadTemplate( 'SimpleXBL' );

		checkSession( 'request' );

		if ( isset( $_REQUEST['id'] ) )
			self::delete_member( (int) $_REQUEST['id'] );

		redirectexit( 'action=xbox' );
	}

	/**
	 * Converts non-UTF strings to UTF-8
	 *
	 * @global array $context
	 * @param string $string String to be converted
	 * @return string $clean
	 */
	public static function clean_string( $string )
	{
		global $context;

		$clean = iconv('UTF-8', !empty($context['character_set']) ? $context['character_set'] . '//IGNORE' : 'ISO-8859-1//IGNORE', $string);

		return $clean;
	}

	public static function convert_gamertag( $gamertag )
	{
		return str_replace( ' ', '%20', $gamertag );
	}

	/**
	 * Retrieves data from the API, then parses it into
	 * a useable array
	 *
	 * @param string $string String of data from the API
	 * @param array $member Array of data for a given member
	 * @global string $sourcedir
	 * @global array $context
	 * @global array $modSettings
	 * @global string $scripturl
	 * @return array $player
	 */
	public static function get_data( $string, $member )
	{
		global $sourcedir, $context, $modSettings, $scripturl;

		$player['existing'] = $member;
		$data = json_decode( $string );

		if ( !is_array( $data ) || $data === false )
			return false;

		else
		{
			/* Create the avatar images */
			self::saveImage( $data->user->avatar, null, $data->user->gamertag );

			$player['id']				= $player['existing']['id_member'];
			$player['account_status']	= $data->user->account_status;
			$player['gender']			= $data->user->gender;
			$player['is_cheater']		= $data->user->is_cheater;
			$player['link']				= $data->user->profile_link;
			$player['gamertag']			= $data->user->gamertag;
			$player['avatar']			= $modSettings['xbl_gtag_image_url'] . '/' . self::convert_gamertag( $data->user->gamertag ) . '.png';
			$player['reputation']		= $data->user->reputation;
			$player['gamerscore']		= $data->user->gamerscore;
			$player['location']			= $data->user->location;
			$player['motto']			= $data->user->motto;
			$player['name']				= $data->user->name;
			$player['bio']				= $data->user->bio;

			if ( !empty( $data->user->recent_games ) )
			{
				$player['games'] = array();

				foreach ( $data->user->recent_games as $key => $val )
				{
					$val->tid											= self::getTid( $val->link );
					$val->last_played									= strtotime( $val->last_played );

					$player['games'][$key]['tid']						= $val->tid;
					$player['games'][$key]['link']						= $scripturl . '?action=xboxleaders;sa=games;id=' . $val->tid;
					$player['games'][$key]['image']						= $modSettings['xbl_game_image_url'] . '/' . $val->tid . '.png';
					$player['games'][$key]['title']						= $val->title;
					$player['games'][$key]['last_played']				= $val->last_played;
					$player['games'][$key]['earned_gamerscore']			= $val->earned_gamerscore;
					$player['games'][$key]['available_gamerscore']		= $val->available_gamerscore;
					$player['games'][$key]['earned_achievements']		= $val->earned_achievements;
					$player['games'][$key]['available_achievements']	= $val->available_achievements;
					$player['games'][$key]['percentage_complete']		= $val->percentage_complete;
				}
					
				$player['lastplayed'] = serialize( $player['games'] );
			}
			else
			{
				$player['games'] = false;
				$player['lastplayed'] = false;
			}

			return $player;
		}
	}

	/**
	 * Updates a member's data for the leaderboard
	 *
	 * @param array $player The member's data
	 * @global array $context
	 * @global array $smcFunc
	 * @return bool
	 */
	public static function update_member( $player )
	{
		global $context, $smcFunc;

		// Make sure we have a valid gamertag
		$player_exists = $player['is_valid'] === 1 ? true : false;

		// OK, so he exists. Now what?
		if ( $player_exists === true )
		{
			$smcFunc['db_query']( '', '
				UPDATE {db_prefix}xbox_leaders
				SET
					gold = {int:gold}, gamertag = {string:gamertag},
					avatar = {string:avatar}, reputation = {string:reputation},
					gamerscore = {string:gamerscore}, updated = {int:updated}
				WHERE id_member = {int:member}',
				array(
					'member' => $player['id'], 'account_status'	=> $player['account_status'],
					'gamertag' => $player['gamertag'], 'avatar' => $player['avatar'],
					'reputation' => $player['reputation'], 'gamerscore' => $player['gamerscore'],
					'updated' => time(),
				)
			);

			// If there are games to insert, do it
			if ( $player['games'] && $player['lastplayed'] )
			{
				$smcFunc['db_query']( '', '
					UPDATE {db_prefix}xbox_leaders
					SET last_played = {string:lastplayed}
					WHERE id_member = {int:member}',
					array(
						'lastplayed'	=> $player['lastplayed'],
						'member'		=> $player['id'],
					)
				);

				// Remove the games before we update it
				@$smcFunc['db_query']( '', '
					DELETE FROM {db_prefix}xbox_games
					WHERE id_member = {int:id_member}',
					array(
						'id_member' => $player['id'],
					)
				);

				// Update the games list too!
				foreach ($player['games'] as $key => $game)
				{
					$game['title'] = self::clean_string( $game['title'] );

					$smcFunc['db_insert']( 'ignore',
						'{db_prefix}xbox_games',
						array(
							'id_member' => 'int', 'position' => 'int',
							'title' => 'string', 'link' => 'string',
							'image' => 'string', 'updated' => 'int',
						),
						array(
							$player['id'], $key,
							$game['title'], $game['link'],
							$game['image'], time(),
						),
						array( 'position' )
					);

					$game['tid'] = self::getTid( $game['link'] );

					// Might as well update the archive
					$smcFunc['db_insert']( 'ignore',
						'{db_prefix}xbox_games_list',
						array(
							'tid' => 'string', 'title' => 'string',
							'image' => 'string',
						),
						array(
							$game['tid'], $game['title'],
							$game['image'],
						),
						array( 'tid' )
					);
				}
			}
		}

		return true;
	}

	/**
	 * Removes a member completely from the app
	 *
	 * @param int $member The member id to remove
	 * @global array $context
	 * @global array $smcFunc
	 * @return bool
	 */
	public static function delete_member( $member )
	{
		global $context, $smcFunc;

		if ( is_numeric( $member ) )
		{
			// Remove them from the leaders table
			$smcFunc['db_query']( '', '
				DELETE FROM {db_prefix}xbox_leaders
				WHERE id_member = {int:member}',
				array(
					'member' => $member,
				)
			);

			// Also remove them from the games table
			$smcFunc['db_query']( '', '
				DELETE FROM {db_prefix}xbox_games
				WHERE id_member = {int:member}',
				array(
					'member' => $member,
				)
			);

			// Might as well remove from the members table too
			$smcFunc['db_query']( '', '
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

	/**
	 * Saves an avatar or game image to the cache to save bandwidth
	 *
	 * @param string $url URL to image to save
	 * @param int $tid Game tid
	 * @param string $gamertag Gamertag for avatar
	 * @global array $modSettings
	 * @global array $settings
	 * @global strin $scripturl
	 * @return bool
	 */
	public static function save_image( $url, $tid = null, $gamertag = null )
	{
		global $modSettings, $settings, $scripturl;

		if ( $tid != null )
		{
			$im = @imagecreatefromjpeg( $url );
			imagepng( $im, $modSettings['xbl_game_image_path'] . '/' . $tid . '.png', 8 );
			imagedestroy( $im );
		}
		elseif ( $gamertag != null )
		{
			$im = @imagecreatefrompng( $url );
			imagepng( $im, $modSettings['xbl_gtag_image_path'] . '/' . $gamertag . '.png' );
			imagedestroy( $im );
		}
		else
			return false;
	}

	/**
	 * Loads all data pertaining to a given gamer
	 *
	 * @param int $mid Member id to load
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @global array $settings
	 * @global array $txt
	 * @return array $gamer_data
	 */
	public static function load_gamer_data( $mid )
	{
		global $smcFunc, $modSettings, $settings, $txt;

		$request = $smcFunc['db_query']( '', '
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
		while ( $row = $smcFunc['db_fetch_assoc']( $request ) )
		{
			$gamer_data[] = array(
				'id' => $row['id_member'],
				'gamertag' => $row['gamertag'],
				'gamerscore' => $row['gamerscore'],
				'reputation' => '<img src="' . $settings['images_url'] . '/xbl/' . $row['reputation'] . '.png" alt="' . $row['reputation'] . '" title="' . $row['reputation'] . '" />',
				'gold' => $row['account_status'],
				'zone' => 'N/A',
				'avatar' => '<img src="' . $row['avatar'] . '" width="32px" height="32px" alt="" />',
				'location' => $row['location'],
				'motto' => $row['motto'],
				'name' => $row['name'],
				'bio' => $row['bio'],
			);

			$gamer_data['games'][$row['tid']] = array(
				'tid' => $row['tid'],
				'title' => $row['title'],
				'tile' => $row['image'],
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

	/**
	 * Grabs a game's unique tid from a URL
	 *
	 * @param string $string URL to parse
	 * @return int $tid
	 */
	public static function get_tid( $string )
	{
		$tid = parse_url( $string );
		$tid = explode( '&', html_entity_decode( $tid['query'] ) );
		$tid = explode( '=', $tid['0'] );
		
		return $tid['1'];
	}

	/**
	 * Counts up some basic stats for the leaderboard
	 *
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $count
	 */
	public static function stats_basic()
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
				'required_posts' => !empty( $modSettings['xbl_required_posts'] ) ? $modSettings['xbl_required_posts'] : 0,
				'user_timeout' => time() - ( $modSettings['xbl_user_timeout'] * 86400 ),
				'show_unranked' => !empty( $modSettings['xbl_show_unranked'] ) ? 0 : 1,
			)
		);
		$count = array();
		$row = $smcFunc['db_fetch_assoc']( $request );
		$smcFunc['db_free_result']( $request );

		$count['members'] 			= comma_format( $row['usercount'] );
		$count['score'] 			= comma_format( $row['gamerscore'] );
		$count['reputation'] 		= $row['reputation'] != 0 ? ceil( $row['reputation'] / $row['usercount'] ) : 0;
		$count['silver'] 			= comma_format( $row['usercount'] - $row['gold'] );
		$count['gold'] 				= comma_format( $row['gold'] );

		// Games
		$request = $smcFunc['db_query']( '', '
			SELECT COUNT(DISTINCT title) AS gamescount
			FROM {db_prefix}xbox_games AS xbg
				LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (xbg.id_member = xbl.id_member)',
			array()
		);
		$row = $smcFunc['db_fetch_assoc']( $request );
		$smcFunc['db_free_result']( $request );

		$count['gamescount'] = comma_format( $row['gamescount'] );

		return $count;
	}

	/**
	 * Returns a list of top avatars for the leaderboard
	 *
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $avatars
	 */
	public static function stats_top_avatars()
	{
		global $smcFunc, $modSettings;

		$filtered_avatars = array(
			'http://tiles.xbox.com/tiles/8y/ov/0Wdsb2JhbC9EClZWVEoAGAFdL3RpbGUvMC8yMDAwMAAAAAAAAAD+ACrT.jpg',
			'/xweb/lib/images/QuestionMark64x64.jpg'
		);

		$request = $smcFunc['db_query']( '', '
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
				'required_posts' => !empty( $modSettings['xbl_required_posts'] ) ? $modSettings['xbl_required_posts'] : 0,
				'user_timeout' => time() - ( $modSettings['xbl_user_timeout'] * 86400 ),
				'show_unranked' => !empty( $modSettings['xbl_show_unranked'] ) ? 0 : 1,
				'exclude' => implode( '\', \'', array_values( $filtered_avatars ) ),
				'limit' => !empty( $modSettings['xbl_stats_limit'] ) ? $modSettings['xbl_stats_limit'] : 5,
			)
		);
		$avatars = array();
		while ( $row = $smcFunc['db_fetch_assoc']( $request ) )
			$avatars[] = $row;
		$smcFunc['db_free_result']( $request );

		return $avatars;
	}

	/**
	 * Returns a list of the top players for the leaderboard
	 *
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $players
	 */
	public static function stats_top_players()
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
				'required_posts' => !empty( $modSettings['xbl_required_posts'] ) ? $modSettings['xbl_required_posts'] : 0,
				'user_timeout' => time() - ( $modSettings['xbl_user_timeout'] * 86400 ),
				'show_unranked' => !empty( $modSettings['xbl_show_unranked'] ) ? 0 : 1,
				'limit' => !empty( $modSettings['xbl_stats_limit'] ) ? $modSettings['xbl_stats_limit'] : 5,
			)
		);
		$players = array();
		while ( $row = $smcFunc['db_fetch_assoc']( $request ) )
			$players[] = $row;
		$smcFunc['db_free_result']( $request );

		return $players;
	}

	/**
	 * Returns a list of the top played games for the leaderboard
	 *
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $games
	 */
	public static function stats_top_games()
	{
		global $smcFunc, $modSettings;

		$request = $smcFunc['db_query']( '', '
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
				'show_unranked' => !empty( $modSettings['xbl_show_unranked'] ) ? 0 : 1,
				'limit' => !empty( $modSettings['xbl_stats_limit'] ) ? $modSettings['xbl_stats_limit'] : 5,
			)
		);
		$games = array();
		while ( $row = $smcFunc['db_fetch_assoc']( $request ) )
		{
			$games[] = array(
				'link' => preg_replace( '~&amp;compareTo=(.*)~i', '', $row['link'] ),
				'title' => $row['title'],
				'image' => $row['image'],
				'count' => $row['count'],
			);
		}
		$smcFunc['db_free_result']( $request );

		return $games;
	}

	/**
	 * Pagination function for the leaderboard
	 *
	 * @param int $start
	 * @param int $items_per_page
	 * @param string $sort
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $members
	 */
	public static function list_get_members( $start, $items_per_page, $sort )
	{
		global $smcFunc, $modSettings;

		$request = $smcFunc['db_query']( '', '
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
		while ( $row = $smcFunc['db_fetch_assoc']( $request ) )
			$members[] = $row;
		$smcFunc['db_free_result']( $request );

		return $members;
	}

	/**
	 * Pagination function for the leaderboard
	 *
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @return array $num_members
	 */
	public static function list_get_num_members()
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

	/**
	 * Update the data for the entire app based on scheduled tasks
	 *
	 * @global string $sourcedir
	 * @global array $smcFunc
	 * @global array $modSettings
	 * @global array $context
	 * @return bool
	 */
	public static function scheduled_update_gamertags()
	{
		global $sourcedir, $smcFunc, $modSettings, $context;

		require_once( $sourcedir . '/Subs-Package.php' );

		$time = time();

		$query = '
			SELECT mem.id_member, mem.posts, mem.last_login, mem.gamertag, xbl.*
			FROM {db_prefix}members AS mem
				LEFT JOIN {db_prefix}xbox_leaders AS xbl ON (xbl.id_member = mem.id_member)
			WHERE mem.gamertag != \'\'
				AND mem.posts >= {int:required_posts}
				AND mem.last_login >= {int:user_timeout}
				AND (xbl.updated IS NULL OR DATE_FORMAT( FROM_UNIXTIME( xbl.updated ), \'%Y-%m-%d\' ) != \'' . date( 'Y-m-d', $time ) . '\')
			ORDER BY
				xbl.updated ASC,
				mem.id_member ASC
			';

		$params = array(
			'required_posts' => !empty( $modSettings['xbl_required_posts'] ) ? $modSettings['xbl_required_posts'] : 0,
			'user_timeout' => $time - ( $modSettings['xbl_user_timeout'] * 86400 ),
		);

		$full_result = $smcFunc['db_query']( '', $query, $params );
		$query_limit = ceil($smcFunc['db_num_rows']( $full_result ) / ceil( ( strtotime( date( 'Y-m-d', strtotime( '+1 day' ) ) ) - $time ) / 60 ) ) + 5;
		$smcFunc['db_free_result']( $full_result );

		// Now make the final queries needed
		$request = $smcFunc['db_query']( '', $query . 'LIMIT 0, ' . $query_limit, $params );

		while ($row = $smcFunc['db_fetch_assoc']( $request ) )
		{
			$url = 'http://api.xboxleaders.com/v2/?gamertag=' . self::convert_gamertag( $row['gamertag'] ) . '&format=json';

			$card = self::get_data( fetch_web_data( $url ), $row );

			if ($card !== false)
				self::update_member( $card );
		}
		$smcFunc['db_free_result']( $request );

		return true;
	}
}