<?php
/**
 * This function used to create a new map in backend.
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.0
 * @package Maps
 */
function wpgmp_create_map()
{
if( isset($_POST['create_map_location']) && $_POST['create_map_location']=="Save Map" )
{
	
if( $_POST['map_title']=="" )
{
   $error[]= __( 'Please enter title.', 'wpgmp_google_map' );
}
if( !intval($_POST['map_width']) && $_POST['map_width']!='' )
{
	$error[]= __( 'Please enter Integer value in map width.', 'wpgmp_google_map' );
}
if( $_POST['map_height']=='' )
{
	$error[]= __( 'Please enter map height.', 'wpgmp_google_map' );
}
else if( !intval($_POST['map_height']) )
{
	$error[]= __( 'Please enter Integer value in map height.', 'wpgmp_google_map' );
}
if( $_POST['direction_servics']['route_direction']=="" )
{
	$_POST['direction_servics']['route_direction'] = 'false';
	$_POST['direction_servics']['route_direction_stroke_color'] = "#0000FF";
	$_POST['direction_servics']['route_direction_stroke_opacity'] = 1.0;
	$_POST['direction_servics']['route_direction_stroke_weight'] = 2;
}
else
{
	if( count($_POST['locations'])<2 )
	{
		$error[]= __( 'please add two locations for route directions.', 'wpgmp_google_map' );
	}
	else
	{
		$_POST['direction_servics']['route_direction'] = $_POST['direction_servics']['route_direction'];
	}
}
if( $_POST['scrolling_wheel']=="" )
{
	$_POST['scrolling_wheel'] = 'true';
}
else
{
	$_POST['scrolling_wheel'] = $_POST['scrolling_wheel'];
}
if( $_POST['visual_refresh']=="" )
{
    $_POST['visual_refresh'] = 'false';
}
else
{
	$_POST['visual_refresh'] = $_POST['visual_refresh'];
}
if( $_POST['street_view_control']['street_control']=="" )
{
   $_POST['street_view_control']['street_control'] = 'false';
}
else
{
	$_POST['street_view_control']['street_control'] = $_POST['street_view_control']['street_control'];
}
if( $_POST['street_view_control']['street_view_close_button']=="" )
{
   $_POST['street_view_control']['street_view_close_button'] = 'false';
}
else
{
	$_POST['street_view_control']['street_view_close_button'] = $_POST['street_view_control']['street_view_close_button'];
}
if( $_POST['street_view_control']['links_control']=="" )
{
   $_POST['street_view_control']['links_control'] = 'true';
}
else
{
	$_POST['street_view_control']['links_control'] = $_POST['street_view_control']['links_control'];
}
if( $_POST['street_view_control']['street_view_pan_control']=="" )
{
   $_POST['street_view_control']['street_view_pan_control'] = 'true';
}
else
{
	$_POST['street_view_control']['street_view_pan_control'] = $_POST['street_view_control']['street_view_pan_control'];
}
if( $_POST['control']['pan_control']=="" )
{
   $_POST['control']['pan_control'] = 'true';
}
else
{
	$_POST['control']['pan_control'] = $_POST['control']['pan_control'];
}
if( $_POST['control']['zoom_control']=="" )
{
   $_POST['control']['zoom_control'] = 'true';
}
else
{
	$_POST['control']['zoom_control'] = $_POST['control']['zoom_control'];
}
if( $_POST['control']['map_type_control']=="" )
{
   $_POST['control']['map_type_control'] = 'true';
}
else
{
	$_POST['control']['map_type_control'] = $_POST['control']['map_type_control'];
}
if( $_POST['control']['scale_control']=="" )
{
   $_POST['control']['scale_control'] = 'true';
}
else
{
	$_POST['control']['scale_control'] = $_POST['control']['scale_control'];
}
if( $_POST['control']['street_view_control']=="" )
{
   $_POST['control']['street_view_control'] = 'true';
}
else
{
	$_POST['control']['street_view_control'] = $_POST['control']['street_view_control'];
}
if( $_POST['control']['overview_map_control']=="" )
{
   $_POST['control']['overview_map_control'] = 'true';
}
else
{
	$_POST['control']['overview_map_control'] = $_POST['control']['overview_map_control'];
}
if( $_POST['info_window_setting']['info_window']=="" )
{
   $_POST['info_window_setting']['info_window'] = 'true';
}
else
{
	$_POST['info_window_setting']['info_window'] = $_POST['info_window_setting']['info_window'];
}
if( $_POST['locations']=="" )
{
   $error[]= __( 'Please check any one location.', 'wpgmp_google_map' );
}
if( $_POST['group_map_setting']['enable_group_map']=='true' )
{
	if( $_POST['group_map_setting']['select_group_map']=="" )
	{
		$error[]= __( 'Please check at least one group map.', 'wpgmp_google_map' );
	}
}




if( empty($error) )
{
global $wpdb;
$map_table=$wpdb->prefix.'create_map';
$create_map_data = array(
	'map_title' => htmlspecialchars(stripslashes($_POST['map_title'])),
	'map_width' => $_POST['map_width'],
	'map_height' => $_POST['map_height'],
	'map_zoom_level' => $_POST['zoom_level'],
	'map_type' => $_POST['choose_map'],
	'map_scrolling_wheel' => $_POST['scrolling_wheel'],
	'map_visual_refresh' => $_POST['visual_refresh'],
	'map_street_view_setting' => serialize($_POST['street_view_control']),
	'map_all_control' => serialize($_POST['control']),
	'map_info_window_setting' => serialize($_POST['info_window_setting']),
	'style_google_map' => serialize($_POST['style_array_type']),
	'map_locations' => serialize($_POST['locations']),
	'map_layer_setting' => serialize($_POST['layer_setting'])
	);
$wpdb->insert($map_table,$create_map_data);
$success= __( 'Maps created Successfully.', 'wpgmp_google_map' );
//$_POST = '';
}
}
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div>
<h2><?php _e('Create Map', 'wpgmp_google_map')?></h2><br />
<form method="post">
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
<div class="map_table">
<fieldset>
    <legend><?php _e('General Settings', 'wpgmp_google_map')?></legend>
    
    <label for="title"><?php _e('Map Title', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
	<input type="text" name="map_title" value="<?php echo $_POST['map_title']; ?>" class="create_map" />
	<p class="description"><?php _e('Enter here the title', 'wpgmp_google_map')?></p>
	<label for="title"><?php _e('Map Width', 'wpgmp_google_map')?></label>
	<input type="text" name="map_width" value="<?php echo $_POST['map_width']; ?>" class="create_map" /><?php _e('&nbsp;px', 'wpgmp_google_map')?>
	<p class="description"><?php _e('Enter here the map width', 'wpgmp_google_map')?></p>
	<label for="title"><?php _e('Map Height', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
	<input type="text" name="map_height" value="<?php echo $_POST['map_height']; ?>" class="create_map" /><?php _e('&nbsp;px', 'wpgmp_google_map')?>
	<p class="description"><?php _e('Enter here the map height', 'wpgmp_google_map')?></p>
	<label for="title"><?php _e('Map Zoom Level', 'wpgmp_google_map')?></label>
	<select name="zoom_level">
        <option value="1"<?php selected($_POST['zoom_level'],'1') ?>>1</option>
        <option value="2"<?php selected($_POST['zoom_level'],'2') ?>>2</option>
        <option value="3"<?php selected($_POST['zoom_level'],'3') ?>>3</option>
        <option value="4"<?php selected($_POST['zoom_level'],'4') ?>>4</option>
        <option value="5"<?php selected($_POST['zoom_level'],'5') ?>>5</option>
        <option value="6"<?php selected($_POST['zoom_level'],'6') ?>>6</option>
        <option value="7"<?php selected($_POST['zoom_level'],'7') ?>>7</option>
        <option value="8"<?php selected($_POST['zoom_level'],'8') ?>>8</option>
        <option value="9"<?php selected($_POST['zoom_level'],'9') ?>>9</option>
        <option value="10"<?php selected($_POST['zoom_level'],'10') ?>>10</option>
        <option value="11"<?php selected($_POST['zoom_level'],'11') ?>>11</option>
        <option value="12"<?php selected($_POST['zoom_level'],'12') ?>>12</option>
        <option value="13"<?php selected($_POST['zoom_level'],'13') ?>>13</option>
        <option value="14"<?php selected($_POST['zoom_level'],'14') ?>>14</option>
    </select>
	<p class="description"><?php _e('(Available options - 1,2,3,4,5,6,8,9,10,11,12,13,14).', 'wpgmp_google_map')?></p>
	
    <label for="title"><?php _e('Choose Map Type', 'wpgmp_google_map')?></label>
    <select name="choose_map">
        <option value="ROADMAP"<?php selected($_POST['choose_map'],'ROADMAP') ?>><?php _e('ROADMAP', 'wpgmp_google_map')?></option>
        <option value="SATELLITE"<?php selected($_POST['choose_map'],'SATELLITE') ?>><?php _e('SATELLITE', 'wpgmp_google_map')?></option>
        <option value="HYBRID"<?php selected($_POST['choose_map'],'HYBRID') ?>><?php _e('HYBRID', 'wpgmp_google_map')?></option>
        <option value="TERRAIN"<?php selected($_POST['choose_map'],'TERRAIN') ?>><?php _e('TERRAIN', 'wpgmp_google_map')?></option>
    </select>
	<p class="description"><?php _e('(Available options - ROADMAP,SATELLITE,HYBRID,TERRAIN {Default is roadmap type}).', 'wpgmp_google_map')?></p>
	
    
    
    <label for="title"><?php _e('Turn Off Scrolling Wheel', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="scrolling_wheel" value="false"<?php checked($_POST['scrolling_wheel'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable scroll wheel zooms.', 'wpgmp_google_map')?></p>
    
  
	<label for="title"><?php _e('Enable Visual Refresh', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="visual_refresh" value="true"<?php checked($_POST['visual_refresh'],'true') ?>/>
	<p class="description"><?php _e('Please check to enable visual refresh.', 'wpgmp_google_map')?></p>
	
	</fieldset>
   
<fieldset>
    <legend><?php _e('Choose Locations', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></legend>
	
    <ul>
		<?php
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."map_locations limit 10",NULL));
		
		if( !empty($results) )
		{
        for($i = 0; $i < count($results); $i++)
		{
        ?>
            <li>
            <?php
            if( !empty($_POST['locations']) )
            { 
            if( in_array($results[$i]->location_id, $_POST['locations']) )
            {
            ?>
            <input type="checkbox" name="locations[]" checked="checked" value="<?php echo $results[$i]->location_id; ?>"/>&nbsp;&nbsp;<?php echo $results[$i]->location_address; ?>
            <?php 
            }
            else
            {
            ?>
            <input type="checkbox" name="locations[]" value="<?php echo $results[$i]->location_id; ?>"/>&nbsp;&nbsp;<?php echo $results[$i]->location_address; ?>
            <?php			
            }
            }
            else
            {
            ?>
            <input type="checkbox" name="locations[]" value="<?php echo $results[$i]->location_id; ?>"/>&nbsp;&nbsp;<?php echo $results[$i]->location_address; ?>
            <?php 
            }
            ?>
            </li>
        <?php
         }
		 }
		 else
		 {
        ?>
        <?php _e('NO LOCATIONS FOUND.', 'wpgmp_google_map')?> <a href="<?php echo admin_url('admin.php?page=wpgmp_add_location') ?>"><?php _e('CLICK HERE', 'wpgmp_google_map')?></a><?php _e('TO ADD A LOCATION', 'wpgmp_google_map')?> 
        <?php
		 }
		 ?>
   </ul>
   
</fieldset>
<fieldset>
    <legend><?php _e('Layers', 'wpgmp_google_map')?></legend>
    
    <label for="title"><?php _e('Select Layers', 'wpgmp_google_map')?></label>
	<select name="layer_setting[choose_layer]" onchange="mylayer(this.value)">
        <option value=""><?php _e('Select Layers', 'wpgmp_google_map')?></option>
        <option value="TrafficLayer"<?php selected($_POST['layer_setting']['choose_layer'],'TrafficLayer') ?>><?php _e('Traffic Layers', 'wpgmp_google_map')?></option>
        <option value="TransitLayer"<?php selected($_POST['layer_setting']['choose_layer'],'TransitLayer') ?>><?php _e('Transit Layers', 'wpgmp_google_map')?></option>
        <option value="WeatherLayer"<?php selected($_POST['layer_setting']['choose_layer'],'WeatherLayer') ?>><?php _e('Weather Layers', 'wpgmp_google_map')?></option>
        <option value="BicyclingLayer"<?php selected($_POST['layer_setting']['choose_layer'],'BicyclingLayer') ?>><?php _e('Bicycling Layers', 'wpgmp_google_map')?></option>
	</select>
	<p class="description"><?php _e('(Available options -Traffic Layers,Transit Layers,Weather Layers,Bicycling Layers).', 'wpgmp_google_map')?></p>
	
	
    <?php
	if( $_POST['layer_setting']['choose_layer']=='WeatherLayer' )
	{
	?>
    <div id="weatherlayer">
    
        <label for="title"><?php _e('Temperature units:', 'wpgmp_google_map')?></label>
        <input type="radio" name="layer_setting[temp]" value="CELSIUS"<?php checked($_POST['layer_setting']['temp'],'CELSIUS'); ?> /><?php _e('&nbsp;Celsius&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[temp]" value="FAHRENHEIT"<?php checked($_POST['layer_setting']['temp'],'FAHRENHEIT'); ?> /><?php _e('&nbsp;Fahrenheit', 'wpgmp_google_map')?>
    	<p class="description"><?php _e('Please check temperature unit.', 'wpgmp_google_map')?></p>
        
        <label for="title"><?php _e('Wind speed units:', 'wpgmp_google_map')?></label>
        <input type="radio" name="layer_setting[wind]" value="MILES_PER_HOUR"<?php checked($_POST['layer_setting']['wind'],'MILES_PER_HOUR'); ?> /><?php _e('&nbsp;mph&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[wind]" value="KILOMETERS_PER_HOUR"<?php checked($_POST['layer_setting']['wind'],'KILOMETERS_PER_HOUR'); ?> /><?php _e('&nbsp;km/h&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[wind]" value="METERS_PER_SECOND"<?php checked($_POST['layer_setting']['wind'],'METERS_PER_SECOND'); ?> /><?php _e('&nbsp;m/s', 'wpgmp_google_map')?>
        <p class="description"><?php _e('Please check wind speed unit.', 'wpgmp_google_map')?></p>
    
    </div>
    
    <?php
	}
	else
	{
	?>
    <div id="weatherlayer" style="display:none;">
    
        <label for="title"><?php _e('Temperature units:', 'wpgmp_google_map')?></label>
        <input type="radio" name="layer_setting[temp]" value="CELSIUS" /><?php _e('&nbsp;Celsius&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[temp]" value="FAHRENHEIT" /><?php _e('&nbsp;Fahrenheit', 'wpgmp_google_map')?>
    	<p class="description"><?php _e('Please check temperature unit.', 'wpgmp_google_map')?></p>
        
        <label for="title"><?php _e('Wind speed units:', 'wpgmp_google_map')?></label>
        <input type="radio" name="layer_setting[wind]" value="MILES_PER_HOUR" /><?php _e('&nbsp;mph&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[wind]" value="KILOMETERS_PER_HOUR" /><?php _e('&nbsp;km/h&nbsp;&nbsp;&nbsp;', 'wpgmp_google_map')?>
        <input type="radio" name="layer_setting[wind]" value="METERS_PER_SECOND" /><?php _e('&nbsp;m/s', 'wpgmp_google_map')?>
        <p class="description"><?php _e('Please check wind speed unit.', 'wpgmp_google_map')?></p>
    
    </div>
    <?php
	}
	?>
</fieldset>
<fieldset>
    <legend><?php _e('Control Settings', 'wpgmp_google_map')?></legend>
    
     <label for="title"><?php _e('Turn Off Pan Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[pan_control]" value="false"<?php checked($_POST['control']['pan_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable pan control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Zoom Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[zoom_control]" value="false"<?php checked($_POST['control']['zoom_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable zoom control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Map Type Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[map_type_control]" value="false"<?php checked($_POST['control']['map_type_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable map type control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Scale Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[scale_control]" value="false"<?php checked($_POST['control']['scale_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable scale control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Street View Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[street_view_control]" value="false"<?php checked($_POST['control']['street_view_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable street view control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Overview Map Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="control[overview_map_control]" value="false"<?php checked($_POST['control']['overview_map_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable overview map control.', 'wpgmp_google_map')?></p>
    
</fieldset>

<fieldset>
    <legend><?php _e('Map Style Settings', 'wpgmp_google_map')?></legend>
	 	<p class="description"><?php _e('Available in Pro Version. <a target="_blank" href="http://codecanyon.net/item/advanced-google-maps/5211638">Buy Now</a>', 'wpgmp_google_map')?></p>

</fieldset>


<fieldset>
    <legend><?php _e('Infowindow Settings', 'wpgmp_google_map')?></legend>
    
     <label for="title"><?php _e('Infowindow Settings', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="info_window_setting[enable_info_window_setting]" class="info_window_toggle" value="true"<?php checked($_POST['info_window_setting']['enable_info_window_setting'],'true') ?>/>
	<p class="description"><?php _e('Please check to enable infowindow settings.', 'wpgmp_google_map')?></p>
 <div id="disply_info_window" style="display:none;">
   
    <label for="title"><?php _e('Turn Off Infowindow', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="info_window_setting[info_window]" value="false"<?php checked($_POST['info_window_setting']['info_window'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable infowindow messages.', 'wpgmp_google_map')?></p>
    <label><?php _e('Infowindow Width:', 'wpgmp_google_map')?></label>
    <input type="text" name="info_window_setting[info_window_width]" value="<?php echo $_POST['info_window_setting']['info_window_width']; ?>" class="create_map"/>&nbsp;px
    <p class="description"><?php _e('Please insert infowindow Width.', 'wpgmp_google_map')?></p>
    
    <label><?php _e('Infowindow Height:', 'wpgmp_google_map')?></label>
    <input type="text" name="info_window_setting[info_window_height]" class="create_map" value="<?php echo $_POST['info_window_setting']['info_window_height']; ?>" />&nbsp;px
    <p class="description"><?php _e('Please insert infowindow height.', 'wpgmp_google_map')?></p>
    
     <label><?php _e('Infowindow ShadowStyle:', 'wpgmp_google_map')?></label>
        <select name="info_window_setting[info_window_shadow_style]">
          <option value=""><?php _e('Select shadow style', 'wpgmp_google_map')?></option>
          <option value="0"<?php selected($_POST['info_window_setting']['info_window_shadow_style'],0) ?>>0</option>
          <option value="1"<?php selected($_POST['info_window_setting']['info_window_shadow_style'],1) ?>>1</option>
          <option value="2"<?php selected($_POST['info_window_setting']['info_window_shadow_style'],2) ?>>2</option>
        </select>
     <p class="description"><?php _e('Please select infowindow shadow style.', 'wpgmp_google_map')?></p>
      
        <label><?php _e('Infowindow Border Radius:', 'wpgmp_google_map')?></label>
        <input type="text" name="info_window_setting[info_window_border_radious]" value="<?php echo $_POST['info_window_setting']['info_window_border_radious']; ?>" class="create_map"/>&nbsp;px
        <p class="description"><?php _e('Please insert infowindow border radious.', 'wpgmp_google_map')?></p>
      
        <label><?php _e('Infowindow Border Width:', 'wpgmp_google_map')?></label>
        <input type="text" name="info_window_setting[info_window_border_width]" value="<?php echo $_POST['info_window_setting']['info_window_border_width']; ?>" class="create_map"/>&nbsp;px
        <p class="description"><?php _e('Please insert infowindow border width.', 'wpgmp_google_map')?></p>
      
        <label><?php _e('Infowindow Border Color:', 'wpgmp_google_map')?></label>
        <?php if($_POST['info_window_setting']['info_window_border_color']!=''){ ?>
        <input type="text" value="<?php echo $_POST['info_window_setting']['info_window_border_color']; ?>" name="info_window_setting[info_window_border_color]" class="color {pickerClosable:true}" />
       <?php }else{ ?>
         <input type="text" value="CCCCCC" name="info_window_setting[info_window_border_color]" class="color {pickerClosable:true}" />
       <?php } ?>
        <p class="description"><?php _e('Please insert infowindow border color.', 'wpgmp_google_map')?></p>
     
        <label><?php _e('Infowindow Background Color:', 'wpgmp_google_map')?></label>
        <?php if($_POST['info_window_setting']['info_window_background_color']!=''){ ?>
        <input type="text" value="<?php echo $_POST['info_window_setting']['info_window_background_color']; ?>" name="info_window_setting[info_window_background_color]" class="color {pickerClosable:true}" />
        <?php }else{ ?>
        <input type="text" value="FFFFFF" name="info_window_setting[info_window_background_color]" class="color {pickerClosable:true}" />
        <?php } ?>
        <p class="description"><?php _e('Please insert infowindow background color.', 'wpgmp_google_map')?></p>
     
        <label><?php _e('Infowindow Arrow Size:', 'wpgmp_google_map')?></label>
        <input type="text" name="info_window_setting[info_window_arrow_size]" value="<?php echo $_POST['info_window_setting']['info_window_arrow_size']; ?>" class="create_map"/>&nbsp;px
        <p class="description"><?php _e('Please insert infowindow arrow size.', 'wpgmp_google_map')?></p>
     
        <label><?php _e('Infowindow Arrow Position:', 'wpgmp_google_map')?></label>
        <input type="text" name="info_window_setting[info_window_arrow_position]" value="<?php echo $_POST['info_window_setting']['info_window_arrow_position']; ?>" class="create_map"/>&nbsp;%
        <p class="description"><?php _e('Please insert infowindow arrow position.', 'wpgmp_google_map')?></p>
     
        <label><?php _e('Infowindow Arrow Style:', 'wpgmp_google_map')?></label>
        <select name="info_window_setting[info_window_arrow_style]">
          <option value=""><?php _e('Select Arrow Style', 'wpgmp_google_map')?></option>
          <option value="0"<?php selected($_POST['info_window_setting']['info_window_arrow_style'],0) ?>>0</option>
          <option value="1"<?php selected($_POST['info_window_setting']['info_window_arrow_style'],1) ?>>1</option>
          <option value="2"<?php selected($_POST['info_window_setting']['info_window_arrow_style'],2) ?>>2</option>
        </select>
        <p class="description"><?php _e('Please select infowindow arrow style.', 'wpgmp_google_map')?></p>
</div>    
</fieldset>
<fieldset>
    <legend><?php _e('Street View Settings', 'wpgmp_google_map')?></legend>
    
     <label for="title"><?php _e('Turn On Street View', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="street_view_control[street_control]" class="street_view_toggle" value="true"<?php checked($_POST['street_view_control']['street_control'],'true') ?>/>
	<p class="description"><?php _e('Please check to enable Street View control.', 'wpgmp_google_map')?></p>
    
   <div id="disply_street_view" style="display:none;">
    
        <label for="title"><?php _e('Turn On Close Button', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="street_view_control[street_view_close_button]" value="true"<?php checked($_POST['street_view_control']['street_view_close_button'],'true') ?>/>
	<p class="description"><?php _e('Please check to enable Close button.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off links Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="street_view_control[links_control]" value="false"<?php checked($_POST['street_view_control']['links_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable links control.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Turn Off Street View Pan Control', 'wpgmp_google_map')?></label>
	<input type="checkbox" name="street_view_control[street_view_pan_control]" value="false"<?php checked($_POST['street_view_control']['street_view_pan_control'],'false') ?>/>
	<p class="description"><?php _e('Please check to disable Street View Pan control.', 'wpgmp_google_map')?></p>
    
    </div>
</fieldset>
    
<fieldset>
    <legend><?php _e('Polygon Settings', 'wpgmp_google_map')?></legend>  
 	<p class="description"><?php _e('Available in Pro Version. <a target="_blank" href="http://codecanyon.net/item/advanced-google-maps/5211638">Buy Now</a>', 'wpgmp_google_map')?></p>
</fieldset>
<fieldset>
    <legend><?php _e('Polyline Settings', 'wpgmp_google_map')?></legend>  
 	<p class="description"><?php _e('Available in Pro Version. <a target="_blank" href="http://codecanyon.net/item/advanced-google-maps/5211638">Buy Now</a>', 'wpgmp_google_map')?></p>

</fieldset>
<fieldset>
    <legend><?php _e('Marker Cluster Settings', 'wpgmp_google_map')?></legend>
	 	<p class="description"><?php _e('Available in Pro Version. <a target="_blank" href="http://codecanyon.net/item/advanced-google-maps/5211638">Buy Now</a>', 'wpgmp_google_map')?></p>

</fieldset>
<fieldset>
    <legend><?php _e('Overlay Settings', 'wpgmp_google_map')?></legend>
	 <p class="description"><?php _e('Available in Pro Version. <a target="_blank" href="http://codecanyon.net/item/advanced-google-maps/5211638">Buy Now</a>', 'wpgmp_google_map')?></p>

</fieldset>
	<p class="submit">
	<input type="submit" name="create_map_location" id="submit" class="button button-primary" value="<?php _e('Save Map', 'wpgmp_google_map')?>" >
	</p>
</div>
</form>
</div>
<?php	
}
