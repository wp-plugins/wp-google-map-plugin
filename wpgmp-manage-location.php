<?php
/**
 * This class used to manage locations in backend.
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.0
 * @package Maps
 */
class Wpgmp_Location_Table extends WP_List_Table {
    var $table_data;
    function __construct(){
    global $status, $page,$wpdb;
        parent::__construct( array(
            'singular'  => __( 'googlemap', 'wpgmp_google_map' ),    
            'plural'    => __( 'googlemaps', 'wpgmp_google_map' ),  
            'ajax'      => false       
    ) );
		if($_GET['page']=='wpgmp_manage_location' && $_POST['s']!='')
		{
		$query = "SELECT * FROM ".$wpdb->prefix."map_locations WHERE location_title LIKE '%".$_POST['s']."%'";
		}
		else
		{
		$query = "SELECT * FROM ".$wpdb->prefix."map_locations ORDER BY location_id DESC";
		}
		
	 	$this->table_data = $wpdb->get_results($wpdb->prepare($query,NULL),ARRAY_A );
    add_action( 'admin_head', array( &$this, 'admin_header' ) );            
    }
	
	function admin_header() {
    $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
    if( 'location' != $page )
    return;
    echo '<style type="text/css">';
    echo '.wp-list-table .column-location_title  { width: 20%; }';
	 echo '.wp-list-table .column-location_address  { width: 20%;}';
	 echo '.wp-list-table .column-location_latitude  { width: 20%;}';
	 
	 echo '.wp-list-table .column-location_longitude  { width: 20%;}';
    echo '.wp-list-table .column-location_added  { width: 20%; }';
    echo '</style>';
  }
  
  function no_items() {
    _e( 'No Records for Map Locations.' ,'wpgmp_google_map');
  }
	
  function column_default( $item, $column_name ) {
    switch( $column_name ) {
	 case 'location_title': 
	 case 'location_address':
	  case 'location_latitude':
	  
	  case 'location_longitude':
      case 'location_added':
            return $this->custom_column_value($column_name,$item);
        default:
            return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
    }
  }
function custom_column_value($column_name,$item)
{
	if($column_name=='post_title ')
	return "<a href='".get_permalink( $item[ 'post_id' ] )."'>".$item[ $column_name ]."</a>";
	elseif($column_name=='user_login')
	return "<a href='".get_author_posts_url($item[ 'user_id' ])."'>".$item[ $column_name ]."</a>";
	else
	return $item[ $column_name ];
}
function get_sortable_columns() {
  $sortable_columns = array(
  'location_title '   => array('location_title ',false),
  	'location_address '   => array('location_description ',false),
	'location_latitude '   => array('location_info_message ',false),
	
	'location_longitude '   => array('location_info_message ',false),
	'location_added '   => array('location_added ',false),
  );
  return $sortable_columns;
}
function get_columns(){
        $columns = array(
           	'cb'        => '<input type="checkbox" />',
			'location_title'      => __( 'Title', 'wpgmp_google_map' ),
			'location_address'      => __( 'Address', 'wpgmp_google_map' ),
			'location_latitude'      => __( 'Latitude', 'wpgmp_google_map' ),
			
			'location_longitude'      => __( 'Longitude', 'wpgmp_google_map' ),
			'location_added'      => __( 'When Added', 'wpgmp_google_map' ),
        );
         return $columns;
    }
function usort_reorder( $a, $b ) {
  // If no sort, default to title
  $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : '';
  // If no order, default to asc
  $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
  // Determine sort order
  $result = strcmp( $a[$orderby], $b[$orderby] );
  // Send final sort direction to usort
  return ( $order === 'asc' ) ? $result : -$result;
}
function column_location_title($item){
  $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&location=%s">Edit</a>',$_REQUEST['page'],'edit',$item['location_id']),
            'delete'      => sprintf('<a href="?page=%s&action=%s&location=%s">Delete</a>',$_REQUEST['page'],'delete',$item['location_id'])
        );
  return sprintf('%1$s %2$s', $item['location_title'], $this->row_actions($actions) );
}
function get_bulk_actions() {
  $actions = array(
    'delete'    => 'Delete',
  );
  return $actions;
}
function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="location[]" value="%s" />', $item['location_id']
        );
    }
function prepare_items() {
  $columns  = $this->get_columns();
  $hidden   = array();
  $sortable = $this->get_sortable_columns();
  $this->_column_headers = array( $columns, $hidden, $sortable );
  usort( $this->table_data, array( &$this, 'usort_reorder' ) );
  
  $per_page = 10;
  $current_page = $this->get_pagenum();
  $total_items = count( $this->table_data );
  // only ncessary because we have sample data
  $this->found_data = array_slice( $this->table_data,( ( $current_page-1 )* $per_page ), $per_page );
  $this->set_pagination_args( array(
    'total_items' => $total_items,                  //WE have to calculate the total number of items
    'per_page'    => $per_page                     //WE have to determine how many items to show on a page
  ) );
  $this->items = $this->found_data;
}
}
/**
 * This function used to edit location in backend.
 * @author Flipper Code <hello@flippercode.com>
 * @version 1.0.0
 * @package Maps
 */
function wpgmp_manage_locations()
{
global $wpdb; 
if( $_GET['action']=='delete' && $_GET['location']!='' )
{
	$id = (int)$_GET['location'];
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."map_locations WHERE location_id=%d",$id));
}
if( $_POST['action'] == 'delete' && $_POST['location']!='' )
{
	foreach($_POST['location'] as $id)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."map_locations WHERE location_id=%d",$id));
						
		}
}
if( isset($_POST['update_location']) && $_POST['update_location']=='Update Locations' )
{
	
			if( $_POST['googlemap_title']=="" )
			{
			   $error[]= __( 'Please enter title.', 'wpgmp_google_map' );
			}
			if( $_POST['googlemap_address']=="" )
			{
	
			   $error[]= __( 'Please enter Address.', 'wpgmp_google_map' );
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
$location_update_table=$wpdb->prefix."map_locations";
$wpdb->update( 
$location_update_table, 
array( 
	'location_title' => htmlspecialchars(stripslashes($_POST['googlemap_title'])),
	'location_address' => htmlspecialchars(stripslashes($_POST['googlemap_address'])),
	'location_draggable' => $_POST['googlemap_draggable'],	 
	'location_latitude' => $_POST['googlemap_latitude'],
	'location_longitude' => $_POST['googlemap_longitude'],
	'location_messages'=> $messages,
	'location_marker_image' => htmlspecialchars(stripslashes($_POST['upload_image_url'])),
	'location_group_map' => $_POST['location_group_map']
), 
array( 'location_id' => $_GET['location'] ) 
);
	
update_post_meta( $_GET['location'], '_image_id', $_POST['upload_image_id'] );
$success= __( 'Locations Updated Successfully.', 'wpgmp_google_map' );
	}
}
?>
<?php
if( $_GET['action']=='edit' && $_GET['location']!='' )
{
$user_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."map_locations WHERE location_id=%d",$_GET['location']));
$unmess = unserialize(base64_decode($user_record->location_messages));
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div>
<h2><?php _e('Edit Location', 'wpgmp_google_map')?></h2><br />
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
    <label for="Title"><?php _e('Location Title', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
    
    <input name="googlemap_title" type="text" style="width:350px;" value="<?php echo stripslashes($user_record->location_title 	); ?>" size="50" class="code" >
    
    <p class="description"><?php _e('Insert here the location title.', 'wpgmp_google_map')?></p>
    
    <label for="Description"><?php _e('Address', 'wpgmp_google_map')?>&nbsp;<span style="color:#F00;">*</span></label>
    
    <input type="text" name="googlemap_address" style="width:350px;" id="googlemap_address" size="50" class="code" value="<?php echo stripslashes($user_record->location_address); ?>" />
    
    <input type="button" value="Geocode" onclick="geocodeaddress()" class="button button-primary">
    
    <p class="description"><?php _e('Insert here the address.', 'wpgmp_google_map')?></p>
    
    <input type="text" name="googlemap_latitude" id="googlemap_latitude" class="google_latitude" placeholder="<?php _e('Latitude', 'wpgmp_google_map')?>" style="width:167px; margin-left:230px;" value="<?php echo $user_record->location_latitude; ?>" />&nbsp;&nbsp;&nbsp;
    
    <input type="text" name="googlemap_longitude" id="googlemap_longitude" class="google_longitude" placeholder="<?php _e('Longitude', 'wpgmp_google_map')?>" value="<?php echo $user_record->location_longitude; ?>" />
    
    <p class="description"><?php _e('Insert here the latitude.', 'wpgmp_google_map')?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Insert here the longitude.', 'wpgmp_google_map')?></p><br />
    
    <div id="map" style="width: 700px; height: 300px;margin: 0.6em; margin-left:230px;"></div><br /><br />   
    
    <label for="title"><?php _e('Info Window Title #1', 'wpgmp_google_map')?></label>
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_one]" style="width:350px;" value="<?php echo stripslashes($unmess['googlemap_infowindow_title_one']); ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Message #1', 'wpgmp_google_map')?></label>
    
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_one]" id="googlemap_infomessage" size="45" /><?php echo stripslashes($unmess['googlemap_infowindow_message_one']); ?></textarea>
    
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Title #2', 'wpgmp_google_map')?></label>
    
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_two]" style="width:350px;" value="<?php echo stripslashes($unmess['googlemap_infowindow_title_two']); ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Message #2', 'wpgmp_google_map')?></label>
    
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_two]" id="googlemap_infomessage" size="45" /><?php echo stripslashes($unmess['googlemap_infowindow_message_two']); ?></textarea>
    
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Title #3', 'wpgmp_google_map')?></label>
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_three]" style="width:350px;" value="<?php echo stripslashes($unmess['googlemap_infowindow_title_three']); ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Message #3', 'wpgmp_google_map')?></label>
    
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_three]" id="googlemap_infomessage" size="45" /><?php echo stripslashes($unmess['googlemap_infowindow_message_three']); ?></textarea>
    
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Title #4', 'wpgmp_google_map')?></label>
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_four]" style="width:350px;" value="<?php echo stripslashes($unmess['googlemap_infowindow_title_four']); ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Message #4', 'wpgmp_google_map')?></label>
    
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_four]" id="googlemap_infomessage" size="45" /><?php echo stripslashes($unmess['googlemap_infowindow_message_four']); ?></textarea>
    
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Title #5', 'wpgmp_google_map')?></label>
    
    <input type="text" name="infowindow_message[googlemap_infowindow_title_five]" style="width:350px;" value="<?php echo stripslashes($unmess['googlemap_infowindow_title_five']); ?>" />
    
    <p class="description"><?php _e('Insert here the infoWindow title.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Info Window Message #5', 'wpgmp_google_map')?></label>
    
    <textarea rows="3" cols="70" name="infowindow_message[googlemap_infowindow_message_five]" id="googlemap_infomessage" size="45" /><?php echo stripslashes($unmess['googlemap_infowindow_message_five']); ?></textarea>
    
    <p class="description"><?php _e('Insert here the infoWindow message.', 'wpgmp_google_map')?></p>
    
    <label for="title"><?php _e('Is Draggale', 'wpgmp_google_map')?></label>
    
    <input type="checkbox" name="googlemap_draggable" value="true"<?php checked($user_record->location_draggable,'true') ?>/>
    
    <p class="description"><?php _e('Marker Draggabble.', 'wpgmp_google_map')?></p>
    
    <label for="Image"><?php _e('Choose Marker Image', 'wpgmp_google_map')?></label>
    
    <div style=" margin-left:5px; width:78%; margin-bottom:10px;">     
    
   
     
    <?php
    
    global $wpdb;
    
    $group_results = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."group_map",NULL));
    
    if( !empty($group_results) )
    {
    ?>
    <select name="location_group_map">
         
         <option value=""><?php _e('Select group', 'wpgmp_google_map')?></option>
    
    <?php
    for($i = 0; $i < count($group_results); $i++)
    {
    ?>
    
    <option value="<?php echo $group_results[$i]->group_map_id; ?>"<?php selected($group_results[$i]->group_map_id,$user_record->location_group_map); ?>><?php echo $group_results[$i]->group_map_title; ?></option>
    
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
    
    <p class="description"><?php _e('Please Select Group.', 'wpgmp_google_map')?></p>
    
    <p class="submit">
    <input type="submit" name="update_location" id="submit" class="button button-primary" value="<?php _e('Update Locations', 'wpgmp_google_map')?>" style="width:130px;">
    
    </p>
    
</div>
</form>
</div>
<?php
}
else
{
?>
<div class="wrap">  
<div id="icon-options-general" class="icon32"><br></div>
<h2><?php _e('Manage Locations', 'wpgmp_google_map')?></h2><br />
<?php
$location_list_table = new Wpgmp_Location_Table();
$location_list_table->prepare_items();
?>
<form method="post">
<?php
$location_list_table->search_box( 'search', 'search_id' );
$location_list_table->display();
?> 
</form> 
</div>
<?php
}
}
