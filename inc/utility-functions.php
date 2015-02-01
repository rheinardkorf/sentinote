<?php
/**
 * @copyright 2013-2014 Rheinard Korf
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

function rksnwp_get_author_id( $display_name ) {
	global $rksnwp_options;
	$id = -1;
	$args = array(                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             
		'exclude' => array()
	);
	$user_query = new WP_User_Query( $args );
	if ( ! empty( $user_query->results ) ) {
		foreach ( $user_query->results as $user ) {
			if( $display_name == $user->display_name ){
				$id = $user->ID;
			}
		}
	}
	if($id<0) { $id = $rksnwp_options['core']['wp_author']; }
	return $id;
}

function rksnwp_attachment_exists ($src) {
	global $wpdb;
	$theattachment = $wpdb->get_row( $wpdb->prepare( "SELECT id, guid
		FROM $wpdb->posts WHERE guid = '%s'", esc_url($src) ));
	return $theattachment;
}

function rksnwp_add_attachment( $postID, $thefile, $thefileurl ) {
	// Generate attachments and thumbnails
	$filename = $thefile;
	$attach_id = null;
	$existing_attachment = rksnwp_attachment_exists ($filename);
	$attach_id = $existing_attachment['id'];
	return $attach_id;
}


// Can be improved by adding some filters
function rksnwp_add_to_menu( $name, $postID) {
	$url = get_permalink( $postID );
	$theMenu = wp_get_nav_menu_object( "Sentinote Menu" );
	$menu_id = $theMenu->term_id;
	
	// Does the menu item exist?
	$menu_exists = false;
	foreach(wp_get_nav_menu_items( $menu_id ) as $menu_item ){
		if(__($name) == __($menu_item->post_title)){
			$menu_exists = true;
		}
	}
	
	if(!$menu_exists){
		$thisMenuItem = wp_update_nav_menu_item($menu_id, 0, array(
	        'menu-item-title' =>  __($name),
	        'menu-item-classes' => 'menu_item',
	        'menu-item-url' => $url, 
	        'menu-item-status' => 'publish'));

		// Wordpress limitation forces hacky relationship insert
	    global $wpdb;
		if(!get_post_type($thisMenuItem)){
	    	$wpdb->insert($wpdb->term_relationships, array("object_id" => $thisMenuItem, "term_taxonomy_id" => $menu_id), array("%d", "%d"));
		}
	}
}

function rksnwp_remove_from_menu ($name, $postID) {
	$url = get_permalink( $postID );
	$theMenu = wp_get_nav_menu_object( "Sentinote Menu" );
	$menu_id = $theMenu->term_id;
	
	foreach(wp_get_nav_menu_items( $menu_id ) as $menu_item ){
		if(__($name) == __($menu_item->post_title)){
			wp_delete_post( $menu_item->ID );
		}
	}
}

function rksnwp_get_timezone_string() {
    // if site timezone string exists, return it
    if ( $timezone = get_option( 'timezone_string' ) )
        return $timezone;
 
    // get UTC offset, if it isn't set then return UTC
    if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
        return 'UTC';
 
    // adjust UTC offset from hours to seconds
    $utc_offset *= 3600;
 
    // attempt to guess the timezone string from the UTC offset
    $timezone = timezone_name_from_abbr( '', $utc_offset );
 
    // last try, guess timezone string manually
    if ( false === $timezone ) {
 
        $is_dst = date( 'I' );
 
        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {
                if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
                    return $city['timezone_id'];
            }
        }
    }
    // fallback to UTC
    return 'UTC';
}


function rksnwp_calc_resource_hash($_hash){
	$chunks = explode("\n", chunk_split($_hash,2,"\n"));
	$calc_hash = "";
	foreach ($chunks as $chunk) {
		if (!empty($chunk)) {
			$bin_chunk = rksnwp_hex2bin($chunk);
			$calc_hash .= $bin_chunk;
		}
    }
	return $calc_hash;
}


function rksnwp_hex2bin($data) {
	$newdata = '';
	$len = strlen($data);
	for($i=0;$i<$len;$i+=2) {
		$newdata .= pack("C",hexdec(substr($data,$i,2)));
	}
	return $newdata;
}

function rksnwp_uchr($u) {
    return mb_convert_encoding(pack("N",$u), mb_internal_encoding(), 'UCS-4BE');
}

function rksnwp_convert_smart_quotes($string) 
{ 
    $search = array(chr(145), 
                    chr(146), 
                    chr(147), 
                    chr(148),
					rksnwp_uchr('201C'),
					rksnwp_uchr('201D'),
					rksnwp_uchr('201E'),
					rksnwp_uchr('201F'),
					rksnwp_uchr('2033'),
					rksnwp_uchr('2036'),
					rksnwp_uchr('0336'),
					rksnwp_uchr('2E3A'),
					rksnwp_uchr('2E3B'),
					rksnwp_uchr('2012'),					
					rksnwp_uchr('2013'),
					rksnwp_uchr('2014'),					
					rksnwp_uchr('2015'),					
					rksnwp_uchr('8210'),
					rksnwp_uchr('8211'),
					rksnwp_uchr('8212'),
					rksnwp_uchr('8213'),
					chr(150),
					chr(151)				
					); 
    $replace = array("'", 
                     "'", 
                     '"', 
                     '"',
					 '"',
					 '"',
					 '"',
					 '"',
					 '"',
					 '"',
					 '-',					
					 '-',
					 '-',
					 '-',
					 '-',
					 '-',
					 '-',
					 '-',					
					 '-',
					 '-',					
					 '-'										
					); 

    return str_replace($search, $replace, $string); 
}