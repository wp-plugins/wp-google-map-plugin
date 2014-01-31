<?php
function wpgmp_display_map($atts, $content=null){
	
 ob_start();
 global $wpdb;
 	
 include_once dirname(__FILE__).'/class-google-map.php';
 $map = new Wpgmp_Google_Map();
 
 $map->center_lat = $atts['lat'];
 $map->center_lng = $atts['long'];
 $map->zoom = $atts['zoom'];
 $map->map_width = $atts['width'];
 $map->map_height = $atts['height'];

 if( !empty($atts['lat']) && !empty($atts['long']) ){
 	$map->addDisplayMarker($atts['lat'],$atts['long'],$atts['title'],$atts['message']);
 }
 
 echo $map->showmap();
 $content =  ob_get_contents();
 ob_clean();
 
 return $content;
}