<div id="pqc-wrapper" class="container">

    <?php if ( ( isset( $error ) && $error ) || ( isset( $notice ) && $notice ) ) : ?>
    
    <header class="codrops-header">
        <?php if ( isset( $error ) && $error ) : ?>
        <div class="error"><p><?php echo $error_msg ?></p></div>
        <?php elseif ( $notice ) : ?>
        <div class="notice"><p><?php echo $notice_msg ?></p></div>
        <?php endif; ?>
    </header>
    
    <?php elseif( isset( $the_items ) && ! empty( $the_items ) ) : ?>
    
    <header class="codrops-header">
        <div id="coupon-msg" style="margin-bottom: 2%;">
            <?php if ( isset( $coupon_msg ) ) : ?>
            <div class="<?php echo $coupon_type; ?>"><p><?php echo $coupon_msg; ?></p></div>
            <?php endif; ?>
        </div>
        <div id="msg"></div>
    </header>

    <div class="content">
    
        <div id="content-full">
            <div class="item-total-heading">
                <h1 style="padding: 0; float: left; width: 50%;"><?php printf( _n( '%1$s item in cart', '%1$s items in cart', $total_item, 'pqc' ), $total_item ); ?></h1>
                <p style="float: right; width: 50%;">
                    <a id="empty-cart" href="#"><?php _e( 'Empty cart', 'pqc' ); ?></a>
                    <a id="add-more-item" href="<?php echo get_permalink( pqc_page_exists( 'pqc-upload' ) ); ?>"><?php _e( 'Add more item', 'pqc' ); ?></a>
                </p>
            </div>
            
            <form id="cart-form" method="POST" enctype="multipart/form-data">
            
                <table class="cart">
                    <thead>
                        <th id="canvas"><?php _e( 'Item', 'pqc' ); ?></th>
                        <th id="item"><?php _e( 'Details', 'pqc' ); ?></th>
                        <th id="quantity"><?php _e( 'Qty x Unit price', 'pqc' ); ?></th>
                        <th id="material"><?php _e( 'Material', 'pqc' ); ?></th>
                        <th id="total"><?php _e( 'Total', 'pqc' ); ?></th>
                        <th id="trash"></th>
                    </thead>
                    
                    <tbody>
                        <?php $i = 0; foreach( $the_items as $the_item ) : ?>
                        <tr id="<?php echo $the_item['ID']; ?>" <?php if ( $i % 2 ) { echo 'class="even"'; } ?>>
                            <td><canvas id="cv<?php echo $i; ?>" class="cv" width="400" height="420"></canvas></td>
                            <td class="item">
                                <p><strong><?php echo $the_item['name']; ?></strong></p>
                                <p><?php echo __( 'Volume: ', 'pqc' ) . $the_item['volume'] . " cm<sup>3</sup>"; ?></p>
                                <p><?php echo __( 'Weight: ', 'pqc' ) . $the_item['weight'] . " gm"; ?></p>
                                <p><?php echo __( 'Density: ', 'pqc' ) . $the_item['density'] . " gm/cc"; ?></p>
                            </td>
                            <td class="item" id="<?php echo $the_item['ID']; ?>-cost">
                                <p>
                                    <input name="quantities[<?php echo $the_item['ID']; ?>]" type="number" value="<?php echo $the_item['quantity']; ?>" min="1" style="width: 60px;">
                                    <span class="cost"> x <?php echo $the_item['cost']; ?></span>
                                </p>
                            </td>
                            <td class="item">
                                <?php if ( $materials ) : ?>
                            
                                <div id="materials">
                                    <select name="materials[<?php echo $the_item['ID']; ?>]" style="font-size: 12px;">
                                        <?php foreach ( $materials as $material ) : ?>
                                        <option value="<?php echo $material->ID; ?>" <?php if ( $material->ID == $selected_material[$the_item['ID']] ) echo 'selected="selected"'; ?>><?php echo $material->material_name; ?></option>
                                        <?php endforeach; ?>
                                    </select>                            
                                </div>
                                
                                <?php endif; ?>
                            </td>
                            <td class="item" id="<?php echo $the_item['ID']; ?>-total"><p><?php echo $the_item['total']; ?></p></td>
                            <td>
                                <a id="<?php echo $the_item['unique_id']; ?>" class="remove-item" href="#" title="<?php _e( 'Remove this item', 'pqc' ); ?>">
                                    <img src="<?php echo PQC_URL . 'assets/images/trash.gif'; ?>" alt="<?php _e( 'Trash', 'pqc' ); ?>" style="width: 80%; height: 18px;">
                                </a>
                            </td>
                        </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>
                
                <div class="coupon">
                    <input type="text" id="coupon" name="coupon" placeholder="Coupon Code" autocomplete="off" style="width: 48%; float: left;">
                    <input type="submit" name="apply-coupon" id="apply-coupon" value="Apply Coupon" style="width: 50%; float: right;">
                </div>
                
                <div class="update-cart">
                    <input type="submit" name="update-cart" value="Update Cart" style="width: 100%; float: right;">
                </div>
            
            </form>
        </div>
        
        <div id="content-right">
        
            <div id="item-attributes">
            
                <fieldset id="item-attributes_fieldset" class="pqc-item-fieldset">
                    
                    <legend><?php _e( 'Cart Total', 'pqc' ); ?></legend>
                    
                    <table class="cart-total">
                        <thead>
                            <tr>
                                <th style="width: 40%; visibility: hidden;"></th>
                                <th style="width: 60%; visibility: hidden;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="sub-total">
                                <td>Subtotal</td>
                                <td id="subtotal"><?php echo $subtotal; ?></td>
                            </tr>
                            <?php if ( $shipping_options ) : ?>
                            <tr class="shipping">
                                <td>Shipping</td>
                                <td>
                                    <div id="shipping_options">

                                        <select name="shipping_option" style="font-size: 12px; margin-top: 4%;">
                                            <option value="-1"><?php _e( '- Select Shipping Option -', 'pqc' ); ?></option>
                                            <?php foreach ( $shipping_options as $shipping_option ) : ?>
                                            <option <?php if ( $current_shipping_option_id == $shipping_option['ID'] ) echo 'selected="selected"'; ?> value="<?php echo $shipping_option['ID']; ?>"><?php echo $shipping_option['title']; ?></option>
                                            <?php endforeach; ?>
                                        </select>

                                        <p id="shipping-description"><?php if ( isset( $current_shipping_option ) ) echo $current_shipping_option['desc']; ?></p>
                                        
                                        <p id="shipping-cost"><?php if ( isset( $current_shipping_option ) ) echo 'Cost: ' . $current_shipping_option['cost']; ?></p>
                                                 
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            
                        </tbody>
                        
                        <tfoot>
                            <tr class="total">
                                <td>Total</td>
                                <td id="total"><?php echo $total; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <form action="<?php echo get_permalink( pqc_page_exists( 'pqc-checkout' ) ); ?>" method="post" id="pqc_proceed_checkout_form">
                    
                        <input type="hidden" name="shipping_option_id">
                        
                        <input type="submit" name="pqc_proceed_checkout" value="Proceed to checkout" style="margin-top: 3%;">

                    </form>
                    
                </fieldset>
                
            </div>
            
        </div>

    </div>
    
    <?php endif; ?>
    
    <div id="modal"></div>
    
</div>