<?php
/*
package: cmc_role
file: admin/role_table.php 
*/

if(!defined('ABSPATH')) { 
    header('HTTP/1.0 403 Forbidden');
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class cmc_role_List extends WP_List_Table {

	public static $roles = array();
	
	public static $role_count = 0;
	
	public function __construct( $str = array() ) {	
		if( is_string($str) )return;
        parent::__construct( [
            'singular' => __( 'Migration', 'cmcmg' ), //singular name of the listed records
            'plural'   => __( 'Migrations', 'cmcmg' ), //plural name of the listed records
            'ajax'     => false, //should this table support ajax?
        ] );
    }
    
    public static function get_roles( $per_page = 5, $page_number = 1 ) {		
		$roles = get_editable_roles(); self::$role_count = count(self::$roles);     
		foreach( $roles as $k => &$r ){
			$r['role'] =  $k;
		}
		self::$roles = $roles;
		return self::$roles;
    }
    
    public static function delete_role( $id ) {
        global $wpdb;
        
        $wpdb->delete(
          CMCRM_TABLE_ROLE,
          [ 'ID' => $id ],
          [ '%d' ]
        );
    }
    
    public static function record_count() {
        return self::$role_count;
    }

    public function cmcmg_get_counts(){
		return self::$migrations_count_all;
    }
	
    public function no_items() {
        _e( 'No Custom Roles avaliable.', 'cmcrm' );
    }
    
    function column_name( $item ) {
        $nonce = wp_create_nonce( 'cmcrm_role_list_inline_action' );
        $title = "<strong>$item[name]</strong>";

        $actions = []; 
		if( !in_array( $item['role'], cmc_role::$roles_native ) ){
			$actions['delete'] =  "<a onclick='return cmcrm.delete_export();' href='?page=cmcrm&cmcrm_action=delete&XDEBUG_SESSION_START&id=$item[role]&_wpnonce=$nonce' style='color:red;' >Delete</a>";
		}
		$actions['editor'] = "<a href='?page=cmcrm&section=role&id=$item[role]&XDEBUG_SESSION_START' style=''> Editor</a>";
		
        $actions = apply_filters('cmcrm_table_actions', $actions);
        return $title . $this->row_actions( $actions );
    }
	
	public function column_capabilities( $item ){
		//print_r($item['capabilities']); $result = '';
		if( empty($item['capabilities']) ) return 'No capabilities';
		foreach( $item['capabilities'] as $k => $c ){
			$result .= "\n<option>$k : $c</option>";
		}
		$result = '<select multiple="true" disabled="true">\n'. $result;
		$result .= '\n</select>';
		return $result;
	}
    
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'safe_mode':
                return $item[ $column_name ]? 'Yes':'No';
            default:
                return $item[ $column_name ]; // print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }
    
    function column_cb( $item ) {
        return sprintf(
          '<input type="checkbox" name="bulk-items[]" value="%s" />', $item['role']
        );
    }
    
    function get_columns() {
        $columns = [
            'cb'      => '<input type="checkbox" />',
            'name'    => __( 'Name', 'cmcrm' ),
            'capabilities'  => __('Capabilities', 'cmcrm'),      
        ];

        return $columns;
    }
    
    public function get_sortable_columns() {
        $sortable_columns = array(
          'site' => array( 'site', true ),
          'date' => array( 'date', true ),
        );

        return $sortable_columns;
    }
    
    public function get_bulk_actions() {
        $actions = [];
        if( $_REQUEST['location'] == 'remote' ){
		
        }else{
            $actions['bulk-trash'] = 'Trash'; 
        } 
        //return $actions;
		return array();
    }
    
    protected function get_views() {
        //$count = $this->cmchk_get_counts();
        $remote = $_REQUEST['location'] == 'remote'? 'current':'';
        $local = empty($_REQUEST['location'])? 'current':'';
		$remote_url = $_REQUEST['location'] == 'remote'? ': '.cmc_migrate::get_setting('remote_migration_url'):'';
		
        $views = array();
		$views['local'] = "<a href='?page=cmcmg&XDEBUG_SESSION_START' class='$local'>Local</a>";			
		$views['remote'] = "<a href='?page=cmcmg&location=remote&XDEBUG_SESSION_START' class='$remote'>Remote $remote_url</a>";

		return $views;
    }
	
    public function prepare_items() {
        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        //$this->process_bulk_action();
        $per_page     = $this->get_items_per_page( 'roles_per_page', 5 );
        $current_page = $this->get_pagenum();
		$this->items = self::get_roles( $per_page, $current_page );
        $total_items  = self::record_count();

		
		
        $this->set_pagination_args( [
          'total_items' => $total_items, //WE have to calculate the total number of items
          'per_page'    => $per_page //WE have to determine how many items to show on a page
        ] );
        
        //$this->search_box( 'Search', 'cmchk_hook_search' );
		$this->cmc_footer();
    }
    
    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sp_delete_hook' ) ) {
              die( 'Go get a life script kiddies' );
            }
            else {
              self::delete_hook( absint( $_GET['cmchk_id'] ) );			  
                if( $_REQUEST['cmchk_proj'] > 0){
					wp_redirect('?page=cmc-hook&cmchk_page=project&cmchk_section=project&cmchk_id='.$_REQUEST['cmchk_proj'].'&cmchk_status=trash');
                }else{
                    wp_redirect('?page=cmc-hook&cmchk_status=trash');
                }
                exit;
            }
        }
				
        $action = !empty($_POST['action'])? $_POST['action']: $_POST['action2'];
		
		if( $action == '' ){
		
		}
   
	}
    
	public function cmc_header(){ ?>
		<h3><?php echo __('All Roles', 'cmcrm'); ?></h3>
	<?php }
	
	public function cmc_footer(){?>		
		
		<script>
			var cmcrm = cmcrm || {};
			(function($, cmcrm){
				
				cmcrm.delete = function (){
					if( confirm("Do Yo Want to Delete The Item") ){
						return true
					}
					return false;
				}
			
			})(jQuery, cmcrm);
			
		</script>
	<?php }
}