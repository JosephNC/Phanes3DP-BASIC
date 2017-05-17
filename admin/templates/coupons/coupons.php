<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

class PQC_Coupons {
    
    private $key = 'pqc_coupon';
    
    private $default_data = array(
        'types'         =>  array(
            'fixed_cart'    => 'Cart Discount',
            'percent'       => 'Cart % Discount',
        ),        
        'amount'        => 1.00,
        'description'   => '',                
        'expiry_date'   => '',        
    );
    
    /**
    * The Constructor
    * 
    */
    public function __construct() {
        
        // Action Before initialization
        do_action( 'pqc_coupon_before_init' );
        
        $this->init();
        
        // Action After initialization
        do_action( 'pqc_coupon_after_init' );
        
    }
    
    /**
    * Initialize Coupon
    * 
    */
    private function init() {
        
        // Register Coupon Post Type
        $this->register_coupon();
        
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
        
        add_action( 'admin_head', array( $this, 'contextual_help' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 999 );

    }
    
    /**
    * Register Coupon Post type
    * 
    */
    private function register_coupon() {
        
        $labels = array(
            'name'                  => _x( 'Coupons', 'Post Type General Name', 'pqc' ),
            'singular_name'         => _x( 'Coupon', 'Post Type Singular Name', 'pqc' ),
            'menu_name'             => __( 'Coupons', 'pqc' ),
            'name_admin_bar'        => __( 'Coupons', 'pqc' ),
            'parent_item_colon'     => __( 'Parent Coupons:', 'pqc' ),
            'all_items'             => __( 'Coupons', 'pqc' ),
            'add_new_item'          => __( 'Add New Coupon', 'pqc' ),
            'add_new'               => __( 'Add Coupon', 'pqc' ),
            'new_item'              => __( 'New Coupon', 'pqc' ),
            'edit_item'             => __( 'Edit Coupon', 'pqc' ),
            'update_item'           => __( 'Update Coupon', 'pqc' ),
            'view_item'             => __( 'View Coupon', 'pqc' ),
            'view_items'            => __( 'View Coupons', 'pqc' ),
            'search_items'          => __( 'Search Coupon', 'pqc' ),
            'not_found'             => __( 'No Coupon found', 'pqc' ),
            'not_found_in_trash'    => __( 'No Coupon found in Trash', 'pqc' ),
        );
        
        $args = array(
            'label'                 => __( 'Coupons', 'pqc' ),
            'description'           => __( 'Coupon used in Phanes 3DP Calculator - Basic', 'pqc' ),
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
        
        // Action after Registering the Coupon Post Type
        do_action( 'pqc_after_register_coupon' );
        
    }

    /**
    * Modify the Title Text
    * 
    * @param mixed $title
    */
    public function enter_title_here( $title ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;
        
        return 'Coupon Code';
    }

    /**
    * Add pqc_coupon_start field
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
        <textarea id="pqc-custom-description" name="description" cols="5" rows="3" placeholder="Description (optional)"><?php echo $description; ?></textarea>
        <?php
    }

    /**
    * Coupon Meta Box to the Edit Screen
    * 
    */
    public function metabox() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        add_meta_box( 'coupon_data', 'Coupon Data', array( $this, 'metabox_html' ), $this->key, 'normal', 'high' );
        
        // Remove the Slug Meta Box
        remove_meta_box( 'slugdiv', $this->key, 'normal' );
    
    }
    
    /**
    * HTML content to add to coupon metabox
    * 
    */
    public function metabox_html() {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $data = get_post_custom();
        
        $data = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( $data[$this->key . '_data'][0] ) : $this->default_data;
        
        $types = $this->default_data['types'];
        
        $type           = isset( $data['type'] ) ? $data['type'] : 'fixed_cart';
        $amount         = $data['amount'];
        $expiry_date    = $data['expiry_date'];
        ?>
        <div class="coupon_options pqc_options_panel">
            
            <?php wp_nonce_field( $this->key . '_nonce', $this->key . '_coupon_data' ); ?>
            
            <div class="form-field type_field" style="margin: 1em 0;">
            
                <label for="type">Discount Type</label>
                
                <select id="type" name="type" style="width: 50%; float: right;">
                    <?php foreach( $types as $key => $label ) : ?>
                    <option value="<?php echo $key; ?>" <?php selected( $key, $type ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select> 
                
            </div>
            
            <div class="form-field amount_field" style="margin: 1em 0;">
                <label for="amount">Coupon Amount <span class="description"><?php _e( '(based on the discount type) ', 'pqc' ); ?></span></label>
                <input style="width: 50%; float: right" name="amount" id="amount" placeholder="1" type="text" value="<?php echo esc_attr( $amount ); ?>">
            </div>
            
            <div class="form-field expiry_date_field" style="margin: 1em 0;">
                <label for="expiry_date">Expiry Date</label>
                <input style="width: 50%; float: right" value="<?php echo esc_attr( $expiry_date ); ?>" name="expiry_date" id="expiry_date" placeholder="YYYY-MM-DD" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" type="text">
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
        
        if ( wp_verify_nonce( $_POST[$this->key . '_coupon_data'], $this->key . '_nonce' ) == false ) return;

        $type           = sanitize_text_field( $_POST['type'] );
        $amount         = floatval( $_POST['amount'] );
        $description    = sanitize_textarea_field( $_POST['description'] );
        $expiry_date    = sanitize_text_field( $_POST['expiry_date'] );
        
        $types = $this->default_data['types']; // Allowed Discount Types
        
        // Discount Type Check
        $type = ( ! array_key_exists( $type, $types ) ) ? 'fixed_cart' : $type;
        
        // Coupon Amount Check
        $amount = ( $amount >= 1 ) ? $amount : $this->default_data['amount'];
        
        // Expiry Date Check
        if ( empty( $expiry_date ) ) {
            
            $expiry_date = $this->default_data['expiry_date'];
            
        }
        elseif ( strtotime( $expiry_date ) > strtotime( current_time( 'Y-m-d' ) ) ) {
            
            $expiry_date = $expiry_date;
        }
        else {
            
            $expiry_date = current_time( 'Y-m-d' );
            
        }
        
        // Save Data
        $data = array(
            'type'          => $type,        
            'amount'        => $amount,
            'description'   => $description,                
            'expiry_date'   => $expiry_date,        
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
            __( 'Coupon updated.', 'pqc' ),
            false,
            false,
            __( 'Coupon updated.', 'pqc' ),
            false,
            __( 'Coupon published.', 'pqc' ),
            __( 'Coupon saved.', 'pqc' ),
            __( 'Coupon submitted.', 'pqc' ),
            sprintf(
                __( 'Coupon scheduled for: <strong>%1$s</strong>.', 'pqc' ),
                date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) )
            ),
            __( 'Coupon draft updated.', 'pqc' ),
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
            'updated'   => _n( '%s coupon updated.', '%s coupons updated.', $bulk_counts['updated'] ),
            'locked'    => _n( '%s coupon not updated, somebody is editing it.', '%s coupons not updated, somebody is editing them.', $bulk_counts['locked'] ),
            'deleted'   => _n( '%s coupon permanently deleted.', '%s coupons permanently deleted.', $bulk_counts['deleted'] ),
            'trashed'   => _n( '%s coupon moved to the Trash.', '%s coupons moved to the Trash.', $bulk_counts['trashed'] ),
            'untrashed' => _n( '%s coupon restored from the Trash.', '%s coupons restored from the Trash.', $bulk_counts['untrashed'] ),
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
            'title'         => 'Coupon Code',
            'description'   => __( 'Description', 'pqc' ),
            'type'          => __( 'Coupon Type', 'pqc' ),
            'amount'        => __( 'Coupon Amount', 'pqc' ),
            'expiry_date'   => __( 'Expiry Date', 'pqc' ),
            'date'          => __( 'Date', 'pqc' ),
        );
         
        return $columns;
    }
    
    /**
    * Sets custom columns for coupon
    * 
    * @param mixed $column
    */
    public function custom_columns( $column ) {
        
        if ( $this->key != get_current_screen()->post_type ) return;

        $types = $this->default_data['types'];
        
        $data = get_post_custom();
        
        $data = isset( $data[$this->key . '_data'] ) ? maybe_unserialize( maybe_unserialize( $data[$this->key . '_data'][0] ) ) : $this->default_data;
        
        switch ( $column ) {
            case 'description': 
                echo isset( $data['description'] ) ? $data['description'] : '–';
                break;
            case 'type':
                echo isset( $data['type'] ) ? $types[$data['type']] : '–';
                break;
            case 'amount':
                echo isset( $data['amount'] ) ? $data['amount'] : '–';
                break;
            case "expiry_date":
                if ( isset( $data['expiry_date'] ) ) {
                    
                    if ( strtotime( $data['expiry_date'] ) >= strtotime( current_time( 'Y-m-d' ) ) )
                        echo 'in ' . pqc_time_difference( current_time( 'Y-m-d' ), $data['expiry_date'], false, false );
                    else
                        echo pqc_time_difference( current_time( 'Y-m-d' ), $data['expiry_date'], true, true ); 
                    
                } else {
                    
                    echo '–';    
                };
                break;
        }
    }
    
    /**
    * Add Contextual Help
    * 
    */
    public function contextual_help() {

        if ( $this->key != get_current_screen()->post_type ) return;
        
        $basics = sprintf(
            '<h2><a href="%1$s">%2$s (%3$s)</a> – Coupons</h2>
            %2$s version %3$s introduces the use of Coupon for your business to help you give discounts to your buyers when the coupon codes are used.',
            PQC_URL,
            PQC_NAME,
            PQC_VERSION
        );

        $basics = array(
            'id'      => 'coupon_basics',
            'title'   => 'Coupon Basics',
            'content' => $basics
        );

        get_current_screen()->add_help_tab( $basics );

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

new PQC_Coupons();