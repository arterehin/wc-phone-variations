<?php

/**
 * Plugin Name: WooCommerce phone variations
 * Description: Generates WooCommerce variation images.
 * Author:      Artem Terehin<arterehin@gmail.com>
 * Author URI:  https://github.com/arterehin
 * Version:     1.1.0
 */

if (!defined('ABSPATH')) {
  return;
}

/**
 * SP_Phone_Variations_Plugin Class.
 */
class SP_Phone_Variations_Plugin {

  /**
   * Phone attribute name
   */
  protected $attribute_name = 'pa_phone';

  /**
   * Build the instance
   */
  public function __construct() {
    $this->includes();
    $this->init_hooks();
  }

  /**
   * Initial includes
   */
  private function includes() {
    include_once dirname(__FILE__) . '/includes/class-sp-ajax.php';
    include_once dirname(__FILE__) . '/includes/class-sp-image-generator.php';
  }

  /**
   * Initialize WP hooks
   */
  private function init_hooks() {
    register_activation_hook(__FILE__, array($this, 'install'));
    add_action('admin_enqueue_scripts', array($this, 'admin_static'));
    add_action($this->attribute_name . '_edit_form_fields', array($this, 'add_taxanomy_field'));
    add_action('edited_' . $this->attribute_name, array($this, 'process_attribute'));
    add_filter('woocommerce_product_data_tabs', array($this, 'add_phone_tab'));
    add_action('woocommerce_product_data_panels', array($this, 'add_phone_tab_panel'));
    add_action('woocommerce_process_product_meta', array($this, 'process_product'));
    add_action('woocommerce_admin_process_variation_object', array($this, 'process_variation'));
    add_action('product_variation_linked', array($this, 'bulk_process_variation'));
    add_action('woocommerce_variable_product_before_variations', array($this, 'add_missied_image_notice'));
  }

  /**
   * Plugin installation
   */
  public function install() {
    if (!in_array($this->attribute_name, wc_get_attribute_taxonomy_names(), true)) {
      wc_create_attribute(array(
        'name' => 'phone',
        'slug' => 'phone',
      ));
    }
  }

  /**
   * Get phone product meta
   */
  public function get_product_info($product_id) {
    $product = wc_get_product($product_id);
    $result = array(
      'background_id' => 0,
      'background_image' => null,
      'background_path' => null,
    );

    if ($product) {
      $background_id = $product->get_meta('_sp_background_image');
      $background_image = wp_get_attachment_image_url($background_id);

      if ($background_image) {
        $result['background_id'] = $background_id;
        $result['background_image'] = $background_image;
        $result['background_path'] = wp_get_original_image_path($background_id);
      }
    }

    return $result;
  }

  /**
   * Get phone product meta
   */
  public function get_attribute_info($term_id) {
    $cover_id = get_term_meta($term_id, '_sp_cover_image', true);
    $result = array(
      'cover_id' => 0,
      'cover_image' => null,
      'cover_path' => null,
    );

    if ($cover_id) {
      $cover_image = wp_get_attachment_image_url($cover_id);

      if ($cover_image) {
        $result['cover_id'] = $cover_id;
        $result['cover_image'] = $cover_image;
        $result['cover_path'] = wp_get_original_image_path($cover_id);
      }
    }

    return $result;
  }

  /**
   * Process attribute cover image save
   */
  public function process_attribute($term_id) {
    if (!isset($_POST['sp_cover_image'])) {
      return;
    }

    update_term_meta(
      wp_strip_all_tags(wp_unslash($term_id)),
      '_sp_cover_image',
      wc_clean(wp_unslash($_POST['sp_cover_image'])),
    );
  }

  /**
   * Get term id for given varion by attribute_name
   */
  public function get_variation_term_id($variation) {
    $attributes = $variation->get_attributes();

    if (in_array($this->attribute_name, array_keys($attributes), true)) {
      $term = get_term_by('slug', $attributes[$this->attribute_name], $this->attribute_name);

      if ($term) {
        return $term->term_id;
      }
    }

    return null;
  }

  /**
   * Bulk process variation after save
   */
  public function bulk_process_variation($variation_id) {
    $variation = wc_get_product($variation_id);
    $this->process_variation($variation);
    $variation->save();
  }

  /**
   * Process variation before save
   */
  public function process_variation($variation) {
    $image_id = $variation->get_image_id('edit');
    $attribute_id = $this->get_variation_term_id($variation);

    if ($image_id || !$attribute_id) {
      return;
    }

    $product_id = $variation->get_parent_id();
    $product_meta = get_post_meta($product_id, '_sp_generated_image', true);

    if ($product_meta) {
      $already_created = current(array_filter(
        $product_meta,
        function ($item) use ($attribute_id) {
          return $item['attribute_id'] == $attribute_id;
        }
      ));

      if (!empty($already_created)) {
        if (wp_get_attachment_image_url($already_created['attachment_id'])) {
          $variation->set_image_id($already_created['attachment_id']);
          return;
        } else {
          $product_meta = array_filter(
            $product_meta,
            function ($item) use ($attribute_id) {
              return $item['attribute_id'] !== $attribute_id;
            }
          );
        }
      }
    }

    $product_info = $this->get_product_info($product_id);
    $attribute_info = $this->get_attribute_info($attribute_id);

    if ($product_info['background_path'] && $attribute_info['cover_path']) {
      $file_name = SP_Image_Generator::generate_image(
        $attribute_info['cover_path'],
        $product_info['background_path'],
      );

      if ($file_name) {
        $attachment = media_handle_sideload(array(
          'name' => basename($file_name),
          'type' => 'image/jpeg',
          'tmp_name' => $file_name,
          'error' => 0,
          'size' => filesize($file_name)
        ));

        if (!is_wp_error($attachment)) {
          $product_data = array(
            'attribute_id' => $attribute_id,
            'attachment_id' => $attachment,
          );

          if (is_array($product_meta)) {
            $product_meta[] = $product_data;
          } else {
            $product_meta = array($product_data);
          }

          $variation->set_image_id($attachment);
          update_post_meta(
            wp_strip_all_tags(wp_unslash($product_id)),
            '_sp_generated_image',
            wc_clean(wp_unslash($product_meta)),
          );
        } else {
          @unlink($file_name);
          error_log('wc-phone-variations: ' . $attachment->get_error_message());
        }
      }
    }
  }

  /**
   * Process phone product meta after save
   */
  public static function process_product($product_id) {
    if (!isset($_POST['sp_background_image'])) {
      return;
    }

    update_post_meta(
      wp_strip_all_tags(wp_unslash($product_id)),
      '_sp_background_image',
      wc_clean(wp_unslash($_POST['sp_background_image'])),
    );
  }

  /**
   * Add image uploader field to phone taxanomy
   */
  public function add_taxanomy_field($taxanomy) {
    include_once dirname(__FILE__) . '/admin/views/html-phone-cover-image.php';
  }

  /**
   * Add phone product tab to WC metabox
   */
  public function add_phone_tab($tabs) {
    $tabs['phone'] = array(
      'label'     => __('Phone Product', 'sp_phones'),
      'target' => 'phone_product_options',
      'class'  => 'show_if_variable',
    );

    return $tabs;
  }

  /**
   * Add phone product tab panel to WC metabox
   */
  public function add_phone_tab_panel() {
    include_once dirname(__FILE__) . '/admin/views/html-phone-tab-panel.php';
  }

  /**
   * Add missied background image notice
   */
  public function add_missied_image_notice() {
    include dirname(__FILE__) . '/admin/views/html-missied-image-notice.php';
  }

  /**
   * Include CSS/JS static files
   */
  public function admin_static() {
    $screen         = get_current_screen();
    $screen_id      = $screen ? $screen->id : '';

    // uploader styles and scripts
    if (in_array($screen_id, array('product', 'edit-' . $this->attribute_name))) {
      wp_enqueue_media();
      wp_enqueue_script(
        'sp-image-uploader',
        plugin_dir_url(__FILE__) . 'admin/js/sp-image-uploader.js',
        array('jquery'),
        false,
        true,
      );
      wp_enqueue_style(
        'sp-image-uploader',
        plugin_dir_url(__FILE__) . 'admin/css/sp-image-uploader.css',
      );
    }

    // ajax interaction
    if (in_array($screen_id, array('product'))) {
      wp_enqueue_script(
        'sp-ajax',
        plugin_dir_url(__FILE__) . 'admin/js/sp-ajax.js',
        array('jquery', 'jquery-blockui', 'sp-image-uploader'),
        false,
        true,
      );
      wp_localize_script(
        'sp-ajax',
        'sp_ajax_params',
        array(
          'security' => wp_create_nonce('sp-save-background'),
        ),
      );
    }
  }
}

new SP_Phone_Variations_Plugin();
