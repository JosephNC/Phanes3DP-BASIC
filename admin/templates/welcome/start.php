    <div class="changelog">
	    <h3><?php _e( 'Getting Started', 'pqc' ); ?></h3>
	    <div class="feature-section">

		    <p><?php printf( __( '%1$s has been designed to be easy to use and you shouldn’t run into any difficulties. However, the plugin contains lots of different elements so we have created the following page to help you get started with %1$s.', 'pqc' ), PQC_NAME ); ?></p>
		    <p>
            <?php if ( empty( $options['pqc_checkout_settings']['paypal_email'] ) ) : ?>
            <a href="<?php echo admin_url( 'admin.php?page=pqc-settings-page&tab=checkout' ); ?>" class="button button-primary"><?php _e( 'Add PayPal Email', 'pqc' ); ?></a>
            <?php _e( 'to start receiving payments.' ); endif; ?>
            </p>
	    </div>
    </div>

    <div class="changelog">
	    
	    <div class="feature-section under-the-hood two-col">
		    
		    <div class="col">
			    <h4><?php _e( 'Automatically installed pages', 'pqc' ); ?></h4>
			    <p><?php _e( 'Upon activation the plugin will install 3 core pages. These pages are required for the plugin to function correctly and cannot be deleted.', 'pqc' ); ?></p>
			    <p>
				    <ul>
                        <li><a href="<?php echo get_permalink( pqc_page_exists( 'pqc-upload' ) ); ?>" target="_blank">Upload</a></li>
                        <li><a href="<?php echo get_permalink( pqc_page_exists( 'pqc-cart' ) ); ?>" target="_blank">Cart</a></li>
                        <li><a href="<?php echo get_permalink( pqc_page_exists( 'pqc-checkout' ) ); ?>" target="_blank">Checkout</a></li>
					    <li><a href="<?php echo get_permalink( pqc_page_exists( 'pqc-orders' ) ); ?>" target="_blank">Orders</a></li>
				    </ul>
			    </p>
		    </div>

		    <div class="col">
			    <h4><?php _e( 'Getting Started', 'pqc' ); ?></h4>
			    <p><?php _e( 'The plugin has several different elements in the WordPress admin that allow you to customize the plugin function just the way you like:', 'pqc' ); ?></p>
			    <p>
				    <ul>
                        <li><a href="<?php echo admin_url( 'admin.php?page=pqc-settings-page' ); ?>" target="_blank">Settings</a></li>
                        <li><a href="<?php echo admin_url( 'admin.php?page=pqc-materials-page' ); ?>" target="_blank">Materials</a></li>
                        <li><a href="<?php echo admin_url( 'edit.php?post_type=pqc_order' ); ?>" target="_blank">Orders</a></li>
                        <li><a href="<?php echo admin_url( 'edit.php?post_type=pqc_coupon' ); ?>" target="_blank">Coupon</a></li>
					    <li><a href="<?php echo admin_url( 'edit.php?post_type=pqc_shipping_option' ); ?>" target="_blank">Shipping Option</a></li>
				    </ul>
			    </p>
		    </div>
		    
	    </div>
	    
    </div>