<?php
/**
 * googlemap.php :: Show map, place markers, info windows.
 *
 * googlemap version 1.0.0.0
 * copyright (c) 2010 by Sandeep Kumar
 * googlemap is an open source PHP class library to create easliy customized google map. 
 * googlemap is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html#SEC3
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @package Google Map
 * @copyright Copyright (c) 2010-2020  by Sandeep Kumar
 * @license http://www.gnu.org/copyleft/lesser.html#SEC3 LGPL License
 */

/**
 *googlemap is an open source PHP class library for easily create googlemap, place multiple markers.
 * @package Google Map
 */

class GOOGLE_API_3
{

	var $code='';  // Do not edit this.
	var $zoom=14; // Zoop Level.
	var $center_lat = '37.09024'; // google map center location
	var $center_lng = '-95.712891'; // google map center location
	var $center_address = '';
	var $divID='map'; // The div id where you want to 	place your google map
	var $marker=array(); // Array to store markers information. 
	var $instance=1;
	var $height=300;
	var $width=300;
	
	function __construct()
	{
	global $wgmp_containers;
	$this->divID="map".(count($wgmp_containers)+1);
	$wgmp_containers[]=$this->divID;
	}
	// Intialized google map scripts.
	private function start()
	{
	    if($this->center_address)
		{ 
		   $output = $this->getData($this->center_address);	
		  if($output->status == 'OK')
		  {
		    $this->center_lat = $output->results[0]->geometry->location->lat;
		    $this->center_lng = $output->results[0]->geometry->location->lng;
		  }
	   }	 
		
		 
		$this->code='<style>#'.$this->divID.' img {
    max-width: none;
 }</style>'.'<div id="'.$this->divID.'" style="width:'.$this->width."px;height:".$this->height.'px"></div>';

		$this->code.='
		<script type="text/javascript">
      (function() {
        	// Creating a LatLng object containing the coordinate for the center of the map  
          var latlng'.$this->divID.' = new google.maps.LatLng('.$this->center_lat.', '.$this->center_lng.');  
          // Creating an object literal containing the properties we want to pass to the map  
          var options'.$this->divID.' = {  
          	zoom: '.$this->zoom.',
          	center: latlng'.$this->divID.',
          	mapTypeId: google.maps.MapTypeId.ROADMAP
          };  
          // Calling the constructor, thereby initializing the map  
          var '.$this->divID.' = new google.maps.Map(document.getElementById("'.$this->divID.'"), options'.$this->divID.'); ';
		   
          
		  for($i=0;$i<count($this->marker);$i++)
		  {
		  
			 $this->code.=' var marker'.$i.$this->divID.' = new google.maps.Marker({
				position: new google.maps.LatLng('.$this->marker[$i]['lat'].', '.$this->marker[$i]['lng'].'), 
				map: '.$this->divID.',
				title: "'.$this->marker[$i]['title'].'",
				clickable: '.$this->marker[$i]['click'].',
				icon: "'.$this->marker[$i]['icon'].'"

			  });';
		  
		  // Creating an InfoWindow object
			if($this->marker[$i]['info']!='')
			{
				$this->code.=' var infowindow'.$i.$this->divID.' = new google.maps.InfoWindow({content: "'.$this->marker[$i]['info'].'"}); ';
	   			$this->code.=" google.maps.event.addListener(marker".$i.$this->divID.", 'click', function() { infowindow".$i.$this->divID.".open(".$this->divID.", marker".$i.$this->divID."); });"; 
			}
	}
    
	
	$this->code.='	}
      )();
		</script>';
		
	}

	// Add markers to google map.
	
	public function addMarker($lat,$lng,$click='false',$title='My WorkPlace',$info='Hello World',$icon='',$map='map')
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
	
	public function addMarkerByAddress($address,$click='false',$title='My WorkPlace',$info='Hello World',$icon='',$map='map')
	{
	    $status = false;
		$output = $this->getData($address);
		if($output->status == 'OK')
		{
		   $lat = $output->results[0]->geometry->location->lat;
		   $lng = $output->results[0]->geometry->location->lng;
		   $status = true;
		}
	    if($status)
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
}


?>