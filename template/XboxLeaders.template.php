<?php
// Version 2.0 RC5; SimpleXBL

function template_leaderboard()
{
	global $context, $scripturl, $txt, $settings;

	echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';

	template_show_list('xbl_leaders');

	$context['xbl_stat_count'] = getXboxStatCount();

	echo '
	<br class="clear" />
	<span class="clear upperframe"><span></span></span>
	<div class="roundframe"><div class="innerframe">
		<div class="cat_bar">
			<h3 class="catbg">
				<img class="icon" id="upshrink_xbls" src="', $settings['images_url'], '/collapse.gif" alt="*" title="', $txt['upshrink_description'], '" style="display: none;" />
				', $txt['xbl_top_stats'], '
			</h3>
		</div>
		<div id="upshrinkHeaderXBLS"', empty($options['collapse_header_xbls']) ? '' : ' style="display: none;"', '>
			<table class="table_grid" width="100%">
			<thead>
				<th class="smalltext" align="center">', $txt['xbl_stats_overall'], '</th>
				<th class="smalltext" align="center">', $txt['xbl_stats_players'], '</th>
				<th class="smalltext" align="center">', $txt['xbl_stats_avatars'], '</th>
				<th class="smalltext" align="center">', $txt['xbl_stats_games'], '</th>
			</thead>
			<tbody>
				<tr class="windowbg">
					<td valign="top" width="20%" nowrap="nowrap">
						<div class="smalltext" width="100%"><span style="float: right;">', $context['xbl_stat_count']['members'], '</span>', $txt['xbl_total_players'], ':</div>
						<div class="smalltext" width="100%"><span style="float: right;"><img src="', $settings['images_url'] . '/xbl/gs.png" height="10" width="10" alt="" /> ', $context['xbl_stat_count']['score'], '</span>', $txt['xbl_total_gamerscore'], ':</div>
						<div class="smalltext" width="100%"><span style="float: right;"><img src="', $settings['images_url'], '/xbl/', $context['xbl_stat_count']['reputation'], '.png" alt="" title="', $txt['xbl_total_reputation'], ': ', $context['xbl_stat_count']['reputation'], '" /></span>', $txt['xbl_total_reputation'], ':</div>
						<div class="smalltext" width="100%"><span style="float: right;">', $context['xbl_stat_count']['gold'], '</span>', $txt['xbl_total_gold'], ':</div>
						<div class="smalltext" width="100%"><span style="float: right;">', $context['xbl_stat_count']['silver'], '</span>', $txt['xbl_total_silver'], ':</div>
						<div class="smalltext" width="100%"><span style="float: right;">', $context['xbl_stat_count']['gamescount'], '</span>', $txt['xbl_total_games'], ':</div>
					</td>
					<td valign="top" width="20%" nowrap="nowrap">';

			foreach (getXblTopPlayers() as $player)
			{
				echo '<div class="smalltext" width="100%">
						<span style="float: right;"><img src="', $settings['images_url'] . '/xbl/gs.png" height="10" width="10" alt="" /> ', comma_format($player['gamerscore']), '</span>
						<a href="', $scripturl, '?action=profile;u=', $player['id_member'], '" onmouseover="xbl_show_gamer(\'gamercard\',\'', $player['gamertag'], '\');" onmouseout="xbl_hide();">', $player['gamertag'], '</a>
					</div>';
			}

		echo '
					</td>
					<td width="20%" align="center">';

			foreach (getXblTopAvatars() as $avatar)
				echo '<img border="0" height="32" width="32" src="', $avatar['avatar'], '" alt="" title="', sprintf($txt['xbl_by_players'], $avatar['count']), '" /> ';

		echo '
					</td>
					<td width="20%" align="center">';

			foreach (getXblTopGames() as $game)
				echo '<a target="_blank" href="', $game['link'], '"><img border="0" height="32" width="32" src="', $game['image'], '" alt="" title="', $game['title'], ': ', sprintf($txt['xbl_by_players'], $game['count']), '" /></a> ';

		echo '
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	</div></div>
	<span class="lowerframe"><span></span></span>';

	echo '<div class="smalltext" align="center">
		Powered by <a href="http://community.xboxleaders.com/">SimpleXBL v', $context['xbl_version'], '</a>
	</div>';

	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		var oXblStatsToggle = new smc_Toggle({
			bToggleEnabled: true,
			bCurrentlyCollapsed: ', empty($options['collapse_header_xbls']) ? 'false' : 'true', ',
			aSwappableContainers: [
				\'upshrinkHeaderXBLS\'
			],
			aSwapImages: [
				{
					sId: \'upshrink_xbls\',
					srcExpanded: smf_images_url + \'/collapse.gif\',
					altExpanded: ', JavaScriptEscape($txt['upshrink_description']), ',
					srcCollapsed: smf_images_url + \'/expand.gif\',
					altCollapsed: ', JavaScriptEscape($txt['upshrink_description']), '
				}
			],
			oThemeOptions: {
				bUseThemeSettings: ', $context['user']['is_guest'] ? 'false' : 'true', ',
				sOptionName: \'collapse_header_xbls\',
				sSessionVar: ', JavaScriptEscape($context['session_var']), ',
				sSessionId: ', JavaScriptEscape($context['session_id']), '
			},
			oCookieOptions: {
				bUseCookie: ', $context['user']['is_guest'] ? 'true' : 'false', ',
				sCookieName: \'upshrinkXBLS\'
			}
		});
	// ]]></script>';

}

?>