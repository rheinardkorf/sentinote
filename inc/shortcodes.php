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
// Shortcodes
function rksnwp_embed_shortcode( $atts, $content = null ) {
	$content = apply_filters('sentinote_embed_shortcodes', $content);
	return do_shortcode('[embed]' . $content . '[/embed]');
}
add_shortcode( 'tweet', 'rksnwp_embed_shortcode' );
add_shortcode( 'youtube', 'rksnwp_embed_shortcode' );
add_shortcode( 'vimeo', 'rksnwp_embed_shortcode' );
add_shortcode( 'soundcloud', 'rksnwp_embed_shortcode' );

function rksnwp_fix_embed_url( $content ) {
	$rep_pattern = "/<a[^>]*>.*?/ims";
	$content = preg_replace($rep_pattern, '', $content);
	$rep_pattern = "/<\/a[^>]*>.*?/ims";
	$content = preg_replace($rep_pattern, '', $content);		
	return $content;
}
add_filter('sentinote_embed_shortcodes', 'rksnwp_fix_embed_url');

function rksnwp_note_content( $atts, $content ){

	try {
		$main_post_id = get_the_ID();
		$post_id = $main_post_id;
		$reg_pattern = "/(id=\")(.*)(\">)/im";
		preg_match_all($reg_pattern,$content,$matches);
		if(!empty($matches[2])){
			$post_id = $matches[2][0];
		}

		if( $post_id != $main_post_id && !empty($matches[2])){
			$thePost = get_post($post_id);
			$content = do_shortcode($thePost->post_content);
		} else {
			if(empty($matches[2])){
				$content = '<p><span class="warning"><em>' . _("<strong>NOTE: </strong>Please only link a note after it has already been published.") . '</em></span></p>';
			}else{
				$content = '<p><span class="warning"><em>' . _("Please don\'t embed the same note in itself.") . '</em></span></p>';
			}
		}
	} catch (Exception $e) {
	}
	
	return $content;
}
add_shortcode('note-content','rksnwp_note_content');

// PROCESSING SHORTCODES
// function rksnwp_featured_image_shortcode( $atts, $content = null ) {
// 	//return '<span class="theclass">' . $content . '</span>';
// 	$attachment = rksnwp_attachment_exists($content);
// 	print_r($attachment);
// 	return '';
// }
// add_shortcode( 'featured', 'rksnwp_featured_image_shortcode' );
