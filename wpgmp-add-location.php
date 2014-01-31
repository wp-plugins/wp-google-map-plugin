<?php
/**
 * This function used to add locations in backend.
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.0
 * @package Maps
 */
 
function wpgmp_add_locations()
{
  if( isset($_POST['googlemap_location']) && $_POST['googlemap_location']=="Save Locations" )
  {
		if( $_POST['googlemap_title']=="" )
		{
		   $error[]= __( 'Please enter title.', 'wpgmp_google_map' );
		}
		if( $_POST['googlemap_address']=="" )
		{
		   $error[]= __( 'Please enter address.', 'wpgmp_google_map' );
		}
		if( $_POST['googlemap_latitude']=="" )
		{
		   $error[]= __( 'Please enter latitude.', 'wpgmp_google_map' );
		}
		if( $_POST['googlemap_longitude']=="" )
		{
		   $error[]= __( 'Please enter longitude.', 'wpgmp_google_map' );
		}
		if( $_POST['googlemap_draggable']=="" )
		{
		   $_POST['googlemap_draggable'] = "false";
		}
		
		$messages = base64_encode(serialize($_POST['infowindow_message']));
		
		
		if( empty($error) )
		{
			global $wpdb,$post;
	
			if( !empty($_POST['googlemap_address']) )
			{				
	
			$address = $_POST['googlemap_address'];
	
			$prepAddr = str_replace(' ','+',$address);
	
			$geocode=wp_remote_get('http://maps.google.com/maps/api/geocode/json?address='.$prepAddr.'&sensor=false');
	
			$output= json_decode($geocode['body']);
	
			$lat = $output->results[0]->geometry->location->lat;
	
			$long = $output->results[0]->geometry->location->lng; 
	
			}
	
			else
	
			{
	
			$lat = $_POST['googlemap_latitude'];
	
			$long = $_POST['googlemap_longitude'];	
	
			}
	
		$location_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."map_locations WHERE location_address = %s",$_POST['googlemap_address']));
	
		
	
			if( empty($location_record->location_address) )
	
			{
	
			$location_table=$wpdb->prefix."map_locations";
	
			$in_loc_data = array(
	
			'location_title' => htmlspecialchars(stripslashes($_POST['googlemap_title'])),
	
			'location_address' => htmlspecialchars(stripslashes($_POST['googlemap_address'])),
	
			'location_draggable' => $_POST['googlemap_draggable'],
	
			'location_latitude' => $lat,
	
			'location_longitude'=> $long,
			
			'location_messages'=> $messages,
			
			'location_marker_image' => htmlspecialchars(stripslashes($_POST['upload_image_url'])),
			
			'location_group_map' => $_POST['location_group_map']
				
			);
	
			$wpdb->insert($location_table,$in_loc_data);
	
			$success = __( 'Locations Added Successfully.', 'wpgmp_google_map' );
	
			$_POST = array();
	
			}
	
			else
	
			{
	
			$error[] = __( 'Address already exists.', 'wpgmp_google_map' );
	
			}
	
		}
	} 
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br/></div>
<h2><?php _e('Add Location', 'wpgmp_google_map')?></h2><br/>
<?php
if( !empty($error) )
{
	$error_msg=implode('<br>',$error);
	
	wpgmp_showMessage($error_msg,true);
}
if( !empty($success) )
{
    wpgmp_showMessage($success);
}
?>
<form method="post">
<div class="map_table">
    <label for="title"><?php _e('Location Title', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
    <input type="text" name="googlemap_title" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    <p class="description"><?php _e('Enter here the location title', 'wpgmp_google_map')?></p>
       
        	
    <label for="title"><?php _e('Address', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
    <input type="text" name="googlemap_address" id="googlemap_address" style="width:350px;" value="<?php echo $_POST['googlemap_address']; ?>" />
    <input type="button" value="<?php _e('Geocode', 'wpgmp_google_map')?>" onclick="geocodeaddress()" class="button button-primary">
    <p class="description"><?php _e('Enter here the address. Google auto suggest helps you to choose one.', 'wpgmp_google_map')?></p>
    <input type="text" name="googlemap_latitude" id="googlemap_latitude" class="google_latitude" placeholder="<?php _e('Latitude', 'wpgmp_google_map')?>" style="width:167px; margin-left:230px;" value="<?php echo $_POST['googlemap_latitude']; ?>" />&nbsp;&nbsp;&nbsp;
    <input type="text" name="googlemap_longitude" id="googlemap_longitude" class="google_longitude" placeholder="<?php _e('Longitude', 'wpgmp_google_map')?>" style="width:167px;" value="<?php echo $_POST['googlemap_longitude']; ?>" />
    <p class="description"><?php _e('Enter here the latitude.', 'wpgmp_google_map')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Enter here the longitude.', 'wpgmp_google_map')?></p><br />
	<div id="map" style="width: 700px; height: 300px;margin: 0.6em; margin-left:230px;"></div>   
	
    <br /><br />
    <label for="title"><?php _e('Info Window Title #1', 'wpgmp_google_map')?></label>
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_one]" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Message #1', 'wpgmp_google_map')?></label>
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_one]" id="googlemap_infomessage" size="45" /><?php echo $_POST['googlemap_infomessage']; ?></textarea>
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Title #2', 'wpgmp_google_map')?></label>
    <input type="text" name="infowindow_message[googlemap_infowindow_title_two]" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Message #2', 'wpgmp_google_map')?></label>
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_two]" id="googlemap_infomessage" size="45" /><?php echo $_POST['googlemap_infomessage']; ?></textarea>
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Title #3', 'wpgmp_google_map')?></label>
    <input type="text" name="infowindow_message[googlemap_infowindow_title_three]" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Message #3', 'wpgmp_google_map')?></label>
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_three]" id="googlemap_infomessage" size="45" /><?php echo $_POST['googlemap_infomessage']; ?></textarea>
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Title #4', 'wpgmp_google_map')?></label>
    <input type="text" name="infowindow_message[googlemap_infowindow_title_four]" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Message #4', 'wpgmp_google_map')?></label>
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_four]" id="googlemap_infomessage" size="45" /><?php echo $_POST['googlemap_infomessage']; ?></textarea>
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Info Window Title #5', 'wpgmp_google_map')?></label>
    <input type="text" name="infowindow_message[googlemap_infowindow_title_five]" style="width:350px;" value="<?php echo $_POST['googlemap_title']; ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
		
    <label for="title"><?php _e('Info Window Message #5', 'wpgmp_google_map')?></label>
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_five]" id="googlemap_infomessage" size="45" /><?php echo $_POST['googlemap_infomessage']; ?></textarea>
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    <label for="title"><?php _e('Is Draggale', 'wpgmp_google_map')?></label>
    <input type="checkbox" name="googlemap_draggable" value="true"<?php checked($_POST['googlemap_draggable'],'true') ?>/>
    <p class="description"><?php _e('Marker Draggabble.', 'wpgmp_google_map')?></p>
            
    <label for="title"><?php _e('Choose Marker Image', 'wpgmp_google_map')?></label>
           
    <div style="margin-left:5px;  margin-bottom:10px;">   
            
               
  				
	<?php
    
    global $wpdb;
    
    $group_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."group_map",NULL));
    
    if( !empty($group_results) )
    {
    ?>
    <select name="location_group_map">
             <option value="">Select group</option>
    
    <?php
        for($i = 0; $i < count($group_results); $i++)
        {
    ?>
    
        <option value="<?php echo $group_results[$i]->group_map_id; ?>"<?php selected($group_results[$i]->group_map_id,$_POST['location_group_map']); ?>><?php echo $group_results[$i]->group_map_title; ?></option>
    
     <?php
        
        }
    ?>
    </select>
       
    <?php
    }
    
    else
    {
    
    ?>	
    
    <?php _e('NO GROUP MAPS FOUND.', 'wpgmp_google_map')?><a href="<?php echo admin_url('admin.php?page=wpgmp_google_wpgmp_create_group_map') ?>"><?php _e('CLICK HERE', 'wpgmp_google_map')?></a><?php _e('TO ADD GROUP MAPS', 'wpgmp_google_map')?> 
    
    <?php
    
     }
    
     ?>
    
    </div>
    
    <p class="description"><?php _e('Please Select one or multiple group.', 'wpgmp_google_map')?></p>
    <p class="submit">
    <input type="submit" name="googlemap_location" id="submit" class="button button-primary" value="<?php _e('Save Locations', 'wpgmp_google_map')?>" style="margin-left:230px;" />
    </p>
</div>
</form>
</div>
<?php
}