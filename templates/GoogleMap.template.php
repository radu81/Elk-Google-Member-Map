<?php

/**
 * @package "Google Member Map" Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2011-2013 Spuds
 * @license This Source Code is subject to the terms of the Mozilla Public License
 * version 1.1 (the "License"). You can obtain a copy of the License at
 * http://mozilla.org/MPL/1.1/.
 *
 * @version 1.0
 *
 */

function template_map()
{
	global $context, $modSettings, $scripturl, $txt, $settings;

	if (!empty($modSettings['googleMap_Enable']))
	{
		echo '
				<div id="gmm">
					<h2 class="category_header">
						<span class="align_left">', $txt['googleMap'], '</span>
					</h2>
					<div class="content">
						<table>
							<tr>';

		// Show a left sidebar?
		if ((!empty($modSettings['googleMap_Sidebar'])) && $modSettings['googleMap_Sidebar'] == 'left')
		{
			echo '
								<td class="sidebarleft">
									<div class="centertext">
										<em><strong>', $txt['googleMap_Pinned'], '</strong></em>
									</div>
									<hr />
									<div id="googleSidebar"></div>';

			if (!empty($modSettings['googleMap_BoldMember']))
				echo '
									<div class="centertext googleMap_Legend">
										<strong>' . $txt['googleMap_bold'] . '</strong>&nbsp;' . $txt['googleMap_OnMove'] . '
									</div>';

			echo '
								</td>';
		}

		// Our map
		echo '
								<td>
									<div id="mapWindow">
										<div id="map" style="height: 500px;"></div>
										<div id="googleMapReset" onclick="resetMap(); return false;" title="'. $txt['googleMap_Reset'] . '"></div>
									</div>';

		// Set the text for the number of pins we are, or can, show
		if ($context['total_pins'] >= $modSettings['googleMap_PinNumber'] && $modSettings['googleMap_PinNumber'] != 0)
			echo
									sprintf($txt['googleMap_Thereare'], '<strong>(' . $modSettings['googleMap_PinNumber'] . '+)</strong>');
		else
			echo
									sprintf($txt['googleMap_Thereare'], '<strong>(' . $context['total_pins'] . ')</strong>');

		echo '
								</td>';

		// Show a right sidebar?
		if (!empty($modSettings['googleMap_Sidebar']) && $modSettings['googleMap_Sidebar'] == 'right')
		{
			echo '
								<td class="sidebarright">
									<div class="centertext">
										<em><strong>', $txt['googleMap_Pinned'], '</strong></em>
									</div>
									<hr />
									<div id="googleSidebar"></div>';

			if (!empty($modSettings['googleMap_BoldMember']))
				echo '
									<div class="centertext googleMap_Legend">
										<strong>' . $txt['googleMap_bold'] . '</strong>&nbsp;' . $txt['googleMap_OnMove'] . '
									</div>';

			echo '
								</td>';
		}

		// close this table
		echo '
							</tr>
						</table>';

		// Show a legend?
		if (!empty($modSettings['googleMap_EnableLegend']))
		{
			echo '
						<h2 class="category_header">
							<span class="align_left">', $txt['googleMap_Legend'], '</span>
						</h2>
						<table id="googleMap_Legend" class="centertext ">
							<tr>';

			if (empty($modSettings['googleMap_PinGender']))
				echo '
								<td><img src="http://chart.apis.google.com/chart', htmlspecialchars($modSettings['npin']), '" alt="" />', $txt['googleMap_MemberPin'], '</td>';
			else
				echo '
								<td><img src="http://chart.apis.google.com/chart', htmlspecialchars($modSettings['npin']), '" alt="" />', $txt['googleMap_AndrogynyPin'], '</td>
								<td><img src="http://chart.apis.google.com/chart', htmlspecialchars($modSettings['mpin']), '" alt="" />', $txt['googleMap_MalePin'], '</td>
								<td><img src="http://chart.apis.google.com/chart', htmlspecialchars($modSettings['fpin']), '" alt="" />', $txt['googleMap_FemalePin'], '</td>';

			if (!empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 0)))
			{
				$codebase = 'http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclusterer';
				$chartbase = "http://chart.apis.google.com/chart";

				switch ($modSettings['cpin'])
				{
					case 1:
						$pinsrc = $codebase . '/images/m1.png';
						break;
					case 2:
						$pinsrc = $codebase . '/images/people35.png';
						break;
					case 3:
						$pinsrc = $codebase . '/images/conv30.png';
						break;
					default:
						$pinsrc = $chartbase . $modSettings['cpin'];
				}

				echo '
								<td><img src="', htmlspecialchars($pinsrc), '" height=37 alt="" />', $txt['googleMap_GroupOfPins'], '</td>';
			}

			echo '
							</tr>
						</table>';
		}

		echo '
							<table class="centertext">';

		// If they can place a pin, give them a hint
		if ($context['place_pin'])
			echo '
							<tr>
								<td>
									<a href="', $scripturl, '?action=profile;area=forumprofile">', $txt['googleMap_AddPinNote'], '</a>
								</td>
							</tr>';

		// Google earth klm output enabled?
		if (!empty($modSettings['googleMap_KMLoutput_enable']))
			echo '
							<tr>
								<td align="center">
									<a href="', $scripturl, '?action=GoogleMap;sa=kml"><img src="', $settings['default_theme_url'], '/images/google_earth_feed.gif" border="0" alt="" /></a>
								</td>
							</tr>';

		// Done with the bottom table
		echo '
						</table>';

		// Close it up jim
		echo '
					</div>
				</div>';

		// Load the scripts so we can render the map
		echo '
				<script src="', $scripturl, '?action=googlemap;sa=js;count='. $context['total_pins'] .'"></script>';
	}
}

/**
 * Call back for Google Map Member Map area of ProfileInfo
 */
function template_profile_googlemap_modify()
{
	global $txt, $modSettings, $context;

	if (!empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'))
	{
		echo '
		<dt>
			<strong>', $txt['googleMap'], '</strong>
			<br /><span class="smalltext">'. $txt['googleMap_PleaseClick'].'<br />' . $txt['googleMap_Disclaimer'] . '</span>
		</dt>
		<dd>
		<script src="http://maps.google.com/maps/api/js?sensor=false&libraries=places"></script>
		<input id="searchTextField" type="text" size="50">
        <div id="map_canvas"></div>
        <input type="hidden" name="latitude" id="latitude" size="50" value="', $context['member']['googleMap']['latitude'], '" />
        <input type="hidden" name="longitude" id="longitude" size="50" value="', $context['member']['googleMap']['longitude'], '" />
        <input type="hidden" name="pindate" id="pindate" size="50" value="', $context['member']['googleMap']['pindate'], '" />
        <script><!-- // --><', '', '![CDATA[
		var markersArray = [];

		// Used to clear any previous pin placed on the map
		function clearOverlays() {
			if (markersArray)
			{
				for (i in markersArray)
				{
					markersArray[i].setMap(null);
				}
			}
		}

		// Show the map
		function initialize() {
			var latlng = new google.maps.LatLng(', (!empty($context['member']['googleMap']['latitude']) ? $context['member']['googleMap']['latitude'] : (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0)) . ', ' . (!empty($context['member']['googleMap']['longitude']) ? $context['member']['googleMap']['longitude'] : (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0)), ');
			var options = {
				zoom: ', !empty($context['member']['googleMap']['latitude']) ? 15 : (!empty($modSettings['googleMap_DefaultZoom']) ? $modSettings['googleMap_DefaultZoom'] : 4), ',
				center: latlng,
				scrollwheel: false,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				mapTypeControlOptions: {
					style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
				},
				zoomControl: true,
				zoomControlOptions: {
					style: google.maps.ZoomControlStyle.DEFAULT
				},
			};
			map = new google.maps.Map(document.getElementById("map_canvas"), options);';

		// show the member pin, and provide a way to remove it,  if one has been set
		if (!empty($context['member']['googleMap']['latitude']) && !empty($context['member']['googleMap']['longitude']))
			echo '
			var marker = new google.maps.Marker({
				position: latlng,
				map: map
			});
			markersArray.push(marker);

			// Listen and act on a marker click, used to remove this pin
			google.maps.event.addListener(marker, "click", function() {
				clearOverlays();
				document.getElementById("latitude").value = 0;
				document.getElementById("longitude").value = 0;
			});';

		echo '
			// Listen and act on a map click, used to add pins
			google.maps.event.addListener(map, "click", function(event) {
				clearOverlays();
				var marker = new google.maps.Marker({position:event.latLng, map:map});
				markersArray.push(marker);
				map.panTo(event.latLng);
				document.getElementById("latitude").value = event.latLng.lat();
				document.getElementById("longitude").value = event.latLng.lng();

				// Listen and act on a marker click, used to remove pins
				google.maps.event.addListener(marker, "click", function() {
				clearOverlays();
				document.getElementById("latitude").value = 0;
				document.getElementById("longitude").value = 0;
				});
			});

			// Set up the searchbox to be part of the map
			var input = document.getElementById("searchTextField"),
				autocomplete = new google.maps.places.Autocomplete(input);

			autocomplete.bindTo("bounds", map);
			autocomplete.setTypes(["geocode"]);

			// Watch for a search box selection, when found move and zoom to that place
			google.maps.event.addListener(autocomplete, "place_changed", function() {
				var place = autocomplete.getPlace();

				if (place.geometry.viewport)
				{
					map.fitBounds(place.geometry.viewport);
				}
				else
				{
					map.setCenter(place.geometry.location);
					map.setZoom(13);
				}
			});
		}
		google.maps.event.addDomListener(window, "load", initialize);
		// ]]', '', '></script>
</dd>';
	}
}

/**
 * Profile Summary template for showing the users map location, if they have one set
 */
function template_profile_block_gmm()
{
	global $scripturl, $context, $txt;

	// If they have a pin set then we show the block
	if (!empty($context['member']['googleMap']['longitude']) && !empty($context['member']['googleMap']['latitude']))
	{
		// Try to get the shout out right where is vs where are
		$title = $txt['googleMap_Where'] . ' ' . (preg_match('~\s(and|&|&amp;)\s~i', $context['member']['name']) ? $txt['googleMap_Whereare'] : $txt['googleMap_Whereis']) . ' ' . $context['member']['name'];

		echo '
	<div class="profileblock">
		<h3 class="category_header hdicon cat_img_eye">
			', ($context['user']['is_owner']) ? '<a href="' . $scripturl . '?action=profile;area=forumprofile;u=' . $context['member']['id'] . '">' . $title . '</a>' : $title, '
		</h3>
		<div class="profileblock">
			<script src="http://maps.google.com/maps/api/js?sensor=false"></script>
			<div id="map_canvas" style="width: 100%; height: 300px; color: #000000;"></div>
				<input type="hidden" name="latitude" size="50" value="', $context['member']['googleMap']['latitude'], '" />
				<input type="hidden" name="longitude" size="50" value="', $context['member']['googleMap']['longitude'], '" />
				<input type="hidden" name="pindate" size="50" value="', $context['member']['googleMap']['pindate'], '" />
				<script><!-- // --><![CDATA[
					var latlng = new google.maps.LatLng(', $context['member']['googleMap']['latitude'], ', ', $context['member']['googleMap']['longitude'], ');
					var options = {
						zoom: 14,
						center: latlng,
						scrollwheel: false,
						mapTypeId: google.maps.MapTypeId.HYBRID,
						mapTypeControlOptions: {
							style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
						},
						zoomControl: true,
						zoomControlOptions: {
							style: google.maps.ZoomControlStyle.DEFAULT
						},
					};

					map = new google.maps.Map(document.getElementById("map_canvas"), options);
					var marker = new google.maps.Marker({
						position: latlng,
						map: map
					});
				// ]]></script>
			</div>
		</div>';
	}
}