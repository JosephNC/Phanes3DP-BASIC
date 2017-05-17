<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

class PQC_Shipping_Options {
    
    private $key = 'pqc_shipping_option';
    
    private $default_data = array(        
        'amount'        => 1.00,
        'description'   => '',        
    );
    
    /**
    * The Constructor
    * 
    */
    public function __construct() {
        
        // Action Before initialization
        do_action( 'pqc_shipping_option_before_init' );
        
        $this->init();
        
        // Action After initialization
        do_action( 'pqc_shipping_option_after_init' );
        
    }
    
    /**
    * Initialize Shipping Option
    * 
    */
    private function init() {
        
        // Register Shipping Option Post Type
        $this->register_shipping_option();
        
        add_action( 'add_meta_boxes_' . $this->key, array( $this, 'metabox' ) ); 
        
        add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
        
        add_action( 'edit_form_after_title',  array( $this, 'edit_form_after_title' ) );
        
        add_action( 'edit_form_after_editor',  array( $this, 'edit_form_after_editor' ) );
        
        add_action( 'save_post_' . $this->key, array( $this, 'metabox_save' ), 10, 3 );
        
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
        
        add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_updated_messages' ), 10, 2 );
        
        add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 1 );
        
        add_filter( 'manage_edit-' . $this->key . '_columns', array( $this, 'edit_columns' ) );

        add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 999 );

    }
    
    /**
    * Register Shipping Option Post type
    * 
    */
    private function register_shipping_option() {
        
        $labels = array(
            'name'                  => _x( 'Shipping Options', 'Post Type General Name', 'pqc' ),
            'singular_name'         => _x( 'Shipping Option', 'Post Type Singular Name', 'pqc' ),
            'menu_name'             => __( 'Shipping Options', 'pqc' ),
            'name_admin_bar'        => __( 'Shipping Options', 'pqc' ),
            'parent_item_colon'     => __( 'Parent Shipping Options:', 'pqc' ),
            'all_items'             => __( 'Shipping Options', 'pqc' ),
            'add_new_item'          => __( 'Add New Shipping Option', 'pqc' ),
            'add_new'               => __( 'Add Shipping Option', 'pqc' ),
            'new_item'              => __( 'New Shipping Option', 'pqc' ),
            'edit_item'             => __( 'Edit Shipping Option', 'pqc' ),
            'update_item'           => __( 'Update Shipping Option', 'pqc' ),
            'view_item'             => __( 'View Shipping Option', 'pqc' ),
            'view_items'            => __( 'View Shipping Options', 'pqc' ),
            'search_items'          => __( 'Search Shipping Option', 'pqc' ),
            'not_found'             => __( 'No Shipping Option found', 'pqc' ),
            'not_found_in_trash'    => __( 'No Shipping Option found in Trash', 'pqc' ),
        );
        
        $args = array(
            'label'                 => __( 'Shipping Options', 'pqc' ),
            'description'           => __( 'Shipping Option used in Phanes 3DP Calculator - Basic', 'pqc' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'pqc-settings-page',
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,        
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'rewrite'               => false,
        );
        
        register_post_type( $this->key, $args );
        
        // Action after Registering the Shipping Option Post Type
        do_action( 'pqc_after_register_shipping_option' );
        
    }

    /**
    * Modify the Title Text
    * 
    * @param mixed $title
    */
    public function enter_title_here( $title ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        return 'Shipping Option Title';
    }

    /**
    * Add pqc_shipping_option_start field
    * 
    */
    public function edit_form_after_title() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        ?>
        <input name="<?php echo $this->key; ?>_start" value="set" type="hidden">
        <?php
    }
    
    /**
    * Add Extra Field after Editor Form
    * 
    */
    public function edit_form_after_editor() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $data = get_post_custom();
        
        $data = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( $data[$this->key . '_data'][0] ) : $this->default_data;
        
        $description = isset( $data['description'] ) ? $data['description'] : '';
        
        ?>
        <textarea id="pqc-custom-description" name="description" cols="5" rows="7" placeholder="Description (optional)"><?php echo $description; ?></textarea>
        <?php
    }

    /**
    * Shipping Option Meta Box to the Edit Screen
    * 
    */
    public function metabox() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        add_meta_box( 'shipping_option_data', 'Shipping Option Data', array( $this, 'metabox_html' ), $this->key, 'side', 'high' );
        
        // Remove the Slug Meta Box
        remove_meta_box( 'slugdiv', $this->key, 'normal' );
    
    }
    
    /**
    * HTML content to add to shipping option metabox
    * 
    */
    public function metabox_html() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $data = get_post_custom();
        
        $data = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( $data[$this->key . '_data'][0] ) : $this->default_data;

        $amount = $data['amount'];
        
        $settings = maybe_unserialize( get_option( PQC_SETTING_OPTIONS ) );
        
        $currency = $settings['pqc_general_settings']['currency'];
        
        ?>
        <div class="shipping_option_options pqc_options_panel">
            
            <?php wp_nonce_field( $this->key . '_nonce', $this->key . '_shipping_option_data' ); ?>
            
            <div class="form-field amount_field" style="margin: 1em 0;">
                <label for="amount">Shipping Cost <span class="description"><?php echo esc_html( "($currency)" ); ?></span></label>
                <input style="width: 50%; float: right" name="amount" id="amount" placeholder="1" type="text" value="<?php echo esc_attr( $amount ); ?>">
            </div>
            
        </div>
        <?php
        
    }

    /**
    * Save post metadata when a post is saved
    * @param int     $post_ID Post ID.
    * @param WP_Post $post    Post object.
    * @param bool    $update  Whether this is an existing post being updated or not.
    */
    public function metabox_save( $post_id, $post, $update ) {
        
        if ( ! isset( $_POST[$this->key . '_start'] ) ) return;
        
        if ( $post->post_type != $this->key ) return;
        
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || $post->post_status == 'auto-draft' ) return;
        
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
        
        if ( wp_verify_nonce( $_POST[$this->key . '_shipping_option_data'], $this->key . '_nonce' ) == false ) return;

        $amount         = floatval( $_POST['amount'] );
        $description    = sanitize_textarea_field( $_POST['description'] );

        // Shipping Option Amount Check
        $amount = ( $amount >= 1 ) ? $amount : $this->default_data['amount'];
        
        // Save Data
        $data = array(
            'description'   => $description,                
            'amount'        => $amount,        
        );
        
        update_post_meta( $post_id, $this->key . '_data', $data );        
    }
    
    /**
    * Sets Messages for update
    * 
    * @param mixed $messages
    */
    public function updated_messages( $messages ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $post = get_post();

        $messages[$this->key] = array(
            '',
            __( 'Shipping Option updated.', 'pqc' ),
            false,
            false,
            __( 'Shipping Option updated.', 'pqc' ),
            false,
            __( 'Shipping Option published.', 'pqc' ),
            __( 'Shipping Option saved.', 'pqc' ),
            __( 'Shipping Option submitted.', 'pqc' ),
            sprintf(
                __( 'Shipping Option scheduled for: <strong>%1$s</strong>.', 'pqc' ),
                date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
            ),
            __( 'Shipping Option draft updated.', 'pqc' ),
        );

        return $messages;
    }
    
    /**
    * Sets Messages for Bulk Update
    * 
    * @param mixed $bulk_messages
    * @param mixed $bulk_counts
    */
    public function bulk_updated_messages( $bulk_messages, $bulk_counts ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $bulk_messages[$this->key] = array(
            'updated'   => _n( '%s shipping option updated.', '%s shipping options updated.', $bulk_counts['updated'] ),
            'locked'    => _n( '%s shipping option not updated, somebody is editing it.', '%s shipping options not updated, somebody is editing them.', $bulk_counts['locked'] ),
            'deleted'   => _n( '%s shipping option permanently deleted.', '%s shipping options permanently deleted.', $bulk_counts['deleted'] ),
            'trashed'   => _n( '%s shipping option moved to the Trash.', '%s shipping options moved to the Trash.', $bulk_counts['trashed'] ),
            'untrashed' => _n( '%s shipping option restored from the Trash.', '%s shipping options restored from the Trash.', $bulk_counts['untrashed'] ),
        );

        return $bulk_messages;

    }
    
    public function row_actions( $actions ) {
        
        if ( $this->key != get_current_screen()->post_type ) return $actions;
        
        unset( $actions['inline hide-if-no-js'] );
        
        return $actions;
    }
    
    /**
    * Modify colums
    * 
    * @param mixed $columns
    */
    public function edit_columns( $columns ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        $columns = array(
            'cb'            => '<input type="checkbox">',
            'title'         => 'Shipping Option',
            'description'   => __( 'Description', 'pqc' ),
            'amount'        => __( 'Shipping Cost', 'pqc' ),
            'date'          => __( 'Date', 'pqc' ),
        );
         
        return $columns;
    }
    
    /**
    * Sets custom columns for shipping option
    * 
    * @param mixed $column
    */
    public function custom_columns( $column ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $data = get_post_custom();
        
        $data = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        
        switch ( $column ) {
            case 'description': 
                echo isset( $data['description'] ) ? $data['description'] : '–';
                break;
            case 'amount':
                echo isset( $data['amount'] ) ? pqc_money_format( $data['amount'] ) : '–';
                break;
        }
    }

    /**
    * Enqueue/Dequeue Admin Scripts
    * 
    */
    public function admin_scripts() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        wp_dequeue_script( PQC_NAME . ' URL MOD' );
        
    }

}

new PQC_Shipping_Options();