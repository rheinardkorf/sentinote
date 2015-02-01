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

function rksnwp_sentinote_core_admin_page(){
	global $rksnwp_options;
	global $rksnwp_url;
	
	?>
	<div class="wrap">
	
		<div id="icon-themes" class="icon32"></div> 
		<h2><img src="<?php echo $rksnwp_url . 'assets/icon32.png'; ?>" width="20" height="20" /> <?php _e('Sentinote Settings','sentinote_tdom'); ?></h2>
		<?php settings_errors(); ?>
		
		<?php
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'sentinote_options';
		?>
		
		<h2 class="nav-tab-wrapper">
			<a href="?page=sentinote-core-settings&tab=sentinote_options" class="nav-tab <?php echo $active_tab == 'sentinote_options' ? 'nav-tab-active' : ''; ?>"><?php _e('Sentinote Core','sentinote_tdom'); ?></a>
			<a href="?page=sentinote-core-settings&tab=evernote_options" class="nav-tab <?php echo $active_tab == 'evernote_options' ? 'nav-tab-active' : ''; ?>"><?php _e('Evernote Extension','sentinote_tdom'); ?></a>
		</h2>
	
		<form method="post" action="options.php"> 
			<?php do_action( 'sentinote_settings', $active_tab ); ?>   
			<hr />
			<?php submit_button(); ?>		
		</form>
	
	</div>
	<?php
}

// Add tab for Sentinote Options
function rksnwp_add_core_settings($active_tab) {
	global $rksnwp_options;
	
	
	$rksnwp_options['core'] = get_option('rksnwp_core_settings');
	if( $active_tab == "sentinote_options" ){
		settings_fields('rksnwp_core_settings_group');
//		do_settings_sections( 'rksnwp_core_settings_group' );
		?>
		<h3><?php _e('Sentinote Frequency','sentinote_tdom');?></h3>
		<p>
		     <label class="description" for="rksnwp_core_settings[schedule]"><?php _e('Select how often you would like to update.', 'sentinote_tdom'); ?></label>      
		     <select name="rksnwp_core_settings[schedule]" id="rksnwp_core_settings[schedule]">		
		     <?php
				$current_schedule = wp_get_schedule( 'rksnwp_every_x_minutes' );
				$schedule_array = wp_get_schedules(); 
				$option_array = array_keys($schedule_array);
			 ?>
		          <?php foreach($option_array as $option_item) {  ?>
					 <?php if($rksnwp_options['core']['schedule'] == $option_item || $option_item == $current_schedule ) { $selected = 'selected="selected"'; } else { $selected = ''; } ?>
		             <option value="<?php echo $option_item; ?>" <?php echo $selected; ?>><?php echo $schedule_array[$option_item]['display'] ?></option>

		          <?php } ?>
		     </select>
		</p>

		<hr />
		
		<h3><?php _e('Wordpress Defaults','sentinote_tdom');?></h3>		
		
		<!-- Default Author -->
		<p>
		     <label class="description" for="rksnwp_core_settings[wp_author]"><?php _e('Default Author', 'sentinote_tdom'); ?></label>      
		     <select name="rksnwp_core_settings[wp_author]" id="rksnwp_core_settings[wp_author]">		
		     	<?php
					$args = array(
						'exclude' => array()
					);
					$user_query = new WP_User_Query( $args );
					$option_array = $user_query->results;
			 	?>
		        <?php foreach($option_array as $option_item) {  ?>
					<?php if($rksnwp_options['core']['wp_author'] == $option_item->ID) { $selected = 'selected="selected"'; } else { $selected = ''; } ?>	
					<option value="<?php echo $option_item->ID; ?>" <?php echo $selected; ?>><?php echo $option_item->display_name . ' (' . $option_item->user_nicename . ')'; ?></option>				
		        <?php } ?>
		     </select>
		</p>		
		
		<!-- Default Category -->
		<p>
		     <label class="description" for="rksnwp_core_settings[wp_category]"><?php _e('Default Category', 'sentinote_tdom'); ?></label>      
		     <select name="rksnwp_core_settings[wp_category]" id="rksnwp_core_settings[wp_category]">		
		     	<?php
					$args = array(
						'type' => 'post',
						'taxonomy' => 'category',
						'hide_empty' => '0'
					);
					$option_array = get_categories( $args ); 
			 	?>
		        <?php foreach($option_array as $option_item) {  ?>
					<?php if($rksnwp_options['core']['wp_category'] == $option_item->cat_ID) { $selected = 'selected="selected"'; } else { $selected = ''; } ?>	
					<option value="<?php echo $option_item->cat_ID; ?>" <?php echo $selected; ?>><?php echo $option_item->cat_name; ?></option>				
		        <?php } ?>
		     </select>
		</p>		

		<hr />

		<h3><?php _e('Markdown Settings','sentinote_tdom');?></h3>	
		<p>Markdown is a way for you to write in plain text and then have it converted to HTML.</p>
		<p>See <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">Markdown Basics</a> to get started.  Sentinote also supports <a href="http://michelf.ca/projects/php-markdown/extra/" target="_blank">Markdown Extra</a> if you want some more advanced features.</p>
		<p>
			<?php if(!isset($rksnwp_options['core']['markdown'])) { $rksnwp_options['core']['markdown'] = 0; } ?>
			<input type="checkbox" id="rksnwp_core_settings[markdown]" name="rksnwp_core_settings[markdown]" value="1" <?php checked('1', $rksnwp_options['core']['markdown']); ?> />
			<label class="description" for="rksnwp_core_settings[markdown]"><?php _e('Enable Markdown. <small>(Will over-ride Evernote DIVs.)</small>','sentinote_tdom'); ?></label>
		</p>		

		<p>
			<?php if(!isset($rksnwp_options['core']['md_strip_html'])) { $rksnwp_options['core']['md_strip_html'] = 0; } ?>
			<input type="checkbox" id="rksnwp_core_settings[md_strip_html]" name="rksnwp_core_settings[md_strip_html]" value="1" <?php checked('1', $rksnwp_options['core']['md_strip_html']); ?> />
			<label class="description" for="rksnwp_core_settings[md_strip_html]"><?php _e('Strip all non-essential HTML before applying Markdown. <small>(All font changes will be stripped: color, family, size)</small>','sentinote_tdom'); ?></label>
		</p>



		<hr />
		
		<?php submit_button( 'Manual Sync Now', 'secondary', 'manual_sync_sentinote', true ); ?>
		<?php
	}
}
add_action( 'sentinote_settings', 'rksnwp_add_core_settings' );

// Add tab for Evernote Options
function rksnwp_add_evernote_settings($active_tab) {
	global $rksnwp_options;
	$rksnwp_options['evernote'] = get_option('rksnwp_evernote_settings');
	if( $active_tab == "evernote_options" ){
		settings_fields('rksnwp_evernote_settings_group');
		// do_settings_sections( 'rksnwp_evernote_settings_group' );
		?>	
		<h3><?php _e('Evernote Account Settings','sentinote_tdom');?></h3>
		<p class="description">To start using Sentinote you will need to get a developer token from: <a href="https://www.evernote.com/api/DeveloperToken.action">Evernote Developer Token</a>.</p>
		<p>
			<label class="description" for="rksnwp_evernote_settings[auth_token]"><?php _e('Evernote Developer Token','sentinote_tdom'); ?></label>
			<input type="text" id="rksnwp_evernote_settings[auth_token]" name="rksnwp_evernote_settings[auth_token]]" value="<?php echo $rksnwp_options['evernote']['auth_token']; ?>" style="min-width:400px;" />
		</p>		
		<p class="description">Type the name of your notebook exactly as it appears in Evernote. Try to avoid symbols.</p>
		<p>
			<label class="description" for="rksnwp_evernote_settings[primary_notebook]"><?php _e('Evernote Notebook','sentinote_tdom'); ?></label>
			<input type="text" id="rksnwp_evernote_settings[primary_notebook]" name="rksnwp_evernote_settings[primary_notebook]" value="<?php echo $rksnwp_options['evernote']['primary_notebook']; ?>" />
		</p>				

		<h3><?php _e('Note Settings (<small>Evernote Specific Tweaks</small>)','sentinote_tdom');?></h3>		
		
		<p>
			<?php if(!isset($rksnwp_options['evernote']['div_to_p'])) { $rksnwp_options['evernote']['div_to_p'] = 1; } ?>
			<input type="checkbox" id="rksnwp_evernote_settings[div_to_p]" name="rksnwp_evernote_settings[div_to_p]" value="1" <?php checked('1', $rksnwp_options['evernote']['div_to_p']); ?> />
			<label class="description" for="rksnwp_evernote_settings[div_to_p]"><?php _e('Change Evernote DIVs to P tags. <small>(Fixes some spacing issues.)</small>','sentinote_tdom'); ?></label>
		</p>		
		
		
		<?php
	}
}
add_action( 'sentinote_settings', 'rksnwp_add_evernote_settings' );

// Add Sentinote Admin Menu Link
function rksnwp_add_option_link(){
	global $rksnwp_url;
	add_menu_page( 'Sentinote', 'Sentinote', 'manage_options', 'sentinote-core-settings', 'rksnwp_sentinote_core_admin_page', $rksnwp_url . 'assets/icon.png' );	
}
add_action('admin_menu', 'rksnwp_add_option_link');

// Sentinote Settings Action Hook
function rksnwp_core_admin_init() {
	do_action( 'sentinote_register_settings' );
}
add_action('admin_init', 'rksnwp_core_admin_init');

// Register Sentinote Core Settings
function rksnwp_register_core_settings() {
	register_setting('rksnwp_core_settings_group', 'rksnwp_core_settings', 'rksnwp_core_settings_process');
}
add_action('sentinote_register_settings', 'rksnwp_register_core_settings');

// Process Sentinote Core Settings
function rksnwp_core_settings_process( $core_settings ) {
	global $rksnwp_options;
	
	// Manual Sync
	if( isset( $_POST['manual_sync_sentinote'] ) ){
		rksnwp_perform_tasks();
		$message_id = 'notify_sentinote_manual_sync';
		$message = __('Sentinote Successfully Synced.', 'sentinote_tdom');
		$type = 'updated';
		add_settings_error($message_id, esc_attr( 'settings_updated' ), $message, $type);		
	}
	
	// Change schedule
	if ( isset( $_POST['rksnwp_core_settings']['schedule'] ) ){
		if($rksnwp_options['core']['schedule'] != $_POST['rksnwp_core_settings']['schedule']){
			wp_clear_scheduled_hook('rksnwp_every_x_minutes');	
			wp_schedule_event( time(), $_POST['rksnwp_core_settings']['schedule'], 'rksnwp_every_x_minutes');
		}
	}

	return $core_settings;
}

// Register Evernote Settings
function rksnwp_register_evernote_settings() {
	register_setting('rksnwp_evernote_settings_group', 'rksnwp_evernote_settings', 'rksnwp_evernote_settings_process');
}
add_action('sentinote_register_settings', 'rksnwp_register_evernote_settings');

// Process Evernote Settings
function rksnwp_evernote_settings_process( $evernote_settings ) {
	return $evernote_settings;
}




