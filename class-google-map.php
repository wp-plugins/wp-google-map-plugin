<?php
class Wpgmp_Google_Map
{

var $code='';  // Do not edit this.

var $zoom=14; // Zoop Level.

var $center_lat = '37.09024'; // google map center location

var $center_lng = '-95.712891'; // google map center location

var $center_address = '';

var $divID='map'; // The div id where you want to 	place your google map

var $groupID = 'groupmap';

var $marker=array(); // Array to store markers information. 

var $instance=1;

var $width="";

var $height="";

var $title = 'WP Google Map Plugin';

var $polygon = array();

var $polyline = array();

var $routedirections = array();

var $map_draw_polygon = "";

var $kml_layers_links="";

var $fusion_select="";

var $fusion_from="";

var $heat_map="";

var $temperature_unit="";

var $wind_speed_unit="";

var $map_width = "";

var $map_height = "";

var $map_start_point="";

var $map_end_point="";

var $map_multiple_point="";

var $map_scrolling_wheel="true";

var $map_pan_control="true";

var $map_zoom_control="true";

var $map_type_control="true"; 

var $map_scale_control="true";

var $map_street_view_control="true";

var $map_overview_control="true";

var $map_enable_info_window_setting = "";

var $map_info_window_width="";

var $map_info_window_height="";

var $map_info_window_shadow_style="";

var $map_info_window_border_radius="";

var $map_info_window_border_width=""; 

var $map_info_window_border_color="";

var $map_info_window_background_color="";

var $map_info_window_arrow_size="";

var $map_info_window_arrow_position="";

var $map_info_window_arrow_style="";

var $map_style_google_map="";

var $map_language="en";

var $polygon_border_color="#f22800";

var $polygon_background_color="#f22800";

var $map_draw_polyline="";

var $map_polyline_stroke_color="";

var $map_polyline_stroke_opacity="";

var $map_polyline_stroke_weight="";

var $map_type="ROADMAP";

var $map_45="";

var $map_layers="";

var $marker_cluster="";

var $grid="";

var $max_zoom="14";

var $style="default";

var $map_overlay = "";

var $map_overlay_border_color="#F22800";

var $map_overlay_width="200";

var $map_overlay_height="200";

var $map_overlay_fontsize="16";

var $map_overlay_border_width="200";

var $map_overlay_border_style="";

var $polygontriangle="polygontriangle"; 

var $visualrefresh = "false";

var $directionsDisplay = "directionsDisplay";

var $directionsService = "directionsService";

var $route_direction = "";

var $map_way_point = "";

var $route_direction_stroke_color = "";

var $route_direction_stroke_opacity = "";

var $route_direction_stroke_weight = "";

var $street_control = "";

var $street_view_close_button = "";

var $links_control = "";

var $street_view_pan_control = "";

var $enable_group_map = "";

var $group_data = "";

var $groups_markers = array();

var $infowindow = "infowindow";
	
function __construct()
{
	global $wpgmp_containers;	
}

// Intialized google map scripts.

private function start()
{

if( $this->center_address )
{ 
	$output = $this->getData($this->center_address);	

if( $output->status == 'OK' )
{
	$this->center_lat = $output->results[0]->geometry->location->lat;
	$this->center_lng = $output->results[0]->geometry->location->lng;
}

}

if( $this->map_width!='' && $this->map_height!='' )
{
  
	  $width = $this->map_width."px";
	  $height = $this->map_height."px";
   
}
elseif( $this->map_width=='' && $this->map_height!='' )
{
  
	  $width = "100%";
	  $height = $this->map_height."px";
   
}
elseif( $this->map_width=='' && $this->map_height=='' )
{
  
	  $width = "100%";
	  $height = "300px";
   
}
else
{
	  $width =  $this->map_width."px";
	  $height = "300px";
	  
}
$this->divID="wgmpmap";
$this->code='
<style>
#'.$this->divID.'
img {
max-width: none;
}
</style>'.'
<div id='.$this->divID.' style="width:'.$width.'; height:'.$height.';"></div>';

if( $this->enable_group_map=='true' )
{
	
	$this->code.='<div id='.$this->groupID.' style="width:'.$width.'; height:50px;">';
	
	for($gm=0; $gm<count($this->group_data); $gm++)
	{
		
		$this->code.='<img src="'.$this->group_data[$gm]->group_marker.'" onclick="maps_group_id('.$this->group_data[$gm]->group_map_id.')" style="padding:5px 8px 0px 8px; cursor:pointer;">';
	}
	
	$this->code.='</div>';
}

$this->code.='

<script type="text/javascript">

var infoWindows = [];

';

$this->code.='google.load("maps", "3.7", {"other_params" : "sensor=false&libraries=places,weather,panoramio&language='.get_option('wpgmp_language').'"});

google.setOnLoadCallback(initialize);';	
	
if( $this->enable_group_map == 'true' )
{	
	$this->code.='var groups = [];';
}


$this->code.='function initialize() {';
	
if( $this->route_direction == 'true' )
{
	$this->code.=''.$this->directionsService.' = new google.maps.DirectionsService();';
	
	if(count($this->marker)<3)
	{
		
	  $this->code.='
		var start = "'.$this->map_way_point[0]->location_address.'";
		var end = "'.$this->map_way_point[1]->location_address.'"

		var request = {
			origin: start,
			destination: end,
			travelMode: google.maps.TravelMode.DRIVING,
			unitSystem: google.maps.DirectionsUnitSystem.METRIC,
			optimizeWaypoints: false
		};
		
		'.$this->directionsService.'.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK)
			{
				   var polyOpts = {
						strokeColor: "#'.$this->route_direction_stroke_color.'",
						strokeOpacity: '.$this->route_direction_stroke_opacity.',
						strokeWeight: '.$this->route_direction_stroke_weight.'
					}
	
				   var rendererOptions = {
						draggable: true,
						suppressMarkers: false, 
						suppressInfoWindows: false, 
						preserveViewport: false, 
						polylineOptions: polyOpts
					};
			
			'.$this->directionsDisplay.' = new google.maps.DirectionsRenderer(rendererOptions);
			'.$this->directionsDisplay.'.setMap('.$this->divID.');
			'.$this->directionsDisplay.'.setDirections(response);
					
			}
			else
			{
			console.info("could not get route");
			console.info(response);
			}
	  });
	';
	}
	elseif( count($this->marker)>2 )
	{
		
		$start_point = current($this->map_way_point);
		$end_point = end($this->map_way_point);
		$newarray = array_slice($this->map_way_point, 1, -1);
		foreach($newarray as $newarr)
		{
		 $new_array_value[] = $newarr->location_address;
		}
		
		$js_array = json_encode($new_array_value);

	  $this->code.='
		var start = "'.$start_point->location_address.'";
		var end = "'.$end_point->location_address.'";
		var waypts = [];
		checkboxArray = '.$js_array.';
		
		for(var mp=0; mp<checkboxArray.length; mp++){
			waypts.push({
				location:checkboxArray[mp],
				stopover:true});
		}
		

		var request = {
			origin: start,
			destination: end,
			waypoints: waypts,
			optimizeWaypoints: true,
			travelMode: google.maps.TravelMode.DRIVING
		};
		
		'.$this->directionsService.'.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK)
			{
				   var polyOpts = {
						strokeColor: "#'.$this->route_direction_stroke_color.'",
						strokeOpacity: '.$this->route_direction_stroke_opacity.',
						strokeWeight: '.$this->route_direction_stroke_weight.'
					}
	
				   var rendererOptions = {
						draggable: true,
						suppressMarkers: false, 
						suppressInfoWindows: false, 
						preserveViewport: false, 
						polylineOptions: polyOpts
					};
			
			'.$this->directionsDisplay.' = new google.maps.DirectionsRenderer(rendererOptions);
			'.$this->directionsDisplay.'.setMap('.$this->divID.');
			'.$this->directionsDisplay.'.setDirections(response);
					
			}
			else
			{
			console.info("could not get route");
			console.info(response);
			}
	  });
	';
	
	}
}




				  
	$this->code.='var latlng = new google.maps.LatLng('.$this->center_lat.','.$this->center_lng.');';

if( $this->street_control!='true' )
{		

	$this->code.='var mapOptions = {';
		
if( empty($this->map_45) )
{

	$this->code.='zoom: '.$this->zoom.',';

}
else
{

	$this->code.='zoom: 18,';	

}
		
$this->code.='scrollwheel: '.$this->map_scrolling_wheel.',
		
		panControl: '.$this->map_pan_control.',
		
		zoomControl: '.$this->map_zoom_control.',
		
		mapTypeControl: '.$this->map_type_control.',
		
		scaleControl: '.$this->map_scale_control.',
		
		streetViewControl: '.$this->map_street_view_control.',
		
		overviewMapControl: '.$this->map_overview_control.',

		center: latlng,

		mapTypeId: google.maps.MapTypeId.'.$this->map_type.'

		}

		'.$this->divID.' = new google.maps.Map(document.getElementById("'.$this->divID.'"), mapOptions);';
}
else
{		
		$this->code.='var panoOptions = {
    			position: latlng,
    			addressControlOptions: {
      			position: google.maps.ControlPosition.BOTTOM_CENTER
    		},
    			linksControl: '.$this->links_control.',
    			panControl: '.$this->street_view_pan_control.',
    			zoomControlOptions: {
      			style: google.maps.ZoomControlStyle.SMALL
    		},
    			enableCloseButton: '.$this->street_view_close_button.'
  		};

  		var panorama = new google.maps.StreetViewPanorama(document.getElementById("'.$this->divID.'"), panoOptions);
		';
}
		 		
if( !empty($this->map_45) )
{

	$this->code.=''.$this->divID.'.setTilt('.$this->map_45.');';

}


if( $this->map_layers=="TrafficLayer" )
{
	$this->code.='
	
	var trafficLayer = new google.maps.'.$this->map_layers.'();
	
	trafficLayer.setMap('.$this->divID.');';
}

if( $this->map_layers=="TransitLayer" )
{
	$this->code.='

	var transitLayer = new google.maps.'.$this->map_layers.'();

	transitLayer.setMap('.$this->divID.');';
}

if( $this->map_layers=="WeatherLayer" )
{
	$this->code.='

	var weatherLayer = new google.maps.weather.'.$this->map_layers.'({

	windSpeedUnit: google.maps.weather.WindSpeedUnit.'.$this->wind_speed_unit.',

	temperatureUnits: google.maps.weather.TemperatureUnit.'.$this->temperature_unit.'

	});

	weatherLayer.setMap('.$this->divID.');

	var cloudLayer = new google.maps.weather.CloudLayer();

	cloudLayer.setMap('.$this->divID.');';
}

if( $this->map_layers=="BicyclingLayer" )
{
	$this->code.='

	var bikeLayer = new google.maps.'.$this->map_layers.'();

	bikeLayer.setMap('.$this->divID.');';

}


if( $this->displaymarker!='' ) {
	$displaymarker = $this->displaymarker[0];
	$this->code.='
	var latLng = new google.maps.LatLng('.$displaymarker['lat'].','.$displaymarker['long'].')
	var marker = new google.maps.Marker({
				 map:'.$this->divID.',
				 position: latLng,
				 title:"'.$displaymarker['title'].'"
	});';
	
	$infos = str_replace(array("\r","\n"),'"+"',$displaymarker['message']);
						
	$this->code.='
	'.$this->infowindow.' =  new google.maps.InfoWindow({
												content: "'.$infos.'"
												});	';
	
	$this->code.=" infoWindows.push(".$this->infowindow.");"; 											
	$this->code.="google.maps.event.addListener(marker, 'click', function() { ";											
	$this->code.="wgmp_closeAllInfoWindows();";
	$this->code.="".$this->infowindow.".open(".$this->divID.",marker);
	
	
	
	});";
}

		
		
		
for($i=0; $i < count($this->marker); $i++)
{
  
  if( empty($this->marker[$i]['draggable']) )
	 $this->marker[$i]['draggable']='false';

	 $this->code.='marker'.$i.$this->divID.'=new google.maps.Marker({
		map: '.$this->divID.',
		draggable:'.$this->marker[$i]['draggable'].',';
		$this->code.='position: new google.maps.LatLng('.$this->marker[$i]['lat'].', '.$this->marker[$i]['lng'].'), 
		title: "'.$this->marker[$i]['title'].'",
		clickable: '.$this->marker[$i]['click'].',
		icon: "'.$this->marker[$i]['icon'].'",
	  });';
  
 if( $this->enable_group_map=='true' )
 {
	  if($this->marker[$i]['group_id'])
	  {
	   $group_id = $this->marker[$i]['group_id'];
	  
	  $this->code .= "\n".'if(typeof groups.group'.$group_id.' == "undefined")
					  groups.group'.$group_id.' = [];
	  ';	  
		  
	   $this->code .= "\n".'groups.group'.$group_id.'.push(marker'.$i.$this->divID.');';	  
	 }
 }
 
// Creating an InfoWindow object

if( $this->marker[$i]['info']!='' )
{
	$infos = $this->marker[$i]['info'];
	
	if( is_array($infos) )
	{
		$infos_mess_one = str_replace(array("\r","\n"),'"+"',$infos['first']['message']);
				$this->code.='
				'.$this->infowindow.''.$i.$this->divID.' =  new google.maps.InfoWindow({
					content: "'.$infos_mess_one.'"
				});';

	}
	elseif( $infos!='' )
	{
		$infos = str_replace(array("\r","\n"),'"+"',$infos);
			
		$this->code.='
		'.$this->infowindow.''.$i.$this->divID.' =  new google.maps.InfoWindow({
			content: "'.$infos.'"
		});
		';
	}
	


	$this->code.="google.maps.event.addListener(marker".$i.$this->divID.", 'click', function() { ";
	
	$this->code.="wgmp_closeAllInfoWindows();";

	$this->code.=" infoWindows.push(".$this->infowindow.''.$i.$this->divID.");"; 											

	$this->code.="
				".$this->infowindow."".$i.$this->divID.".open(".$this->divID.",marker".$i.$this->divID.");
			google.maps.event.addListener(".$this->divID.", 'click', function() {
			".$this->infowindow."".$i.$this->divID.".close();
		});";
		

	$this->code.="});"; 
}

}

	
$this->code.='}


function wgmp_closeAllInfoWindows() {
  for (var i=0;i<infoWindows.length;i++) {
     infoWindows[i].close();
  }
  infoWindows = [];
}

';


if( $this->enable_group_map == 'true' )
{	

$this->code.='

function maps_group_id(group_id)
{
	
position = false;
var bounds = new google.maps.LatLngBounds();
if(groups)
{	
 for( var n in groups){
 
   if(n.indexOf("group") != "-1"){
	 if(n == "group"+group_id)
	 {
	   for(i = 0; i <groups[n].length; i++){
		if( typeof groups[n][i].getMap() == "null");
		groups[n][i].setMap('.$this->divID.');
		bounds.extend(groups[n][i].getPosition());
	   } 
	   position = true;  
	 }else{
	   for(i = 0; i <groups[n].length; i++){
		groups[n][i].setMap(null);
	   }
	 }
   }
 }
}	
		
if( position == true ) 
'.$this->divID.'.fitBounds(bounds);
}';

}

$this->code.='</script>';

}


public function addDisplayMarker($lat,$long,$title,$message)
{
	$count=count($this->displaymarker);	
	
	$this->displaymarker[$count]['lat']=$lat;
	
	$this->displaymarker[$count]['long']=$long;
	
	$this->displaymarker[$count]['title']=$title;
	
	$this->displaymarker[$count]['message']=$message;
}

public function addMarker($lat,$lng,$click='false',$title='My WorkPlace',$info='Hello World',$icon='',$map='map',$draggable='',$animation='',$group_id='')
{
	$count=count($this->marker);	
	
	$this->marker[$count]['lat']=$lat;
	
	$this->marker[$count]['lng']=$lng;
	
	$this->marker[$count]['map']=$map;
	
	$this->marker[$count]['title']=$title;
	
	$this->marker[$count]['click']=$click;
	
	$this->marker[$count]['icon']=$icon;
	
	$this->marker[$count]['info']=$info;
	
	$this->marker[$count]['draggable']=$draggable;
	
	$this->marker[$count]['animation']=$animation;
	
	if($group_id)
	$this->marker[$count]['group_id']=$group_id;
}

public function addMarkerByAddress($address,$click='false',$title='My WorkPlace',$info='Hello World',$icon='',$map='map')
{
	$status = false;

	$output = $this->getData($address);

	if( $output->status == 'OK' )
	{
	   $lat = $output->results[0]->geometry->location->lat;

	   $lng = $output->results[0]->geometry->location->lng;

	   $status = true;
	}

	if( $status )
	{
		$count=count($this->marker);	

		$this->marker[$count]['lat']=$lat;

		$this->marker[$count]['lng']=$lng;

		$this->marker[$count]['map']=$map;

		$this->marker[$count]['title']=$title;

		$this->marker[$count]['click']=$click;

		$this->marker[$count]['icon']=$icon;

		$this->marker[$count]['info']=$info;
    }		
}

public function addroutedirections($lat,$lng)
{
	$count=count($this->routedirections);	
	
	$this->routedirections[$count]['lat']=$lat;
	
	$this->routedirections[$count]['lng']=$lng;
}



// Call this function to create a google map.

public function showmap()
{
	$this->start();

	$this->instance++;

	return $this->code;
}

public function getData($address)
{
  $url = 'http://maps.google.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false';

  if( ini_get('allow_url_fopen') )
  {
		$geocode2 = wp_remote_get($url);

		$geocode=$geocode2['body'];
  }
  elseif( !ini_get('allow_url_fopen') )
  {
		$geocode2 = wp_remote_get($url);

		$geocode=$geocode2['body'];
  }
  else
  {
	echo "Configure your php.ini settings. allow_url_fopen may be disabled";

	exit;
  }	

  return json_decode($geocode);
}
}
