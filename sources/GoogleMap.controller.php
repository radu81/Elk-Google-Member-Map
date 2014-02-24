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

// Are we calling this directly the lets just say no
if (!defined('ELK'))
	die('No access...');

class GoogleMap_Controller extends Action_Controller
{
	/**
	 * Normal pin
	 * @var string
	 */
	protected $_npin;

	/**
	 * Cluser pin style
	 * @var string
	 */
	protected $_cpin;

	/**
	 * Female pin style
	 * @var string
	 */
	protected $_fpin;

	/**
	 * Male pin style
	 * @var string
	 */
	protected $_mpin;

	/**
	 * Pin background color/icon/text
	 * @var string
	 */
	protected $_mchld;

	/**
	 * Cluster pin background color/icon/text
	 * @var string
	 */
	protected $_cchld;

	/**
	 * Normal pin shadow style
	 * @var string
	 */
	protected $_nshd;

	/**
	 * Cluster pin shadow style
	 * @var string
	 */
	protected $_cshd;

	/**
	 * Entry point function for GMM, permission checks, makes sure its on
	 */
	public function pre_dispatch()
	{
		global $modSettings;

		// If GMM is disabled, we don't go any further
		if (empty($modSettings['googleMap_Enable']))
			fatal_lang_error('feature_disabled', true);

		// Are we allowed to view the map?
		isAllowedTo('googleMap_view');

		// Some things we will need
		loadLanguage('GoogleMap');
		require_once(SUBSDIR . '/GoogleMap.subs.php');
	}

	/**
	 * Default action method, if a specific method wasn't
	 * directly called already. Simply forwards to main.
	 */
	public function action_index()
	{
		$this->action_gmm_main();
	}

	/**
	 * gmm_main()
	 *
	 * Calls the googlemap template which in turn makes the
	 * xml or js request for data
	 */
	public function action_gmm_main()
	{
		global $context, $txt, $modSettings;

		// Load up our template and style sheet
		loadTemplate('GoogleMap', 'GoogleMap');

		// Load number of member pins
		$totalSet = gmm_pinCount();

		// Create the pins for template use
		if (!empty($modSettings['googleMap_EnableLegend']))
			$this->gmm_buildpins();

		// Load in our javascript
		loadJavascriptFile('markerclusterer_packed.js');
		loadJavascriptFile('http://maps.google.com/maps/api/js?sensor=false', array(), 'sensor.js');

		// Show the map
		$context['place_pin'] = allowedTo('googleMap_place');
		$context['total_pins'] = $totalSet;
		$context['sub_template'] = 'map';
		$context['page_title'] = $txt['googleMap'];
	}

	/**
	 * Creates the maps javascript file based on the admin settings
	 * Called from the map template file via map action=GoogleMap;sa=js
	 */
	public function action_js()
	{
		global $context, $txt, $modSettings;

		// Clean and restart the buffer so we only return JS back to the template
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
			@ob_start('ob_gzhandler');
		else
			ob_start();

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// Let them know what they are about to get
		header('Content-Type: application/javascript');

		// Our push pins as defined from gmm_buildpins
		$this->gmm_buildpins();
		$this->_npin = $modSettings['npin'];
		$this->_cpin = $modSettings['cpin'];
		$this->_mpin = $modSettings['mpin'];
		$this->_fpin = $modSettings['fpin'];

		// Push Pin shadows as well?
		$this->_nshd = (!empty($modSettings['googleMap_PinShadow'])) ? $this->_nshd = '_withshadow' : $this->_nshd = '';
		$this->_cshd = (!empty($modSettings['googleMap_ClusterShadow'])) ? $this->_cshd = '_withshadow' : $this->_cshd = '';

		// Validate the specified pin size is not to small
		$m_iconsize = (isset($modSettings['googleMap_PinSize']) && $modSettings['googleMap_PinSize'] > 19) ? $modSettings['googleMap_PinSize'] : 20;
		$c_iconsize = (isset($modSettings['googleMap_ClusterSize']) && $modSettings['googleMap_ClusterSize'] > 19) ? $modSettings['googleMap_ClusterSize'] : 20;

		// Scaling factors based on these W/H ratios to maintain aspect ratio and overall size
		// Such that a mixed shadown/no sprite push pin appear the same size
		$m_iconscaled_w = !empty($this->_nshd) ? $m_iconsize * 1.08 : $m_iconsize * .62;
		$m_iconscaled_h = $m_iconsize;

		$c_iconscaled_w = !is_int($this->_cpin) ? (!empty($this->_cshd) ? $c_iconsize * 1.08 : $c_iconsize * .62) : $c_iconsize;
		$c_iconscaled_h = $c_iconsize;

		// Set all those anchor points based on the scaled icon size, icon at pin mid bottom
		$m_iconanchor_w = (!empty($this->_nshd)) ? $m_iconscaled_w / 3.0 : $m_iconscaled_w / 2.0;
		$m_iconanchor_h = $m_iconscaled_h;

		// Pin count
		$context['total_pins'] = isset($_REQUEST['count']) ? (int) $_REQUEST['count'] : 0;

		// Lets start making some javascript
		echo '// Globals
	var xhr = false;

	// Arrays to hold copies of the markers and html used by the sidebar
	var gmarkers = [],
		htmls = [],
		sidebar_html = "";

	// Map, cluster and info bubble
	var map = null,
		mc = null,
		infowindow = null;

	// Icon locations
	var codebase = "http://google-maps-utility-library-v3.googlecode.com/svn/trunk/markerclusterer",
		chartbase = "http://chart.apis.google.com/chart";

	// Our normal pin to show on the map
	var npic = {
		url: chartbase + "' . $this->_npin . '",
		size: null,
		origin: null,
		anchor: new google.maps.Point(' . $m_iconanchor_w . ', ' . $m_iconanchor_h . '),
		scaledSize: new google.maps.Size(' . $m_iconscaled_w . ', ' . $m_iconscaled_h . ')
	};';

		// Gender pins as well?
		if (!empty($modSettings['googleMap_PinGender']))
			echo '
	// The Gender Pins
	var fpic = {
		url: chartbase + "' . $this->_fpin . '",
		size: null,
		origin: null,
		anchor: new google.maps.Point(' . $m_iconanchor_w . ', ' . $m_iconanchor_h . '),
		scaledSize: new google.maps.Size(' . $m_iconscaled_w . ', ' . $m_iconscaled_h . ')
	};

	var mpic = {
		url: chartbase + "' . $this->_mpin . '",
		size: null,
		origin: null,
		anchor: new google.maps.Point(' . $m_iconanchor_w . ', ' . $m_iconanchor_h . '),
		scaledSize: new google.maps.Size(' . $m_iconscaled_w . ', ' . $m_iconscaled_h . ')
	};';

		// Cluster Pin Styles
		if (!empty($modSettings['googleMap_EnableClusterer']))
			echo '

	// Various cluster pin styles
	var styles = [[
		{url: chartbase + "' . $this->_cpin . '", width: ' . $c_iconscaled_w . ', height: ' . $c_iconscaled_h . '},
		{url: chartbase + "' . $this->_cpin . '", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.3 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.2 : 1) . '},
		{url: chartbase + "' . $this->_cpin . '", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.6 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . '},
		{url: chartbase + "' . $this->_cpin . '", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.9 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.6 : 1) . '},
		{url: chartbase + "' . $this->_cpin . '", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 2.1 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . '}
	],[
		{url: codebase + "/images/m1.png", width: ' . $c_iconscaled_w . ', height: ' . $c_iconscaled_h . '},
		{url: codebase + "/images/m2.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.2 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.2 : 1) . '},
		{url: codebase + "/images/m3.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . '},
		{url: codebase + "/images/m4.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.6 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.6 : 1) . '},
		{url: codebase + "/images/m5.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . '}
	],[
		{url: codebase + "/images/people35.png", width: ' . $c_iconscaled_w . ', height: ' . $c_iconscaled_h . '},
		{url: codebase + "/images/people45.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . '},
		{url: codebase + "/images/people55.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . '}
	],[
		{url: codebase + "/images/conv30.png", width: ' . $c_iconscaled_w . ', height: ' . $c_iconscaled_h . '},
		{url: codebase + "/images/conv40.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.4 : 1) . '},
		{url: codebase + "/images/conv50.png", width: ' . $c_iconscaled_w * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . ', height: ' . $c_iconscaled_h * (!empty($modSettings['googleMap_ScalableCluster']) ? 1.8 : 1) . '}
	]];

	// Who does not like a good old fashioned cluster, cause thats what we have here
	var style = ' . (is_int($this->_cpin) ? $this->_cpin : 0) . ';
	var mcOptions = {
			gridSize: ' . (!empty($modSettings['googleMap_GridSize']) ? $modSettings['googleMap_GridSize'] : 2) . ',
			maxZoom: 6,
			averageCenter: true,
			zoomOnClick: false,
			minimumClusterSize: ' . (!empty($modSettings['googleMap_MinMarkerPerCluster']) ? $modSettings['googleMap_MinMarkerPerCluster'] : 60) . ',
			title: "' . $txt['googleMap_GroupOfPins'] . '",
			styles: styles[style],
		};';

		echo '

	// Functions to read xml data
	function makeRequest(url) {
		if (window.XMLHttpRequest)
		{
			xhr = new XMLHttpRequest();
		}
		else
		{
			if (window.ActiveXObject) {
				try {
					xhr = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) { }
			}
		}

		if (xhr)
		{
			xhr.onreadystatechange = showContents;
			xhr.open("GET", url, true);
			xhr.send(null);
		}
		else
		{
			document.write("' . $txt['googleMap_xmlerror'] . '");
		}
	}

	function showContents() {
		var xmldoc = \'\';

		if (xhr.readyState === 4)
		{
			// Run on server (200) or local machine (0)
			if (xhr.status === 200 || xhr.status === 0)
			{
				xmldoc = xhr.responseXML;
				makeMarkers(xmldoc);
			}
			else
			{
				document.write("' . $txt['googleMap_error'] . ' - " + xhr.status);
			}
		}
	}

	// Create the map and load our data
	function initialize() {
		// create the map
		var latlng = new google.maps.LatLng(' . (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . ');
		var options = {
			zoom: ' . $modSettings['googleMap_DefaultZoom'] . ',
			center: latlng,
			scrollwheel: false,
			mapTypeId: google.maps.MapTypeId.' . $modSettings['googleMap_Type'] . ',
			mapTypeControlOptions: {
				style: google.maps.MapTypeControlStyle.DROPDOWN_MENU
			},
			zoomControl: true,
			zoomControlOptions: {
				style: google.maps.ZoomControlStyle.' . $modSettings['googleMap_NavType'] . '
			}
		};

		map = new google.maps.Map(document.getElementById("map"), options);

		// Load the members data
		makeRequest(elk_scripturl + "?action=GoogleMap;sa=xml");

		// Our own initial state button since its gone walkies in the v3 api
		var reset = document.getElementById("googleMapReset");

		reset.style.filter = "alpha(opacity=0)";
		reset.style.mozOpacity = "0";
		reset.style.opacity = "0";
	}

	// Read the output of the marker xml
	function makeMarkers(xmldoc) {
		var markers = xmldoc.documentElement.getElementsByTagName("marker"),
			point = null,
			html = null,
			label = null,
			marker = null;

		for (var i = 0; i < markers.length; ++i) {
			point = new google.maps.LatLng(parseFloat(markers[i].getAttribute("lat")), parseFloat(markers[i].getAttribute("lng")));
			html = markers[i].childNodes[0].nodeValue;
			label = markers[i].getAttribute("label");';

		if (!empty($modSettings['googleMap_PinGender']))
			echo '
			if (parseInt(markers[i].getAttribute("gender")) === 0)
				marker = createMarker(point, npic, label, html, i);

			if (parseInt(markers[i].getAttribute("gender")) === 1)
				marker = createMarker(point, mpic, label, html, i);

			if (parseInt(markers[i].getAttribute("gender")) === 2)
				marker = createMarker(point, fpic, label, html, i);
		}';
		else
			echo '
			marker = createMarker(point, npic, label, html, i);
		}';

		// Clustering enabled and we have enough pins?
		if (!empty($modSettings['googleMap_EnableClusterer']) && ($context['total_pins'] > (!empty($modSettings['googleMap_MinMarkertoCluster']) ? $modSettings['googleMap_MinMarkertoCluster'] : 0)))
			echo '
		// Send the markers array to the cluster script
		mc = new MarkerClusterer(map, gmarkers, mcOptions);

		google.maps.event.addListener(mc, "clusterclick", function(cluster) {
			if (infowindow)
				infowindow.close();

			var clusterMarkers = cluster.getMarkers();
			map.setCenter(cluster.getCenter());

			// Build the info window content
			var content = "<div style=\"text-align:left\">",
				numtoshow = Math.min(cluster.getSize(),', $modSettings['googleMap_MaxLinesCluster'], ');

			for (var i = 0; i < numtoshow; ++i)
				content = content + "<img src=\"" + clusterMarkers[i].icon.url + "\" width=\"12\" height=\"12\" />   " + clusterMarkers[i].title + "<br />";

			if (cluster.getSize() > numtoshow)
				content = content + "<br />', $txt['googleMap_Plus'], ' [" + (cluster.getSize() - numtoshow) + "] ', $txt['googleMap_Otherpins'], '";

			content = content + "</div>";

			infowindow = new google.maps.InfoWindow;
			myLatlng = new google.maps.LatLng(cluster.getCenter().lat(), cluster.getCenter().lng());
			infowindow.setPosition(myLatlng);
			infowindow.setContent(content);
			infowindow.open(map);
		});';

		echo '
		// Place the assembled sidebar_html contents into the sidebar div
		document.getElementById("googleSidebar").innerHTML = sidebar_html;
	}

	// Create a marker and set up the event window
	function createMarker(point, pic, name, html, i) {
		// Map marker
		var marker = new google.maps.Marker({
			position: point,
			map: map,
			icon: pic,
			clickable: true,
			title: name.replace(/\[b\](.*)\[\/b\]/gi, "$1")
		});

		// Listen for a marker click
		google.maps.event.addListener(marker, "click", function() {
			if (infowindow)
				infowindow.close();

			infowindow = new google.maps.InfoWindow({content: html, maxWidth:280});
			infowindow.open(map, marker);
		});

		// Save the info used to populate the sidebar
		gmarkers.push(marker);
		htmls.push(html);
		name = name.replace(/\[b\](.*)\[\/b\]/gi, "<strong>$1</strong>");

		// Add a line to the sidebar html';
		if ($modSettings['googleMap_Sidebar'] !== 'none')
			echo '
		sidebar_html += \'<a href="javascript:finduser(\' + i + \')">\' + name + \'</a><br /> \';
	}

	// Picks up the sidebar click and opens the corresponding info window
	function finduser(i) {
		if (infowindow)
			infowindow.close();

		var marker = gmarkers[i]["position"];

		infowindow = new google.maps.InfoWindow({content: htmls[i], maxWidth:280});
		infowindow.setPosition(marker);
		infowindow.open(map);
	}

	// Resets the map to the inital zoom/center values
	function resetMap() {
		// Close any info windows we may have opened
		if (infowindow)
			infowindow.close();

		map.setCenter(new google.maps.LatLng(' . (!empty($modSettings['googleMap_DefaultLat']) ? $modSettings['googleMap_DefaultLat'] : 0) . ', ' . (!empty($modSettings['googleMap_DefaultLong']) ? $modSettings['googleMap_DefaultLong'] : 0) . '));
		map.setZoom(' . $modSettings['googleMap_DefaultZoom'] . ');

		// map.setMapTypeId(google.maps.MapTypeId.' . $modSettings['googleMap_Type'] . ');
	}

	google.maps.event.addDomListener(window, "load", initialize);';

		obExit(false);
	}

	/**
	 * Creates xml data for use on a map
	 * Builds the pin info window content
	 * Builds the map sidebar layout
	 * Called from the googlemap JS initialize function via ajax (?action=GoogleMap;sa=xml)
	 */
	public function action_xml()
	{
		global $context, $settings, $options, $scripturl, $txt, $modSettings, $user_info, $memberContext;

		// Make sure the buffer is empty so we return clean XML to the template
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
			@ob_start('ob_gzhandler');
		else
			ob_start();

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// XML Header
		header('Content-Type: application/xml; charset=UTF-8');

		// Lets load in some pin data
		$temp = gmm_loadPins();

		// Load all of the data for these 'pined' members
		loadMemberData($temp);
		foreach ($temp as $mem)
			loadMemberContext($mem);
		unset($temp);

		// Begin the XML output
		$last_week = time() - (7 * 24 * 60 * 60);
		echo '<?xml version="1.0" encoding="UTF-8"?', '>
		<markers>';
		if (isset($memberContext))
		{
			// To prevent the avatar being outside the popup info window we set a max div height
			$div_height = max(isset($modSettings['avatar_max_height_external']) ? $modSettings['avatar_max_height_external'] : 0, isset($modSettings['avatar_max_height_upload']) ? $modSettings['avatar_max_height_upload'] : 0);

			// For every member with a pin, build the info bubble ...
			foreach ($memberContext as $marker)
			{
				$datablurb = '';

				// Guests don't get to see this ....
				if (!$user_info['is_guest'])
				{
					$datablurb = '
			<div class="googleMap">
				<h4>
					<a  href="' . $marker['online']['href'] . '">
						<img class="centericon" src="' . $marker['online']['image_href'] . '" alt="' . $marker['online']['text'] . '" /></a>
					<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
				</h4>';

					// avatar?
					if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
						$datablurb .= '
					<div class="gmm_avatar" style="height:' . $div_height . 'px">' . $marker['avatar']['image'] . '<br /></div>';

					// user info section
					$datablurb .= '
				<div class="gmm_poster">
					<ul class="reset">';

					// Show the member's primary group (like 'Administrator') if they have one.
					if (!empty($marker['group']))
						$datablurb .= '
						<li class="membergroup">' . $marker['group'] . '</li>';

					// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
					if ((empty($settings['hide_post_group']) || $marker['group'] == '') && $marker['post_group'] != '')
						$datablurb .= '
						<li class="postgroup">' . $marker['post_group'] . '</li>';

					// groups icons
					$datablurb .= '
						<li class="iocns">' . $marker['group_icons'] . '</li>';

					// show the title, if they have one
					if (!empty($marker['title']) && !$user_info['is_guest'])
						$datablurb .= '
						<li class="title">' . $marker['title'] . '</li>';

					// Show the profile, website, email address, and personal message buttons.
					if ($settings['show_profile_buttons'])
					{
						$datablurb .= '
						<li>
							<ul>';

						// Don't show an icon if they haven't specified a website.
						if ($marker['website']['url'] != '' && !isset($context['disabled_fields']['website']))
							$datablurb .= '
								<li>
									<a href="' . $marker['website']['url'] . '" title="' . $marker['website']['title'] . '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $marker['website']['title'] . '" />' : $txt['www']) . '
								</li>';

						// Don't show the email address if they want it hidden.
						if (in_array($marker['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
							$datablurb .= '
								<li>
									<a href="' . $scripturl . '?action=emailuser;sa=email;uid=' . $marker['id'] . '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '
								</li>';

						// Show the PM tag
						$datablurb .= '
								<li>
									<a href="' . $scripturl . '?action=pm;sa=send;u=' . $marker['id'] . '">';
						$datablurb .= $settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/im_' . ($marker['online']['is_online'] ? 'on' : 'off') . '.png" />' : ($marker['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline']);
						$datablurb .= '
								</li>
							</ul>
						</li>';
					}

					$datablurb .= '
					</ul>
				</div>';

					// Show their personal text?
					if (!empty($settings['show_blurb']) && $marker['blurb'] != '')
						$datablurb .= '
				<br class="clear" />' . $marker['blurb'];

					$datablurb .= '
			</div>';
				}

				// Let's bring it all together...
				$markers = '<marker lat="' . round($marker['googleMap']['latitude'], 8) . '" lng="' . round($marker['googleMap']['longitude'], 8) . '" ';

				if ($marker['gender']['name'] == $txt['male'])
					$markers .= 'gender="1"';
				elseif ($marker['gender']['name'] == $txt['female'])
					$markers .= 'gender="2"';
				else
					$markers .= 'gender="0"';

				if (!empty($modSettings['googleMap_BoldMember']) && $marker['googleMap']['pindate'] >= $last_week)
					$markers .= ' label="[b]' . $marker['name'] . '[/b]"><![CDATA[' . $datablurb . ']]></marker>';
				else
					$markers .= ' label="' . $marker['name'] . '"><![CDATA[' . $datablurb . ']]></marker>';

				echo $markers;
			}
		}
		echo '
		</markers>';

		// Ok we should be done with output, dump it to the template
		obExit(false);
	}

	/**
	 * Creates google earth kml data
	 *
	 * - Generates a file for saving that can then be imported in to Google Earth
	 */
	public function action_kml()
	{
		global $smcFunc, $settings, $options, $context, $scripturl, $txt, $modSettings, $user_info, $mbname, $memberContext;

		// Are we allowed to view the map?
		isAllowedTo('googleMap_view');

		// If it's not enabled, die.
		if (empty($modSettings['googleMap_KMLoutput_enable']))
			obExit(false);

		// Language
		loadLanguage('GoogleMap');

		// Start off empty, we want a clean stream
		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
			@ob_start('ob_gzhandler');
		else
			ob_start();

		// Start up the session URL fixer.
		ob_start('ob_sessrewrite');

		// It will be a file called ourforumname.kml
		header('Content-type: application/keyhole;');
		header('Content-Disposition: attachment; filename="' . $mbname . '.kml"');

		// Load all the data up, no need to limit an output file to the 'world'
		$temp = gmm_loadPins(true);

		loadMemberData($temp);
		foreach ($temp as $v)
			loadMemberContext($v);

		$smcFunc['db_free_result']($request);

		// Start building the output
		echo '<?xml version="1.0" encoding="', $context['character_set'], '"?' . '>
		<kml xmlns="http://www.opengis.net/kml/2.2"
		 xmlns:gx="http://www.google.com/kml/ext/2.2">
		<Folder>
			<name>' . $mbname . '</name>
			<open>1</open>';

		// create the pushpin styles ... just color really, all with a 80% transparancy
		echo '
		<Style id="member">
			<IconStyle>
				<color>CF', $this->gmm_validate_color('googleMap_PinBackground', '66FF66'), '</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="cluster">
			<IconStyle>
				<color>CF', $this->gmm_validate_color('googleMap_ClusterBackground', '66FF66'), '</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="female">
			<IconStyle>
				<color>CFFF0099</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>
		<Style id="male">
			<IconStyle>
				<color>CF0066FF</color>
				<scale>1.0</scale>
			</IconStyle>
			<BalloonStyle>
			  <text><![CDATA[
			  <font face="verdana">$[description]</font>
			  <br clear="all"/>
			  $[geDirections]
			  ]]></text>
			</BalloonStyle>
		</Style>';

		if (isset($memberContext))
		{
			// Assuming we have data to work with...
			foreach ($memberContext as $marker)
			{
				// to prevent the avatar being outside the popup window we need to set a max div height
				$div_height = max(isset($modSettings['avatar_max_height_external']) ? $modSettings['avatar_max_height_external'] : 0, isset($modSettings['avatar_max_height_upload']) ? $modSettings['avatar_max_height_upload'] : 0);

				echo '
		<Placemark id="' . $marker['name'] . '">
			<description>
				<![CDATA[
					<div style="width:240px">
						<h4>
							<a href="' . $marker['online']['href'] . '">
								<img src="' . $marker['online']['image_href'] . '" alt="' . $marker['online']['text'] . '" /></a>
							<a href="' . $marker['href'] . '">' . $marker['name'] . '</a>
						</h4>';

				// avatar?
				if (!empty($settings['show_user_images']) && empty($options['show_no_avatars']) && !empty($marker['avatar']['image']))
					echo '
							<div style="float:right;height:' . $div_height . 'px">'
					. $marker['avatar']['image'] . '<br />
							</div>';

				// user info section
				echo '
						<div style="float:left;">
							<ul style="padding:0;margin:0;list-style:none;">';

				// Show the member's primary group (like 'Administrator') if they have one.
				if (!empty($marker['group']))
					echo '
								<li>' . $marker['group'] . '</li>';

				// Show the post group if and only if they have no other group or the option is on, and they are in a post group.
				if ((empty($settings['hide_post_group']) || $marker['group'] == '') && $marker['post_group'] != '')
					echo '
								<li>' . $marker['post_group'] . '</li>';

				// groups icons
				echo '
								<li>' . $marker['group_icons'] . '</li>';

				// show the title, if they have one
				if (!empty($marker['title']) && !$user_info['is_guest'])
					echo '
								<li>' . $marker['title'] . '</li>';

				// Show the profile, website, email address, etc
				if ($settings['show_profile_buttons'])
				{
					echo '
								<li>
									<ul style="padding:0;margin:0;list-style:none;">';

					// Don't show an icon if they haven't specified a website.
					if ($marker['website']['url'] != '' && !isset($context['disabled_fields']['website']))
						echo '
										<li>
											<a href="', $marker['website']['url'], '" title="', $marker['website']['title'], '" target="_blank" class="new_win">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/www_sm.png" alt="' . $marker['website']['title'] . '" />' : $txt['www']) . '
										</li>';

					// Don't show the email address if they want it hidden.
					if (in_array($marker['show_email'], array('yes', 'yes_permission_override', 'no_through_forum')))
						echo '
										<li>
											<a href="', $scripturl, '?action=emailuser;sa=email;uid=', $marker['id'], '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/email_sm.png" alt="' . $txt['email'] . '" title="' . $txt['email'] . '" />' : $txt['email']) . '
										</li>';

					// Show the PM tag
					echo '
										<li>
											<a href="', $scripturl, '?action=pm;sa=send;u=', $marker['id'], '">' . ($settings['use_image_buttons'] ? '<img class="icon" src="' . $settings['images_url'] . '/profile/im_' . ($marker['online']['is_online'] ? 'on' : 'off') . '.png" />' : ($marker['online']['is_online'] ? $txt['pm_online'] : $txt['pm_offline'])) . '
										</li>
									</ul>
								</li>';
				}

				echo '
							</ul>
						</div>
					</div>
				]]>
			</description>
			<name>', $marker['name'], '</name>
			<LookAt>
				<longitude>', round($marker['googleMap']['longitude'], 8), '</longitude>
				<latitude>', round($marker['googleMap']['latitude'], 8), '</latitude>
				<range>15000</range>
			</LookAt>';

				// pin color
				if (!empty($modSettings['googleMap_PinGender']))
				{
					if ($marker['gender']['name'] == 'Male')
						echo '
			<styleUrl>#male</styleUrl>';
					elseif ($marker['gender']['name'] == 'Female')
						echo '
			<styleUrl>#female</styleUrl>';
					else
						echo '
			<styleUrl>#member</styleUrl>';
				}
				else
					echo '
			<styleUrl>#member</styleUrl>';

				echo '
			<Point>
				<extrude>1</extrude>
				<altitudeMode>clampToGround</altitudeMode>
				<coordinates>' . round($marker['googleMap']['longitude'], 8) . ',' . round($marker['googleMap']['latitude'], 8) . ',0</coordinates>
			</Point>
		</Placemark>';
			}
		}
		echo '
		</Folder>
	</kml>';

		// Ok done, should send everything now..
		obExit(false);
	}

	/**
	 * Does the majority of work in determining how the map pin should look based on admin settings
	 */
	private function gmm_buildpins()
	{
		global $modSettings;

		// Lets work out all those options so this works
		$modSettings['googleMap_ClusterBackground'] = $this->gmm_validate_color('googleMap_ClusterBackground', 'FF66FF');
		$modSettings['googleMap_PinBackground'] = $this->gmm_validate_color('googleMap_PinBackground', '66FF66');
		$modSettings['googleMap_ClusterForeground'] = $this->gmm_validate_color('googleMap_ClusterForeground', '202020');
		$modSettings['googleMap_PinForeground'] = $this->gmm_validate_color('googleMap_PinForeground', '202020');

		// What style of member and cluster pins have been chosen
		$this->_npin = $this->gmm_validate_pin('googleMap_PinStyle', 'd_map_pin_icon');
		$this->_cpin = $this->gmm_validate_pin('googleMap_ClusterStyle', 'd_map_pin_icon');

		// Shall we add in shadows
		$this->_nshd = (isset($modSettings['googleMap_PinShadow']) && $modSettings['googleMap_PinShadow']) ? $this->_nshd = '_withshadow' : $this->_nshd = '';
		$this->_cshd = (isset($modSettings['googleMap_ClusterShadow']) && $modSettings['googleMap_ClusterShadow']) ? $this->_cshd = '_withshadow' : $this->_cshd = '';

		// Set the member style, icon or text
		$this->_set_member_pin_style();

		// Cluster pin style, icon, text or image
		$this->_set_cluster_pin_style();

		// And now for the colors
		$this->_mchld .= '|' . $modSettings['googleMap_PinBackground'] . '|' . $modSettings['googleMap_PinForeground'];
		$this->_cchld .= '|' . $modSettings['googleMap_ClusterBackground'] . '|' . $modSettings['googleMap_ClusterForeground'];

		// Finaly build those beautiful pins
		$modSettings['npin'] = '?chst=' . $this->_npin . $this->_nshd . '&chld=' . $this->_mchld;
		$modSettings['cpin'] = is_int($this->_cpin) ? $this->_cpin : '?chst=' . $this->_cpin . $this->_cshd . '&chld=' . $this->_cchld;

		// The gender pins follow the member pin format ....
		if ($this->_npin == 'd_map_pin_icon')
		{
			$modSettings['fpin'] = '?chst=d_map_pin_icon' . $this->_nshd . '&chld=WCfemale|FF0099';
			$modSettings['mpin'] = '?chst=d_map_pin_icon' . $this->_nshd . '&chld=WCmale|0066FF';
		}
		else
		{
			$modSettings['fpin'] = '?chst=d_map_pin_letter' . $this->_nshd . '&chld=|FF0099|' . $modSettings['googleMap_PinForeground'];
			$modSettings['mpin'] = '?chst=d_map_pin_letter' . $this->_nshd . '&chld=|0066FF|' . $modSettings['googleMap_PinForeground'];
		}

		return;
	}

	/**
	 * Sets the cluster pin style
	 */
	private function _set_cluster_pin_style()
	{
		global $modSettings;

		if ($this->_cpin === 'd_map_pin_icon')
			$this->_cchld = ((isset($modSettings['googleMap_ClusterIcon']) && trim($modSettings['googleMap_ClusterIcon']) != '') ? $modSettings['googleMap_ClusterIcon'] : 'info');
		elseif ($this->_cpin === 'd_map_pin_letter')
			$this->_cchld = (isset($modSettings['googleMap_ClusterText']) && trim($modSettings['googleMap_ClusterText']) != '') ? $modSettings['googleMap_ClusterText'] : '';
		elseif (is_int($this->_cpin))
			$this->_cchld = '';
		else
		{
			$this->_cpin = 'd_map_pin_letter';
			$this->_cchld = '';
		}
	}

	/**
	 * Sets the normal pin style
	 */
	private function _set_member_pin_style()
	{
		global $modSettings;

		if ($this->_npin === 'd_map_pin_icon')
			$this->_mchld = ((isset($modSettings['googleMap_PinIcon']) && trim($modSettings['googleMap_PinIcon']) != '') ? $modSettings['googleMap_PinIcon'] : 'info');
		elseif ($this->_npin === 'd_map_pin_letter')
			$this->_mchld = (isset($modSettings['googleMap_PinText']) && trim($modSettings['googleMap_PinText']) != '') ? $modSettings['googleMap_PinText'] : '';
		else
		{
			$this->_npin = 'd_map_pin_letter';
			$this->_mchld = '';
		}
	}

	/**
	 * Makes sure we have a 6digit hex for the color definitions or sets a default value
	 *
	 * @param string $color
	 * @param string $default
	 */
	private function gmm_validate_color($color, $default)
	{
		global $modSettings;

		// no leading #'s please
		if (substr($modSettings[$color], 0, 1) === '#')
			$modSettings[$color] = substr($modSettings[$color], 1);

		// is it a hex
		if (!preg_match('~^[a-f0-9]{6}$~i', $modSettings[$color]))
			$modSettings[$color] = $default;

		return strtoupper($modSettings[$color]);
	}

	/**
	 * Outputs the correct goggle chart pin type based on selection
	 *
	 * @param string $area
	 * @param string $default
	 */
	private function gmm_validate_pin($area, $default)
	{
		global $modSettings;

		// Return the type of pin requested
		if (isset($modSettings[$area]))
		{
			switch ($modSettings[$area])
			{
				case 'googleMap_plainpin':
					$pin = 'd_map_pin';
					break;
				case 'googleMap_textpin':
					$pin = 'd_map_pin_letter';
					break;
				case 'googleMap_iconpin':
					$pin = 'd_map_pin_icon';
					break;
				case 'googleMap_zonepin':
					$pin = 1;
					break;
				case 'googleMap_peepspin':
					$pin = 2;
					break;
				case 'googleMap_talkpin':
					$pin = 3;
					break;
				default:
					$pin = 'd_map_pin_icon';
			}
		}
		else
			$pin = $default;

		return $pin;
	}
}