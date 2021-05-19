<?php

global $scripturl;

// Template strings
$txt['googleMap'] = 'Mappa utenti';
$txt['googleMap_Reset'] = 'Reset mappa';
$txt['googleMap_GroupOfPins'] = "Gruppo di segnaposto";
$txt['googleMap_Where'] = 'Di dove';
$txt['googleMap_Whereis'] = 'è';
$txt['googleMap_Whereare'] = 'sono';
$txt['googleMap_Thereare'] = 'Ci sono %s utenti sulla mappa';
$txt['googleMap_Plus'] = 'più';
$txt['googleMap_Otherpins'] = 'altri segnaposto';
$txt['googleMap_Legend'] = 'Leggenda';
$txt['googleMap_Pinned'] = 'Segnaposto di un utente';
$txt['googleMap_MemberPin'] = 'Segnaposto di qualcuno';
$txt['googleMap_AndrogynyPin'] = 'Nessun genere';
$txt['googleMap_MalePin'] = 'Maschio';
$txt['googleMap_FemalePin'] = 'Femmina';
$txt['googleMap_OnMove'] = 'Aggiunti o modificati di recente';
$txt['googleMap_bold'] = 'Grassetto';
$txt['googleMap_AddPinNote'] = '<b>Clicca qui per aggiungere o modificare la tua posizione sulla mappa.</b>';
$txt['googleMap_PleaseClick'] = 'Clicca sulla mappa per aggiungere o rimuovere il tuo segnaposto.';
$txt['googleMap_Disclaimer'] = '<br />Posiziona il tuo segnaposto quanto vicino ritieni sia necessario.<br />Utilizza la ricerca sulla mappa per aggiungere il tuo segnaposto (Puoi cercare città, provincie, cap, ecc) poi clicca sulla mappa per aggiungere il tuo segnaposto.<br /><br />Quando hai finito clicca su "Aggiorna Profilo" per salvare il tuo segnaposto.';

// Permissions / errors
$txt['cannot_googleMap_view'] = 'Spiacente, non sei abilitato a visualizzare la Mappa Utenti.';
$txt['permissionname_googleMap_view'] = 'Visualizza Mappa Utenti';
$txt['permissionhelp_googleMap_view'] = 'Abilita utenti a visualizzare la Mappa Utenti. Se non settato, gli utenti non vedranno la mappa.';
$txt['cannot_googleMap_place'] = 'Spiacenti, non sei abilitato a posizionare un segnaposto sulla Mappa Utenti.';
$txt['permissionname_googleMap_place'] = 'Posiziona segnaposto sulla Mappa Utenti';
$txt['permissionhelp_googleMap_place'] = 'Abilita utenti a posizionare un segnaposto sulla Mappa Utenti. Se non settato, gli utenti non potranno posizionare segnaposti sulla mappa.';
$txt['googleMap_xmlerror'] = 'Errore durante la richiesta ajax';
$txt['googleMap_error'] = 'Impossibile leggere i dati sulla mapa, il risultato è stato';

// Map addon settings
$txt['googleMap_license'] = 'The Google JavaScript Maps API V3 is a free service, available for any web site that is free to consumers. By enabling and using this ElkArte modification you are acknowledging and agreeing to the <a href="https://developers.google.com/maps/terms?csw=1" target="_blank"> Google terms of use</a>';
$txt['googleMap_Key'] = 'Google Map API key';
$txt['googleMap_Key_desc'] = '<a class="linkbutton" href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Get an API key</a>';
$txt['googleMap_MapSettings'] = 'General Map Settings';
$txt['googleMap_desc'] = 'From here you can control the settings for how the Member Map appears and functions on your forum';
$txt['googleMap_Enable'] = 'Enable the Member Map Modification';
$txt['googleMap_ButtonLocation'] = 'Select the top menu location for the Member Map button';
$txt['googleMap_DefaultLat'] = 'The default Latitude';
$txt['googleMap_DefaultLat_info'] = 'Latitude for map center';
$txt['googleMap_DefaultLong'] = 'The default Longitude';
$txt['googleMap_DefaultLong_info'] = 'Longitude for map center';
$txt['googleMap_DefaultZoom'] = 'The default Zoom Level';
$txt['googleMap_DefaultZoom_Info'] = 'Defines the default map location by defining the map center and zoom level. Examples: Europe: Lat:48, Lng:15, Zm:4 / USA: Lat:39, Lng:-95, Zm:4';
$txt['googleMap_Type'] = 'The type of map to display by default';
$txt['googleMap_roadmap'] = 'RoadMap';
$txt['googleMap_satellite'] = 'Satellite';
$txt['googleMap_hybrid'] = 'Hybrid';
$txt['googleMap_EnableLegend'] = 'Display a Pin Legend below the map';
$txt['googleMap_KMLoutput_enable'] = 'Allow KML (Google Earth) output';
$txt['googleMap_KMLoutput_enable_info'] = 'KML is a file that is downloadable on the Google Map page that will display the member pins inside Google Earth';
$txt['googleMap_PinNumber'] = 'Maximum number of pins to show on map';
$txt['googleMap_PinNumber_info'] = 'Enter 0 for no limit';
$txt['googleMap_Sidebar'] = 'Where to show the map sidebar';
$txt['googleMap_nosidebar'] = 'No Sidebar';
$txt['googleMap_rightsidebar'] = 'Right Sidebar';
$txt['googleMap_leftsidebar'] = 'Left Sidebar';
$txt['googleMap_BoldMember'] = 'Show recently added / moved pins as <strong>bold</strong> in the sidebar';

$txt['googleMap_MemeberpinSettings'] = 'Member Pin Style';
$txt['googleMap_PinStyle'] = 'What style of member pin to use';
$txt['googleMap_PinSize'] = 'Enter the size of the member icon pin';
$txt['googleMap_plainpin'] = 'Plain Pin';

$txt['googleMap_ClusterpinSettings'] = 'Cluster Pin Settings';
$txt['googleMap_EnableClusterer'] = 'Enable Pin Clustering';
$txt['googleMap_EnableClusterer_info'] = 'Groups nearby pins into a single cluster pin to prevent overloading a map.  Zooming in on a cluster will expand it out to the individual pins.';
$txt['googleMap_MinMarkerPerCluster'] = 'Minimum number of pins per grid to generate a cluster';
$txt['googleMap_MinMarkertoCluster'] = 'Minimum number of pins on the map before clustering starts (if enabled)';
$txt['googleMap_GridSize'] = 'The size of the cluster searching grid';
$txt['googleMap_MaxLinesCluster'] = 'Maximum number of info lines to display in a Cluster Info Box';
$txt['googleMap_ClusterpinStyle'] = 'Cluster Pin Style';
$txt['googleMap_PinBackground'] = 'Background color of the member pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterBackground'] = 'Background color of the cluster pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterForeground'] = 'Text color of the cluster pin as a six-digit HTML hexadecimal color';
$txt['googleMap_ClusterStyle'] = 'What style of clustering pin to use';
$txt['googleMap_ClusterSize'] = 'Enter the size of the cluster pin';
$txt['googleMap_zonepin'] = 'Zone icon';
$txt['googleMap_peepspin'] = 'People icon';
$txt['googleMap_talkpin'] = 'Talk icon';
$txt['googleMap_ScalableCluster'] = 'Allow Clusters with more pins to grow in size';
$txt['googleMap_ScalableCluster_info'] = 'Allows cluster pins to grow dynamically in size depending on how many pins they contain';

// Who strings
$txt['whoall_googlemap'] = 'Sta visualizzando la <a href="' . $scripturl . '?action=GoogleMap">Mappa Utenti</a>.';
$txt['whoall_kml'] = 'Sta visualizzando un feed di Google Earth.';
