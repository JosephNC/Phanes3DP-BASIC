<div class="wrap pqc-wrapper about-wrap pqc-about-wrap">

	<h1><?php echo __( 'Welcome to ', 'pqc' ) . PQC_NAME; ?></h1>

	<div class="about-text"><?php printf( __( 'Thank you for installing! %s is a powerful STL object Calculator plugin that allows your Customers to upload their STL files, get instant quotes, and order online seamlessly by paying with PayPal on your sites with WordPress.', 'pqc' ), PQC_NAME ); ?></div>

	<div class="wp-badge pqc-badge">Version <?php echo PQC_VERSION; ?></div>
    
    <div class="about-text" style="color: rgb(224, 21, 21); font-style: oblique; margin: 0px; padding: 0px; line-height: 1; text-decoration: underline; font-size: 18.3px;">
        <?php _e( 'Note: You will be charged 8% commission + paypal processing fees on every order with BASIC version', 'pqc' ); ?>
    </div>
	
	<p class="pqc-admin-notice pqc-actions">
		<a href="<?php echo admin_url( 'admin.php?page=pqc-settings-page' ); ?>" class="button button-primary"><?php _e( 'Settings', 'pqc' ); ?></a>
	</p>
    
    <h2 class="nav-tab-wrapper pqc-nav-tab-wrapper">
    
    <?php
        
        foreach ( $pqc_getting_started_tabs as $key => $label ) {
            
            $class = ( $key == $string ) ? 'nav-tab nav-tab-active' : 'nav-tab';
            
            $link = admin_url( 'admin.php?page=pqc-' . $key );
            
            echo '<a href="' . $link . '" class="' . $class .'">' . $label . '</a>';
            
        }
    
    ?>
    
    </h2>