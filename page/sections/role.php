<?php
if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}
if( !cmc_role::is_user_allowed()){
	echo "You do not have permission to access this page"; return;
}
if(	empty($_REQUEST['id']) ){
	echo "<p>No Role Selected</p>"; return;
}


$role_id = sanitize_text_field( wp_unslash( $_REQUEST['id'] ) );
$role = get_role( $role_id );
if( empty($role) ){
	echo "<p>Invalid Role</p>"; return;
}

//$roles = get_editable_roles(); $cap = '';
//foreach( $roles as $k => $r){
//	if( !in_array( $k, cmc_role::$roles_native ) ) continue;
//	$c = array_map( function( $i ){ return "'$i'";}, array_keys($r['capabilities']) ); $c = implode(", ", $c);
//	$cap .= "'$k'=> array( $c ),\n";
//}
//$cap =  "array(\n $cap \n);"; echo $cap;
 
$traditional_capability = array(
	'administrator'=> array( 'switch_themes', 'edit_themes', 'activate_plugins', 'edit_plugins', 'edit_users', 'edit_files', 'manage_options', 'moderate_comments', 'manage_categories', 'manage_links', 'upload_files', 'import', 'unfiltered_html', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts', 'edit_pages', 'read', 'level_10', 'level_9', 'level_8', 'level_7', 'level_6', 'level_5', 'level_4', 'level_3', 'level_2', 'level_1', 'level_0', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'edit_private_posts', 'read_private_posts', 'delete_private_pages', 'edit_private_pages', 'read_private_pages', 'delete_users', 'create_users', 'unfiltered_upload', 'edit_dashboard', 'update_plugins', 'delete_plugins', 'install_plugins', 'update_themes', 'install_themes', 'update_core', 'list_users', 'remove_users', 'promote_users', 'edit_theme_options', 'delete_themes', 'export' ),
	'editor'=> array( 'moderate_comments', 'manage_categories', 'manage_links', 'upload_files', 'unfiltered_html', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts', 'edit_pages', 'read', 'level_7', 'level_6', 'level_5', 'level_4', 'level_3', 'level_2', 'level_1', 'level_0', 'edit_others_pages', 'edit_published_pages', 'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts', 'edit_private_posts', 'read_private_posts', 'delete_private_pages', 'edit_private_pages', 'read_private_pages' ),
	'author'=> array( 'upload_files', 'edit_posts', 'edit_published_posts', 'publish_posts', 'read', 'level_2', 'level_1', 'level_0', 'delete_posts', 'delete_published_posts' ),
	'contributor'=> array( 'edit_posts', 'read', 'level_1', 'level_0', 'delete_posts' ),
	'subscriber'=> array( 'read', 'level_0' ),
);

$trad_cap = $traditional_capability[$role_id];
//print_r($trad_cap);
?>
<style>
	.cmcrm-list{
		height: 300px; overflow-y: auto; border: 1px solid #ccc; border-radius: 5px; padding: 5px;
	}
	
	.cmcrm-list p{
		margin:0;
		padding:10px;
	}
	
	.cmcrm-list p:nth-child(odd){
		background: #CCC;
	}
	
	.cmcrm-list p:hover{
		background: #968c8c;
	}
</style>
<div id="cmcrm-role-container" >
	<div id="cmcrm-role-form" method="post" action="?page=cmcrm" style="margin-top:10px;">						
		<h3>Role: <b><?php echo $role->name; ?></b> <span style="margin-left:15px;"> Display Name: <b><?php echo self::get_role_name($role->name); ?></b></span></h3>
		<div id="cmcrm-role-editor" class="">			
			<p><label>Role Name: </label> <?php echo $role->name; ?></p>
			<p><label>Display Name: </label> <?php echo self::get_role_name($role->name); ?></p>
			<div>
				<button type="button" id="cmcrm-rolename-editor-add-btn" class="page-title-action cmcrm-help-tip" data-tip="Edit Role Display Name" onclick="jQuery('form#cmcrm-rolename-editor-add').slideToggle('fast').find(':file').focus();" >                
					<?php echo __('Display Name Editor', 'cmcrm'); ?>
				</button> 
				<div style="width:400px;">
					<form id="cmcrm-rolename-editor-add" onsubmit="" method="post" enctype="multipart/form-data" class="" style="display:none;" action="" >
						<p>
							<?php wp_nonce_field( 'cmcrm-rolename-editor-nonce','_wpnonce', true, true ); ?>
							<input name="cmcrm_action" type="hidden" value="rolename_editor" />
							<input name="role" type="hidden" value="<?php echo $role->name; ?>" />
							<input name="XDEBUG_SESSION_START" type="hidden" value="xdebug" />
							<label><?php echo __('Display Name', 'cmcrm'); ?>: </label><input type="text" name="name" value="<?php echo self::get_role_name($role->name); ?>" />
							<button type="submit" class="button button-primary" style="width:15%;"><?php echo __('Save', 'cmcrm'); ?></button>
						</p>
					</form>
				</div>
			</div>
			<div style="margin-top:10px;">
				<button type="button" id="cmcrm-role-clone-btn" class="page-title-action cmcrm-help-tip" data-tip="Clone Role" onclick="jQuery('form#cmcrm-role-clone').slideToggle('fast').find(':file').focus();" >                
					<?php echo __('Clone Role', 'cmcrm'); ?>
				</button> 
				<div style="width:400px;">
					<form id="cmcrm-role-clone" onsubmit="" method="post" enctype="multipart/form-data" class="" style="display:none;" action="" >
						<p>
							<?php wp_nonce_field( 'cmcrm-role-clone-nonce','_wpnonce', true, true ); ?>
							<input name="cmcrm_action" type="hidden" value="role_clone" />
							<input name="role_clone" type="hidden" value="<?php echo $role->name; ?>" />
							<input name="XDEBUG_SESSION_START" type="hidden" value="xdebug" />
							<label><?php echo __('Role', 'cmcrm'); ?>: </label><input type="text" name="role" value="<?php  ?>" /><br/>
							<label><?php echo __('Display Name', 'cmcrm'); ?>: </label><input type="text" name="name" value="<?php  ?>" /> <br/>
							<button type="submit" class="button button-primary" style="width:15%;"><?php echo __('clone', 'cmcrm'); ?></button>
						</p>
					</form>
				</div>
			</div>
		</div>
		
		<h3>Capabilities</h3>
		<div id="cmcrm-role-capability">
			<div>
				<button type="button" id="cmcrm-role-capability-add-btn" class="page-title-action cmcmg-help-tip" data-tip="Add Capability to Role" onclick="jQuery('form#cmcrm-role-capability-add').slideToggle('fast').find(':file').focus();" >                
					<?php echo __('Add Capability', 'cmcmg'); ?>
				</button> 
				<div style="width:400px;">
					<form id="cmcrm-role-capability-add" onsubmit="" method="post" enctype="multipart/form-data" class="" style="display:none;" >
						<p>
							<?php wp_nonce_field( 'cmcrm-add-capability-nonce','_wpnonce', true, true ); ?>
							<input name="cmcrm_action" type="hidden" value="add_capability" />
							<input name="role" type="hidden" value="<?php echo $role->name; ?>" />
							<input name="XDEBUG_SESSION_START" type="hidden" value="xdebug" />
							<label><?php echo __('Capability', 'cmcrm'); ?>: </label><input type="text" name="capability" />
							<button type="submit" class="button button-primary" style="width:15%;"><?php echo __('Create', 'cmcmg'); ?></button>
						</p>
					</form>
				</div>
			</div>
			<div id="cmcrm-role-capability-list" class="cmcrm-list" style="">
				<?php 
				$nonce = wp_create_nonce( 'cmcrm_delete_capability_nonce' );
				foreach( $role->capabilities as $k => $c){ if( !$c ) continue; 
				?>
					<p>
						<?php 
							$del = "<a href='?page=cmcrm&section=role&cmcrm_action=delete_capability&id={$role->name}&capability=$k&_wpnonce=$nonce&XDEBUG_SESSION_START' style='color:red;'>X</a>";
							if( in_array( $role->name, cmc_role::$roles_native ) ){
								if( !in_array($k, $trad_cap) )
									echo $del;														
							}else{
								echo $del;
							}
						?>						
						<label><?php echo $k; ?></label>
					</p>
				<?php	} ?>
			</div>			
		</div>

		<h3>Users</h3>
		<div id="cmcrm-role-users">			
			<?php 
				$args = array( 'role' => $role->name, 'orderby' => 'user_nicename', 'order' => 'ASC' );
				 $users = get_users($args);
			?>
			<div id="cmcrm-role-user-list" class="cmcrm-list">
				<?php  foreach ($users as $user) { ?>
					<p><label><?php echo $user->display_name.' ['.$user->user_email . ']'; ?></label></p>
				<?php } ?>
			</div>
		</div>
		
	</div>	
	<script>
		var cmcrm = cmcrm || {};
		(function($, cmcrm){
		
			cmcrm.submitform = function( form ){
				if( !form )return false; $form = $(form);
				var data = $form.serializeArray(); $submit = $form.find(':submit').attr('disabled',true);
				$.post(form.attr('action'), data, function(result){
					if(result.message)
						alert(result.message);
					if(result.replace)
						$('#cmcrm_page').html( result.replace );
				}).fail(function(){
					alert("Network Error");
				}).always(function(){
					$submit.attr('disabled', false);
				});
				return false;
			}
		
			$(function(){
				$('#cmcrm-role-form').accordion({collapsible: true, active: false});
			});			
		})(jQuery, cmcrm);
	</script>
</div>	
	