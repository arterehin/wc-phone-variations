<?php

if (!defined('ABSPATH')) {
  exit;
}

/**
 * SP_Image_Generator Class.
 */
class SP_Ajax {

  public function __construct() {
    $this->init_hooks();
  }

  /**
   * Initialize WP hooks
   */
  private function init_hooks() {
    add_action('wp_ajax_sp_save_background', array($this, 'save_background'));
    add_action('wp_ajax_nopriv_sp_save_background', array($this, 'save_background'));
  }

  public function save_background() {
    check_admin_referer('sp-save-background', 'security');

    if (!current_user_can('edit_products') || empty($_POST['product_id'])) {
      wp_die(-1);
    }

    SP_Phone_Variations_Plugin::process_product($_POST['product_id']);

    wp_send_json_success();
  }
}

new SP_Ajax();
