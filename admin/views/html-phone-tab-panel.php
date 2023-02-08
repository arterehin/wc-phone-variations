<?php

/**
 * Admin View: Phone Tab Panel
 */

if (!defined('ABSPATH')) {
  exit;
}

global $post;

$product_info = $this->get_product_info($post->ID);

?>

<div id="phone_product_options" class="panel wc-metaboxes-wrapper woocommerce_options_panel">
  <div class="options_group">
    <p class="form-field">
      <label>Phone background</label>
      <span id="sp_media" data-button-text="Set background image" data-type="image/jpeg">
        <span class="sp_image <?php if ($product_info['background_image']) echo 'filled'; ?>">
          <?php if ($product_info['background_image']) : ?>
            <img src="<?php echo esc_url($product_info['background_image']); ?>" />
          <?php endif; ?>
          <span class="sp_image_remove">Remove</span>
        </span>
        <input type="hidden" name="product_id" value="<?php echo absint( $post->ID ); ?>" />
        <input type="hidden" name="sp_background_image" value="<?php echo esc_attr($product_info['background_id']); ?>" />
      </span>
    </p>
  </div>
  <div class="toolbar">
    <button type="button" class="button button-primary sp-save-changes" disabled="disabled">Save changes</button>
    <button type="button" class="button sp-cancel-changes" disabled="disabled">Cancel</button>
  </div>
</div>