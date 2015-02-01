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

// Add the sentinote menu
function rksnwp_add_sentinote_menu() {
	$menu = apply_filters( 'sentinote_menu_name_filter', __('Sentinote Menu') );
	$menu_exists = wp_get_nav_menu_object( $menu );
	if( !$menu_exists ){
		$menu_id = wp_create_nav_menu( $menu );
	}
}
add_action('sentinote_core_activated', 'rksnwp_add_sentinote_menu');

// Add other cron options
function rksnwp_cron_add_five( $schedules ){
	$schedules['five'] = array(
		'interval'	=> 300,
		'display'	=> __('Every Five Minutes', 'sentinote_tdom')
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'rksnwp_cron_add_five' );

function rksnwp_cron_add_fifteen( $schedules ){
	$schedules['fifteen'] = array(
		'interval'	=> 900,
		'display'	=> __('Every Fifteen Minutes', 'sentinote_tdom')
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'rksnwp_cron_add_fifteen' );

// Add default cron for every 5 minutes
function rksnwp_add_default_cron() {
	if ( !wp_next_scheduled( 'rksnwp_every_x_minutes' ) ) {
		wp_schedule_event( time(), 'five', 'rksnwp_every_x_minutes');
	}
}
add_action('sentinote_core_activated', 'rksnwp_add_default_cron');

// Create action hook to perform tasks
add_action( 'rksnwp_every_x_minutes',  'rksnwp_perform_tasks' );
