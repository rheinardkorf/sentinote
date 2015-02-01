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

// EVERNOTE NAMESPACE
use EDAM\Types\Data, EDAM\Types\Note, EDAM\NoteStore\NoteFilter, EDAM\Types\Resource, EDAM\Types\ResourceAttributes, EDAM\Types\Tag;
use EDAM\Error\EDAMUserException, EDAM\Error\EDAMErrorCode;
use Evernote\Client;
date_default_timezone_set(rksnwp_get_timezone_string());

// EVERNOTE EXCEPTION HANDLER
// A global exception handler for our program so that error messages all go to the console
function en_exception_handler($exception)
{
    // echo "Uncaught " . get_class($exception) . ":\n";
    if ($exception instanceof EDAMUserException) {
		echo 'There seems to be a problem with the Evernote user details.';
		echo '<p><a href="' . site_url() . '/wp-admin/admin.php?page=sentinote-core-settings">Go back to Sentinote</a></p>';
    } elseif ($exception instanceof EDAMSystemException) {
		echo "Problem connecting to Evernote services. <a href=\"http://status.evernote.com/\">Check Evernote's status.</a>";
		echo '<p><a href="' . site_url() . '/wp-admin/admin.php?page=sentinote-core-settings">Go back to Sentinote</a></p>';
    } else {
		echo "Problem connecting to Evernote services. <a href=\"http://status.evernote.com/\">Check Evernote's status.</a>";
		echo '<p><a href="' . site_url() . '/wp-admin/admin.php?page=sentinote-core-settings">Go back to Sentinote</a></p>';
    }
}
set_exception_handler('en_exception_handler');


// Locals
$featured_url = '';
$resourceHash = array();

function rksnwp_sync_evernote() {
	global $rksnwp_path;
	global $rksnwp_options;

	$sdk_path = $rksnwp_path . "lib/";
	
	require_once $sdk_path . 'autoload.php';
	
	require_once $sdk_path . 'Evernote/Client.php';
	
	require_once $sdk_path . 'packages/Errors/Errors_types.php';
	require_once $sdk_path . 'packages/Types/Types_types.php';
	require_once $sdk_path . 'packages/Limits/Limits_constants.php';

	$authToken = $rksnwp_options['evernote']['auth_token'];
	
	if ($authToken == "your developer token") {
	    print "Please fill in your developer token\n";
	    print "To get a developer token, visit https://www.evernote.com/api/DeveloperToken.action\n";
	    exit(1);
	}
	
	$client = new Client(array('token' => $authToken, 'sandbox' => false));
	$userStore = $client->getUserStore();
	$noteStore = $client->getNoteStore();

	$search_words = apply_filters( 'sentinote_evernote_search_string', '');
	
	// Search and retrieve notes
	$search = new NoteFilter();
	$search->words =$search_words;

	$offset = 0;
	$pageSize = 10;
	$notes = null;
	$guids = array(); // Evernote GUIDs
	
	// Find and process each note
	do {
		
		$result = $noteStore->findNotes($search,$offset,$pageSize);
		$notes = $result->notes;
		
		if(is_array($notes))
		if(count($notes) > 0)
		foreach ($notes as $note) {
			// Keep track of read notes
			array_push($guids, $note->guid);
			// Note Args
			$theNote = apply_filters('sentinote_process_en_note_data', array(
				'guid'				=> $note->guid,
				'title' 			=> $note->title,
				'updated' 			=> $note->updated,
				'created'			=> $note->created,
				'tag_guids'			=> $note->tagGuids,
				'resources'			=> $note->resources,
				'resource_path'		=> array(),
				'resource_url'		=> array(),
				'resource_hash'		=> array(),
				'latitude'			=> $note->attributes->latitude,
				'longitude'			=> $note->attributes->longitude,
				'altitude'			=> $note->attributes->altitude,
				'author'			=> $note->attributes->author,
				'tag_names'			=> array(),
				'action'			=> 'none',
				'content'			=> null,
				'note_store'		=> $noteStore,
				'wp_post'			=> null,
				'wp_attachments'	=> array()							
			));

			do_action( 'sentinote_process_en_note', $theNote );
			
			wp_reset_query(); // just to be safe
		}

		$offset = $offset + $pageSize;
	    
	} while ($result->totalNotes > $offset);

	do_action( 'sentinote_evernote_remove_posts', $guids);
}
add_action('sentinote_sync_service', 'rksnwp_sync_evernote');

// 0x0001: Pre-populate note for processing
function rksnwp_evernote_get_full_note( $theNote ) {
	// Is there already a Wordpress post/page?
	$my_query = new WP_Query( array( 'meta_key' => 'en_guid', 'meta_value' => $theNote['guid'], 'post_status' => 'any', 'post_type' => 'any') );
	
	// Is it an existing post or new page
	if ( $my_query->have_posts() ) {
		$theNote['wp_post'] = $my_query->posts[0];
		$postmodified = get_post_meta($theNote['wp_post']->ID,'en_modified',1);
		$notemodified = $theNote['updated'];
		
		// Update post
		if($postmodified != $notemodified)
		{
			$theNote['action'] = 'update';
		}
		
	} else {
		$theNote['action'] = 'new';
	}
	
	// If action is required, get the full note
	if( $theNote['action'] != 'none' ) {
		// Flags
		$getNoteContent = 1;
		$getResourceBody = 1;
		$getResourceOCRData = 0;
		$getResourceAlternateData = 0;
		
		$note = $theNote['note_store']->getNote($theNote['guid'],
		                                $getNoteContent,
		                                $getResourceBody, 
									    $getResourceOCRData, 
									    $getResourceAlternateData);

		$theNote['tag_guids']	= $note->tagGuids;
		$theNote['resources']	= $note->resources;
		$theNote['content']		= $note->content;
		$theNote['tag_names']	= $theNote['note_store']->getNoteTagNames($theNote['guid']);
	}
	
	// Unset 'Note Store' to save overhead
	unset($theNote['note_store']);
    return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_evernote_get_full_note' );

// 0x0001a: Replace note links with WordPress links.
function rksnwp_evernote_replace_note_links( $theNote ){
	$reg_pattern = "/(<a href=\"evernote:\/\/\/view.*\/\")(.*\">)(.*)(<\/a>)/im";
	preg_match_all($reg_pattern,$theNote['content'],$matches);
	
	$counter = -1;

	foreach($matches[1] as $match){
		$counter++;
		$replacement = '';
		$guid = str_replace('/"','',$match);
		$guid = explode('/', $guid);
		$guid = array_pop($guid);
		
		$my_query = new WP_Query( array( 'meta_key' => 'en_guid', 'meta_value' => $guid, 'post_status' => 'any', 'post_type' => 'any') );
		
		if ( $my_query->have_posts() ) {
			$thePost = $my_query->posts[0];
			$replacement = '<a href="' . get_permalink($thePost->ID) . '" id="' . $thePost->ID . '">' . $thePost->post_title . '</a>';
		} else {
			$replacement = '<span class="not-published"><em>Note, ' . $matches[3][$counter] . ' , not yet published.</em></span>';
		}				
		$theNote['content'] = str_replace($matches[0][$counter], $replacement, $theNote['content']);	
	}
	
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_evernote_replace_note_links' );

// Modify the search string
function rksnwp_evernote_search_string( $search_term ) {
	global $rksnwp_options;
	
	$search_term = 'notebook:"' . $rksnwp_options['evernote']['primary_notebook'] . '" tag:published';
	// $search_term = 'notebook:"' . $rksnwp_options['evernote']['primary_notebook'] . '"';
    return $search_term;
}
add_filter( 'sentinote_evernote_search_string', 'rksnwp_evernote_search_string' );


// 0x0002: Replace note content resources with links
function rksnwp_en_process_resources ( $theNote ) {
	// Upload location
	$now = time();
	$month = date("m", $now);
	$year = date("Y", $now);
	$upload_dir = apply_filters('sentinote_post_upload_path', wp_upload_dir());
	$baseurl = $upload_dir['baseurl'];
	$basedir = $upload_dir['basedir'];

	// Upload resources
	if( is_array($theNote['resources']) )
	foreach( $theNote['resources'] as $resource ) {
		
		$file_suffix = apply_filters('sentinote_post_upload_filename', "/" . $year . "/" . $month . "/" . $resource->attributes->fileName);
		$filename = $basedir . $file_suffix;
		$theurl = $baseurl . $file_suffix;
		
		// Save to the uploads directory
		// print "The filename is: " . $filename . "\n";
		// print "Does it exist? " . file_exists($filename) . "\n\n";
				
		if(!file_exists($filename)) {
			// Upload the file
			file_put_contents($filename, $resource->data->body,LOCK_EX);
			// Attach the file to the Wordpress database
		  	$wp_filetype = wp_check_filetype(basename($filename), null );

		  	$attachment = array(
		     	'guid' => esc_url($theurl), 
		     	'post_mime_type' => $wp_filetype['type'],
		     	'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		     	'post_content' => '',
		     	'post_status' => 'inherit'
		  	);
		
			$attach_id = wp_insert_attachment( $attachment, $filename );
		  	require_once( ABSPATH . 'wp-admin/includes/image.php' );
		  	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		  	wp_update_attachment_metadata( $attach_id, $attach_data );			
		}
		
		array_push($theNote['resource_hash'], $resource->data->bodyHash);
		array_push($theNote['resource_url'], $theurl);
		array_push($theNote['resource_path'], $filename);
	}

	// Replace Hashes with Urls
	$media_pattern = "/<en-media[^>]*>/ims";
	preg_match_all($media_pattern, $theNote['content'], $output_array);
	$media_array = $output_array[0];

	if(is_array($media_array))    
	foreach($media_array as $media_inner)
	{	
		preg_match('/hash="([^"]*)"/',$media_inner,$matches);
		$hash = rksnwp_calc_resource_hash($matches[1]);
		unset($matches);
		$resource_pos = array_search($hash, $theNote['resource_hash']);
		$theHTML = apply_filters('sentinote_media_url_filter', $theNote['resource_url'][$resource_pos]);
		$theNote['content'] = str_replace($media_inner,$theHTML,$theNote['content']);
		$theNote['content'] = str_replace('</en-media>', '', $theNote['content']);
	}	
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_process_resources' );


function rksnwp_return_media_url( $_url ){
	$parts = explode('.',$_url);
	$type = $parts[count($parts)-1];
	
	$output = "";
	switch (strtolower($type)) {
		case 'png':
		case 'jpg':
		case 'gif':
		case 'jpeg':
			$output = '<img src="' . $_url . '" />';
			break;
		default:
			$output = '<a href="' . $_url . '">' . $_url . '</a>';
	}
	
	return $output;	
}
add_filter( 'sentinote_media_url_filter', 'rksnwp_return_media_url' );

// 0x0003: Remove Evernote wrapper
function rksnwp_en_remove_wrapper( $theNote ) {
	$rep_pattern = "/<\?xml[^>]*>|<\!DOCTYPE[^>]*>|<en-note[^>]*>|<\/en-note>/ims";
	$theNote['content'] = trim(preg_replace($rep_pattern, '', $theNote['content']));
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_remove_wrapper' );

// 0x0004: Remove and replace special chars and smartquotes
function rksnwp_en_fix_chars ( $theNote ){

	$content = $theNote['content'];
	$content = rksnwp_convert_smart_quotes($content); 
	$content = str_replace('&quot;','"',$content);
	$theNote['content'] = $content;
	
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_fix_chars' );

// 0x0005: Remove and replace special chars and smartquotes
function rksnwp_en_remove_insertion_point ( $theNote ) {
	$theNote['content'] = str_replace('<span style="-evernote-last-insertion-point:true;"/>','', $theNote['content']);
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_remove_insertion_point' );

// 0x0006: Change <div> to <p>
function rksnwp_en_div_to_p ( $theNote ) {
	global $rksnwp_options;
	
	$bMarkdown = 0;
	if(!empty($rksnwp_options['core']['markdown'])) { $bMarkdown = 1; }
	if(in_array('md', $theNote['tag_names'])) { $bMarkdown = 1; }
	if(in_array('nomd', $theNote['tag_names'])) { $bMarkdown = 0; }
	
	
	if($rksnwp_options['evernote']['div_to_p'] && !$bMarkdown) {

		$content = $theNote['content'];
		$content = preg_replace("/\n|\r/", '', $content);
		$content = str_replace("\n\n",'<div><br/></div>',$content);
		$content = str_replace('<div', '<p', $content);
		$content = str_replace('</div>', "</p>\n", $content);
		$content = preg_replace("/<p><br[^>]*><\/p>/","", $content);
				
		$theNote['content'] = $content;
	}

	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_div_to_p' );

// 0x0007: Get the note Markdown ready
function rksnwp_en_prepare_markdown ( $theNote ) {
	global $rksnwp_options;
	
	$bMarkdown = 0;
	if(!empty($rksnwp_options['core']['markdown'])) { $bMarkdown = 1; }
	if(in_array('md', $theNote['tag_names'])) { $bMarkdown = 1; }
	if(in_array('nomd', $theNote['tag_names'])) { $bMarkdown = 0; }
		
	if($bMarkdown) {

		$content = $theNote['content'];

		$content = preg_replace("/<div[^>]*>/", '', $content);
		$content = preg_replace("/<\/div>/", "\n", $content);
		$content = preg_replace("/<br[^>]*>/","", $content);
		$content = preg_replace("/.style=\".*\"/","", $content);
				
		$theNote['content'] = html_entity_decode($content);		
	}
	return $theNote;
}
add_filter( 'sentinote_process_en_note_data', 'rksnwp_en_prepare_markdown' );

function rksnwp_en_remove_posts( $_guids ) {
	// Load all posts created via Evernote notes.
	$my_query = new WP_Query( array( 'meta_key' => 'en_guid', 'post_type' => 'any') );

	if ( $my_query->have_posts() ) {
		foreach($my_query->posts as $post)
		{
			$theID = get_post_meta( $post->ID, 'en_guid', 1 );
								
			if (!in_array($theID,$_guids))
			{
				if($post->post_type == 'page'){
					$theMenu = wp_get_nav_menu_object( "Sentinote Menu" );
					$items = wp_get_nav_menu_items( $theMenu->term_id );
					if(is_array($items))
					foreach($items as $menu_item){
						if ($menu_item->post_title == $post->post_title)
						{
							// Delete the menu item...
							wp_delete_post($menu_item->object_id, 1);
						}
					}
				}
				// Delete all attachments too
				global $wpdb;
				$post_attachments = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_parent = '$post->ID' AND post_status = 'inherit' AND post_type='attachment'");
				if(is_array($post_attachments))
				foreach($post_attachments as $post_attachment) {
					wp_delete_attachment( $post_attachment->ID, 1);
				}

				wp_delete_post($post->ID, 1);
			}
		}
	}	
}
add_action('sentinote_evernote_remove_posts', 'rksnwp_en_remove_posts');