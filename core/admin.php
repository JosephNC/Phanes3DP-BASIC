<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) die;

if ( ! class_exists( 'PQC_Admin' ) ) : 

final class PQC_Admin {
    
    private $slug = 'pqc-settings-page';
    
    /**
    * The Constructor
    * 
    */
    public function __construct() {

        add_action( 'admin_init', array( $this, 'admin_init' ) );

        add_action( 'admin_menu',  array( &$this, 'admin_menu' ), 5 );

        add_filter( 'custom_menu_order', array( &$this, 'submenu_order' ) );

        add_action( 'admin_enqueue_scripts',  array( &$this, 'admin_scripts' ), 10 );

    }
    
    public function admin_init() {
        
        if ( isset( $_GET['page'] ) && in_array( $_GET['page'], ['pqc-start', 'pqc-about' ] ) ) return;
        
        if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'checkout' ) return;
        
        global $pqc;
    
        $pqc->add_notice(
            sprintf(
                __( '<strong>%s</strong> charges 8%% off on every order.', 'pqc' ),
                PQC_NAME
            ), 'error', false
        );
        
        if ( ! pqc_is_paypal_ready() ) {

            $pqc->add_notice(
                sprintf(
                    __( '<strong>%s</strong> needs your PayPal email. <a href="%s">Add email</a>', 'pqc' ),
                    PQC_NAME,
                    admin_url( 'admin.php?page=pqc-settings-page&tab=checkout&section=paypal' )
                ), 'error', false
            );
            
        }
        
    }
    
    /**
    * Set Screen
    * 
    * @param mixed $status
    * @param mixed $option
    * @param mixed $value
    */
    public static function set_screen( $status, $option, $value ) {
        
        return $value;

    }

    /**
    * Admin Menu
    * 
    */
	public function admin_menu() {
    
        // Include/Require files
        $this->includes();
        
        $settings = add_menu_page(
            '3DPC Basic',
            '3DPC Basic',
            'manage_options',
            $this->slug,
            array( $this, 'settings' ),
            PQC_URL . 'assets/images/icon.png',
            42.28473
        );
        
        $pqc_submenus = array(
            'materials_load'    => array( 'Materials', 'Materials', 'manage_options', 'pqc-materials-page', array( 'PQC_Materials', 'materials_page' ) ),
        );
        
        $pqc_load_submenus = array(
            'materials_load'    => array( 'PQC_Materials', 'materials_screen_option' ),
        );
        
        $pqc_submenus = apply_filters( 'pqc_admin_submenus', $pqc_submenus );
        
        $pqc_load_submenus = apply_filters( 'pqc_admin_load_submenus', $pqc_load_submenus );
        
        foreach( $pqc_submenus as $key => $pqc_submenu ) {
            
            if ( ! is_array( $pqc_submenu ) || count( $pqc_submenu ) != 5 ) continue;
            
            array_unshift( $pqc_submenu, $this->slug );
            
            $load = call_user_func_array( 'add_submenu_page', $pqc_submenu );
            
            if ( isset( $pqc_load_submenus[$key] ) ) add_action( "load-$load", $pqc_load_submenus[$key] );
            
        }
        
        add_submenu_page( '_pqc_start_doesnt_exist', __( 'Getting Started | ', 'pqc' ) . PQC_NAME, '', 'manage_options', 'pqc-start', [$this, 'getting_started'] );
        
        add_submenu_page( '_pqc_about_doesnt_exist', __( 'Getting Started | ', 'pqc' ) . PQC_NAME, '', 'manage_options', 'pqc-about', [$this, 'getting_started'] );
    
	}
    
    /**
    * Reorder submenus
    * 
    * @param mixed $menu_order
    */
    public function submenu_order( $menu_order ) {
        
        global $submenu;
        
        $slug = $this->slug;
        
        $submenu[$slug][0][0] = 'Settings'; // Replace The Default Name

        // Reorder Submenus
        $args = array(
            $submenu[$slug][2], // Orders
            $submenu[$slug][3], // Coupons
            $submenu[$slug][1], // Materials
            $submenu[$slug][4], // Shipping Options
            $submenu[$slug][0], // Settings
        );

        $submenu[$slug] = $args + $submenu[$slug];

        return $submenu[$slug];
        
    }
    
    /**
    * Include/Require files in the admin page
    * 
    */
    private function includes() {
        
        require_once PQC_PATH . 'admin/templates/materials/materials.php';
        
        require_once PQC_PATH . 'admin/templates/orders/orders.php';
        
        require_once PQC_PATH . 'admin/templates/coupons/coupons.php';
        
        require_once PQC_PATH . 'admin/templates/shipping/shipping.php';
        
    }
    
    /**
    * Load the getting Started template
    * 
    */
    public function getting_started() {
        
        global $pqc_getting_started_tabs;
        
        if ( isset( $_GET['page'] ) && substr( $_GET['page'], 0, 3 ) == 'pqc' ) {
            
            $options = maybe_unserialize( get_option( PQC_SETTING_OPTIONS, false ) );
            
            $string = substr( $_GET['page'], 4 );
            
            if ( ! array_key_exists( $string, $pqc_getting_started_tabs ) ) return;
            
            include_once PQC_PATH . 'admin/templates/welcome/header.php';
            
            include_once PQC_PATH . 'admin/templates/welcome/' . $string . '.php';
            
            include_once PQC_PATH . 'admin/templates/welcome/footer.php';
            
        }
        
    }
    
    /**
    * Load the Settings Page
    * 
    */
    public function settings() {
        
        global $pqc_settings_tabs;

        $current = ( ! isset( $_GET['tab'] ) && empty( $_GET['tab'] ) ) ? 'general' : esc_attr( $_GET['tab'] );
        
        $section = ( ! isset( $_GET['section'] ) && empty( $_GET['section'] ) ) ? '' : esc_attr( $_GET['section'] );
        
        ?>
        <div class="wrap pqc-wrapper">
        
            <?php
            
            // Display Settings Tabs
            $this->settings_tabs( $current, $section );
            
            // Run Callback function
            $tab = $pqc_settings_tabs[$current];
                
            // Check if we have section and load callback
            if ( ! empty( $section ) && isset( $tab['sections'] ) && array_key_exists( $section, $tab['sections'] ) ) {
                
                $callback = $tab['sections'][$section]['callback'];
                
            } else {
                
                $callback = $tab['callback'];
                
            }
            
            if ( is_array( $callback ) ) {
                
                if ( is_callable( $callback, true ) ) call_user_func( array( $callback[0], $callback[1] ) );
            }
            else {
                
                if ( is_callable( $callback, true ) ) call_user_func( $callback );
            }
            
            ?>

        </div>
        <?php      
        
    }
    
    /**
    * Prepare and Load the settings tabs and sections
    * 
    * @param mixed $current
    * @param mixed $section
    */
    public function settings_tabs( $current = 'general', $section = '' ) {
        
        global $pqc_settings_tabs;
        
        if ( ! array_key_exists( $current, $pqc_settings_tabs ) ) $current = 'general';
        
        $self = admin_url() . 'admin.php?page=pqc-settings-page';
        
        $nav = '';
        
        foreach ( $pqc_settings_tabs as $key => $data ) {
            
            $label = $data['label'];
            
            $class = ( $key == $current ) ? 'nav-tab nav-tab-active' : 'nav-tab';
            
            $checkout_options_section = ( ! isset( $_GET['section'] ) && $current != 'checkout' && $key == 'checkout' ) ? '&section=checkout_options' : '';
            
            $link = '&tab=' . $key . $checkout_options_section;
            
            $nav .= '<a href="' . $self . $link . '" class="' . $class .'">' . $label . '</a>';
            
        }
        
        $content = '<nav class="nav-tab-wrapper pqc-nav-tab-wrapper">' . $nav . '</nav>';
        
        echo $content;
        
        // If there're subsections, let's display them.
        if ( ! empty( $pqc_settings_tabs[$current]['sections'] ) ) {
            
            $nav = '';
            
            $last_key = array_keys( $pqc_settings_tabs[$current]['sections'] );
            
            $last_key = end( $last_key );
            
            foreach( $pqc_settings_tabs[$current]['sections'] as $key => $data ) {

                $label = $data['label'];
                
                $class = ( $key == $section ) ? 'current' : '';
                
                $link = '&tab=' . $current . '&section=' . $key;
                
                $pipe = ( $last_key == $key ) ? '' : '&nbsp;|&nbsp;';
                
                $nav .= '<li><a href="' . $self . $link . '" class="' . $class .'">' . $label . '</a>' . $pipe . '</li>';
                
            }
            
            $content = '<ul class="subsubsub">' . $nav . '</ul>';
            
            echo $content;
            
        }
        
        echo '<br class="clear">';
        
    }
    
    /**
    * Load the General Tab content
    * 
    */
    public function general_tab() {
        
        global $pqc;
        
        $settings = get_option( PQC_SETTING_OPTIONS, array() );
        
        $options = $settings['pqc_general_settings'];
        
        if ( isset( $_POST['pqc_save_general_settings'] ) ) {
            
            check_admin_referer( 'pqc_save_general_settings' );

            $error = false;
            
            foreach ( $_POST as $name => $value ) {
                
                if ( ! strpos( $name, 'pqc_' ) ) continue;
                
                if ( $value == '' ) {
                    
                    $error = true;
                    
                    break;
                    
                }
                
            }
            
            if ( ! $error ) {
                
                $max_filesize   = (int) $_POST['pqc_max_file_size'];
                $max_filestay   = (int) $_POST['pqc_file_max_stay'];
                $max_fileupload = (int) $_POST['pqc_file_max_upload'];
                $initial_price  = (float) $_POST['pqc_initial_price'];
                $currency       = sanitize_text_field( strtoupper( $_POST['pqc_currency'] ) );
                $currency_pos   = sanitize_text_field( $_POST['pqc_currency_pos'] );
                $thousand_sep   = sanitize_text_field( $_POST['pqc_price_thousand_sep'] );
                $decimal_sep    = sanitize_text_field( $_POST['pqc_price_decimal_sep'] );
                $num_decimals   = (int) $_POST['pqc_price_num_decimals'];
                
                // Validation
                if ( ! in_array( $currency_pos, [ 'left', 'left_space', 'right', 'right_space' ] ) ) {
                    
                    $error = true;
                    
                    $pqc->add_notice(
                        sprintf(
                            __( 'Invalid currency position selected.', 'pqc' ),
                            PQC_NAME
                        ),
                    'error' );
                    
                }
                
                if ( ! $error ) {
                    
                    $args = wp_parse_args( array(
                        'max_file_size'     => $max_filesize,
                        'max_file_stay'     => $max_filestay,
                        'max_file_upload'   => $max_fileupload,
                        'initial_price'     => $initial_price,
                        'currency'          => $currency,
                        'currency_pos'      => $currency_pos,
                        'thousand_sep'      => $thousand_sep,
                        'decimal_sep'       => $decimal_sep,
                        'num_decimals'      => $num_decimals,
                    ), $options );
                    
                    $settings['pqc_general_settings'] = $args;
                    
                    $update = update_option( PQC_SETTING_OPTIONS, $settings );
                    
                    if ( $update || array_diff_assoc( $args, $options ) === array_diff_assoc( $options, $args ) ) {
                        
                        $pqc->add_notice( __( '<strong> Done! </strong> Settings saved.', 'pqc' ), 'updated' );
                        
                        $options = $args;
                        
                    } else {
                        
                        $pqc->add_notice( __( '<strong> Failed! </strong> Error occurred.', 'pqc' ), 'error' );
                        
                    }
                    
                }
                    
            }
            else {
                
                $pqc->add_notice( __( '<strong> Doing wrong! </strong> All fields are required.', 'pqc' ), 'error' );
                
            }    
        }
        
        extract( $options );
        
        require_once PQC_PATH . 'admin/templates/settings/general.php';
            
    }

    /**
    * Load the Checkout Tab content
    * 
    */
    public function checkout_section() {
        
        global $pqc;
        
        $settings = get_option( PQC_SETTING_OPTIONS, array() );
        
        $options = $settings['pqc_checkout_settings'];
        
        if ( isset( $_POST['pqc_save_checkout_settings'] ) ) {
            
            check_admin_referer( 'pqc_save_checkout_settings' );
            
            $error = false;

            foreach ( $_POST as $name => $value ) {
                
                if ( ! strpos( $name, 'pqc_' ) ) continue;
                
                if ( $value == '' ) {
                    
                    $error = true;
                    
                    break;
                    
                }
                
            }
            
            if ( ! $error ) {
                
                $args = wp_parse_args( array(
                    'checkout_option' =>
                        isset( $_POST['pqc_checkout_option'] )
                        && $_POST['pqc_checkout_option'] >= 1
                        && $_POST['pqc_checkout_option'] <= 3 ? intval( $_POST['pqc_checkout_option'] ) : 1,
                    'shop_location' =>
                        isset( $_POST['pqc_shop_location'] )
                        && ( $_POST['pqc_shop_location'] == 1 || $_POST['pqc_shop_location'] == 2 ) ? intval( $_POST['pqc_shop_location'] ) : 1,
                ), $options );
                
                $settings['pqc_checkout_settings'] = $args;
                
                $update = update_option( PQC_SETTING_OPTIONS, $settings );
                
                if ( $update || array_diff_assoc( $args, $options ) === array_diff_assoc( $options, $args ) ) {
                    
                    $pqc->add_notice( __( '<strong> Done! </strong> Settings saved.', 'pqc' ), 'updated' );
                    
                    $options = $args;
                    
                } else {
                    
                    $pqc->add_notice( __( '<strong> Failed! </strong> Error occurred.', 'pqc' ), 'error' );
                    
                }
                    
            }
            else {
                
                $pqc->add_notice( __( '<strong> Doing wrong! </strong> All fields are required.', 'pqc' ), 'error' );
                
            }
        }
        
        extract( $options );
        
        require_once PQC_PATH . 'admin/templates/settings/checkout.php';
            
    }
    
    /**
    * Load the PayPal Tab content
    * 
    */
    public function paypal_section() {
        
        global $pqc;
        
        $settings = get_option( PQC_SETTING_OPTIONS, array() );
        
        $options = $settings['pqc_checkout_settings'];
        
        if ( isset( $_POST['pqc_save_paypal_settings'] ) ) {
            
            check_admin_referer( 'pqc_save_paypal_settings' );
            
            $error = false;

            foreach ( $_POST as $name => $value ) {
                
                if ( ! strpos( $name, 'pqc_' ) ) continue;
                
                if ( $value == '' ) {
                    
                    $error = true;
                    
                    break;
                    
                }
                
            }
            
            if ( ! $error ) {
                
                $paypal_email = sanitize_email( $_POST['pqc_paypal_email'] );
                
                $args = wp_parse_args( array(
                    'paypal_email'      => $paypal_email,
                    'paypal_sandbox'    => isset( $_POST['pqc_paypal_sandbox'] ) ? 1 : 0,
                ), $options );
                
                $settings['pqc_checkout_settings'] = $args;
                
                $update = update_option( PQC_SETTING_OPTIONS, $settings );
                
                if ( $update || array_diff_assoc( $args, $options ) === array_diff_assoc( $options, $args ) ) {
                    
                    $pqc->add_notice( __( '<strong> Done! </strong> Settings saved.', 'pqc' ), 'updated' );
                    
                    $options = $args;
                    
                } else {
                    
                    $pqc->add_notice( __( '<strong> Failed! </strong> Error occurred.', 'pqc' ), 'error' );
                    
                }
                    
            }
            else {
                
                $pqc->add_notice( __( '<strong> Doing wrong! </strong> All fields are required.', 'pqc' ), 'error' );
                
            }
        }
        
        extract( $options );
        
        require_once PQC_PATH . 'admin/templates/settings/paypal.php';    
    }
    
    /**
    * Enqueue Admin Scripts
    * 
    */
    public function admin_scripts() {
        
        $screen = get_current_screen();
        
        if ( ! isset( $screen->id ) ) return;
        
        if ( strstr( $screen->id, 'pqc' ) == false ) return;
        
        /**
        * Enqueue Styles
        */
        wp_enqueue_style( PQC_NAME, PQC_URL . 'assets/css/admin.css', array(), PQC_VERSION, 'all' );

        wp_enqueue_style( PQC_NAME . '-jquery-ui-base', PQC_URL . 'assets/css/jquery-ui-base/jquery-ui.css', '', '1.12.1', 'all' );
        
        /**
        * Enqueue Scripts
        */
        wp_enqueue_media();
        
        wp_enqueue_script( 'jquery-ui-datepicker' );
        
        wp_enqueue_script( PQC_NAME . ' URL SCRIPT', PQC_URL . 'assets/js/uri.min.js', array(), PQC_VERSION, true );
        
        wp_enqueue_script( PQC_NAME . ' URL MOD', PQC_URL . 'assets/js/urlmod.js', array( PQC_NAME . ' URL SCRIPT' ), PQC_VERSION, true );
        
        wp_enqueue_script( PQC_NAME, PQC_URL . 'assets/js/admin.js', array(), PQC_VERSION, true );
        
        /**
        * Whether to do the the url modification or not
        * 
        * @var mixed
        */
        $do_url_mod = apply_filters( 'pqc_do_url_mod', true );

        wp_localize_script( PQC_NAME . ' URL MOD', 'PQC_Admin', array(
                'do_url_mod' => $do_url_mod === false ? false : true,
            )
        );
        
    }

}

endif;

if ( is_admin() ) new PQC_Admin();