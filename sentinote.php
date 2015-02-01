<?php
/*
Plugin Name: Sentinote
Version: 1.1.4
Plugin URI: http://sentinote.com
Description: Sentinote lets you easily publish to your Wordpress site using Evernote. (Formerly: Everlicious)
Author: Rheinard Korf
Author URI: http://rheinardkorf.com/
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: sentinote_tdom
 */

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


// ******** Global Variable ********
$sentinote_prefix = 'rksnwp_';
$rksnwp_url = plugins_url( 'sentinote' ) . '/';
$sentinote_core_file = $rksnwp_url . 'sentinote.php';
$rksnwp_path = plugin_dir_path(__FILE__);
$rksnwp_system_tags = array('published',
							'post',
							'page',
							'draft',
							'nolink',
							'addnav',
							'closed',
							'open',
							'pingopen',
							'md',
							'nomd'
							);
							

// Load text domain
load_plugin_textdomain( 'sentinote_tdom', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

// Get Sentinote Core Settings
$rksnwp_options = array();
$rksnwp_options['core'] = get_option('rksnwp_core_settings');
$rksnwp_options['evernote'] = get_option('rksnwp_evernote_settings');
// print_r($rksnwp_options['core']);
// *********** Includes ************
include('inc/shortcodes.php');
include('inc/load-scripts.php');
include('inc/utility-functions.php');
include('inc/activate.php');
include('inc/admin-page.php');
include('inc/connect-service.php');
include('inc/connect-evernote.php');
include('inc/markdown.php');
include('inc/process-notes.php');
include('inc/deactivate.php');

// Register Activation Hook
function rksnwp_core_activate() {
	do_action( 'sentinote_core_activated' );
}
register_activation_hook( __FILE__, 'rksnwp_core_activate' );

// Register Deactivation Hook
function rksnwp_core_deactivate() {
	do_action( 'sentinote_core_deactivated' );
}
register_deactivation_hook( __FILE__, 'rksnwp_core_deactivate' );