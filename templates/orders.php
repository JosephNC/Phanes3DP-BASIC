<div id="pqc-wrapper" class="container">

    <header class="codrops-header">
        <?php if ( ! empty( $GLOBALS['payment_message'] ) ) : ?>
        <div class="notice"><p><?php echo $GLOBALS['payment_message']; ?></p></div>
        <?php endif; ?>
    </header>

    <div id="content-full">
        <div class="item-total-heading">
            <h1 style="padding: 0; float: left; width: 50%;"><?php printf( _n( '%1$s order found', '%1$s orders found', $total_item, 'pqc' ), $total_item ); ?></h1>
        </div>

        <table class="cart">
            <thead>
                <th id="order"><?php _e( 'Order #', 'pqc' ); ?></th>
                <th id="transaction-id"><?php _e( 'Transaction ID', 'pqc' ); ?></th>
                <th id="order-state"><?php _e( 'Order State', 'pqc' ); ?></th>
                <th id="total"><?php _e( 'Total', 'pqc' ); ?></th>
                <th id="date"><?php _e( 'Date', 'pqc' ); ?></th>
            </thead>
            
            <tbody>
                <?php if ( $total_item > 0 ) : ?>
                <?php $i = 0; foreach( $the_orders as $the_order ) : ?>
                <tr id="<?php echo $the_order['id']; ?>" <?php if ( $i % 2 ) { echo 'class="even"'; } ?>>
                    <td class="item">
                        <div id="order">
                            <a style="cursor: pointer;" title="<?php echo $the_order['title']; ?>"><?php echo $the_order['title']; ?></a>
                            <p><?php echo 'Via ' . $the_order['payment_method']; ?></p>
                            <p><?php echo 'Payment ' . $the_order['order_status']; ?></p>
                        </div>
                    </td>
                    <td class="item">
                        <div id="transaaction-id"><?php echo $the_order['txn_id']; ?></div>
                    </td>
                    
                    <td class="item">
                        <div id="order-action"><?php echo ucfirst( $the_order['order_action'] ) . ' Order'; ?></div>
                    </td>
                    
                    <td class="total"><p><?php echo pqc_money_format( $the_order['total'], $the_order['currency'] ); ?></p></td>
                    
                    <td class="date"><p><?php echo pqc_time_difference( $the_order['date'], current_time( 'mysql' ) ); ?></p></td>
                </tr>
                <?php $i++; endforeach; ?>
                <?php else : ?>
                <tr><td><?php _e( 'No Order Found', 'pqc' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /container -->