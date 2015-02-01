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
use \Michelf\MarkdownExtra;
date_default_timezone_set(rksnwp_get_timezone_string());

// HEAVY LIFTING HOOK - scheduled and manual sync
function rksnwp_perform_tasks() {
	do_action( 'sentinote_perform_task' );
}

// Prepare Wordpress Post
function rksnwp_prepare_post( $note ) {
	// Only do these things if there is an action to take.
	if( $note['action'] != 'none') {
		
		global $rksnwp_options;

		$post_id = '';
		if(isset($note['wp_post'])){
			$post_id = $note['wp_post']->ID;
		}
	
		$post = apply_filters('sentinote_process_wp_post_data',array(
		  'ID'             => $post_id,
		  'post_content'   => $note['content'], 
		  // 'post_name'      => [ <string> ] // The name (slug) for your post
		  'post_title'     => $note['title'], // The title of your post.
		  'post_status'    => 'publish', 
		  'post_type'      => 'post', 
		  'post_format'    => 'standard',
		  'post_author'    => rksnwp_get_author_id($note['author']), 
		  'ping_status'    => get_option('default_ping_status'),
		  'post_parent'    => 0, 
		  'menu_order'     => 0,
		  'to_ping'        => '',
		  'pinged'         => '',
		  'post_password'  => '',
		  // 'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
		  'post_date'      => date("Y-m-d H:i:s", $note['created']/1000),
		  'comment_status' => get_option('default_comment_status'),
		  'post_category'  => array($rksnwp_options['core']['wp_category']), 
		  'tags_input'     => '',
		  'tax_input'      => array()
		  // 'page_template'  => ''
		), $note['tag_names']);
		
		// In case it slips through... Remove blank paragraphs
		$post['post_content'] = str_replace('<p/>','',$post['post_content']);
		$post['post_content'] = str_replace('<p />','',$post['post_content']);
		$post['post_content'] = str_replace('<p></p>','',$post['post_content']);
		
		//print_r($post);
		switch ( $note['action'] ){
			case 'update':
					wp_update_post( $post );
				break;
			case 'new':
					unset($post['ID']);
					$post['ID'] = wp_insert_post( $post );
				break;
		}

		$post_extra = apply_filters('sentinote_process_wp_post_extra_data', array(
		  'en_guid'		   => $note['guid'],
		  'en_modified'    => $note['updated'],
		  'files'   	   => $note['resource_path'],
		  'filenames'  	   => $note['resource_url']		
		));

		// Hook this to add attachments and post meta.
		do_action( 'sentinote_after_post_edit', $post, $post_extra );
	} 
}
add_action('sentinote_process_en_note', 'rksnwp_prepare_post');
// Future: Hook additional actions to this function (provided the note is formatted the same way)

function rksnwp_strip_html_before_md ( $post, $tags ) {
	global $rksnwp_options;

	if(!empty($rksnwp_options['core']['md_strip_html'])){	
		$post['post_content'] = strip_tags($post['post_content'],'<img><a><ul><ol><li><strong><em><table><thead><tbody><tr><td><th><hr><b><i><u><iframe><embed><object><param><video><audio>');
	}
	
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_strip_html_before_md', 10, 2);

// 0x0001: Get post type from tags
function rksnwp_post_type( $post, $tags ){
	if(in_array('post', $tags)) { $post['post_type'] = 'post'; }
	if(in_array('page', $tags)) { $post['post_type'] = 'page'; }
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_type', 10, 2);

// 0x0002: Get post status from tags
function rksnwp_post_status( $post, $tags ){
	if(in_array('draft', $tags)) { $post['post_status'] = 'draft'; }
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_status', 10, 2);

// 0x0003: Get post category from tags
function rksnwp_post_category( $post, $tags ){
	// Set the Categories
	$postCats = array();
	
	foreach( $tags as $tag ){
		if(substr($tag,0,1) == '[' && substr($tag,-1,1) == ']') {
			$catName = str_replace(']','', str_replace('[','',$tag));
			$theCat = get_cat_ID($catName);
			if($theCat > 0){
				array_push($postCats, $theCat);
				$hasCats = 1;
			} else {
				$cID = wp_create_category($catName);
				if($cID > 0){ array_push($postCats, $cID); $hasCats = 1; }
			}
		}
	}
	$post['post_category'] = $postCats;
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_category', 10, 2);

// 0x0004: Get post parent or post format from tags
function rksnwp_post_parent( $post, $tags ){
	$post_formats = array_keys(get_post_format_strings());
	foreach( $tags as $tag ){
		if(substr($tag,0,1) == '{' && substr($tag,-1,1) == '}') {
			$parentID = str_replace('}','', str_replace('{','',$tag));
			if( is_numeric($parentID) ){
				$post['post_parent'] = $parentID;
			} elseif (in_array($parentID, $post_formats)) {
				$post['post_format'] = $parentID;
			}
		}
	}
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_parent', 10, 2);

// 0x0005: Get post tags
function rksnwp_post_tags( $post, $tags ){
	global $rksnwp_system_tags;
	$post_tags = array();
	foreach( $tags as $tag ){
		// Only add non-sentinote system tags and not [] or {} tags
		if(substr($tag,0,1) != '{' && substr($tag,-1,1) != '}')
		if(substr($tag,0,1) != '[' && substr($tag,-1,1) != ']')
		if(!in_array($tag, $rksnwp_system_tags)) {
			array_push($post_tags, $tag);
		}
	}
	$post['tags_input'] = $post_tags;
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_tags', 10, 2);

// 0x0006: Get post comment status
function rksnwp_post_comment_status( $post, $tags ){
	if(in_array('closed', $tags)) { $post['ping_status'] = 'closed';  $post['comment_status'] = 'closed';}
	if(in_array('open', $tags)) { $post['comment_status'] = 'open';}
	if(in_array('pingopen', $tags)) { $post['ping_status'] = 'open';}
	
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_post_comment_status', 10, 2);

// 0x0007: Parse Mardkown
function rksnwp_parse_markdown( $post, $tags ) {
	global $rksnwp_options;
	
	//// FIX ENCODING ISSUE
	$post['post_content'] = str_replace("\xc2\xa0",' ', $post['post_content']);
	
	$bMarkdown = 0;
	if(!empty($rksnwp_options['core']['markdown'])) { $bMarkdown = 1; }
	if(in_array('md', $tags)) { $bMarkdown = 1; }
	if(in_array('nomd', $tags)) { $bMarkdown = 0; }
	
	if($bMarkdown) {	
		// $post['post_content'] = do_shortcode($post['post_content']);
		//// REPLACE COMMON ENTITIES WITH ACTUAL CHARACTERS	
		$post['post_content'] = html_entity_decode($post['post_content']);
		
		$post['post_content'] = MarkdownExtra::defaultTransform($post['post_content']);
	}
	
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_parse_markdown', 10, 2);

// 0x0008: Smart dashes
function rksnwp_create_smart_dashes (  $post, $tags  ) {
	$content = $post['post_content'];
	$content = str_replace('<!--','<!##',$content);
	$content = str_replace('-->','##>',$content);	
	$content = str_replace('---', '&mdash;', $content);
	$content = str_replace('--', '&ndash;', $content);
	$content = str_replace('<!##','<!--',$content);
	$content = str_replace('##>','-->',$content);	
	$post['post_content'] = $content;
	return $post;
}
add_action('sentinote_process_wp_post_data', 'rksnwp_create_smart_dashes', 10, 2);

// 0x0000: Remove post_thumbnail
function rksnwp_post_remove_thumbnail ( $post, $extra ) {		
	delete_post_thumbnail($post['ID']);
}
add_action('sentinote_after_post_edit', 'rksnwp_post_remove_thumbnail', 10, 2);

// 0xA001: Add post meta data
function rksnwp_post_add_meta ( $post, $extra ) {		
	// Add the Note GUID to the post
	update_post_meta( $post['ID'], 'en_guid', $extra['en_guid'] ); 
	// Add modified date for sync purposes
	update_post_meta( $post['ID'], 'en_modified', $extra['en_modified'] ); 
}
add_action('sentinote_after_post_edit', 'rksnwp_post_add_meta', 10, 2);

// 0xA002: Add post taxonomies - categories/tags
function rksnwp_post_add_taxonomies ( $post, $extra ) {
	wp_set_post_terms( $post['ID'], $post['tags_input'] );
	wp_set_post_terms( $post['ID'], $post['post_category'], 'category' );
}
add_action('sentinote_after_post_edit', 'rksnwp_post_add_taxonomies', 10, 2);

// 0xA003: Add file attachments to post
function rksnwp_post_add_attachments ( $post, $extra ) {
	if(is_array($extra['filenames']))
	foreach ($extra['filenames'] as $file) {
		$theattachment = rksnwp_attachment_exists($file);
		// Attachments are posts, so to attach them make sure you give them a parent.
		wp_update_post( array(
		        'ID' => $theattachment->id,
		        'post_parent' => $post['ID']
		    )
		);
	}	
}
add_action('sentinote_after_post_edit', 'rksnwp_post_add_attachments', 10, 2);

// 0xA004: Post to menu
function rksnwp_post_add_to_menu ( $post, $extra ) {
	// Could do with some tweaking
	if( ($post['post_type'] == 'page') && (!in_array('nolink', $post['tags_input'])) || (in_array('addnav', $post['tags_input'])) ) {
		rksnwp_add_to_menu($post['post_title'], $post['ID']);
	}
	
	if( ($post['post_type'] != 'page') && (!in_array('addnav', $post['tags_input'])) ) {
		rksnwp_remove_from_menu($post['post_title'], $post['ID']);
	}
	
}
add_action('sentinote_after_post_edit', 'rksnwp_post_add_to_menu', 10, 2);

// 0xA005: Set featured image
function rksnwp_post_add_featured_image ( $post, $tags ) {
	$content = $post['post_content'];

	// IF FEATURED IMAGE IDENTIFIED
	$featured_url = '';
	$featured_pattern = "/\[featured\].*?src=\"(?P<url>.*?)\".*?\[\/featured]/ims";
	preg_match_all($featured_pattern, $content, $output_array);
	if(!empty($output_array['url'])){
		$featured_url = $output_array['url'][0];
	}
	$content = preg_replace($featured_pattern,'',$content);
	$content = str_replace('<p></p>','',$content);
	// FEATURED IMAGES GET REMOVED AUTOMATICALLY - THEMES MAY BRING IT BACK
	// $content = str_replace($featured_url,'',$content);

	$post['post_content'] = trim($content);
	
	$attachment = rksnwp_attachment_exists($featured_url);

	if(!empty($attachment)){
		set_post_thumbnail( $post['ID'], $attachment->id );	
	}
	
	// Update the post
	wp_update_post( $post );
}
add_action('sentinote_after_post_edit', 'rksnwp_post_add_featured_image', 10, 2);

function rksnwp_post_set_format( $post, $tags ) {
	set_post_format($post['ID'], $post['post_format']);
}
add_action('sentinote_after_post_edit', 'rksnwp_post_set_format', 10, 2);