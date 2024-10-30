<?php
/*
package: cmc_role
file: admin/page.php 
*/

if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}
$menu = array(
    'roles'=>array('text'=>__('Role', 'cmcrm'), 'href'=>'?page=cmcrm', 'page'=>function(){    
        $sections = array(
            'roles'=>array('page'=>function(){ 
                echo "<div id='cmcrm_section_roles' class='cmcrm_tab'>";
				echo cmc_role::$roles->cmc_header();
                cmc_role::$roles->prepare_items();
                //cmc_role::$roles->views();
                echo "<form method='post' action='?page=cmcrm'>";
                echo "<input type='hidden' name='XDEBUG_SESSION_START' />";                
                cmc_role::$roles->display();
                echo "</form>";
                echo "<div>";
            }),
			'role'=>array('page'=>function(){
				require('sections/role.php');
			}),
        ); 
        $sections = apply_filters('cmcrm_admin_page_section', $sections);
        $selected = empty($_REQUEST['section'])? 'roles':$_REQUEST['section'];
        $sec_page = $sections[$selected];
        call_user_func_array( $sec_page['page'], array() );
        //require("sections/hook_list.php");
    }),  
    'settings'=>array('text'=>__('Settings', 'cmcrm'), 'href'=> '?page=cmcrm&tab=settings', 'page'=>function(){
        echo "<div id='cmrm_section_settings' class='cmchk_section'>";
        require("sections/settings.php");  
        echo "<div>";
    }),
);
$sel_page = empty($_REQUEST['tab']) ? 'roles': $_REQUEST['tab'];
?>
<div id="cmcrm-admin-page" class="wrap bootstrap">
    <h1>
        <?php echo __('CMC Roles', 'cmcrm'); ?> 
		<button type="button" id="cmcrm-form-migration-impport-btn" class="page-title-action cmcmg-help-tip" data-tip="Create New Role" onclick="jQuery('form#cmcrm-form-migration-import').slideToggle('fast').find(':file').focus();" >                
            <?php echo __('Add Role', 'cmcmg'); ?>
        </button>  
    </h1>
	<div style="width:400px;">
        <form id="cmcrm-form-migration-import" method="post" enctype="multipart/form-data" class="" style="display:none;" action="?page=cmcrm" >
            <p>
                <?php wp_nonce_field( 'cmcmg-add-role-nonce','_wpnonce', true, true ); ?>
                <input name="cmcrm_action" type="hidden" value="add_role" />
                <input name="XDEBUG_SESSION_START" type="hidden" value="xdebug" />
				<p><label><?php echo __('Role', 'cmcmg'); ?>: </label><input type="text" name="role" /></p>
				<p><label><?php echo __('Display Name', 'cmcmg'); ?>: </label><input type="text" name="display_name" /></p>
                <button type="submit" class="button button-primary" style="width:15%;"><?php echo __('Create', 'cmcmg'); ?></button>
            </p>
        </form>
	</div>
	<h2 id="cmcmg_page_menu" class="nav-tab-wrapper wp-clearfix">        
        <?php             
            $menu = apply_filters('cmcrm_admin_page_menu', $menu);
            foreach($menu as $k => $m){
                if( $m['active'] === false) continue;
                $s = ($sel_page == $k)? "nav-tab-active":""; $m['class'] = is_array($m['class'])? implode(' ', $m['class']):$m['class'];
                echo sprintf('<a href="%s" class="nav-tab %s %s" %s > %s </a>', $m['href'], $m['class'], $s, $m['atts'], $m['text'] );
            }
        ?> 		
    </h2>

	<div id="cmcrm_page" class="cmcrm_page_<?php echo $sel_page; ?>"> 
        <?php
            $page = $menu[$sel_page];
            call_user_func_array( $page['page'], array() );
        ?>
    </div>
	<script>
		var cmcrm = cmcrm || {};
		(function($, cmcrm){
			cmcrm.page_load = function( $wrap ){
					if( !$wrap )return;
					var tiptip_args = {
						'attribute': 'data-tip',
						'fadeIn': 50,
						'fadeOut': 50,
						'delay': 200
					};	
				
					$wrap.find( '.cmcmg-help-tip' ).tipTip( tiptip_args ).css( 'cursor', 'help' );
				}
			
			$(function(){
				cmcrm.page_load( $('#cmcrm-admin-page') );
			});
		
		})(jQuery, cmcrm);
	
	</script>
</div>