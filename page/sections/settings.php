<?php 
/*
package: cmc_hook
file: admin/page.php 
*/

if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}
if( !cmc_role::is_user_allowed()){
	echo "You do not have permission to access this page"; return;
}

global $cmcrm_settings_default;
$model = get_option(CMCRM_SETTINGS, $cmcrm_settings_default);
?>
<div class="cmcmg_section_settings_inner">
	<style>
		#cmcmg_section_settings_form .cmcmg-help-tip{
			float:right;
		}
	</style>
    <h3> <?php echo __('All Settings', 'cmcrm'); ?></h3>
    <form id="cmcmg_section_settings_form" method="post">
        <?php wp_nonce_field( 'cmcrm-settings-save-nonce','_wpnonce', true, true ); ?>
        <input name="XDEBUG_SESSION_START" type="hidden" />
        <table id="cmcmg_section_settings_table_1" class="cmcmg_section_settings_table form-table">
            <tr>
                <th>
                    <?php echo __('Delete on Uninstall', 'cmcrm'); ?>
					<span class="cmcmg-help-tip" data-tip="<?php echo __( "On Deactivation of Plugin Select items to delete" , 'cmcrm'); ?>">
						<i class="fa fa-question-circle"></i>
					</span>
                </th>
                <td>				
					<label>
						<?php echo __('Settings', 'cmcrm'); ?>
						<input name="del_opt_uninstall" type="checkbox" <?php checked( $model['del_opt_uninstall'], 1); ?> />
					</label>
                </td>
            </tr>
            <tr>
                <th>
                    <?php echo __('Roles', 'cmcrm'); ?>
					<span class="cmcmg-help-tip" data-tip="<?php echo __("Enter role per line to allow usage of the system", 'cmcrm'); ?>">
						<i class="fa fa-question-circle"></i>
					</span>
                </th>
                <td>
                    <textarea name="allowed_users" class="widefat" style="min-height:150px;" title="One Role Per Line"><?php echo $model['allowed_users']; ?></textarea>
                </td>
            </tr>
        </table>
        <?php
            do_action('cmcrm_admin_page_settings_controls', $model);
        ?>
        <button type="submit" name="cmcrm_action" value="save_settings" class="button button-primary" ><?php echo __('Submit', 'cmcmg'); ?></button>
    </form>   
</div>