<form action="" method="POST" enctype="multipart/form-data">

    <h2>PayPal</h2>
    
    <p><?php _e( 'PayPal standard sends customers to PayPal to enter their payment information.', 'pqc' ); ?></p>

    <table class="form-table">
        <tbody>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_paypal_email"><?php _e( 'PayPal Receiving Email', 'pqc' ); ?></label>
                </th>
                <td class="forminp">
                    <input id="pqc_paypal_email" name="pqc_paypal_email" value="<?php echo esc_attr( $paypal_email ); ?>" class="regular-text ltr" type="email" autocomplete="off" required>
                    <p class="description"><?php _e( 'Your PayPal Email Address', 'pqc' ); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_paypal_sandbox"><?php _e( 'PayPal Sandbox', 'pqc' ); ?></label>
                </th>
                <td class="forminp">
                    <input id="pqc_paypal_sandbox" name="pqc_paypal_sandbox" <?php checked( $paypal_sandbox, 1 ); ?> type="checkbox">
                    <p class="description"><?php _e( 'Activate Sandbox to test payments. Get a PayPal Developer account <a href="https://developer.paypal.com">here</a>.', 'pqc' ); ?></p>
                </td>
            </tr>
            
        </tbody>        
    </table>

    <p class="submit">
        <input name="pqc_save_paypal_settings" class="button-primary pqc-save-button" value="<?php _e( 'Save Changes', 'pqc' ); ?>" type="submit">
        <?php wp_nonce_field( 'pqc_save_paypal_settings' ); ?>
    </p>
    
</form>