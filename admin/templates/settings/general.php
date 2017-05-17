<form action="" method="POST" enctype="multipart/form-data">

    <h2><?php _e( 'General Options', 'pqc' ); ?></h2>

    <table class="form-table">
        <tbody>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_max_file_size"><?php _e( 'Max. File Size', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-number">
                    <input id="pqc_max_file_size" name="pqc_max_file_size" style="width:60px;" value="<?php echo esc_attr( $max_file_size ); ?>" min="0" type="number" required> MB
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_file_max_stay"><?php _e( 'Delete files older than', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-number">
                    <input id="pqc_file_max_stay" name="pqc_file_max_stay" style="width: 60px;" value="<?php echo esc_attr( $max_file_stay ); ?>" min="1" type="number" required> day(s)
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_file_max_upload"><?php _e( 'Max. File to upload simultaneously', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-number">
                    <input id="pqc_file_max_upload" name="pqc_file_max_upload" style="width: 60px;" value="<?php echo esc_attr( $max_file_upload ); ?>" min="1" type="number" required> File(s)
                </td>
            </tr>
            
        </tbody>        
    </table>

    <h2><?php _e( 'Currency Position', 'pqc' ); ?></h2>
    <p><?php _e( 'This options affect how prices are displayed on the frontend.', 'pqc' ); ?></p>

    <table class="form-table">
        <tbody>
        
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_initial_price"><?php _e( 'Initial Price', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-number">
                    <input id="pqc_initial_price" name="pqc_initial_price" style="width:100px;" value="<?php echo esc_attr( $initial_price ); ?>" step="any" type="number" required>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_currency"><?php _e( 'Currency', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <?php
                        $currencies = array(
                            'AUD' => 'Australian dollar ($)',
                            'CNY' => 'Chinese yuan (¥)',
                            'EUR' => 'Euro (€)',
                            'GBP' => 'Pound sterling (£)',
                            'JPY' => 'Japanese yen (¥)',
                            'USD' => 'United States dollar ($)',
                        );
                        
                        $options = '';
                        
                        foreach ( $currencies as $key => $value ) {
                            
                            $selected = selected( $currency, $key, false );
                            
                            $options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $value ) . '</option>';
                            
                        }
                        
                        echo '<select id="pqc_currency" name="pqc_currency">' . $options . '</select>';
                    ?>
                </td>
            </tr>            

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_currency_pos"><?php _e( 'Currency Position', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-select">
                    <?php
                        $currencies = array(
                            'AUD' => '$',
                            'CNY' => '¥',
                            'EUR' => '€',
                            'GBP' => '£',
                            'JPY' => '¥',
                            'USD' => '$',
                        );
                        
                        $symbol = $currencies[$currency];
                        
                        $currency_poss = array(
                            'left'          => 'Left (' . $symbol . '99.99)',
                            'right'         => 'Right (99.99' . $symbol . ')',
                            'left_space'    => 'Left with space (' . $symbol . ' 99.99)',
                            'right_space'   => 'Right with space (99.99 ' . $symbol . ')',
                        );
                        
                        $options = '';
                        
                        foreach ( $currency_poss as $key => $value ) {
                            
                            $selected = selected( $currency_pos, $key, false );
                            
                            $options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $value ) . '</option>';
                            
                        }
                        
                        echo '<select id="pqc_currency_pos" name="pqc_currency_pos">' . $options . '</select>';
                    ?>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_price_thousand_sep"><?php _e( 'Thousand Seperator', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-text">
                    <input id="pqc_price_thousand_sep" name="pqc_price_thousand_sep" style="width:50px;" value="<?php echo esc_attr( $thousand_sep ); ?>" type="text" required>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_price_decimal_sep"><?php _e( 'Decimal Seperator', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-text">
                    <input id="pqc_price_decimal_sep" name="pqc_price_decimal_sep" style="width:50px;" value="<?php echo esc_attr( $decimal_sep ); ?>" type="text" required>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="pqc_price_num_decimals"><?php _e( 'Number of Decimals', 'pqc' ); ?></label>
                </th>
                <td class="forminp forminp-number">
                    <input id="pqc_price_num_decimals" name="pqc_price_num_decimals" style="width:50px;" value="<?php echo esc_attr( $num_decimals ); ?>" min="1" type="number" required>
                </td>
            </tr>
            
        </tbody>        
    </table>
    <p class="submit">
        <input name="pqc_save_general_settings" class="button-primary pqc-save-button" value="<?php esc_attr_e( 'Save Changes', 'pqc' ); ?>" type="submit">
        <?php wp_nonce_field( 'pqc_save_general_settings' ); ?>
    </p>

</form>