<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class PQC_Materials_List extends WP_List_Table {

	/**
    * Class Constructor
    * 
    */
	public function __construct() {

		parent::__construct( [
			'singular' => __( 'Material', 'pqc' ), //singular name of the listed records
			'plural'   => __( 'Materials', 'pqc' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
		] );

	}
    
	/**
	 * Retrieve materials data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_materials( $per_page = 5, $page_number = 1 ) {

		global $wpdb;
        
        $table = PQC_MATERIALS_TABLE;
        
        if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {
            
            $search_term = esc_attr( $_POST['s'] );
            
            $sql = "SELECT * FROM $table WHERE material_name LIKE '%$search_term%'";
            
        } else {
            
            $sql = "SELECT * FROM $table";
            
        }

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		} else {
            $sql .= ' ORDER BY ID DESC';
        }

		$sql .= " LIMIT $per_page";
        
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}
    
	/**
	 * Delete a material record.
	 *
	 * @param int $id material ID
	 */
	public static function delete_material( $id ) {
        
		global $wpdb;
        
        $table = PQC_MATERIALS_TABLE;

		$delete = $wpdb->delete( $table, [ 'ID' => $id ], [ '%d' ] );
        
        return $delete;
        
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
        
		global $wpdb;
        
        $table = PQC_MATERIALS_TABLE;

		$sql = "SELECT COUNT(*) FROM $table";

		return $wpdb->get_var( $sql );
	}

    /**
    * Text displayed when no data is available
    * 
    */
	public function no_items() {
		_e( 'No material found.', 'pqc' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
        
        $settings = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );

		switch ( $column_name ) {
            case 'date':
                $text = ( $item['date_created'] === $item['date_modified'] ) ? __( 'Published', 'pqc' ) : __( 'Updated', 'pqc' );
                return sprintf(
                    '%s<br><abbr title="%s">%s</abbr>',
                    $text, mysql2date( 'Y/m/d h:i:s a', $item['date_modified'] ),
                    pqc_time_difference( current_time( 'mysql' ), $item['date_modified'] )
                );
                break;
            case 'material_cost':
                return pqc_money_format( $item[$column_name] );
            case 'material_description':
                return $item[$column_name];
            case 'material_name':
                return $item[$column_name];
                break;
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	public function column_material_name( $item ) {
        
        $delete_nonce = wp_create_nonce( 'pqc_delete_material' );
        
		$actions = [
            'edit'      => sprintf( __( '<a class="editinline material" href="#" data-id="%d">Edit</a>' ), absint( $item['ID'] ) ),
            'delete'    => sprintf( '<a href="?page=%s&action=%s&material=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['ID'] ), $delete_nonce )
		];
        
        $content = '<input class="material_name_hidden" type="hidden" value="' . $item['material_name'] . '">'; 
        $content .= '<input class="material_description_hidden" type="hidden" value="' . $item['material_description'] . '">'; 
        $content .= '<input class="material_cost_hidden" type="hidden" value="' . $item['material_cost'] . '">'; 
        
        $material_name = $content . $item['material_name'];

        return sprintf( '%1$s %2$s', $material_name, $this->row_actions( $actions ) );
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'                    => '<input type="checkbox" />',
            'material_name'         => __( 'Material Name', 'pqc' ),
            'material_description'  => __( 'Material Description', 'pqc' ),
            'material_cost'         => __( 'Cost', 'pqc' ),
            'date'                  => __( 'Date', 'pqc' ),
		];

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
            'material_name' => array( 'material_name', true ),
			'date'          => array( 'date_modified', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
        
		$this->_column_headers = $this->get_column_info();

		// Process bulk action
        $this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'materials_per_page', 5 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( [
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
		] );

		$this->items = self::get_materials( $per_page, $current_page );
	}

	public function process_bulk_action() {
        
        global $pqc;

		//Detect when a delete action is being triggered...
		if ( 'delete' === $this->current_action() ) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr( $_REQUEST['_wpnonce'] );
            
            wp_verify_nonce( $nonce, 'pqc_delete_material' );
            
            $delete = self::delete_material( absint( $_GET['material'] ) );
            
            if ( $delete ) {
                
                $pqc->add_notice( __( 'Material deleted successfully.', 'pqc' ), 'updated' );
                
            } else {
                
                $pqc->add_notice( __( 'Failed to delete material. Perharps it does not exist anymore.', 'pqc' ), 'pqc-update-nag update-nag' );
                
            }

		}

		// If the delete bulk action is triggered
		elseif ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$delete_ids = esc_sql( $_POST['bulk-delete'] );
            
            if ( isset( $delete_ids ) && ! empty( $delete_ids ) ) {
                // loop over the array of record IDs and delete them
                foreach ( $delete_ids as $id ) {
                    
                    $delete = self::delete_material( $id );

                }
                
                if ( $delete ) {
                    
                    $pqc->add_notice( __( 'Material deleted successfully.', 'pqc' ), 'updated' );
                    
                } else {
                    
                    $pqc->add_notice( __( 'Failed to delete material. Perharps it does not exist anymore.', 'pqc' ), 'pqc-update-nag update-nag' );
                    
                }
                
            }

		}
        
	}
}


class PQC_Materials {
    
    public static $materials_obj;
    
    public function __construct() {}
    
    /**
     * Plugin materials page
     */
    public static function materials_page() {

        if ( isset( $_POST['pqc_material_add'] ) ) {
            
            unset( $_POST['pqc_id'] );
            
            self::add_material( $_POST );
            
        }
        elseif ( isset( $_POST['pqc_material_update'] ) ) {
            
            self::update_material( $_POST );
            
        }
        
        $settings = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $currency = $settings['pqc_general_settings']['currency'];
        
        ?>
        <div class="wrap nosubsub">
        
            <h2><?php _e( 'Materials Table', 'pqc' ); ?></h2>
            
            <br class="clear">
            
            <div id="col-container">

                <div id="col-right" class="material-col-right">
                
                    <div class="col-wrap">
                        <form method="post" id="material-table">
                            <?php
                            self::$materials_obj->prepare_items();
                            self::$materials_obj->search_box( 'search', 'search_id' );
                            self::$materials_obj->display();
                            ?>
                        </form>
                    </div>
                    
                </div>
                
                <div id="col-left" class="material-col-left">
                    <div class="col-wrap">
                        <div class="form-wrap" id="material-wrapper">
                            <h2 id="edit-material" style="display: none;"><?php _e( 'Edit Material', 'pqc' ); ?></h2>
                            <h2 id="add-material"><?php _e( 'Add Material', 'pqc' ); ?></h2>
                            <?php require PQC_PATH . 'admin/templates/materials/custom-materials.php'; ?>
                        </div>
                    </div>
                </div>
                
                <br class="clear">
                
                <br class="clear">
                
            </div>
            
        </div>
        <?php
    }
    
    /**
    * Screen Option
    * 
    */
    public static function materials_screen_option() {

        $option = 'per_page';
        $args   = [
            'label'   => 'Materials',
            'default' => 5,
            'option'  => 'materials_per_page'
        ];

        add_screen_option( $option, $args );

        self::$materials_obj = new PQC_Materials_List();
    }
    
    /**
    * Add new material
    * 
    * @param mixed $args
    */
    public static function add_material( $args ) {
            
        check_admin_referer( 'nonce_material' );
        
        global $wpdb, $pqc;
        
        $table = PQC_MATERIALS_TABLE;        
        
        $error = false;
        
        foreach ( $args as $key => $value ) {
            
            if ( empty( $value ) ) {
                
                $error = true;
                
                break;
                
            }
            
        }
        
        if ( ! $error ) {

            $author         = get_current_user_id();
            $material_name  = sanitize_text_field( $args['pqc_material_name'] );
            $material_desc  = sanitize_textarea_field( $args['pqc_material_description'] );
            $material_cost  = floatval( $args['pqc_material_cost'] );
            $date_created   = current_time( 'mysql' );
            $date_modified  = current_time( 'mysql' );
            
            $exist = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE material_name = %s", $material_name ) );
            
            if ( ! $exist ) {
                
                $sql = $wpdb->prepare( "
                INSERT INTO $table ( author, material_name, material_description, material_cost, date_created, date_modified )
                VALUES ( %d, %s, %s, %s, %s, %s )
                ", $author, $material_name, $material_desc, $material_cost, $date_created, $date_modified );
                
                $add = $wpdb->query( $sql );
                    
            }
            
            if ( isset( $add ) && $add ) {
                
                $pqc->add_notice( __( 'Material added successfully.', 'pqc' ), 'updated' );    
                
            } else {
                
                $pqc->add_notice( __( 'Failed to add material. Perharps it already exist.', 'pqc' ), 'pqc-update-nag update-nag' );
                
            }
            
        } else {
            
            $pqc->add_notice( __( 'All fields are required.', 'pqc' ), 'error' );
    
        }
        
    }

    /**
    * Update Existing Material
    * 
    * @param mixed $args
    */
    public static function update_material( $args ) {
            
        check_admin_referer( 'nonce_material' );
        
        if ( ! isset( $args['pqc_id'] ) || empty( $args['pqc_id'] ) ) return false;
        
        global $wpdb, $pqc;
        
        $ID = absint( $args['pqc_id'] );
        
        $table = PQC_MATERIALS_TABLE;        
        
        $error = false;
        
        foreach ( $args as $key => $value ) {
            
            if ( empty( $value ) ) {
                
                $error = true;
                
                break;
                
            }
            
        }

        if ( ! $error ) {

            $author         = get_current_user_id();
            $material_name  = sanitize_text_field( $args['pqc_material_name'] );
            $material_desc  = sanitize_textarea_field( $args['pqc_material_description'] );
            $material_cost  = floatval( $args['pqc_material_cost'] );
            $date_modified  = current_time( 'mysql' );
            
            $exist = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $table WHERE ID = %d", $ID ) );
            
            if ( $exist ) {
                
                $exist = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $table WHERE material_name = %s", $material_name ) );
                
                if ( $exist == $ID || ! $exist ) {
                
                    $sql = $wpdb->prepare( "
                    UPDATE $table
                    SET author = %d, material_name = %s, material_description = %s, material_cost = %s, date_modified = %s
                    WHERE ID = %d
                    ", $author, $material_name, $material_desc, $material_cost, $date_modified, $ID );
                    
                    $update = $wpdb->query( $sql );
                
                }
                                
            }
            
            if ( isset( $update ) && $update ) {
                
                $pqc->add_notice( __( 'Material updated successfully.', 'pqc' ), 'updated' );    
                
            } else {
                
                $pqc->add_notice( __( 'Failed to update material. Perharps it does not exist. PS: Use unique Material Name.', 'pqc' ), 'pqc-update-nag update-nag' );
                
            }
            
        } else {
            
            $pqc->add_notice( __( 'All fields are required.', 'pqc' ), 'error' );
    
        }
        
    }
    
}
