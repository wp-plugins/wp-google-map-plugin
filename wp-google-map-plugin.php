<?php 
/*
Plugin Name: WP Google Map Plugin
Description: Easiest way to display Markers on Locations by Address on Google Map. Plugin based on latest google api version.
Author: flippercode
Version: 1.2.0
Author URI: http://profiles.wordpress.org/flippercode/
*/

add_action('init','wgmp_location_post_type');
add_shortcode('map_locations','wgmp_show_location_in_map');
add_action( 'save_post', 'save_location_website_address_meta');
$wgmp_containers=array('map'); 
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
    'name' => 'WP Google MAP',
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
    'menu_name' => 'WP Google MAP'
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
    'menu_position' => 100,
    'supports' => array( 'title', 'editor', 'thumbnail','excerpt' )
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
 $icon=$atts['icon'];
 $the_query =  new WP_Query($args); 

 
  include_once dirname(__FILE__).'/googlemap.php';
  
  $first=0;
  $map=new GOOGLE_API_3();
   
   
    if($the_query->have_posts())
 {
   
	 	while ( $the_query->have_posts() ) :
		$the_query->the_post();
		
		$address = stripslashes(trim(get_the_content()));
	
		$title = stripslashes(get_the_title());
		
		$address = stripslashes(trim(get_the_content()));
		
		$infobox = '<div class=\'wgmp_infobox\'>';
		
		$infobox = apply_filters('the_content',$address);

		if(has_post_thumbnail()){
		
		$thumbnail_url = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), 'thumbnail');
		
		$infobox.= "<img class='wgmp_featuredimage' src='".$thumbnail_url[0]."' title='".$title."' align='center' /> <address>".$address."</address>";
		
		}
		
		$infobox.="</div>";
		
		$infobox = str_replace(array("\r","\n"),'"+"',$infobox);

		$latitude = get_post_meta(get_the_ID(),'gmap_latitude', true);
		
		
		
		$longitude = get_post_meta(get_the_ID(),'gmap_longitude',true);
	
		if($first==0)
		{
			$center_latitude=$latitude;
			$center_longitude=$longitude;
			
		}
		$first++;
		
		
		
		
		if($latitude && $longitude)
		$map->addMarker($latitude,$longitude,'true',$title,$infobox);

	 endwhile;
    wp_reset_postdata();
	
 }
 else
 {
  
 }
   
   if($center_latitude=='' or $center_longitude=='')
    {
    
    $center_latitude="39.774769";
  	$center_longitude="-101.439514";
  	
  	$map->addMarker($center_latitude,$center_longitude,'true',"WP Google Map Plugin","Thank you for using this plugin. Please <a href='".admin_url('edit.php?post_type=location')."'>Add your locations</a> or set plugin <a href='".admin_url('edit.php?post_type=location&page=wgmp_settings')."'>Settings</a>.");

  	} 
   
    if($zoom=='')
    $zoom=4;
    
    if($height=='')
    $height=400;
    
    
    $map->zoom=$zoom;
	$map->center_lat=$center_latitude;
	$map->center_lng=$center_longitude;
	$map->width=$width;
	$map->height=$height;

	if($icon!='')
	$map->addMarker($center_latitude,$center_longitude,'false',$title,$address,$icon);
	elseif(get_option('wgmp_displaymarker')=='yes')
	{
		$map->addMarker($center_latitude,$center_longitude,'false',$title,$address);
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
add_submenu_page("edit.php?post_type=location", "Settings", "Settings", "manage_options","wgmp_settings", "wgmp_settings");

}	

// Hook things in, late enough so that add_meta_box() is defined

function wgmp_settings()
{
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div><h2>Google MAP Default Settings</h2>

        <form method="post" action="options.php">  
            <?php wp_nonce_field('update-options') ?>  
      <p>
 <fieldset class="info">Use [map_locations] shortcode to display map.  <a href="<?php echo get_bloginfo('siteurl'); ?>/wp-admin/post-new.php?post_type=location">Click Here</a> to add a new location or <a href="<?php echo get_bloginfo('siteurl'); ?>/wp-admin/edit.php?post_type=location">browse</a> your existings locations.
 </p>
       
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

/*Adding a 'Google Map Widget' in back end*/

add_action('widgets_init' , 'register_google_map_widget');

function register_google_map_widget()
{
	register_widget('Google_Map_Widget');
}

class Google_Map_Widget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct(
			'google-map-widget',
			'Google Map Widget',
			array('description' => __('A widget that displays the google map' , 'google-map'))
		);
	}

	function widget( $args, $instance )
	{
		global $wpdb;
		extract($args);
		$title=$instance['title'];
		$z=$instance['mapzoom_level'];
		$w=$instance['mapwidth'];
		$h=$instance['mapheight'];
		$c_lat=$instance['mapcenter_latitude'];
		$c_long=$instance['mapcenter_longitude'];
		$c_id=$instance['mapcontainer_id'];
		$sm=get_option('wgmp_short_mapselect_marker');			
		$s_marker=$sm['mapselect_marker'];
		if($s_marker!='')
		$s_marker=plugins_url('icons/'.$s_marker,__FILE__);
		if($title)
			echo $before_title . $title . $after_title;
		if($z)
		{
			if(get_option('wgmp_short_mapzoom_level')==FALSE)
				add_option('wgmp_short_mapzoom_level' , $z);
			else
				update_option('wgmp_short_zoom_level' , $z);
		}
		if($w)
		{
			if(get_option('wgmp_short_mapwidth')==FALSE)
				add_option('wgmp_short_mapwidth' , $w);
			else
				update_option('wgmp_short_mapwidth' , $w);
		}
		if($h)
		{
			if(get_option('wgmp_short_mapheight')==FALSE)
				add_option('wgmp_short_mapheight' , $h);
			else
				update_option('wgmp_short_mapheight' , $h);
		}
		if($c_lat)
		{
			if(get_option('wgmp_short_mapcenter_latitude')==FALSE)
				add_option('wgmp_short_mapcenter_latitude' , $c_lat);
			else
				update_option('wgmp_short_mapcenter_latitude' , $c_lat);
		}
		if($c_long)
		{
			if(get_option('wgmp_short_mapcenter_longitude')==FALSE)
				add_option('wgmp_short_mapcenter_longitude' , $c_long);
			else
				update_option('wgmp_short_mapcenter_longitude' , $c_long);
		}
		if($c_id)
		{
			if(get_option('wgmp_short_mapcontainer_id')==FALSE)
				add_option('wgmp_short_mapcontainer_id' , $c_id);
			else
			{
				update_option('wgmp_short_mapcontainer_id' , $c_id);
			}
		}
		else
		{
			if(get_option('wgmp_short_mapcontainer_id')==FALSE)
				add_option('wgmp_short_mapcontainer_id' , 'map');
		}
		
		if($c_id=='')
		$c_id=rand(10,100);
		
		
		echo do_shortcode('[map_locations zoom='.$z.' width='.$w.' height='.$h.' center_longitude='.$c_long.'  center_latitude='.$c_lat.' icon='.$s_marker.' container_id='.$c_id.' ]' );
	
	}
	
	function update( $new_instance, $old_instance )
	{
		$instance=$old_instance;
		$instance['title']=strip_tags($new_instance['title']);
		$instance['mapzoom_level']=strip_tags($new_instance['mapzoom_level']);
		$instance['mapwidth']=strip_tags($new_instance['mapwidth']);
		$instance['mapheight']=strip_tags($new_instance['mapheight']);
		$instance['mapcenter_latitude']=strip_tags($new_instance['mapcenter_latitude']);
		$instance['mapcenter_longitude']=strip_tags($new_instance['mapcenter_longitude']);
		$instance['mapcontainer_id']=strip_tags($new_instance['mapcontainer_id']);
		$instance['mapselect_marker']=($new_instance['mapselect_marker']);
		$mark['mapselect_marker']=strip_tags($new_instance['mapselect_marker']);
		update_option('wgmp_short_mapselect_marker' , $mark);
		return $instance;
	}
	
	function form( $instance )
	{
		$marker_saved=get_option('wgmp_short_mapselect_marker');
	
	?>
		<p>
			<label for="<?php echo $this->get_field_id('title');?>"><?php _e('Title:' , 'google-map');?>
			</label>
			<br>
			<input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:80%;" />
			
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('mapzoom_level');?>"><?php _e('Zoom Level:' , 'google-map');?>
			</label>
			
			<input id="<?php echo $this->get_field_id('mapzoom_level'); ?>" name="<?php echo $this->get_field_name( 'mapzoom_level' ); ?>" value="<?php echo $instance['mapzoom_level']; ?>" style="width:80%;" />
			
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('mapwidth');?>"><?php _e('Width:' , 'google-map');?>
			</label>
			
			<input id="<?php echo $this->get_field_id('mapwidth'); ?>" name="<?php echo $this->get_field_name( 'mapwidth' ); ?>" value="<?php echo $instance['mapwidth']; ?>" style="width:80%;" />
			
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('mapheight');?>"><?php _e('Height:' , 'google-map');?>
			</label>
			
			<input id="<?php echo $this->get_field_id('mapheight'); ?>" name="<?php echo $this->get_field_name( 'mapheight' ); ?>" value="<?php echo $instance['mapheight']; ?>" style="width:80%;" />
			
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('mapcenter_latitude');?>"><?php _e('Latitude:' , 'google-map');?>
			</label>
			
			<input id="<?php echo $this->get_field_id('mapcenter_latitude'); ?>" name="<?php echo $this->get_field_name( 'mapcenter_latitude' ); ?>" value="<?php echo $instance['mapcenter_latitude']; ?>" style="width:80%;" />
			
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('mapcenter_longitude');?>"><?php _e('Longitude:' , 'google-map');?>
			</label>
			
			<input id="<?php echo $this->get_field_id('mapcenter_longitude'); ?>" name="<?php echo $this->get_field_name( 'mapcenter_longitude' ); ?>" value="<?php echo $instance['mapcenter_longitude']; ?>" style="width:80%;" />
			
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('mapselect_marker');?>"><?php _e('Select Marker:' , 'google-map');?>
			</label> 
				<select id="<?php echo $this->get_field_id('mapselect_marker'); ?>" name="<?php echo $this->get_field_name( 'mapselect_marker' ); ?>" style="width:80%;">
		
 					<option value="default" <?php if ($marker_saved['mapselect_marker']=='default' || $marker_saved['mapselect_marker']=='') { ?> selected="selected" <?php }?> >Default</option>
					
 					<option value="accident.png" <?php if ($marker_saved['mapselect_marker']=='accident.png') { ?> selected="selected" <?php }?> >Accident</option>
					
					<option value="administration.png" <?php if ($marker_saved['mapselect_marker']=='administration.png') { ?> selected="selected" <?php }?>>Administration</option>
					
					<option value="aestheticscenter.png" <?php if ($marker_saved['mapselect_marker']=='aestheticscenter.png') { ?> selected="selected" <?php }?> >Aestheticscenter</option>
					
					<option value="agriculture2.png" <?php if ($marker_saved['mapselect_marker']=='agriculture2.png') { ?> selected="selected" <?php }?> >Agriculture2</option> 
					
					<option value="agriculture3.png" <?php if ($marker_saved['mapselect_marker']=='agriculture3.png') { ?> selected="selected" <?php }?> >Agriculture3</option> 
					
					<option value="agriculture4.png" <?php if ($marker_saved['mapselect_marker']=='agriculture4.png') { ?> selected="selected" <?php }?> >Agriculture4</option> 
					
					<option value="agriculture.png" <?php if ($marker_saved['mapselect_marker']=='agriculture.png') { ?> selected="selected" <?php }?> >Agriculture</option> 
					
					<option value="aircraft-small.png" <?php if ($marker_saved['mapselect_marker']=='aircraft-small.png') { ?> selected="selected" <?php }?> >Aircraft Small</option> 
					
					<option value="airplane-sport.png" <?php if ($marker_saved['mapselect_marker']=='airplane-sport.png') { ?> selected="selected" <?php }?> >Airplane Sport</option> 
					
					<option value="airplane-tourism.png" <?php if ($marker_saved['mapselect_marker']=='airplane-tourism.png') { ?> selected="selected" <?php }?> >Airplane Tourism</option>
					
					<option value="airport.png" <?php if ($marker_saved['mapselect_marker']=='airport.png') { ?> selected="selected" <?php }?> >Airport</option 
					
					><option value="airport-apron.png" <?php if ($marker_saved['mapselect_marker']=='airport-apron.png') { ?> selected="selected" <?php }?> >Airport Apron</option> 
					
					<option value="airport-runway.png" <?php if ($marker_saved['mapselect_marker']=='airport-runway.png') { ?> selected="selected" <?php }?> >Airport Runway</option>
					
					<option value="airport-terminal.png" <?php if ($marker_saved['mapselect_marker']=='airport-terminal.png') { ?> selected="selected" <?php }?> >Airport Terminal</option> 
					
					<option value="amphitheater.png" <?php if ($marker_saved['mapselect_marker']=='amphitheater.png') { ?> selected="selected" <?php }?> >Amphitheater</option> 
					
					<option value="amphitheater-tourism.png" <?php if ($marker_saved['mapselect_marker']=='amphitheater-tourism.png') { ?> selected="selected" <?php }?> >Amphitheater Tourism</option> 
					
					<option value="ancientmonument.png" <?php if ($marker_saved['mapselect_marker']=='ancientmonument.png') { ?> selected="selected" <?php }?> >Ancientmonument</option> 
					
					<option value="ancienttemple.png" <?php if ($marker_saved['mapselect_marker']=='ancienttemple.png') { ?> selected="selected" <?php }?> >Ancienttemple</option> 
					
					<option value="ancienttempleruin.png" <?php if ($marker_saved['mapselect_marker']=='ancienttempleruin.png') { ?> selected="selected" <?php }?> >Ancienttempleruin</option> 
					
					<option value="animals.png" <?php if ($marker_saved['mapselect_marker']=='animals.png') { ?> selected="selected" <?php }?> >Animals</option> 
					
					<option value="anniversary.png" <?php if ($marker_saved['mapselect_marker']=='anniversary.png') { ?> selected="selected" <?php }?> >Anniversary</option> 
					
					<option value="apartment.png" <?php if 
					($marker_saved['mapselect_marker']=='apartment.png') { ?> selected="selected" <?php }?> >Apartment</option> 
					<option value="aquarium.png" <?php if ($marker_saved['mapselect_marker']=='aquarium.png') { ?> selected="selected" <?php }?> >Aquarium</option> 
					
					<option value="arch.png" <?php if ($marker_saved['mapselect_marker']=='arch.png') { ?> selected="selected" <?php }?> >Arch</option> 
					
					<option value="archery.png" <?php if ($marker_saved['mapselect_marker']=='archery.png') { ?> selected="selected" <?php }?> >Archery</option> 
					
					<option value="artgallery.png" <?php if ($marker_saved['mapselect_marker']=='artgallery.png') { ?> selected="selected" <?php }?> >Artgallery</option> 
					
					<option value="atm.png" <?php if ($marker_saved['mapselect_marker']=='atm.png') { ?> selected="selected" <?php }?> >Atm</option> 
					
					<option value="atv.png" <?php if ($marker_saved['mapselect_marker']=='atv.png') { ?> selected="selected" <?php }?> >Atv</option> 
					
					<option value="audio.png" <?php if ($marker_saved['mapselect_marker']=='audio.png') { ?> selected="selected" <?php }?> >Audio</option> 
					
					<option value="australianfootball.png" <?php if ($marker_saved['mapselect_marker']=='australianfootball.png') { ?> selected="selected" <?php }?> >Australianfootball</option> 
					
					<option value="bags.png" <?php if ($marker_saved['mapselect_marker']=='bags.png') { ?> selected="selected" <?php }?> >Bags</option> 
					
					<option value="bank.png" <?php if ($marker_saved['mapselect_marker']=='bank.png') { ?> selected="selected" <?php }?> >Bank</option> 
					
					<option value="bankeuro.png" <?php if ($marker_saved['mapselect_marker']=='bankeuro.png') { ?> selected="selected" <?php }?> >Bankeuro</option> 
					
					<option value="bankpound.png" <?php if ($marker_saved['mapselect_marker']=='bankpound.png') { ?> selected="selected" <?php }?> >Bankpound</option> 
					
					<option value="bar.png" <?php if ($marker_saved['mapselect_marker']=='bar.png') { ?> selected="selected" <?php }?> >Bar</option> 
					
					<option value="baseball.png" <?php if ($marker_saved['mapselect_marker']=='baseball.png') { ?> selected="selected" <?php }?> >Baseball</option> 
					
					<option value="basketball.png" <?php if ($marker_saved['mapselect_marker']=='basketball.png') { ?> selected="selected" <?php }?> >Basketball</option> 
					
					<option value="baskteball2.png" <?php if ($marker_saved['mapselect_marker']=='baskteball2.png') { ?> selected="selected" <?php }?> >Baskteball2</option> 
					
					<option value="beach.png" <?php if ($marker_saved['mapselect_marker']=='beach.png') { ?> selected="selected" <?php }?> >Beach</option> 
					
					<option value="beautiful.png" <?php if ($marker_saved['mapselect_marker']=='beautiful.png') { ?> selected="selected" <?php }?> >Beautiful</option> 
					
					<option value="bench.png" <?php if ($marker_saved['mapselect_marker']=='bench.png') { ?> selected="selected" <?php }?> >bench</option> 
					
					<option value="bicycleparking.png" <?php if ($marker_saved['mapselect_marker']=='bicycleparking.png') { ?> selected="selected" <?php }?> >Bicycleparking</option>
					 
					<option value="bigcity.png" <?php if ($marker_saved['mapselect_marker']=='bigcity.png') { ?> selected="selected" <?php }?> >Bigcity</option> 
					
					<option value="billiard.png" <?php if ($marker_saved['mapselect_marker']=='billiard.png') { ?> selected="selected" <?php }?> >Billiard</option> 
					
					<option value="bobsleigh.png" <?php if ($marker_saved['mapselect_marker']=='bobsleigh.png') { ?> selected="selected" <?php }?> >Bobsleigh</option> 
					
					<option value="bomb.png" <?php if ($marker_saved['mapselect_marker']=='bomb.png') { ?> selected="selected" <?php }?> >Bomb</option> 
					
					<option value="bookstore.png" <?php if ($marker_saved['mapselect_marker']=='bookstore.png') { ?> selected="selected" <?php }?> >Bookstore</option> 
					
					<option value="bowling.png" <?php if ($marker_saved['mapselect_marker']=='bowling.png') { ?> selected="selected" <?php }?> >Bowling</option> 
					
					<option value="boxing.png" <?php if ($marker_saved['mapselect_marker']=='boxing.png') { ?> selected="selected" <?php }?> >Boxing</option> 
					
					<option value="bread.png" <?php if ($marker_saved['mapselect_marker']=='bread.png') { ?> selected="selected" <?php }?> >Bread</option> 
					
					<option value="bridge.png" <?php if ($marker_saved['mapselect_marker']=='bridge.png') { ?> selected="selected" <?php }?> >bridge</option> 
					
					<option value="bridgemodern.png" <?php if ($marker_saved['mapselect_marker']=='bridgemodern.png') { ?> selected="selected" <?php }?> >Bridgemodern</option> 
					
					<option value="bullfight.png" <?php if ($marker_saved['mapselect_marker']=='bullfight.png') { ?> selected="selected" <?php }?> >bullfight</option> 
					
					<option value="bungalow.png" <?php if ($marker_saved['mapselect_marker']=='bungalow.png') { ?> selected="selected" <?php }?> >Bungalow</option>
					
					<option value="bus.png" <?php if ($marker_saved['mapselect_marker']=='bus.png') { ?> selected="selected" <?php }?> >Bus</option>
					
					<option value="butcher.png" <?php if ($marker_saved['mapselect_marker']=='butcher.png') { ?> selected="selected" <?php }?> >Butcher</option>
					
					<option value="cabin.png" <?php if ($marker_saved['mapselect_marker']=='cabin.png') { ?> selected="selected" <?php }?> >Cabin</option> 
					
					<option value="cablecar.png" <?php if ($marker_saved['mapselect_marker']=='cablecar.png') { ?> selected="selected" <?php }?> >Cablecar</option> 
					
					<option value="camping.png" <?php if ($marker_saved['mapselect_marker']=='camping.png') { ?> selected="selected" <?php }?> >Camping</option> 
					
					<option value="campingsite.png" <?php if ($marker_saved['mapselect_marker']=='campingsite.png') { ?> selected="selected" <?php }?> >Campingsite</option> 
					
					<option value="canoe.png" <?php if ($marker_saved['mapselect_marker']=='canoe.png') { ?> selected="selected" <?php }?> >Canoe</option> 
					
					<option value="car.png" <?php if ($marker_saved['mapselect_marker']=='car.png') { ?> selected="selected" <?php }?> >Car</option> 
					
					<option value="carrental.png" <?php if ($marker_saved['mapselect_marker']=='carrental.png') { ?> selected="selected" <?php }?> >Carrental</option> 
					
					<option value="carrepair.png" <?php if ($marker_saved['mapselect_marker']=='carrepair.png') { ?> selected="selected" <?php }?> >Carrepair</option> 
					
					<option value="carwash.png" <?php if ($marker_saved['mapselect_marker']=='carwash.png') { ?> selected="selected" <?php }?> >Carwash</option> 
					
					<option value="casino.png" <?php if ($marker_saved['mapselect_marker']=='casino.png') { ?> selected="selected" <?php }?> >Casino</option> 
					
					<option value="castle.png" <?php if ($marker_saved['mapselect_marker']=='castle.png') { ?> selected="selected" <?php }?> >Castle</option> 
					
					<option value="cathedral2.png" <?php if ($marker_saved['mapselect_marker']=='cathedral2.png') { ?> selected="selected" <?php }?> >Cathedral2</option> 
					
					<option value="cathedral.png" <?php if ($marker_saved['mapselect_marker']=='cathedral.png') { ?> selected="selected" <?php }?> >Cathedral</option> 
					
					<option value="cave.png" <?php if ($marker_saved['mapselect_marker']=='cave.png') { ?> selected="selected" <?php }?> >Cave</option> 
					
					<option value="cemetary.png" <?php if ($marker_saved['mapselect_marker']=='cemetary.png') { ?> selected="selected" <?php }?> >Cemetary</option> 
					
					<option value="chapel.png" <?php if ($marker_saved['mapselect_marker']=='chapel.png') { ?> selected="selected" <?php }?> >Chapel</option> 
					
					<option value="church2.png" <?php if ($marker_saved['mapselect_marker']=='church2.png') { ?> selected="selected" <?php }?> >Church2.png a</option> 
					
					<option value="church.png" <?php if ($marker_saved['mapselect_marker']=='church.png') { ?> selected="selected" <?php }?> >Church</option> 
					
					<option value="cinema.png" <?php if ($marker_saved['mapselect_marker']=='cinema.png') { ?> selected="selected" <?php }?> >Cinema</option> 
					
					<option value="circus.png" <?php if ($marker_saved['mapselect_marker']=='circus.png') { ?> selected="selected" <?php }?> >Circus</option> 
					
					<option value="citysquare.png" <?php if ($marker_saved['mapselect_marker']=='citysquare.png') { ?> selected="selected" <?php }?> >Citysquare</option> 
					
					<option value="climbing.png" <?php if ($marker_saved['mapselect_marker']=='climbing.png') { ?> selected="selected" <?php }?> >Climbing</option> 
					
					<option value="clothes.png" <?php if ($marker_saved['mapselect_marker']=='clothes.png') { ?> selected="selected" <?php }?> >Clothes</option> 
					
					<option value="clothes-female.png" <?php if ($marker_saved['mapselect_marker']=='clothes-female.png') { ?> selected="selected" <?php }?> >Clothes-female</option> 
					
					<option value="clothes-male.png" <?php if ($marker_saved['mapselect_marker']=='clothes-male.png') { ?> selected="selected" <?php }?> >Clothes-male</option> 
					
					<option value="clouds.png" <?php if ($marker_saved['mapselect_marker']=='clouds.png') { ?> selected="selected" <?php }?> >Clouds</option> 
					
					<option value="cloudsun.png" <?php if ($marker_saved['mapselect_marker']=='cloudsun.png') { ?> selected="selected" <?php }?> >Cloudsun</option> 
					
					<option value="club.png" <?php if ($marker_saved['mapselect_marker']=='club.png') { ?> selected="selected" <?php }?> >Club</option> 
					
					<option value="cluster2.png" <?php if ($marker_saved['mapselect_marker']=='cluster2.png') { ?> selected="selected" <?php }?> >Cluster2</option> 
					
					<option value="cluster3.png" <?php if ($marker_saved['mapselect_marker']=='cluster3.png') { ?> selected="selected" <?php }?> >Cluster3</option> 
					
					<option value="cluster4.png" <?php if ($marker_saved['mapselect_marker']=='cluster4.png') { ?> selected="selected" <?php }?> >Cluster4</option> 
					
					<option value="cluster5.png" <?php if ($marker_saved['mapselect_marker']=='cluster5.png') { ?> selected="selected" <?php }?> >cluster5</option> 
					
					<option value="cluster.png" <?php if ($marker_saved['mapselect_marker']=='cluster.png') { ?> selected="selected" <?php }?> >cluster</option> 
					
					<option value="cocktail.png" <?php if ($marker_saved['mapselect_marker']=='cocktail.png') { ?> selected="selected" <?php }?> >cocktail</option> 
					
					<option value="coffee.png" <?php if ($marker_saved['mapselect_marker']=='coffee.png') { ?> selected="selected" <?php }?> >Coffee</option> 
					
					<option value="communitycentre.png" <?php if ($marker_saved['mapselect_marker']=='communitycentre.png') { ?> selected="selected" <?php }?> >Communitycentre</option> 
					
					<option value="company.png" <?php if ($marker_saved['mapselect_marker']=='company.png') { ?> selected="selected" <?php }?> >Company</option> 
					
					<option value="computer.png" <?php if ($marker_saved['mapselect_marker']=='computer.png') { ?> selected="selected" <?php }?> >Computer</option> 
					
					<option value="concessionaire.png" <?php if ($marker_saved['mapselect_marker']=='concessionaire.png') { ?> selected="selected" <?php }?> >Concessionaire</option> 
					
					<option value="conference.png" <?php if ($marker_saved['mapselect_marker']=='conference.png') { ?> selected="selected" <?php }?> >Conference</option> 
					
					<option value="construction.png" <?php if ($marker_saved['mapselect_marker']=='construction.png') { ?> selected="selected" <?php }?> >Construction</option> 
					
					<option value="convenience.png" <?php if ($marker_saved['mapselect_marker']=='convenience.png') { ?> selected="selected" <?php }?> >Convenience</option> 
					
					<option value="convent.png" <?php if ($marker_saved['mapselect_marker']=='convent.png') { ?> selected="selected" <?php }?> >Convent</option> 
					
					<option value="corral.png" <?php if ($marker_saved['mapselect_marker']=='corral.png') { ?> selected="selected" <?php }?> >Corral</option> 
					
					<option value="country.png" <?php if ($marker_saved['mapselect_marker']=='country.png') { ?> selected="selected" <?php }?> >Country</option> 
					
					<option value="court.png" <?php if ($marker_saved['mapselect_marker']=='court.png') { ?> selected="selected" <?php }?> >Court</option> 
					
					<option value="cross.png.png" <?php if ($marker_saved['mapselect_marker']=='cross.png') { ?> selected="selected" <?php }?> >Cross</option>
					
					<option value="crossingguard.png" <?php if ($marker_saved['mapselect_marker']=='crossingguard.png') { ?> selected="selected" <?php }?> >Crossingguard</option>
					
					<option value="cruise.png" <?php if ($marker_saved['mapselect_marker']=='cruise.png') { ?> selected="selected" <?php }?> >Cruise</option>
					
					<option value="currencyexchange.png" <?php if ($marker_saved['mapselect_marker']=='currencyexchange.png') { ?> selected="selected" <?php }?> >Currencyexchange</option> 
					
					<option value="customs.png" <?php if ($marker_saved['mapselect_marker']=='customs.png') { ?> selected="selected" <?php }?> >Customs</option> 
					
					<option value="cycling.png" <?php if ($marker_saved['mapselect_marker']=='cycling.png') { ?> selected="selected" <?php }?> >Cycling</option> 
					
					<option value="cyclingfeedarea.png" <?php if ($marker_saved['mapselect_marker']=='cyclingfeedarea.png') { ?> selected="selected" <?php }?> >Cyclingfeedarea</option>
					
					<option value="cyclingmountain1.png" <?php if ($marker_saved['mapselect_marker']=='cyclingmountain1.png') { ?> selected="selected" <?php }?> >Cyclingmountain1</option> 
					
					<option value="cyclingmountain2.png" <?php if ($marker_saved['mapselect_marker']=='cyclingmountain2.png') { ?> selected="selected" <?php }?> >Cyclingmountain2</option> 
					
					<option value="cyclingmountain3.png" <?php if ($marker_saved['mapselect_marker']=='cyclingmountain3.png') { ?> selected="selected" <?php }?> >Cyclingmountain3</option> 
					
					<option value="cyclingmountain4.png" <?php if ($marker_saved['mapselect_marker']=='cyclingmountain4.png') { ?> selected="selected" <?php }?> >Cyclingmountain4</option> 
					
					<option value="cyclingmountainnotrated.png" <?php if ($marker_saved['mapselect_marker']=='cyclingmountainnotrated.png') { ?> selected="selected" <?php }?> >Cyclingmountainnotrated</option> 
					
					<option value="cyclingsport.png" <?php if ($marker_saved['mapselect_marker']=='cyclingsport.png') { ?> selected="selected" <?php }?> >Cyclingsport</option> 
					
					<option value="cyclingsprint.png" <?php if ($marker_saved['mapselect_marker']=='cyclingsprint.png') { ?> selected="selected" <?php }?> >Cyclingsprint</option> 
					
					<option value="cyclinguncategorized.png" <?php if ($marker_saved['mapselect_marker']=='cyclinguncategorized.png') { ?> selected="selected" <?php }?> >Cyclinguncategorized</option> 
					
					<option value="dam.png" <?php if ($marker_saved['mapselect_marker']=='dam.png') { ?> selected="selected" <?php }?> >Dam</option> 
					
					<option value="dancinghall.png" <?php if ($marker_saved['mapselect_marker']=='dancinghall.png') { ?> selected="selected" <?php }?> >Dancinghall</option> 
					
					<option value="dates.png" <?php if ($marker_saved['mapselect_marker']=='dates.png') { ?> selected="selected" <?php }?> >Dates</option> 
					
					<option value="daycare.png" <?php if ($marker_saved['mapselect_marker']=='daycare.png') { ?> selected="selected" <?php }?> >Daycare</option> 
					
					<option value="days-dim.png" <?php if ($marker_saved['mapselect_marker']=='days-dim.png') { ?> selected="selected" <?php }?> >Days dim</option> 
					
					<option value="days-dom.png" <?php if ($marker_saved['mapselect_marker']=='days-dom.png') { ?> selected="selected" <?php }?> >Days-dom</option> 
					
					<option value="days-jeu.png" <?php if ($marker_saved['mapselect_marker']=='days-jeu.png') { ?> selected="selected" <?php }?> >Days-jeu</option> 
					
					<option value="days-jue.png" <?php if ($marker_saved['mapselect_marker']=='days-jue.png') { ?> selected="selected" <?php }?> >Days-jue</option> 
					
					<option value="days-lun.png" <?php if ($marker_saved['mapselect_marker']=='days-lun.png') { ?> selected="selected" <?php }?> >Days-lun</option> 
					
					<option value="days-mar.png" <?php if ($marker_saved['mapselect_marker']=='days-mar.png') { ?> selected="selected" <?php }?> >Days-mar</option> 
					
					<option value="days-mer.png" <?php if ($marker_saved['mapselect_marker']=='days-mer.png') { ?> selected="selected" <?php }?> >Days-mer</option> 
					
					<option value="days-mie.png" <?php if ($marker_saved['mapselect_marker']=='days-mie.png') { ?> selected="selected" <?php }?> >Days-mie</option> 
					
					<option value="days-qua.png" <?php if ($marker_saved['mapselect_marker']=='days-qua.png') { ?> selected="selected" <?php }?> >Days-qua</option> 
					
					<option value="days-qui.png" <?php if ($marker_saved['mapselect_marker']=='days-qui.png') { ?> selected="selected" <?php }?> >Days-qui</option> 
					
					<option value="days-sab.png" <?php if ($marker_saved['mapselect_marker']=='days-sab.png') { ?> selected="selected" <?php }?> >Days-sab</option> 
					
					<option value="days-sam.png" <?php if ($marker_saved['mapselect_marker']=='days-sam.png') { ?> selected="selected" <?php }?> >Days-sam</option> 
					
					<option value="days-seg.png" <?php if ($marker_saved['mapselect_marker']=='days-seg.png') { ?> selected="selected" <?php }?> >Days-seg</option> 
					
					<option value="days-sex.png" <?php if ($marker_saved['mapselect_marker']=='days-sex.png') { ?> selected="selected" <?php }?> >Days-sex</option> 
					
					<option value="days-ter.png" <?php if ($marker_saved['mapselect_marker']=='days-ter.png') { ?> selected="selected" <?php }?> >Days-ter</option> 
					
					<option value="days-ven.png" <?php if ($marker_saved['mapselect_marker']=='days-ven.png') { ?> selected="selected" <?php }?> >Days-ven</option> 
					
					<option value="days-vie.png" <?php if ($marker_saved['mapselect_marker']=='days-vie.png') { ?> selected="selected" <?php }?> >Days-vie</option> 
					
					<option value="dentist.png" <?php if ($marker_saved['mapselect_marker']=='dentist.png') { ?> selected="selected" <?php }?> >Dentist</option> 
					
					<option value="deptstore.png" <?php if ($marker_saved['mapselect_marker']=='deptstore.png') { ?> selected="selected" <?php }?> >Deptstore</option> 
					
					<option value="disability.png" <?php if ($marker_saved['mapselect_marker']=='disability.png') { ?> selected="selected" <?php }?> >Disability</option> 
					
					<option value="disabledparking.png" <?php if ($marker_saved['mapselect_marker']=='disabledparking.png') { ?> selected="selected" <?php }?> >Disabledparking</option> 
					
					<option value="diving.png" <?php if ($marker_saved['mapselect_marker']=='diving.png') { ?> selected="selected" <?php }?> >Diving</option> 
					
					<option value="doctor.png" <?php if ($marker_saved['mapselect_marker']=='doctor.png') { ?> selected="selected" <?php }?> >Doctor</option> 
					
					<option value="dog-leash.png" <?php if ($marker_saved['mapselect_marker']=='dog-leash.png') { ?> selected="selected" <?php }?> >Dog-leash</option> 
					
					<option value="dog-offleash.png" <?php if ($marker_saved['mapselect_marker']=='dog-offleash.png') { ?> selected="selected" <?php }?> >Dog-offleash</option> 
					
					<option value="door.png" <?php if ($marker_saved['mapselect_marker']=='door.png') { ?> selected="selected" <?php }?> >Door</option> 
					
					<option value="down.png" <?php if ($marker_saved['mapselect_marker']=='down.png') { ?> selected="selected" <?php }?> >Down</option> 
					
					<option value="downleft.png" <?php if ($marker_saved['mapselect_marker']=='downleft.png') { ?> selected="selected" <?php }?> >Downleft</option> 
					
					<option value="downright.png" <?php if ($marker_saved['mapselect_marker']=='downright.png') { ?> selected="selected" <?php }?> >Downright</option> 
					
					<option value="downthenleft.png" <?php if ($marker_saved['mapselect_marker']=='downthenleft.png') { ?> selected="selected" <?php }?> >Downthenleft</option> 
					
					<option value="downthenright.png" <?php if ($marker_saved['mapselect_marker']=='downthenright.png') { ?> selected="selected" <?php }?> >Downthenright</option> 
					
					<option value="drinkingfountain.png" <?php if ($marker_saved['mapselect_marker']=='drinkingfountain.png') { ?> selected="selected" <?php }?> >Drinkingfountain</option>
					
					<option value="drinkingwater.png" <?php if ($marker_saved['mapselect_marker']=='drinkingwater.png') { ?> selected="selected" <?php }?> >Drinkingwater</option>
					
					<option value="drugs.png" <?php if ($marker_saved['mapselect_marker']=='drugs.png') { ?> selected="selected" <?php }?> >Drugs</option>
					
					<option value="elevator.png" <?php if ($marker_saved['mapselect_marker']=='elevator.png') { ?> selected="selected" <?php }?> >Elevator</option> 
					
					<option value="embassy.png" <?php if ($marker_saved['mapselect_marker']=='embassy.png') { ?> selected="selected" <?php }?> >Embassy</option> 
					
					<option value="entrance.png" <?php if ($marker_saved['mapselect_marker']=='entrance.png') { ?> selected="selected" <?php }?> >Entrance</option> 
					
					<option value="escalator-down.png" <?php if ($marker_saved['mapselect_marker']=='escalator-down.png') { ?> selected="selected" <?php }?> >Escalator-down</option> 
					
					<option value="escalator-up.png"  <?php if ($marker_saved['mapselect_marker']=='escalator-up.png') { ?> selected="selected" <?php }?> >Escalator-up</option> 
					
					<option value="exit.png" <?php if ($marker_saved['mapselect_marker']=='exit.png') { ?> selected="selected" <?php }?> >Exit</option> 
					<option value="expert.png" <?php if ($marker_saved['mapselect_marker']=='expert.png') { ?> selected="selected" <?php }?> >Expert</option> 
					
					<option value="explosion.png" <?php if ($marker_saved['mapselect_marker']=='explosion.png') { ?> selected="selected" <?php }?> >Explosion</option> 
					
					<option value="factory.png" <?php if ($marker_saved['mapselect_marker']=='factory.png') { ?> selected="selected" <?php }?> >Factory</option> 
					
					<option value="fallingrocks.png" <?php if ($marker_saved['mapselect_marker']=='fallingrocks.png') { ?> selected="selected" <?php }?> >Fallingrocks</option> 
					
					<option value="family.png" <?php if ($marker_saved['mapselect_marker']=='family.png') { ?> selected="selected" <?php }?> >Family</option> 
					
					<option value="farm.png" <?php if ($marker_saved['mapselect_marker']=='farm.png') { ?> selected="selected" <?php }?> >Farm</option> 
					
					<option value="fastfood.png" <?php if ($marker_saved['mapselect_marker']=='fastfood.png') { ?> selected="selected" <?php }?> >Fastfood</option> 
					
					<option value="festival.png" <?php if ($marker_saved['mapselect_marker']=='festival.png') { ?> selected="selected" <?php }?> >Festival</option> 
					
					<option value="findajob.png" <?php if ($marker_saved['mapselect_marker']=='findajob.png') { ?> selected="selected" <?php }?> >Findajob</option> 
					
					<option value="findjob.png" <?php if ($marker_saved['mapselect_marker']=='findjob.png') { ?> selected="selected" <?php }?> >Findjob</option> 
					
					<option value="fire.png" <?php if ($marker_saved['mapselect_marker']=='fire.png') { ?> selected="selected" <?php }?> >Fire</option> 
					
					<option value="fire-extinguisher.png" <?php if ($marker_saved['mapselect_marker']=='fire-extinguisher.png') { ?> selected="selected" <?php }?> >Fire-extinguisher</option> 
					
					<option value="firemen.png" <?php if ($marker_saved['mapselect_marker']=='firemen.png') { ?> selected="selected" <?php }?> >Firemen</option> 
					
					<option value="fireworks.png" <?php if ($marker_saved['mapselect_marker']=='fireworks.png') { ?> selected="selected" <?php }?> >Fireworks</option> 
					
					<option value="firstaid.png" <?php if ($marker_saved['mapselect_marker']=='firstaid.png') { ?> selected="selected" <?php }?> >Firstaid</option> 
					
					<option value="fishing.png" <?php if ($marker_saved['mapselect_marker']=='fishing.png') { ?> selected="selected" <?php }?> >Fishing</option> 
					
					<option value="fishing.png" <?php if ($marker_saved['mapselect_marker']=='fishing.png') { ?> selected="selected" <?php }?> >Fishing</option> 
					
					<option value="fitnesscenter.png" <?php if ($marker_saved['mapselect_marker']=='fitnesscenter.png') { ?> selected="selected" <?php }?> >Fitnesscenter</option> 
					
					<option value="fjord.png" <?php if ($marker_saved['mapselect_marker']=='fjord.png') { ?> selected="selected" <?php }?> >Fjord</option> 
					
					<option value="flood.png" <?php if ($marker_saved['mapselect_marker']=='flood.png') { ?> selected="selected" <?php }?> >Flood</option> 
					
					<option value="flowers.png" <?php if ($marker_saved['mapselect_marker']=='flowers.png') { ?> selected="selected" <?php }?> >Flowers</option> 
					
					<option value="followpath.png" <?php if ($marker_saved['mapselect_marker']=='followpath.png') { ?> selected="selected" <?php }?> >Followpath</option> 
					
					<option value="foodtruck.png" <?php if ($marker_saved['mapselect_marker']=='foodtruck.png') { ?> selected="selected" <?php }?> >Foodtruck</option> 
					<option value="forest.png" <?php if ($marker_saved['mapselect_marker']=='forest.png') { ?> selected="selected" <?php }?> >Forest</option>
					
					<option value="fortress.png" <?php if ($marker_saved['mapselect_marker']=='fortress.png') { ?> selected="selected" <?php }?> >Fortress</option> 
					
					<option value="fossils.png" <?php if ($marker_saved['mapselect_marker']=='fossils.png') { ?> selected="selected" <?php }?> >Fossils</option> 
					
					<option value="fountain.png" <?php if ($marker_saved['mapselect_marker']=='fountain.png') { ?> selected="selected" <?php }?> >Fountain</option> 
					
					<option value="friday.png" <?php if ($marker_saved['mapselect_marker']=='friday.png') { ?> selected="selected" <?php }?> >Friday</option> 
					
					<option value="friends.png" <?php if ($marker_saved['mapselect_marker']=='friends.png') { ?> selected="selected" <?php }?> >Friends</option> 
					
					<option value="garden.png" <?php if ($marker_saved['mapselect_marker']=='garden.png') { ?> selected="selected" <?php }?> >Garden</option> 
					
					<option value="gateswalls.png" <?php if ($marker_saved['mapselect_marker']=='gateswalls.png') { ?> selected="selected" <?php }?> >Gateswalls</option> 
					
					<option value="gazstation.png" <?php if ($marker_saved['mapselect_marker']=='gazstation.png') { ?> selected="selected" <?php }?> >Gazstation</option>
					
					<option value="gifts.png" <?php if ($marker_saved['mapselect_marker']=='gifts.png') { ?> selected="selected" <?php }?> >Gifts</option>  
					
					<option value="geyser.png" <?php if ($marker_saved['mapselect_marker']=='geyser.png') { ?> selected="selected" <?php }?> >Geyser</option> 
					
					<option value="girlfriend.png" <?php if ($marker_saved['mapselect_marker']=='girlfriend.png') { ?> selected="selected" <?php }?> >Girlfriend</option> 
					
					<option value="glacier.png" <?php if ($marker_saved['mapselect_marker']=='glacier.png') { ?> selected="selected" <?php }?> >Glacier</option> 
					
					<option value="golf.png" <?php if ($marker_saved['mapselect_marker']=='golf.png') { ?> selected="selected" <?php }?> >Golf</option> 
					
					<option value="gondola.png" <?php if ($marker_saved['mapselect_marker']=='gondola.png') { ?> selected="selected" <?php }?> >Gondola</option> 
					
					<option value="gourmet.png" <?php if ($marker_saved['mapselect_marker']=='gourmet.png') { ?> selected="selected" <?php }?> >Gourmet</option> 
					
					<option value="grocery.png" <?php if ($marker_saved['mapselect_marker']=='grocery.png') { ?> selected="selected" <?php }?> >Grocery</option> 
					
					<option value="gun.png" <?php if ($marker_saved['mapselect_marker']=='gun.png') { ?> selected="selected" <?php }?> >Gun</option> 
					
					<option value="gym.png" <?php if ($marker_saved['mapselect_marker']=='gym.png') { ?> selected="selected" <?php }?> >Gym</option> 
					
					<option value="hairsalon.png" <?php if ($marker_saved['mapselect_marker']=='hairsalon.png') { ?> selected="selected" <?php }?> >Hairsalon</option> 
					
					<option value="handball.png" <?php if ($marker_saved['mapselect_marker']=='handball.png') { ?> selected="selected" <?php }?> >Handball</option>
					
					<option value="hats.png" <?php if ($marker_saved['mapselect_marker']=='hats.png') { ?> selected="selected" <?php }?> >Hats</option>
					
					<option value="headstone.png" <?php if ($marker_saved['mapselect_marker']=='headstone.png') { ?> selected="selected" <?php }?> >Headstone</option>
					
					<option value="headstonejewish.png" <?php if ($marker_saved['mapselect_marker']=='headstonejewish.png') { ?> selected="selected" <?php }?> >Headstonejewish</option> 
					
					<option value="highway.png" <?php if ($marker_saved['mapselect_marker']=='highway.png') { ?> selected="selected" <?php }?> >Highway</option> 
					
					<option value="hiking-tourism.png" <?php if ($marker_saved['mapselect_marker']=='hiking-tourism.png') { ?> selected="selected" <?php }?> >Hiking-tourism</option> 
					
					<option value="historicalquarter.png" <?php if ($marker_saved['mapselect_marker']=='historicalquarter.png') { ?> selected="selected" <?php }?> >Historicalquarter</option> 
					
					<option value="home.png" <?php if ($marker_saved['mapselect_marker']=='home.png') { ?> selected="selected" <?php }?> >Home</option> 
					
					<option value="horseriding.png" <?php if ($marker_saved['mapselect_marker']=='horseriding.png') { ?> selected="selected" <?php }?> >Horseriding</option>
					 
					<option value="hospital.png" <?php if ($marker_saved['mapselect_marker']=='hospital.png') { ?> selected="selected" <?php }?> >Hospital</option> 
					
					<option value="hostel.png" <?php if ($marker_saved['mapselect_marker']=='hostel.png') { ?> selected="selected" <?php }?> >Hostel</option> 
					
					<option value="hotairballoon.png" <?php if ($marker_saved['mapselect_marker']=='hotairballoon.png') { ?> selected="selected" <?php }?> >Hotairballoon</option>
					 
					<option value="hotel1star.png" <?php if ($marker_saved['mapselect_marker']=='hotel1star.png') { ?> selected="selected" <?php }?> >Hotel1star</option> 
					
					<option value="hotel2stars.png" <?php if ($marker_saved['mapselect_marker']=='hotel2stars.png') { ?> selected="selected" <?php }?> >Hotel2stars</option> 
					
					<option value="hotel3stars.png" <?php if ($marker_saved['mapselect_marker']=='hotel3stars.png') { ?> selected="selected" <?php }?> >Hotel3stars</option> 
					
					<option value="hotel4stars.png" <?php if ($marker_saved['mapselect_marker']=='hotel4stars.png') { ?> selected="selected" <?php }?> >Hotel4stars</option> 
					
					<option value="hotel5stars.png" <?php if ($marker_saved['mapselect_marker']=='hotel5stars.png') { ?> selected="selected" <?php }?> >Hotel5stars</option> 
					
					<option value="hunting.png" <?php if ($marker_saved['mapselect_marker']=='hunting.png') { ?> selected="selected" <?php }?> >Hunting</option> 
					
					<option value="icecream.png" <?php if ($marker_saved['mapselect_marker']=='icecream.png') { ?> selected="selected" <?php }?> >Icecream</option> 
					
					<option value="icehockey.png" <?php if ($marker_saved['mapselect_marker']=='icehockey.png') { ?> selected="selected" <?php }?> >Icehockey</option> 
					
					<option value="iceskating.png" <?php if ($marker_saved['mapselect_marker']=='iceskating.png') { ?> selected="selected" <?php }?> >Iceskating</option> 
					
					<option value="info.png" <?php if ($marker_saved['mapselect_marker']=='info.png') { ?> selected="selected" <?php }?> >Info</option> 
					
					<option value="jewelry.png" <?php if ($marker_saved['mapselect_marker']=='jewelry.png') { ?> selected="selected" <?php }?> >Jewelry</option> 
					
					<option value="jewishquarter.png" <?php if ($marker_saved['mapselect_marker']=='jewishquarter.png') { ?> selected="selected" <?php }?> >Jewishquarter</option> 
									
					<option value="jogging.png" <?php if ($marker_saved['mapselect_marker']=='jogging.png') { ?> selected="selected" <?php }?> >Jogging</option> 
					
					<option value="judo.png" <?php if ($marker_saved['mapselect_marker']=='judo.png') { ?> selected="selected" <?php }?> >Judo</option> 
					
					<option value="justice.png" <?php if ($marker_saved['mapselect_marker']=='justice.png') { ?> selected="selected" <?php }?> >Justice</option> 
					
					<option value="karate.png" <?php if ($marker_saved['mapselect_marker']=='karate.png') { ?> selected="selected" <?php }?> >Karate</option> 
					
					<option value="karting.png" <?php if ($marker_saved['mapselect_marker']=='karting.png') { ?> selected="selected" <?php }?> >Karting</option> 
					
					<option value="kayak.png" <?php if ($marker_saved['mapselect_marker']=='kayak.png') { ?> selected="selected" <?php }?> >Kayak</option> 
					
					<option value="laboratory.png" <?php if ($marker_saved['mapselect_marker']=='laboratory.png') { ?> selected="selected" <?php }?> >laboratory</option> 
					
					<option value="lake.png" <?php if ($marker_saved['mapselect_marker']=='lake.png') { ?> selected="selected" <?php }?> >Lake</option> 
					
					<option value="laundromat.png" <?php if ($marker_saved['mapselect_marker']=='laundromat.png') { ?> selected="selected" <?php }?> >Laundromat</option> 
					
					<option value="left.png" <?php if ($marker_saved['mapselect_marker']=='left.png') { ?> selected="selected" <?php }?> >Left</option> 
					
					<option value="leftthendown.png" <?php if ($marker_saved['mapselect_marker']=='leftthendown.png') { ?> selected="selected" <?php }?> >Leftthendown</option> 
					
					<option value="leftthenup.png" <?php if ($marker_saved['mapselect_marker']=='leftthenup.png') { ?> selected="selected" <?php }?> >Leftthenup</option> 
					
					<option value="library.png" <?php if ($marker_saved['mapselect_marker']=='library.png') { ?> selected="selected" <?php }?> >Library</option> 
					<option value="lighthouse.png" <?php if ($marker_saved['mapselect_marker']=='lighthouse.png') { ?> selected="selected" <?php }?> >Lighthouse</option> 
					
					<option value="liquor.png" <?php if ($marker_saved['mapselect_marker']=='liquor.png') { ?> selected="selected" <?php }?> >Liquor</option> 
					
					<option value="lock.png" <?php if ($marker_saved['mapselect_marker']=='lock.png') { ?> selected="selected" <?php }?> >Lock</option> 
					
					<option value="lockerrental.png" <?php if ($marker_saved['mapselect_marker']=='lockerrental.png') { ?> selected="selected" <?php }?> >Lockerrental</option> 
					
					<option value="magicshow.png" <?php if ($marker_saved['mapselect_marker']=='magicshow.png') { ?> selected="selected" <?php }?> >Magicshow</option> 
					
					<option value="mainroad.png" <?php if ($marker_saved['mapselect_marker']=='mainroad.png') { ?> selected="selected" <?php }?> >Mainroad</option> 
					
					<option value="massage.png" <?php if ($marker_saved['mapselect_marker']=='massage.png') { ?> selected="selected" <?php }?> >Massage</option> 
					
					<option value="military.png" <?php if ($marker_saved['mapselect_marker']=='accimilitarydent.png') { ?> selected="selected" <?php }?> >Military</option> 
					
					<option value="c.png" <?php if ($marker_saved['mapselect_marker']=='fossils.png') { ?> selected="selected" <?php }?> >Mine</option> 
					
					<option value="mobilephonetower.png" <?php if ($marker_saved['mapselect_marker']=='mobilephonetower.png') { ?> selected="selected" <?php }?> >Mobilephonetower</option> 
					
					<option value="modernmonument.png" <?php if ($marker_saved['mapselect_marker']=='modernmonument.png') { ?> selected="selected" <?php }?> >Modernmonument</option> 
					
					<option value="moderntower.png" <?php if ($marker_saved['mapselect_marker']=='moderntower.png') { ?> selected="selected" <?php }?> >Moderntower</option> 
					
					<option value="monastery.png" <?php if ($marker_saved['mapselect_marker']=='monastery.png') { ?> selected="selected" <?php }?> >Monastery</option> 
					
					<option value="monday.png" <?php if ($marker_saved['mapselect_marker']=='monday.png') { ?> selected="selected" <?php }?> >Monday</option> 
					
					<option value="mosque.png" <?php if ($marker_saved['mapselect_marker']=='mosque.png') { ?> selected="selected" <?php }?> >Mosque</option>
					
					<option value="motorcycle.png" <?php if ($marker_saved['mapselect_marker']=='motorcycle.png') { ?> selected="selected" <?php }?> >Motorcycle</option>
					
					<option value="movierental.png" <?php if ($marker_saved['mapselect_marker']=='movierental.png') { ?> selected="selected" <?php }?> >Movierental</option>
					
					<option value="museum.png" <?php if ($marker_saved['mapselect_marker']=='museum.png') { ?> selected="selected" <?php }?> >Museum</option> 
					
					<option value="museum-archeological.png" <?php if ($marker_saved['mapselect_marker']=='"museum-archeological.png') { ?> selected="selected" <?php }?> >Museum-archeological</option> 
					
					<option value="museum-art.png" <?php if ($marker_saved['mapselect_marker']=='museum-art.png') { ?> selected="selected" <?php }?> >Museum-art</option> 
					
					<option value="museum-crafts.png" <?php if ($marker_saved['mapselect_marker']=='museum-crafts.png') { ?> selected="selected" <?php }?> >Museum-crafts</option> 
					
					
					<option value="museum-historical.png"  <?php if ($marker_saved['mapselect_marker']=='museum-historical.png') { ?> selected="selected" <?php }?> >Museum-historical</option> 
					
					
					<option value="museum-naval.png"  <?php if ($marker_saved['mapselect_marker']=='museum-naval.png') { ?> selected="selected" <?php }?> >Museum-naval</option> 
					
					<option value="museum-science.png" <?php if ($marker_saved['mapselect_marker']=='museum-science.png') { ?> selected="selected" <?php }?> >Museum-science</option> 
					
					<option value="museum-war.png" <?php if ($marker_saved['mapselect_marker']=='museum-war.png') { ?> selected="selected" <?php }?> >Museum-war</option> 
					
					<option value="music.png" <?php if ($marker_saved['mapselect_marker']=='music.png') { ?> selected="selected" <?php }?> >music</option> 
					
					<option value="music-classical.png" <?php if ($marker_saved['mapselect_marker']=='music-classical.png') { ?> selected="selected" <?php }?> >Music-classical</option>
					 
					<option value="music-hiphop.png" <?php if ($marker_saved['mapselect_marker']=='music-hiphop.png') { ?> selected="selected" <?php }?> >Music-hiphop</option> 
					
					<option value="music-live.png" <?php if ($marker_saved['mapselect_marker']=='music-live.png') { ?> selected="selected" <?php }?> >Music-live</option> 
					
					<option value="music-rock.png" <?php if ($marker_saved['mapselect_marker']=='music-rock.png') { ?> selected="selected" <?php }?> >Music-rock</option> 
					
					<option value="nanny.png" <?php if ($marker_saved['mapselect_marker']=='nanny.png') { ?> selected="selected" <?php }?> >Nanny</option> 
					
					<option value="newsagent.png" <?php if ($marker_saved['mapselect_marker']=='newsagent.png') { ?> selected="selected" <?php }?> >Newsagent</option> 
					
					<option value="nordicski.png" <?php if ($marker_saved['mapselect_marker']=='nordicski.png') { ?> selected="selected" <?php }?> >Nordicski</option> 
					
					<option value="nursery.png" <?php if ($marker_saved['mapselect_marker']=='nursery.png') { ?> selected="selected" <?php }?> >Nursery</option> 
					
					<option value="observatory.png" <?php if ($marker_saved['mapselect_marker']=='observatory.png') { ?> selected="selected" <?php }?> >Observatory</option> 
					
					<option value="oilpumpjack.png" <?php if ($marker_saved['mapselect_marker']=='oilpumpjack.png') { ?> selected="selected" <?php }?> >Oilpumpjack</option>
					 
					<option value="olympicsite.png" <?php if ($marker_saved['mapselect_marker']=='olympicsite.png') { ?> selected="selected" <?php }?> >Olympicsite</option> 
					
					<option value="ophthalmologist.png" <?php if ($marker_saved['mapselect_marker']=='ophthalmologist.png') { ?> selected="selected" <?php }?> >Ophthalmologist</option> 
					
					<option value="pagoda.png" <?php if ($marker_saved['mapselect_marker']=='pagoda.png') { ?> selected="selected" <?php }?> >Pagoda</option> 
					
					<option value="paint.png" <?php if ($marker_saved['mapselect_marker']=='paint.png') { ?> selected="selected" <?php }?> >Paint</option> 
					
					<option value="palace.png" <?php if ($marker_saved['mapselect_marker']=='palace.png') { ?> selected="selected" <?php }?> >Palace</option> 
					
					<option value="panoramic180.png" <?php if ($marker_saved['mapselect_marker']=='panoramic180.png') { ?> selected="selected" <?php }?> >Panoramic180</option>
					 
					<option value="panoramic.png" <?php if ($marker_saved['mapselect_marker']=='panoramic.png') { ?> selected="selected" <?php }?> >Panoramic</option> 
					
					<option value="park.png" <?php if ($marker_saved['mapselect_marker']=='park.png') { ?> selected="selected" <?php }?> >Park</option> 
					
					<option value="parkandride.png" <?php if ($marker_saved['mapselect_marker']=='parkandride.png') { ?> selected="selected" <?php }?> >Parkandride</option> 
					
					<option value="parking.png" <?php if ($marker_saved['mapselect_marker']=='parking.png') { ?> selected="selected" <?php }?> >Parking</option> 
					
					<option value="park-urban.png" <?php if ($marker_saved['mapselect_marker']=='park-urban.png') { ?> selected="selected" <?php }?> >Park-urban</option> 
					
					<option value="party.png" <?php if ($marker_saved['mapselect_marker']=='party.png') { ?> selected="selected" <?php }?> >Party</option> 
					
					<option value="patisserie.png" <?php if ($marker_saved['mapselect_marker']=='patisserie.png') { ?> selected="selected" <?php }?> >Patisserie</option> 
					
					<option value="pedestriancrossing.png" <?php if ($marker_saved['mapselect_marker']=='pedestriancrossing.png') { ?> selected="selected" <?php }?> >Pedestriancrossing</option> 
					
					<option value="perfumery.png" <?php if ($marker_saved['mapselect_marker']=='perfumery.png') { ?> selected="selected" <?php }?> >perfumery</option> 
					
					<option value="personalwatercraft.png" <?php if ($marker_saved['mapselect_marker']=='personalwatercraft.png') { ?> selected="selected" <?php }?> >Personalwatercraft</option> 
					
					<option value="petroglyphs.png" <?php if ($marker_saved['mapselect_marker']=='petroglyphs.png') { ?> selected="selected" <?php }?> >Petroglyphs</option> 
					
					<option value="pets.png" <?php if ($marker_saved['mapselect_marker']=='pets.png') { ?> selected="selected" <?php }?> >Pets</option> 
					
					<option value="phones.png" <?php if ($marker_saved['mapselect_marker']=='phones.png') { ?> selected="selected" <?php }?> >Phones</option> 
					
					<option value="photo.png" <?php if ($marker_saved['mapselect_marker']=='photo.png') { ?> selected="selected" <?php }?> >Photo</option> 
					
					<option value="photodown.png" <?php if ($marker_saved['mapselect_marker']=='photodown.png') { ?> selected="selected" <?php }?> >Photodown</option> 
					
					<option value="photodownleft.png" <?php if ($marker_saved['mapselect_marker']=='photodownleft.png') { ?> selected="selected" <?php }?> >Photodownleft</option> 
					
					<option value="photodownright.png" <?php if ($marker_saved['mapselect_marker']=='photodownright.png') { ?> selected="selected" <?php }?> >Photodownright</option> 
					
					<option value="photography.png" <?php if ($marker_saved['mapselect_marker']=='photography.png') { ?> selected="selected" <?php }?> >Photography</option> 
					
					<option value="photoleft.png" <?php if ($marker_saved['mapselect_marker']=='photoleft.png') { ?> selected="selected" <?php }?> >Photoleft</option> 
					
					<option value="photoright.png" <?php if ($marker_saved['mapselect_marker']=='photoright.png') { ?> selected="selected" <?php }?> >Photoright</option> 
					
					<option value="photoup.png" <?php if ($marker_saved['mapselect_marker']=='photoup.png') { ?> selected="selected" <?php }?> >Photoup</option> 
					
					<option value="photoupleft.png" <?php if ($marker_saved['mapselect_marker']=='photoupleft.png') { ?> selected="selected" <?php }?> >Photoupleft</option> 
					
					<option value="photoupright.png" <?php if ($marker_saved['mapselect_marker']=='photoupright.png') { ?> selected="selected" <?php }?> >Photoupright</option> 
					
					<option value="picnic.png" <?php if ($marker_saved['mapselect_marker']=='picnic.png') { ?> selected="selected" <?php }?> >Picnic</option> 
					
					<option value="pizza.png" <?php if ($marker_saved['mapselect_marker']=='pizza.png') { ?> selected="selected" <?php }?> >Pizza</option> 	
					
					<option value="places-unvisited.png" <?php if ($marker_saved['mapselect_marker']=='places-unvisited.png') { ?> selected="selected" <?php }?> >Places-unvisited</option>
					
					<option value="places-visited.png" <?php if ($marker_saved['mapselect_marker']=='places-visited.png') { ?> selected="selected" <?php }?> >Places-visited</option>
					
					<option value="planecrash.png" <?php if ($marker_saved['mapselect_marker']=='planecrash.png') { ?> selected="selected" <?php }?> >Planecrash</option>
					
					<option value="playground.png" <?php if ($marker_saved['mapselect_marker']=='playground.png') { ?> selected="selected" <?php }?> >Playground</option> 
					
					<option value="poker.png" <?php if ($marker_saved['mapselect_marker']=='poker.png') { ?> selected="selected" <?php }?> >Poker</option> 
					
					<option value="police2.png" <?php if ($marker_saved['mapselect_marker']=='police2.png') { ?> selected="selected" <?php }?> >Police2</option> 
					
					<option value="police.png" <?php if ($marker_saved['mapselect_marker']=='police.png') { ?> selected="selected" <?php }?> >Police</option> 
					
					<option value="pool.png" <?php if ($marker_saved['mapselect_marker']=='pool.png') { ?> selected="selected" <?php }?> >Pool</option> 
					
					<option value="pool-indoor.png" <?php if ($marker_saved['mapselect_marker']=='pool-indoor.png') { ?> selected="selected" <?php }?> >Pool-indoor</option> 
					
					<option value="port.png" <?php if ($marker_saved['mapselect_marker']=='port.png') { ?> selected="selected" <?php }?> >Port</option> 
					
					<option value="postal.png" <?php if ($marker_saved['mapselect_marker']=='postal.png') { ?> selected="selected" <?php }?> >Postal</option> 
					
					<option value="powerlinepole.png" <?php if ($marker_saved['mapselect_marker']=='powerlinepole.png') { ?> selected="selected" <?php }?> >Powerlinepole</option> 
					
					<option value="powerplant.png" <?php if ($marker_saved['mapselect_marker']=='powerplant.png') { ?> selected="selected" <?php }?> >Powerplant</option> 
					
					<option value="powersubstation.png" <?php if ($marker_saved['mapselect_marker']=='powersubstation.png') { ?> selected="selected" <?php }?> >Powersubstation</option> 
					
					<option value="prison.png" <?php if ($marker_saved['mapselect_marker']=='prison.png') { ?> selected="selected" <?php }?> >Prison</option> 
					
					<option value="publicart.png" <?php if ($marker_saved['mapselect_marker']=='publicart.png') { ?> selected="selected" <?php }?> >Publicart</option> 
					
					<option value="racing.png" <?php if ($marker_saved['mapselect_marker']=='racing.png') { ?> selected="selected" <?php }?> >Racing</option> 
					
					<option value="radiation.png" <?php if ($marker_saved['mapselect_marker']=='radiation.png') { ?> selected="selected" <?php }?> >Radiation</option> 
					
					<option value="rain.png" <?php if ($marker_saved['mapselect_marker']=='rain.png') { ?> selected="selected" <?php }?> >Rain</option> 
					
					<option value="rattlesnake.png" <?php if ($marker_saved['mapselect_marker']=='rattlesnake.png') { ?> selected="selected" <?php }?> >Rattlesnake</option>
					 
					<option value="realestate.png" <?php if ($marker_saved['mapselect_marker']=='realestate.png') { ?> selected="selected" <?php }?> >realestate</option> 
					
					<option value="recycle.png" <?php if ($marker_saved['mapselect_marker']=='recycle.png') { ?> selected="selected" <?php }?> >Recycle</option> 
					
					<option value="regroup.png" <?php if ($marker_saved['mapselect_marker']=='regroup.png') { ?> selected="selected" <?php }?> >Regroup</option> 
					
					<option value="resort.png" <?php if ($marker_saved['mapselect_marker']=='resort.png') { ?> selected="selected" <?php }?> >Resort</option> 
					
					<option value="restaurant.png" <?php if ($marker_saved['mapselect_marker']=='restaurant.png') { ?> selected="selected" <?php }?> >Restaurant</option> 
					
					<option value="restaurantafrican.png" <?php if ($marker_saved['mapselect_marker']=='restaurantafrican.png') { ?> selected="selected" <?php }?> >Restaurantafrican</option> 
					
					<option value="restaurant-barbecue.png" <?php if ($marker_saved['mapselect_marker']=='accidrestaurant-barbecueent.png') { ?> selected="selected" <?php }?> >Restaurant-barbecue</option> 
					
					<option value="restaurant-buffet.png" <?php if ($marker_saved['mapselect_marker']=='restaurant-buffet.png') { ?> selected="selected" <?php }?> >Restaurant-buffet</option> 
					
					<option value="restaurantchinese.png" <?php if ($marker_saved['mapselect_marker']=='restaurantchinese.png') { ?> selected="selected" <?php }?> >Restaurantchinese</option> 
					
					<option value="restaurant-fish.png" <?php if ($marker_saved['mapselect_marker']=='restaurant-fish.png') { ?> selected="selected" <?php }?> >Restaurant-fish</option> 
					
					<option value="restaurantfishchips.png" <?php if ($marker_saved['mapselect_marker']=='restaurantfishchips.png') { ?> selected="selected" <?php }?> >Restaurantfishchips</option> 
					
					<option value="restaurantgourmet.png" <?php if ($marker_saved['mapselect_marker']=='restaurantgourmet.png') { ?> selected="selected" <?php }?> >Restaurantgourmet</option> 
					
					<option value="restaurantgreek.png" <?php if ($marker_saved['mapselect_marker']=='restaurantgreek.png') { ?> selected="selected" <?php }?> >Restaurantgreek</option> 
					
					<option value="restaurantindian.png" <?php if ($marker_saved['mapselect_marker']=='restaurantindian.png') { ?> selected="selected" <?php }?> >Restaurantindian</option> 
					
					<option value="restaurantitalian.png" <?php if ($marker_saved['mapselect_marker']=='restaurantitalian.png') { ?> selected="selected" <?php }?> >Restaurantitalian</option> 
					
					<option value="restaurantjapanese.png" <?php if ($marker_saved['mapselect_marker']=='restaurantjapanese.png') { ?> selected="selected" <?php }?> >Restaurantjapanese</option> 
					
					<option value="restaurantkebab.png" <?php if ($marker_saved['mapselect_marker']=='restaurantkebab.png') { ?> selected="selected" <?php }?> >Restaurantkebab</option> 
					
					<option value="restaurantkorean.png" <?php if ($marker_saved['mapselect_marker']=='restaurantkorean.png') { ?> selected="selected" <?php }?> >Restaurantkorean</option> 
					
					<option value="restaurantmediterranean.png" <?php if ($marker_saved['mapselect_marker']=='restaurantmediterranean.png') { ?> selected="selected" <?php }?> >Restaurantmediterranean</option> 
					
					<option value="restaurantmexican.png" <?php if ($marker_saved['mapselect_marker']=='restaurantmexican.png') { ?> selected="selected" <?php }?> >Restaurantmexican</option> 
					
					<option value="restaurant-romantic.png" <?php if ($marker_saved['mapselect_marker']=='restaurant-romantic.png') { ?> selected="selected" <?php }?> >Restaurant-romantic</option> 
					
					<option value="restaurantthai.png" <?php if ($marker_saved['mapselect_marker']=='restaurantthai.png') { ?> selected="selected" <?php }?> >Restaurantthai</option> 
					
					<option value="restaurantturkish.png" <?php if ($marker_saved['mapselect_marker']=='restaurantturkish.png') { ?> selected="selected" <?php }?> >Restaurantturkish</option> 
					
					<option value="revolution.png" <?php if ($marker_saved['mapselect_marker']=='revolution.png') { ?> selected="selected" <?php }?> >Revolution</option> 
					
					<option value="right.png" <?php if ($marker_saved['mapselect_marker']=='right.png') { ?> selected="selected" <?php }?> >Right</option> 
					
					<option value="rightthendown.png" <?php if ($marker_saved['mapselect_marker']=='rightthendown.png') { ?> selected="selected" <?php }?> >Rightthendown</option> 
					
					<option value="rightthenup.png" <?php if ($marker_saved['mapselect_marker']=='rightthenup.png') { ?> selected="selected" <?php }?> >Rightthenup</option> 
					
					<option value="riparian.png" <?php if ($marker_saved['mapselect_marker']=='riparian.png') { ?> selected="selected" <?php }?> >Riparian</option> 
					
					<option value="riparian.png" <?php if ($marker_saved['mapselect_marker']=='riparian.png') { ?> selected="selected" <?php }?> >Ropescourse</option> 
					
					<option value="rowboat.png" <?php if ($marker_saved['mapselect_marker']=='rowboat.png') { ?> selected="selected" <?php }?> >Rowboat</option> 
					
					<option value="rugby.png" <?php if ($marker_saved['mapselect_marker']=='rugby.png') { ?> selected="selected" <?php }?> >Rugby</option> 
					
					<option value="ruins.png" <?php if ($marker_saved['mapselect_marker']=='ruins.png') { ?> selected="selected" <?php }?> >Ruins</option> 		
					
					<option value="sailboat.png" <?php if ($marker_saved['mapselect_marker']=='sailboat.png') { ?> selected="selected" <?php }?> >Sailboat</option>
					
					<option value="sailboat-sport.png" <?php if ($marker_saved['mapselect_marker']=='sailboat-sport.png') { ?> selected="selected" <?php }?> >Sailboat-sport</option>
					
					<option value="satursday.png" <?php if ($marker_saved['mapselect_marker']=='satursday.png') { ?> selected="selected" <?php }?> >Satursday</option> 
					
					<option value="sauna.png" <?php if ($marker_saved['mapselect_marker']=='sauna.png') { ?> selected="selected" <?php }?> >Sauna</option> 
					
					<option value="school.png" <?php if ($marker_saved['mapselect_marker']=='school.png') { ?> selected="selected" <?php }?> >School</option> 
					
					<option value="schrink.png" <?php if ($marker_saved['mapselect_marker']=='schrink.png') { ?> selected="selected" <?php }?> >Schrink</option> 
					<option value="sciencecenter.png" <?php if ($marker_saved['mapselect_marker']=='sciencecenter.png') { ?> selected="selected" <?php }?> >Sciencecenter</option> 
					
					<option value="seals.png" <?php if ($marker_saved['mapselect_marker']=='seals.png') { ?> selected="selected" <?php }?> >seals</option> 
					
					<option value="seniorsite.png" <?php if ($marker_saved['mapselect_marker']=='seniorsite.png') { ?> selected="selected" <?php }?> >Seniorsite</option> 
					
					<option value="shelter-picnic.png" <?php if ($marker_saved['mapselect_marker']=='shelter-picnic.png') { ?> selected="selected" <?php }?> >Shelter-picnic</option> 
					
					<option value="shelter-sleeping.png" <?php if ($marker_saved['mapselect_marker']=='shelter-sleeping.png') { ?> selected="selected" <?php }?> >Shelter-sleeping</option> 
					
					<option value="shoes.png" <?php if ($marker_saved['mapselect_marker']=='shoes.png') { ?> selected="selected" <?php }?> >shoes</option> 
					
					<option value="shoppingmall.png" <?php if ($marker_saved['mapselect_marker']=='shoppingmall.png') { ?> selected="selected" <?php }?> >Shoppingmall</option>
					 
					<option value="shore.png" <?php if ($marker_saved['mapselect_marker']=='shore.png') { ?> selected="selected" <?php }?> >shore</option> 
					
					<option value="shower.png" <?php if ($marker_saved['mapselect_marker']=='shower.png') { ?> selected="selected" <?php }?> >Shower</option> 
					
					<option value="sight.png" <?php if ($marker_saved['mapselect_marker']=='sight.png') { ?> selected="selected" <?php }?> >Sight</option> 
					
					<option value="skateboarding.png" <?php if ($marker_saved['mapselect_marker']=='skateboarding.png') { ?> selected="selected" <?php }?> >Skateboarding</option> 
					
					<option value="skiing.png" <?php if ($marker_saved['mapselect_marker']=='skiing.png') { ?> selected="selected" <?php }?> >Skiing</option> 
					
					<option value="skijump.png" <?php if ($marker_saved['mapselect_marker']=='skijump.png') { ?> selected="selected" <?php }?> >Skijump</option> 
					
					<option value="skilift.png" <?php if ($marker_saved['mapselect_marker']=='skilift.png') { ?> selected="selected" <?php }?> >Skilift</option> 
					
					<option value="smallcity.png" <?php if ($marker_saved['mapselect_marker']=='smallcity.png') { ?> selected="selected" <?php }?> >Smallcity</option> 
					
					<option value="smokingarea.png" <?php if ($marker_saved['mapselect_marker']=='smokingarea.png') { ?> selected="selected" <?php }?> >Smokingarea</option> 
					
					<option value="sneakers.png" <?php if ($marker_saved['mapselect_marker']=='sneakers.png') { ?> selected="selected" <?php }?> >Sneakers</option> 
					
					<option value="snow.png" <?php if ($marker_saved['mapselect_marker']=='snow.png') { ?> selected="selected" <?php }?> >Snow</option> 
					
					<option value="snowboarding.png" <?php if ($marker_saved['mapselect_marker']=='snowboarding.png') { ?> selected="selected" <?php }?> >Snowboarding</option> 
					
					<option value="snowmobiling.png" <?php if ($marker_saved['mapselect_marker']=='snowmobiling.png') { ?> selected="selected" <?php }?> >Snowmobiling</option> 
					
					<option value="snowshoeing.png" <?php if ($marker_saved['mapselect_marker']=='snowshoeing.png') { ?> selected="selected" <?php }?> >Snowshoeing</option> 
					
					<option value="soccer.png" <?php if ($marker_saved['mapselect_marker']=='soccer.png') { ?> selected="selected" <?php }?> >Soccer</option> 
					<option value="soccer2.png" <?php if ($marker_saved['mapselect_marker']=='soccer2.png') { ?> selected="selected" <?php }?> >Soccer2</option> 
					<option value="spaceport.png" <?php if ($marker_saved['mapselect_marker']=='spaceport.png') { ?> selected="selected" <?php }?> >Spaceport</option> 
					
					<option value="speed20.png" <?php if ($marker_saved['mapselect_marker']=='speed20.png') { ?> selected="selected" <?php }?> >Speed20</option> 
					
					<option value="speed30.png" <?php if ($marker_saved['mapselect_marker']=='speed30.png') { ?> selected="selected" <?php }?> >Speed30</option> 
					
					<option value="speed40.png" <?php if ($marker_saved['mapselect_marker']=='speed40.png') { ?> selected="selected" <?php }?> >Speed40</option> 
					
					<option value="speed50.png" <?php if ($marker_saved['mapselect_marker']=='speed50.png') { ?> selected="selected" <?php }?> >Speed50</option> 
					
					<option value="speed60.png" <?php if ($marker_saved['mapselect_marker']=='speed60.png') { ?> selected="selected" <?php }?> >Speed60</option> 
					
					<option value="speed70.png" <?php if ($marker_saved['mapselect_marker']=='Speed70.png') { ?> selected="selected" <?php }?> >Speed70</option> 
					
					<option value="speed80.png" <?php if ($marker_saved['mapselect_marker']=='speed80.png') { ?> selected="selected" <?php }?> >Speed80</option> 
					
					<option value="speed90.png" <?php if ($marker_saved['mapselect_marker']=='speed90.png') { ?> selected="selected" <?php }?> >Speed90</option> 
					
					<option value="speed100.png" <?php if ($marker_saved['mapselect_marker']=='speed100.png') { ?> selected="selected" <?php }?> >Speed100</option> 
					
					<option value="speed110.png" <?php if ($marker_saved['mapselect_marker']=='speed110.png') { ?> selected="selected" <?php }?> >Speed110</option> 
					
					<option value="speed120.png" <?php if ($marker_saved['mapselect_marker']=='speed120.png') { ?> selected="selected" <?php }?> >Speed120</option> 
					
					<option value="speed130.png" <?php if ($marker_saved['mapselect_marker']=='speed130.png') { ?> selected="selected" <?php }?> >Speed130</option> 
					
					<option value="speedhump.png" <?php if ($marker_saved['mapselect_marker']=='speedhump.png') { ?> selected="selected" <?php }?> >Speedhump</option> 
					
					<option value="spelunking.png" <?php if ($marker_saved['mapselect_marker']=='spelunking.png') { ?> selected="selected" <?php }?> >Spelunking</option> 
					
					<option value="stadium.png" <?php if ($marker_saved['mapselect_marker']=='stadium.png') { ?> selected="selected" <?php }?> >Stadium</option> 
					
					<option value="statue.png" <?php if ($marker_saved['mapselect_marker']=='statue.png') { ?> selected="selected" <?php }?> >Statue</option>
					
					<option value="steamtrain.png" <?php if ($marker_saved['mapselect_marker']=='steamtrain.png') { ?> selected="selected" <?php }?> >Steamtrain</option> 
										
					<option value="stop.png" <?php if ($marker_saved['mapselect_marker']=='stop.png') { ?> selected="selected" <?php }?> >Stop</option> 
					
					<option value="stoplight.png" <?php if ($marker_saved['mapselect_marker']=='stoplight.png') { ?> selected="selected" <?php }?> >Stoplight</option> 
					
					<option value="strike.png" <?php if ($marker_saved['mapselect_marker']=='strike.png') { ?> selected="selected" <?php }?> >Strike</option> 		
					
					<option value="strike1.png" <?php if ($marker_saved['mapselect_marker']=='strike1.png') { ?> selected="selected" <?php }?> >Strike1</option>
					
					<option value="subway.png" <?php if ($marker_saved['mapselect_marker']=='subway.png') { ?> selected="selected" <?php }?> >Subway</option>
					
					<option value="sun.png" <?php if ($marker_saved['mapselect_marker']=='sun.png') { ?> selected="selected" <?php }?> >Sun</option>
					
					<option value="sunday.png" <?php if ($marker_saved['mapselect_marker']=='sunday.png') { ?> selected="selected" <?php }?> >Sunday</option> 
					
					<option value="supermarket.png" <?php if ($marker_saved['mapselect_marker']=='supermarket.png') { ?> selected="selected" <?php }?> >Supermarket</option> 
					
					<option value="surfing.png" <?php if ($marker_saved['mapselect_marker']=='surfing.png') { ?> selected="selected" <?php }?> >Surfing</option> 
					
					<option value="suv.png" <?php if ($marker_saved['mapselect_marker']=='suv.png') { ?> selected="selected" <?php }?> >Suv</option> 
					
					<option value="synagogue.png" <?php if ($marker_saved['mapselect_marker']=='synagogue.png') { ?> selected="selected" <?php }?> >Synagogue</option> 
					
					<option value="tailor.png" <?php if ($marker_saved['mapselect_marker']=='tailor.png') { ?> selected="selected" <?php }?> >Tailor</option> 
					
					<option value="tapas.png" <?php if ($marker_saved['mapselect_marker']=='tapas.png') { ?> selected="selected" <?php }?> >Tapas</option> 
					
					<option value="taxi.png" <?php if ($marker_saved['mapselect_marker']=='taxi.png') { ?> selected="selected" <?php }?> >Taxi</option> 
					
					<option value="taxiway.png" <?php if ($marker_saved['mapselect_marker']=='taxiway.png') { ?> selected="selected" <?php }?> >Taxiway</option> 
					
					<option value="teahouse.png" <?php if ($marker_saved['mapselect_marker']=='teahouse.png') { ?> selected="selected" <?php }?> >Teahouse</option> 
					
					<option value="telephone.png" <?php if ($marker_saved['mapselect_marker']=='telephone.png') { ?> selected="selected" <?php }?> >Telephone</option> 
					
					<option value="temple.png" <?php if ($marker_saved['mapselect_marker']=='temple.png') { ?> selected="selected" <?php }?> >Temple</option> 
					
					<option value="tennis.png" <?php if ($marker_saved['mapselect_marker']=='tennis.png') { ?> selected="selected" <?php }?> >Tennis</option> 
					
					<option value="tennis2.png" <?php if ($marker_saved['mapselect_marker']=='tennis2.png') { ?> selected="selected" <?php }?> >Tennis2</option> 
					
					<option value="tent.png" <?php if ($marker_saved['mapselect_marker']=='tent.png') { ?> selected="selected" <?php }?> >Tent</option> 
					
					<option value="terrace.png" <?php if ($marker_saved['mapselect_marker']=='terrace.png') { ?> selected="selected" <?php }?> >Terrace</option> 
					
					<option value="text.png" <?php if ($marker_saved['mapselect_marker']=='text.png') { ?> selected="selected" <?php }?> >Text</option> 
					
					<option value="textiles.png" <?php if ($marker_saved['mapselect_marker']=='textiles.png') { ?> selected="selected" <?php }?> >Textiles</option> 
					
					<option value="theater.png" <?php if ($marker_saved['mapselect_marker']=='theater.png') { ?> selected="selected" <?php }?> >Theater</option> 
					
					<option value="themepark.png" <?php if ($marker_saved['mapselect_marker']=='themepark.png') { ?> selected="selected" <?php }?> >Themepark</option> 
					
					<option value="thunder.png" <?php if ($marker_saved['mapselect_marker']=='thunder.png') { ?> selected="selected" <?php }?> >Thunder</option> 
					
					<option value="thursday.png" <?php if ($marker_saved['mapselect_marker']=='thursday.png') { ?> selected="selected" <?php }?> >Thursday</option> 
					
					<option value="toilets.png" <?php if ($marker_saved['mapselect_marker']=='toilets.png') { ?> selected="selected" <?php }?> >Toilets</option> 
					
					<option value="tollstation.png" <?php if ($marker_saved['mapselect_marker']=='tollstation.png') { ?> selected="selected" <?php }?> >Tollstation</option> 
					
					<option value="tools.png" <?php if ($marker_saved['mapselect_marker']=='tools.png') { ?> selected="selected" <?php }?> >Tools</option> 
					
					<option value="tower.png" <?php if ($marker_saved['mapselect_marker']=='tower.png') { ?> selected="selected" <?php }?> >Tower</option> 
					
					<option value="toys.png" <?php if ($marker_saved['mapselect_marker']=='toys.png') { ?> selected="selected" <?php }?> >Toys</option> 
					
					<option value="trafficenforcementcamera.png" <?php if ($marker_saved['mapselect_marker']=='trafficenforcementcamera.png') { ?> selected="selected" <?php }?> >Trafficenforcementcamera</option>
					 
					<option value="train.png" <?php if ($marker_saved['mapselect_marker']=='train.png') { ?> selected="selected" <?php }?> >Train</option> 
					
					<option value="tram.png" <?php if ($marker_saved['mapselect_marker']=='tram.png') { ?> selected="selected" <?php }?> >Tram</option> 
					
					<option value="trash.png" <?php if ($marker_saved['mapselect_marker']=='trash.png') { ?> selected="selected" <?php }?> >Trash</option> 
					
					<option value="truck.png" <?php if ($marker_saved['mapselect_marker']=='truck.png') { ?> selected="selected" <?php }?> >Truck</option> 
					<option value="tuesday.png" <?php if ($marker_saved['mapselect_marker']=='tuesday.png') { ?> selected="selected" <?php }?> >Tuesday</option> 
					<option value="tunnel.png" <?php if ($marker_saved['mapselect_marker']=='tunnel.png') { ?> selected="selected" <?php }?> >Tunnel</option> 
					<option value="turnleft.png" <?php if ($marker_saved['mapselect_marker']=='turnleft.png') { ?> selected="selected" <?php }?> >Turnleft</option> 
					
					<option value="turnright.png" <?php if ($marker_saved['mapselect_marker']=='turnright.png') { ?> selected="selected" <?php }?> >Turnright</option> 
					
					<option value="university.png" <?php if ($marker_saved['mapselect_marker']=='university.png') { ?> selected="selected" <?php }?> >University</option> 
					
					<option value="up.png" <?php if ($marker_saved['mapselect_marker']=='up.png') { ?> selected="selected" <?php }?> >Up</option> 
					
					<option value="upleft.png" <?php if ($marker_saved['mapselect_marker']=='upleft.png') { ?> selected="selected" <?php }?> >Upleft</option> 
					
					<option value="upright.png" <?php if ($marker_saved['mapselect_marker']=='upright.png') { ?> selected="selected" <?php }?> >Upright</option> 
					
					<option value="upthenleft.png" <?php if ($marker_saved['mapselect_marker']=='upthenleft.png') { ?> selected="selected" <?php }?> >Upthenleft</option> 
					
					<option value="upthenright.png" <?php if ($marker_saved['mapselect_marker']=='upthenright.png') { ?> selected="selected" <?php }?> >Upthenright</option> 
					
					<option value="usfootball.png" <?php if ($marker_saved['mapselect_marker']=='usfootball.png') { ?> selected="selected" <?php }?> >Usfootball</option> 
					
					<option value="vespa.png" <?php if ($marker_saved['mapselect_marker']=='vespa.png') { ?> selected="selected" <?php }?> >Vespa</option> 
					<option value="vet.png" <?php if ($marker_saved['mapselect_marker']=='vet.png') { ?> selected="selected" <?php }?> >Vet</option> 
					<option value="video.png" <?php if ($marker_saved['mapselect_marker']=='video.png') { ?> selected="selected" <?php }?> >Video</option> 
					
					<option value="videogames.png" <?php if ($marker_saved['mapselect_marker']=='videogames.png') { ?> selected="selected" <?php }?> >Videogames</option> 
					
					<option value="villa.png" <?php if ($marker_saved['mapselect_marker']=='villa.png') { ?> selected="selected" <?php }?> >Villa</option> 
					
					<option value="villa-tourism.png" <?php if ($marker_saved['mapselect_marker']=='villa-tourism.png') { ?> selected="selected" <?php }?> >Villa-tourism</option> 
					
					<option value="waitingroom.png" <?php if ($marker_saved['mapselect_marker']=='waitingroom.png') { ?> selected="selected" <?php }?> >Waitingroom</option> 
					
					<option value="water.png" <?php if ($marker_saved['mapselect_marker']=='water.png') { ?> selected="selected" <?php }?> >Water</option>
					
					<option value="waterfal.png" <?php if ($marker_saved['mapselect_marker']=='waterfal.png') { ?> selected="selected" <?php }?> >Waterfall</option>
					
					<option value="watermil.png" <?php if ($marker_saved['mapselect_marker']=='watermil.png') { ?> selected="selected" <?php }?> >Watermill</option>
					
					<option value="waterpar.png" <?php if ($marker_saved['mapselect_marker']=='waterpar.png') { ?> selected="selected" <?php }?> >Waterpark</option> 
					
					<option value="waterskiing.png" <?php if ($marker_saved['mapselect_marker']=='waterskiing.png') { ?> selected="selected" <?php }?> >Waterskiing</option> 
					
					<option value="watertower.png" <?php if ($marker_saved['mapselect_marker']=='watertower.png') { ?> selected="selected" <?php }?> >Watertower</option> 
					
					<option value="waterwell.png" <?php if ($marker_saved['mapselect_marker']=='waterwell.png') { ?> selected="selected" <?php }?> >Waterwell</option> 
					
					<option value="waterwellpump.png" <?php if ($marker_saved['mapselect_marker']=='waterwellpump.png') { ?> selected="selected" <?php }?> >Waterwellpump</option> 
					
					<option value="wedding.png" <?php if ($marker_saved['mapselect_marker']=='wedding.png') { ?> selected="selected" <?php }?> >Wedding</option> 
					
					<option value="wednesday.png" <?php if ($marker_saved['mapselect_marker']=='wednesday.png') { ?> selected="selected" <?php }?> >Wednesday</option> 
					
					<option value="wetland.png" <?php if ($marker_saved['mapselect_marker']=='wetland.png') { ?> selected="selected" <?php }?> >Wetland</option> 
					
					<option value="white1.png" <?php if ($marker_saved['mapselect_marker']=='white1.png') { ?> selected="selected" <?php }?> >White1</option> 
					
					<option value="white20.png" <?php if ($marker_saved['mapselect_marker']=='white20.png') { ?> selected="selected" <?php }?> >White20</option> 
					
					<option value="wifi.png" <?php if ($marker_saved['mapselect_marker']=='wifi.png') { ?> selected="selected" <?php }?> >Wifi</option> 
					
					<option value="windmill.png" <?php if ($marker_saved['mapselect_marker']=='windmill.png') { ?> selected="selected" <?php }?> >Windmill</option> 
					
					<option value="windsurfing.png" <?php if ($marker_saved['mapselect_marker']=='windsurfing.png') { ?> selected="selected" <?php }?> >Windsurfing</option> 
					
					<option value="windturbine.png" <?php if ($marker_saved['mapselect_marker']=='windturbine.png') { ?> selected="selected" <?php }?> >Windturbine</option> 
					
					<option value="winery.png" <?php if ($marker_saved['mapselect_marker']=='winery.png') { ?> selected="selected" <?php }?> >Winery</option> 
					
					<option value="wineyard.png" <?php if ($marker_saved['mapselect_marker']=='wineyard.png') { ?> selected="selected" <?php }?> >Wineyard</option> 
					
					<option value="workoffice.png" <?php if ($marker_saved['mapselect_marker']=='workoffice.png') { ?> selected="selected" <?php }?> >Workoffice</option> 
					
					<option value="world.png" <?php if ($marker_saved['mapselect_marker']=='world.png') { ?> selected="selected" <?php }?> >World</option> 
					<option value="worldheritagesite.png" <?php if ($instance['mapse
					lect_marker']=='worldheritagesite.png') { ?> selected="selected" <?php }?> >Worldheritagesite</option> 
					
					<option value="yoga.png" <?php if ($marker_saved['mapselect_marker']=='yoga.png') { ?> selected="selected" <?php }?> >Yoga</option> 
					
					<option value="youthhostel.png" <?php if ($marker_saved['mapselect_marker']=='youthhostel.png') { ?> selected="selected" <?php }?> >Youthhostel</option> 
					
					<option value="zipline.png" <?php if ($marker_saved['mapselect_marker']=='zipline.png') { ?> selected="selected" <?php }?> >Zipline</option> 
					
					<option value="zoo.png" <?php if ($marker_saved['mapselect_marker']=='zoo.png') { ?> selected="selected" <?php }?> >Zoo</option> 
					
				</select>
	<?php	
	}
}

function wgmp_scripts_method() {
    wp_enqueue_script('wgmp_map','http://maps.google.com/maps/api/js?sensor=false');
}

add_action( 'wp_enqueue_scripts', 'wgmp_scripts_method' );

?>