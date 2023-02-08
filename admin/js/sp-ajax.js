/**
 * Ajax interactions.
 */
jQuery(function ($) {

  var $product = $('#woocommerce-product-data'),
    $container = $('#phone_product_options'),
    $uploader = $container.find('#sp_media'),
    $saveButton = $container.find('button.sp-save-changes'),
    $cancelButton = $container.find('button.sp-cancel-changes');

  function toggle_notice() {
    var $notice = $product.find('#sp-image-notice');
    var defaults = $uploader.data('defaults');

    if (defaults.id === '0') {
      $notice.show();
    } else {
      $notice.hide();
    }
  }

  function enable_controls() {
    $saveButton.prop('disabled', false);
    $cancelButton.prop('disabled', false);
  }

  function disabel_controls() {
    $saveButton.prop('disabled', true);
    $cancelButton.prop('disabled', true);
  }

  function process_ajax() {
    var values = $uploader.find(':input').serializeJSON();
    var data = $.extend({ security: sp_ajax_params.security }, values);

    $container.block({
      message: null,
      overlayCSS: {
        background: '#fff',
        opacity: 0.6
      }
    });

    wp.ajax.post('sp_save_background', data)
      .then(function () {
        $uploader.trigger('commit');
        toggle_notice();
        disabel_controls();
      }).always(function () {
        $container.unblock();
      });
  }

  function rollback_values() {
    $uploader.trigger('reset');
    disabel_controls();
  }

  // attach form handlers
  $uploader.on('change', enable_controls);
  $saveButton.on('click', process_ajax);
  $cancelButton.on('click', rollback_values);
  $product.on('woocommerce_variations_loaded', toggle_notice);
});
