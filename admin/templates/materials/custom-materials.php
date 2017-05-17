<form id="addmaterial" method="post" action="" class="validate">

    <?php wp_nonce_field( 'nonce_material' ); ?>
    
    <input name="pqc_id" id="pqc_id" type="hidden">
    
    <div class="form-material form-required term-name-wrap">
        <label for="pqc_material_name"><?php _e( 'Material Name', 'pqc' ); ?></label>
        <input name="pqc_material_name" id="pqc_material_name" size="40" class="regular-text code" autocomplete="off" type="text">
        <p><?php esc_html__( 'The name is how it appears on the site.', 'pqc' ); ?></p>
    </div>
    
    <div class="form-material form-required term-description-wrap">
        <label for="pqc_material_description"><?php _e( 'Material Description', 'pqc' ); ?></label>
        <textarea name="pqc_material_description" id="pqc_material_description" autocomplete="off" rows="5" cols="40"></textarea>
        <p><?php esc_html__( 'The description is used in the frontend. Include all material properties here.', 'pqc' ); ?></p>
    </div>
    
    <div class="form-material form-required term-name-wrap">
        <label for="pqc_material_cost"><?php _e( 'Material Cost', 'pqc' ); ?></label>
        <input name="pqc_material_cost" id="pqc_material_cost" required autocomplete="off" type="number" style="width: 10em;" min="0" step="any"> per cm<sup>3</sup>
        <p><?php printf( esc_html__( 'The cost for the material in %s', 'pqc' ), $currency ); ?></p>
    </div>

    <p class="submit">
        <input name="pqc_material_add" class="button button-primary" value="<?php _e( 'Add', 'pqc' ); ?>" type="submit">
        <input name="pqc_material_update" class="button button-primary" value="<?php _e( 'Update', 'pqc' ); ?>" type="submit" style="display: none;">
        <button id="cancel_edit" class="button button-default" style="display: none;"><?php _e( 'Cancel', 'pqc' ); ?></button>
    </p>
    
</form>