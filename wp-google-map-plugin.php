<?php 
/*
Plugin Name: WP Google Map Plugin
Description: Easiest way to display Markers on Locations by Address on Google Map. Plugin based on latest google api version.
Author: flippercode
Version: 1.0.0
Author URI: http://profiles.wordpress.org/flippercode/
*/

add_action('init','wgmp_location_post_type');
add_shortcode('map_locations','wgmp_show_location_in_map');
add_action( 'save_post', 'save_location_website_address_meta');
 
function save_location_website_address_meta($post_id){
  
 	  
  if ( 'location' != $_POST['post_type'] ) {
        return;
  }
  if ( !current_user_can( 'edit_post', $post_id ) ) {
        return;
  }
  $post_object = get_post( $post_id );
  $address = trim(stripslashes($post_object->post_content));
  $output = wgmp_getData($address);
  if($output->status == 'OK')
  {
	$lat = $output->results[0]->geometry->location->lat;
	$lng = $output->results[0]->geometry->location->lng;
    update_post_meta($post_id,'gmap_latitude',$lat);
    update_post_meta($post_id,'gmap_longitude',$lng);
  }
  
}
 
function wgmp_location_post_type(){

 $labels = array(
    'name' => 'Locations',
    'singular_name' => 'Location',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Location',
    'edit_item' => 'Edit Location',
    'new_item' => 'New Location',
    'all_items' => 'All Locations',
    'view_item' => 'View Location',
    'search_items' => 'Search Locations',
    'not_found' =>  'No locations found',
    'not_found_in_trash' => 'No locations found in Trash', 
    'parent_item_colon' => '',
    'menu_name' => 'Locations'
  );

  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => 'location' ),
    'capability_type' => 'post',
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'thumbnail' )
  ); 

  register_post_type( 'location', $args );  
} 

function wgmp_show_location_in_map($atts){
 ob_start();
 extract( shortcode_atts( array(
		'zoom' => get_option('wgmp_zoomlevel'),
		'width' => get_option('wgmp_mapwidth'),
		'height' => get_option('wgmp_mapheight'),
		'class' => 'map',
		'center_latitude' => get_option('wgmp_centerlatitude'),
		'center_longitude' => get_option('wgmp_centerlongitude'),
		'container_id' => 'map',
		
	), $atts ) );
	
 $args = array(
   'post_type' => 'location',
   'post_status' => 'publish',
   'posts_per_page' => -1
 );
 
 $the_query =  new WP_Query($args); 
 echo "<style>
 
 #".$container_id." { width:".$width."px; height:".$height."px;}
 
 </style>";
 
  include dirname(__FILE__).'/googlemap.php';
    $map=new GOOGLE_API_3();
    $map->zoom=$zoom;
	$map->divID=$container_id;
	$map->center_lat=$center_latitude;
	$map->center_lng=$center_longitude;

	if(get_option('wgmp_displaymarker')=='yes')
	{
		$map->addMarker($center_latitude,$center_longitude,'false',$title,$address);
	}
	
	echo '<div id="'.$container_id.'"  class="'.$class.'"></div>';
	
	
 if($the_query->have_posts())
 {
   
	 	while ( $the_query->have_posts() ) :
		$the_query->the_post();
		
		$address = stripslashes(trim(get_the_content()));
		
		$title = stripslashes(get_the_title());
		
		$infobox = '<div class=\'wgmp_infobox\'>';
		
		$address = apply_filters('the_content',$address);

		if(has_post_thumbnail()){
		
		$thumbnail_url = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), 'thumbnail');
		
		$infobox.= "<img class='wgmp_featuredimage' src='".$thumbnail_url[0]."' title='".$title."' align='center' /> <address>".$address."</address>";
		
		}
		
		$infobox.="</div>";
		
		$infobox = str_replace(array("\r","\n"),'"+"',$infobox);

		$latitude = get_post_meta(get_the_ID(),'gmap_latitude', true);

		$longitude = get_post_meta(get_the_ID(),'gmap_longitude',true);

		if($latitude && $longitude)
		$map->addMarker($latitude,$longitude,'true',$title,$infobox);

	 endwhile;
    wp_reset_postdata();
	
 }
 else
 {
  
 }
 echo $map->showmap();
 $content =  ob_get_contents();
 ob_clean();
 return $content;
}

function wgmp_getData($address)
{
  $url = 'http://maps.google.com/maps/api/geocode/json?address='.urlencode($address).'&sensor=false';
  if(ini_get('allow_url_fopen') && function_exists('file_get_contents')){
   $geocode = file_get_contents($url);
  }
  else
  if(function_exists('curl_version'))
  {
	  $ch = curl_init();
	  curl_setopt($ch, CURLOPT_URL, $url);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	  $geocode = curl_exec($ch);
	  curl_close($ch);
  }
  else
  {
	echo "Configure your php.ini settings. Curl is not enabled or allow_url_fopen is disabled";
	exit;
  }	
  
  return json_decode($geocode);

}
add_action( 'admin_menu','wgmp_admin_menu' );

function wgmp_admin_menu()
{
 global $overview;

   add_options_page(__('WP Google Map'),__('WP Google Map Settings'),'manage_options',__FILE__,'wgmp_settings');
}	

// Hook things in, late enough so that add_meta_box() is defined

function wgmp_settings()
{
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div><h2>Google MAP Default Settings</h2>

        <form method="post" action="options.php">  
            <?php wp_nonce_field('update-options') ?>  
            
      <table class="form-table">
<tbody>    
            <tr valign="top">
<th scope="row"><label for="wgmp_zoomlevel">Zoom Level</label></th>
<td><input type="text" name="wgmp_zoomlevel" size="45" value="<?php echo get_option('wgmp_zoomlevel'); ?>" />
<p class="description">Choose Zoom Level between 1 to 14. Default is 4. </p></td>
</tr>


            <tr valign="top">
<th scope="row"><label for="wgmp_centerlatitude">Center Latitude</label></th>
<td><input type="text" name="wgmp_centerlatitude" size="45" value="<?php echo get_option('wgmp_centerlatitude'); ?>" />
<p class="description">Write down center location on the map. Default is </p></td>
</tr>

<tr valign="top">
<th scope="row"><label for="wgmp_centerlongitude">Center Longitude</label></th>
<td><input type="text" name="wgmp_centerlongitude" size="45" value="<?php echo get_option('wgmp_centerlongitude'); ?>" />
<p class="description">Write down center location on the map. Default is </p></td>
</tr>


<tr valign="top">
<th scope="row"><label for="wgmp_mapwidth">Map Width</label></th>
<td> <input type="text" name="wgmp_mapwidth" size="45" value="<?php echo get_option('wgmp_mapwidth'); ?>" />
<p class="description">Write down width of the map. Default is 900px. </p></td>
</tr>

<tr valign="top">
<th scope="row"><label for="wgmp_mapheight">Map Height</label></th>
<td> <input type="text" name="wgmp_mapheight" size="45" value="<?php echo get_option('wgmp_mapheight'); ?>" />
<p class="description">Write down height of the map. Default is 400px. </p></td>
</tr>


<tr valign="top">
<th scope="row"><label for="wgmp_displaymarker">Display Marker On Center Location ?</label></th>
<td>  <input type="checkbox" name="wgmp_displaymarker" size="45" value="yes" <?php if(get_option('wgmp_displaymarker')=='yes') echo "checked='checked'" ?> />
<p class="description">Check if you want to display a marker on center location. Default is hidden. </p></td>
</tr>           
            
   </tbody></table>        
          <input type="hidden" name="action" value="update" />  
            <input type="hidden" name="page_options" value="wgmp_zoomlevel,wgmp_centerlatitude,wgmp_centerlongitude,wgmp_mapwidth,wgmp_mapheight,wgmp_displaymarker" />  
     
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
 		   </form> 
 <p>
 
 <fieldset class="info">Discuss with 
	 <a href="mailto:hello@flippercode.com?subject=Customization is Required for WP Google Map Plugin&body=Please describe your requirement here. I have been used your map here <?php echo get_permalink($post->ID) ?>">Plugin Developer</a></fieldset>
 </p>
    </div>  
<?php
}