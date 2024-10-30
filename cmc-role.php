<?php
/**
Plugin Name: cmc-role
Description: Roles, Users and Capabilities Management
Version: 0.0.1
Author: Evans Edem Ladzagla
Author URI: https://profiles.wordpress.org/lovnic/
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: cmcrm
**/ 

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/*
 * Main cmc_migrate Class.
 *
 * @class cmc_migrate
 */
final class cmc_role {
	
	/*
     * The single instance of the class.
     *
     * @var cmc_migrage
     */
	public static $_instance = null;
	
	/*
     * migrations instance
     *
     * @var cmc_role_List.
     */
    public static $roles;
	
	/*
     * Admin Page Url.
     *
     * @var string
     */
    public static $menu;
	
	/*
     * Roles already in wordpress.
     *
     * @var array
     */
	public static $roles_native = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
	
	/*
     * Main cmc_migrate Instance.
     *
     * Ensures only one instance of cmc_migrate is loaded or can be loaded.
     *
     * @static
     * @return cmc_migate - Main instance.
     */
	public static function instance(){
        if( self::$_instance == null ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
	
	/*
    * 	constructor of cmc_migrate
    */
	function __construct(){
		self::constants(); self::includes();
		add_action( 'plugins_loaded', array( __CLASS__, 'init'));
		register_activation_hook( __FILE__, array( __CLASS__, 'plugin_activate' ) );
        register_deactivation_hook( __FILE__, array( __CLASS__, 'plugin_deactivate' ) );  		
	}
	
	/**
    * Check whether current user is allowed to use cmc migrate
    */
	public static function is_user_allowed(){
		if( current_user_can('administrator') ) return true;
		$allowed_roles = self::get_setting('allowed_roles');  $allowed_roles = explode('\n', $allowed_roles);
		foreach($allowed_roles  as $role){
			if( current_user_can( $role ) ) return true;
		}
		return false;
	}
	
	/*
    * Init cmc-migrage when WordPress Initialises
    */
	public static function init(){
		if( is_admin() ){			
			if( self::is_user_allowed() ){
				if( isset($_REQUEST['cmcrm_action']) && !empty($_REQUEST['cmcrm_action']) ){
					self::do_action( $_REQUEST['cmcrm_action'] );
				}				
				add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
				add_filter( 'set-screen-option', function($status, $option, $value ){return $value;}, 10, 3 );
			}
		}		
	}
	
	/*
     * 	Performs an action if cmcrm_action in $_REQUEST is not empty
     * 
	 *	@param	string	$action	name of action passed
     */
	private static function do_action( $action ){
		switch( $action ){
			case 'add_role': self::add_role(); break;
			case 'role_clone': self::role_clone(); break;
			case 'rolename_editor': self::role_name_editor(); break;
			case 'add_capability': self::add_role_capability(); break;
			case 'delete_capability': self::delete_role_capability(); break;			
			case 'delete': self::delete_role(); break;
			case 'save_settings': self::save_settings(); break;
		}
	}
	
	/*
     * Loads Admin Menu 
     * Page cmc role is added to Users
     */
    public static function admin_menu(){
        $hook = add_users_page( __('CMC Role', 'cmcmg'), 'CMC Role', 'manage_options', 'cmcrm', function(){
				require_once( 'page/admin.php' ); 
			});
        self::$menu = menu_page_url('cmcrm', false);
        add_action( "load-$hook", array(__CLASS__, "menu_load"));		
    }
	
	/*
     * On Admin Menu load this function run
     */
	public static function menu_load(){
	
        if( empty($_REQUEST['tab']) && empty($_REQUEST['section']) ){
			require_once("include/class-cmc-role-table.php");
			$option = 'per_page';
			$args   = [
				'label'   => 'Roles',
				'default' => 5,
				'option'  => 'roles_per_page'
			];
			add_screen_option( $option, $args );	
			self::$roles = new cmc_role_List(); 
			self::$roles->process_bulk_action();
        }
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-accordion' );
		wp_enqueue_script( 'tiptip_js',CMCRM_JS_URL.'TipTip/jquery.tipTip.js', '', '', true );
		//wp_enqueue_script( 'bootstrap_js',CMCRM_JS_URL.'bootstrap/bootstrap.js', '', '', true ); 		
	
		wp_enqueue_style( 'tiptip_css', CMCRM_JS_URL.'TipTip/tipTip.css');
		wp_enqueue_style( 'font_font-awesome_css', CMCRM_CSS_URL.'font-awesome/css/font-awesome.min.css');
		//wp_enqueue_style( 'bootstrap_css', CMCRM_CSS_URL.'bootstrap/css/bootstrap_pre.css');
		wp_enqueue_style( 'jquery-ui_css', CMCRM_CSS_URL.'jquery-ui/jquery-ui.css');
	}

	/**
     *  add role
     */
	public static function add_role(){
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-add-capability-nonce' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['role'] ); $name = sanitize_text_field( $_REQUEST['display_name'] );
		
		$response = self::_add_role($role, $name);
		
		$notice = ($response['success'])?'success':'error'; $msg = $response['message'];
		add_action('admin_notices', function() use ($msg, $notice){
			echo "<div class='notice notice-$notice is-dismissible'><p>$msg</p></div>";
		});
		
	}
	
	/*
     *  Clone Role
     */
	public static function role_clone(){		
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-role-clone-nonce' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['role'] ); $name = sanitize_text_field($_REQUEST['name']); 
		$role_clone = sanitize_text_field( $_REQUEST['role_clone'] );	
		$role_obj = get_role( $role_clone );		
		$response = self::_add_role( $role, $name, $role_obj->capabilities ); 
		
		$notice = ($response['success'])?'success':'error'; $msg = $response['message'];
		add_action('admin_notices', function() use ($msg, $notice){
			echo "<div class='notice notice-$notice is-dismissible'><p>$msg</p></div>";
		});
	}
	
	/*
     *  Privately add role
     */
	private static function _add_role( $role, $name, $capabities = array() ){
		$response = array();
		if( empty($role) || empty($name) ){
			$response['success'] = false; $response['message'] = 'Role and Display Name cannot be empty';
			return $response;
		}
		$role = sanitize_text_field($role); $name = sanitize_text_field($name);
		$result = add_role( $role, $name, $capabities );
		
		$response['success'] = true; $response['message'] = "Role: <b>$role</b> Add Successfully";
		return $response;
	}
	
	/*
     *  Delete role
     */
	public static function delete_role(){
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm_role_list_inline_action' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['id'] );
		if( get_role( $role ) ){
			remove_role( $role );	
		}			
		wp_redirect( '?page=cmcrm' );
		exit();
	}
	
	/*
     *  Edit role display name
     */
	public static function role_name_editor(){		
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-name-editor-nonce' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['role'] ); $name = sanitize_text_field( $_REQUEST['name'] );		
		$response = self::_role_name_editor($role, $name); 
		
		$notice = ($response['success'])?'success':'error'; $msg = $response['message'];
		add_action('admin_notices', function() use ($msg, $notice){
			echo "<div class='notice notice-$notice is-dismissible'><p>$msg</p></div>";
		});
	}
	
	/*
     *  Privately Edit role display name
     */
	private static function _role_name_editor( $role, $name){
		if( in_array( $role, self::$roles_native ) ) return; $response = array();
		if( empty($role) || empty($name) ){
			$response['success'] = false; $response['message'] = 'Role and Display Name cannot be empty';
			return $response;
		}
		
		$role = sanitize_text_field($role); $name = sanitize_text_field($name);
		$user_roles = get_option( CMCRM_ROLE_KEY );		
		$user_roles[$role]['name'] = $name;
		update_option( CMCRM_ROLE_KEY, $user_roles );
		
		$response['success'] = true; $response['message'] = "Saved Successfully";
		return $response;
	}
	
	/*
     *  Add Role Capability
     */
	public static function add_role_capability(){		
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-add-capability-nonce' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['role'] ); $capabilty = sanitize_text_field( $_REQUEST['capability'] );		
		$response = self::_add_role_capability($role, $capabilty); 
		
		$notice = ($response['success'])?'success':'error'; $msg = $response['message'];
		add_action('admin_notices', function() use ($msg, $notice){
			echo "<div class='notice notice-$notice is-dismissible'><p>$msg</p></div>";
		});
	}
	
	/*
     *  Privately Add Role Capability
     */
	private static function _add_role_capability( $role, $capabilty ){
		if( in_array( $role, self::$roles_native ) ) return; $response = array();
		if( empty($role) || empty($capabilty) ){
			$response['success'] = false; $response['message'] = 'Role and Capabilty cannot be empty';
			return $response;
		}
		
		$role = sanitize_text_field($role); $capabilty = sanitize_text_field($capabilty);
		$role_obj = get_role( $role ); $role_obj->add_cap( $capabilty ); 
		
		$response['success'] = true; $response['message'] = "Capability Added";
		return $response;
	}
	
	/*
     *  Delete Role Capability
     */
	public static function delete_role_capability(){		
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-delete-capability-nonce' ) ) {
            die( 'Cheating...' );
        }
		
		$role = sanitize_text_field( $_REQUEST['id'] ); $capabilty = sanitize_text_field( $_REQUEST['capability'] );		
		$response = self::_delete_role_capability($role, $capabilty); 
		
		$notice = ($response['success'])?'success':'error'; $msg = $response['message'];
		add_action('admin_notices', function() use ($msg, $notice){
			echo "<div class='notice notice-$notice is-dismissible'><p>$msg</p></div>";
		});
	}
	
	/*
     *  Privately Delete Role Capability
     */
	private static function _delete_role_capability( $role, $capabilty ){
		if( in_array( $role, self::$roles_native ) ) return; $response = array();
		if( empty($role) || empty($capabilty) ){
			$response['success'] = false; $response['message'] = 'Role and Capabilty cannot be empty';
			return $response;
		}
		
		$role = sanitize_text_field($role); $capabilty = sanitize_text_field($capabilty);
		$role_obj = get_role( $role ); $role_obj->remove_cap( $capabilty ); 
		
		$response['success'] = true; $response['message'] = "Capability Deleted";
		return $response;
	}

	/*
     *  Save Settings of cmc migrate
     */
	protected static function save_settings(){
		if ( isset( $_REQUEST['_wpnonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );                     
        }
        if ( !isset( $nonce ) and ! wp_verify_nonce( $nonce, 'cmcrm-settings-save-nonce' ) ) {
            die( 'Cheating...' );
        }
		$response = self::_save_settings( $_POST );    

        do_action('cmcrm_settings_save', $data);
	}
	
	/*
     *  Privately save settings
	 *
	 * 	@param array $model items to save
     */
	private static function _save_settings( $model ){
		$data = array(); $response = array();
		$data['allowed_users'] = wp_unslash( $model['allowed_users'] );
		$data['del_opt_uninstall'] = isset( $model['del_opt_uninstall'] )? 1: 0;
		
		$data = apply_filters( 'cmcrm_settings_data_save', $data);
        if( $data === false )return false;

        update_option(CMCRM_SETTINGS, $data);
		$response['success'] = true; $response['message'] = "Saved Successfully";
		return $response;
	}
	
	/*
     *  Get value of one cmc migrate Settings
     * 
     * 	@param string $name name of the settings
     * 	@param string $default default value if name doesnt exist
     */
    public static function get_setting( $name, $default = ""){
        global $cmcrm_settings_default;
        $opt = get_option( CMCRM_SETTINGS, $cmcrm_settings_default );
        return isset($opt[$name])? $opt[$name]: $default;
    }
	
	/*
     *  Get display name of a role
     * 
     * 	@param string $role name of role
     */
	public static function get_role_name( $role ){
		if( empty($role) ) return;
		
		$roles = get_editable_roles();
		return $roles[$role]['name'];
	}
	
	/*
     *  Activation function runs on plugin activation
     */
    public static function plugin_activate(){	
		if( !get_option( CMCRM_SETTINGS ) ){
			global $cmcrm_settings_default;
			update_option( CMCRM_SETTINGS , $cmcrm_settings_default);
		}
    }
    
    /*
     *  Deactivation function runs on plugin deactivation
     */
    public static function plugin_deactivate(){	
        if( self::get_setting('del_opt_uninstall', false) ){
            delete_option( CMCMG_SETTINGS );
        }
    }
       
	/*
     *  Get Current Url
     */
    public static function current_url(){
        return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
	
	/*
     * 	Include required core files used in admin and on the frontend.
     */
    protected static function includes(){
        require_once("include/default.php");
		require_once("include/functions.php");
    }
		
	/*
     * 	Define cmc_migrate Constants.
     */
    protected static function constants(){
        global $wpdb;
        define('CMCRM_VERSION', '0.0.1');
        define('CMCRM_FOLDER', basename( dirname( __FILE__ ) ) );
        define('CMCRM_DIR', plugin_dir_path( __FILE__ ) );
        define('CMCRM_DIR_URL',  plugin_dir_url( __FILE__ ) );
		define('CMCRM_JS_URL', CMCRM_DIR_URL.'assets/js/');
		define('CMCRM_CSS_URL', CMCRM_DIR_URL.'assets/css/');
		define('CMCRM_SETTINGS',  'cmc_role_settings' );
		define('CMCRM_ROLE_KEY',  $wpdb->get_blog_prefix() . 'user_roles' );	
    }
}

/**
 * Main instance of cmc_role.
 *
 * Returns the main instance of cmcrm to prevent the need to use globals.
 *
 * @return cmc_role
 */
function cmcrm() {
	return cmc_role::instance();
}
cmcrm();
?>
