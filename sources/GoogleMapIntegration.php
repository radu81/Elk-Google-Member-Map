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

if (!defined('ELK'))
	die('No access...');

/**
 * integrate_member_context hook
 *
 * Called from load.php
 * Used to add items to the $memberContext array
 */
function imc_googlemap($user, $display_custom_fields)
{
	global $memberContext, $user_profile;

	$memberContext[$user] += array(
		'googleMap' => array(
			'latitude' => !isset($user_profile[$user]['latitude']) ? 0 : (float) $user_profile[$user]['latitude'],
			'longitude' => !isset($user_profile[$user]['longitude']) ? 0 : (float) $user_profile[$user]['longitude'],
			'pindate' => !isset($user_profile[$user]['pindate']) ? '' : $user_profile[$user]['pindate'],
		)
	);
}

/**
 * integrate load member data
 *
 * Called from load.php
 * Used to add columns / tables to the query so additional data can be loaded for a set
 *
 */
function ilmd_googlemap(&$select_columns, &$select_tables, $set)
{
	if ($set == 'profile' || $set == 'normal')
		$select_columns .= ',mem.latitude, mem.longitude, mem.pindate';
}

/**
 * integrate_load_profile_fields
 *
 * Called from profile.subs
 * Used to add additonal fields to the profile createlist
 */
function ilpf_googlemap(&$profile_fields)
{
	// Our callback_func template is here
	LoadTemplate('GoogleMap', 'GoogleMap');

	$profile_fields += array(
		'latitude' => array(
			'type' => 'callback',
			'callback_func' => 'googlemap_modify',
			'permission' => 'googleMap_place',
			'input_validate' => create_function('&$value', '
				global $profile_vars, $cur_profile;

				// Set latitude to a float value
				$value = (float) $value;

				// Fix up longitude as well
				$profile_vars[\'longitude\'] = !empty($_POST[\'longitude\']) ? (float) $_POST[\'longitude\'] : 0;
				$cur_profile[\'longitude\'] = !empty($_POST[\'longitude\']) ? (float) $_POST[\'longitude\'] : 0;

				// Right now is a good time for the pin date ;)
				$pintime = time();
				$profile_vars[\'pindate\'] = (int) $pintime;
				$cur_profile[\'pindate\'] = (int) $pintime;

				return true;
			'),
			'preload' => create_function('', '
				global $context, $cur_profile;

				$context[\'member\'][\'googleMap\'][\'latitude\'] = (float) $cur_profile[\'latitude\'];
				$context[\'member\'][\'googleMap\'][\'longitude\'] = (float) $cur_profile[\'longitude\'];
				$context[\'member\'][\'googleMap\'][\'pindate\'] = $cur_profile[\'pindate\'];

				return true;
			'),
		)
	);
}

/**
 * integrate_menu_buttons
 *
 * Menu Button hook, called from subs.php
 * used to add top menu buttons
 *
 * @param array $buttons
 */
function imb_googlemap(&$buttons)
{
	global $txt, $scripturl, $modSettings;

	loadlanguage('GoogleMap');

	// Where do we want to place our button
	$insert_after = empty($modSettings['googleMap_ButtonLocation']) ? 'calendar' : $modSettings['googleMap_ButtonLocation'];
	$counter = 0;

	// find the location in the buttons array
	foreach ($buttons as $area => $dummy)
	{
		if (++$counter && $area == $insert_after)
			break;
	}

	// Define the new menu item(s)
	$new_menu = array(
		'googlemap' => array(
			'title' => $txt['googleMap'],
			'href' => $scripturl . '?action=googlemap',
			'show' => !empty($modSettings['googleMap_Enable']) && allowedTo('googleMap_view'),
			'sub_buttons' => array(),
		)
	);

	// Insert the new items in the existing array with array-a-matic ...it slices, it dices, it puts it back together
	$buttons = array_merge(array_slice($buttons, 0, $counter), array_merge($new_menu, array_slice($buttons, $counter)));
}

/**
 * integrate_profile_save
 *
 * Profile save fields hook, called from Profile.controller.php
 * used to prep and check variables before a profile update is saved
 */
function ips_googlemap(&$profile_vars, &$post_errors, $memID)
{
	if (isset($_POST['latitude']))
		$profile_vars['latitude'] = $_POST['latitude'] != '' ? (float) $_POST['latitude'] : 0;

	if (isset($_POST['longitude']))
		$profile_vars['longitude'] = $_POST['longitude'] != '' ? (float) $_POST['longitude'] : 0;
}

/**
 * ilp_googlemap()
 *
 * Permissions hook, integrate_load_permissions, called from ManagePermissions.php
 * used to add new permisssions
 *
 * @param array $permissionGroups
 * @param array $permissionList
 * @param array $leftPermissionGroups
 * @param array $hiddenPermissions
 * @param array $relabelPermissions
 */
function ilp_googlemap(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	$permissionList['membergroup']['googleMap_view'] = array(false, 'general', 'view_basic_info');
	$permissionList['membergroup']['googleMap_place'] = array(false, 'general', 'view_basic_info');
}

/**
 * iaa_googlemap()
 *
 * Admin Hook, integrate_admin_areas, called from Admin.php
 * used to add/modify admin menu areas
 *
 * @param array $admin_areas
 */
function iaa_googlemap(&$admin_areas)
{
	global $txt;

	loadlanguage('GoogleMap');
	$admin_areas['config']['areas']['modsettings']['subsections']['googlemap'] = array($txt['googleMap']);
}

/**
 * imm_googlemap()
 *
 * Modifications hook, integrate_modify_modifications, called from ManageSettings.php
 * used to add new menu screens areas.
 *
 * @param array $sub_actions
 */
function imm_googlemap(&$sub_actions)
{
	$sub_actions['googlemap'] = array(
		'dir' => SOURCEDIR,
		'file' => 'GoogleMapIntegration.php',
		'function' => 'ModifyGoogleMapSettings'
	);
}

/**
 * ModifyGoogleMapSettings()
 *
 * The defines our settings array and uses our settings class to manage the data
 */
function ModifyGoogleMapSettings()
{
	global $txt, $scripturl, $context;

	loadlanguage('GoogleMap');
	$context[$context['admin_menu_name']]['tab_data']['tabs']['googlemap']['description'] = $txt['googleMap_desc'];

	// Lets build a settings form
	require_once(SUBSDIR . '/Settings.class.php');

	// Instantiate the form
	$gmmSettings = new Settings_Form();

	$config_vars = array(
		// Map - On or off?
		array('check', 'googleMap_Enable', 'postinput' => $txt['googleMap_license']),
		// Default Location/Zoom/Map Controls/etc
		array('title', 'googleMap_MapSettings'),
		array('select', 'googleMap_ButtonLocation', array(
				'home' => $txt['home'],
				'help' => $txt['help'],
				'search' => $txt['search'],
				'login' => $txt['login'],
				'register' => $txt['register'],
				'calendar' => $txt['calendar'],
				'profile' => $txt['profile'],
				'pm' => $txt['pm_short'])
		),
		array('float', 'googleMap_DefaultLat', 10, 'postinput' => $txt['googleMap_DefaultLat_info']),
		array('float', 'googleMap_DefaultLong', 10, 'postinput' => $txt['googleMap_DefaultLong_info']),
		array('int', 'googleMap_DefaultZoom', 'subtext' => $txt['googleMap_DefaultZoom_Info']),
		array('select', 'googleMap_Type', array(
				'ROADMAP' => $txt['googleMap_roadmap'],
				'SATELLITE' => $txt['googleMap_satellite'],
				'HYBRID' => $txt['googleMap_hybrid'])
		),
		array('select', 'googleMap_NavType', array(
				'LARGE' => $txt['googleMap_largemapcontrol3d'],
				'SMALL' => $txt['googleMap_smallzoomcontrol3d'],
				'DEFAULT' => $txt['googleMap_defaultzoomcontrol'])
		),
		array('check', 'googleMap_EnableLegend'),
		array('check', 'googleMap_KMLoutput_enable', 'subtext' => $txt['googleMap_KMLoutput_enable_info']),
		array('int', 'googleMap_PinNumber', 'subtext' => $txt['googleMap_PinNumber_info']),
		array('select', 'googleMap_Sidebar', array(
				'none' => $txt['googleMap_nosidebar'],
				'right' => $txt['googleMap_rightsidebar'],
				'left' => $txt['googleMap_leftsidebar'])
		),
		array('check', 'googleMap_BoldMember'),
		// Member Pin Style
		array('title', 'googleMap_MemeberpinSettings'),
		array('check', 'googleMap_PinGender'),
		array('text', 'googleMap_PinBackground', 6),
		array('text', 'googleMap_PinForeground', 6),
		array('select', 'googleMap_PinStyle', array(
				'googleMap_plainpin' => $txt['googleMap_plainpin'],
				'googleMap_textpin' => $txt['googleMap_textpin'],
				'googleMap_iconpin' => $txt['googleMap_iconpin'])
		),
		array('check', 'googleMap_PinShadow'),
		array('int', 'googleMap_PinSize', 2),
		array('text', 'googleMap_PinText', 10, 'postinput' => $txt['googleMap_PinText_info']),
		array('select', 'googleMap_PinIcon',
			gmm_pinArray(),
			'postinput' => $txt['googleMap_PinIcon_info']
		),
		// Clustering Options
		array('title', 'googleMap_ClusterpinSettings'),
		array('check', 'googleMap_EnableClusterer', 'subtext' => $txt['googleMap_EnableClusterer_info']),
		array('int', 'googleMap_MinMarkerPerCluster'),
		array('int', 'googleMap_MinMarkertoCluster'),
		array('int', 'googleMap_GridSize'),
		array('check', 'googleMap_ScalableCluster', 'subtext' => $txt['googleMap_ScalableCluster_info']),
		// Clustering Style
		array('title', 'googleMap_ClusterpinStyle'),
		array('text', 'googleMap_ClusterBackground', 6),
		array('text', 'googleMap_ClusterForeground', 6),
		array('select', 'googleMap_ClusterStyle', array(
				'googleMap_plainpin' => $txt['googleMap_plainpin'],
				'googleMap_textpin' => $txt['googleMap_textpin'],
				'googleMap_iconpin' => $txt['googleMap_iconpin'],
				'googleMap_zonepin' => $txt['googleMap_zonepin'],
				'googleMap_peepspin' => $txt['googleMap_peepspin'],
				'googleMap_talkpin' => $txt['googleMap_talkpin'])
		),
		array('check', 'googleMap_ClusterShadow'),
		array('int', 'googleMap_ClusterSize', '2'),
		array('text', 'googleMap_ClusterText', 'postinput' => $txt['googleMap_PinText_info']),
		array('select', 'googleMap_ClusterIcon',
			gmm_pinArray(),
			'postinput' => $txt['googleMap_PinIcon_info']
		),
	);

	// Load the settings to the form class
	$gmmSettings->settings($config_vars);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();
		Settings_Form::save_db($config_vars);
		redirectexit('action=admin;area=modsettings;sa=googlemap');
	}

	// Continue on to the settings template

	$context['post_url'] = $scripturl . '?action=admin;area=modsettings;save;sa=googlemap';
	$context['settings_title'] = $txt['googleMap'];
	loadJavascriptFile('jscolor/jscolor.js');
	addInlineJavascript('
		var myPicker1 = new jscolor.color(document.getElementById(\'googleMap_PinBackground\'), {});
		myPicker1.fromString(document.getElementById(\'googleMap_PinBackground\').value);

		var myPicker2 = new jscolor.color(document.getElementById(\'googleMap_PinForeground\'), {});
		myPicker2.fromString(document.getElementById(\'googleMap_PinForeground\').value);

		var myPicker3 = new jscolor.color(document.getElementById(\'googleMap_ClusterBackground\'), {});
		myPicker3.fromString(document.getElementById(\'googleMap_ClusterBackground\').value);

		var myPicker4 = new jscolor.color(document.getElementById(\'googleMap_ClusterForeground\'), {});
		myPicker4.fromString(document.getElementById(\'googleMap_ClusterForeground\').value);', true);

	Settings_Form::prepare_db($config_vars);
	return;
}

/**
 * Defines an array of pins icons for use in the settings form
 */
function gmm_pinArray()
{
	global $txt;

	return array(
			'academy' => $txt['academy'],
			'activities' => $txt['activities'],
			'airport' => $txt['airport'],
			'amusement' => $txt['amusement'],
			'aquarium' => $txt['aquarium'],
			'art-gallery' => $txt['art-gallery'],
			'atm' => $txt['atm'],
			'baby' => $txt['baby'],
			'bank-dollar' => $txt['bank-dollar'],
			'bank-euro' => $txt['bank-euro'],
			'bank-intl' => $txt['bank-intl'],
			'bank-pound' => $txt['bank-pound'],
			'bank-yen' => $txt['bank-yen'],
			'bar' => $txt['bar'],
			'barber' => $txt['barber'],
			'beach' => $txt['beach'],
			'beer' => $txt['beer'],
			'bicycle' => $txt['bicycle'],
			'books' => $txt['books'],
			'bowling' => $txt['bowling'],
			'bus' => $txt['bus'],
			'cafe' => $txt['cafe'],
			'camping' => $txt['camping'],
			'car-dealer' => $txt['car-dealer'],
			'car-rental' => $txt['car-rental'],
			'car-repair' => $txt['car-repair'],
			'casino' => $txt['casino'],
			'caution' => $txt['caution'],
			'cemetery-grave' => $txt['cemetery-grave'],
			'cemetery-tomb' => $txt['cemetery-tomb'],
			'cinema' => $txt['cinema'],
			'civic-building' => $txt['civic-building'],
			'computer' => $txt['computer'],
			'corporate' => $txt['corporate'],
			'fire' => $txt['fire'],
			'flag' => $txt['flag'],
			'floral' => $txt['floral'],
			'helicopter' => $txt['helicopter'],
			'home' => $txt['home1'],
			'info' => $txt['info'],
			'landslide' => $txt['landslide'],
			'legal' => $txt['legal'],
			'location' => $txt['location1'],
			'locomotive' => $txt['locomotive'],
			'medical' => $txt['medical'],
			'mobile' => $txt['mobile'],
			'motorcycle' => $txt['motorcycle'],
			'music' => $txt['music'],
			'parking' => $txt['parking'],
			'pet' => $txt['pet'],
			'petrol' => $txt['petrol'],
			'phone' => $txt['phone'],
			'picnic' => $txt['picnic'],
			'postal' => $txt['postal'],
			'repair' => $txt['repair'],
			'restaurant' => $txt['restaurant'],
			'sail' => $txt['sail'],
			'school' => $txt['school'],
			'scissors' => $txt['scissors'],
			'ship' => $txt['ship'],
			'shoppingbag' => $txt['shoppingbag'],
			'shoppingcart' => $txt['shoppingcart'],
			'ski' => $txt['ski'],
			'snack' => $txt['snack'],
			'snow' => $txt['snow'],
			'sport' => $txt['sport'],
			'star' => $txt['star'],
			'swim' => $txt['swim'],
			'taxi' => $txt['taxi'],
			'train' => $txt['train'],
			'truck' => $txt['truck'],
			'wc-female' => $txt['wc-female'],
			'wc-male' => $txt['wc-male'],
			'wc' => $txt['wc'],
			'wheelchair' => $txt['wheelchair'],
		);
}