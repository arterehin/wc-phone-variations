/**
 * Extend media uploader frame.
 */
jQuery(function ($) {

  var Frame = wp.media.view.Frame,
    l10n = wp.media.view.l10n;

  wp.media.view.MediaFrame.prototype = Object.create(wp.media.view.MediaFrame.prototype, {
    initialize: {
      value: function () {
        Frame.prototype.initialize.apply(this, arguments);

        _.defaults(this.options, {
          title: l10n.mediaFrameDefaultTitle,
          modal: true,
          uploader: true
        });

        // Ensure core UI is enabled.
        this.$el.addClass('wp-core-ui');

        // Initialize modal container view.
        if (this.options.modal) {
          this.modal = new wp.media.view.Modal({
            controller: this,
            title: this.options.title
          });

          this.modal.content(this);
        }

        // Force the uploader off if the upload limit has been exceeded or
        // if the browser isn't supported.
        if (wp.Uploader.limitExceeded || !wp.Uploader.browser.supported) {
          this.options.uploader = false;
        }

        // Initialize window-wide uploader.
        if (this.options.uploader) {
          this.uploader = new wp.media.view.UploaderWindow({
            controller: this,
            uploader: $.extend({
              dropzone: this.modal ? this.modal.$el : this.$el,
              container: this.$el
            }, this.options.uploader)
          });
          this.views.set('.media-frame-uploader', this.uploader);
        }

        this.on('attach', _.bind(this.views.ready, this.views), this);

        // Bind default title creation.
        this.on('title:create:default', this.createTitle, this);
        this.title.mode('default');

        // Bind default menu.
        this.on('menu:create:default', this.createMenu, this);

        // Set the menu ARIA tab panel attributes when the modal opens.
        this.on('open', this.setMenuTabPanelAriaAttributes, this);
        // Set the router ARIA tab panel attributes when the modal opens.
        this.on('open', this.setRouterTabPanelAriaAttributes, this);

        // Update the menu ARIA tab panel attributes when the content updates.
        this.on('content:render', this.setMenuTabPanelAriaAttributes, this);
        // Update the router ARIA tab panel attributes when the content updates.
        this.on('content:render', this.setRouterTabPanelAriaAttributes, this);
      },
    },
  });
});

/**
 * Initializes media uploader.
 */
jQuery(function ($) {

  var $container = $('#sp_media'),
    $addButton = $container.find('.sp_image'),
    $removeButton = $container.find('.sp_image_remove'),
    $input = $container.find('input[name^="sp_"]');

  var frame;
  var data = $container.data();
  var allowedTypes = {
    'image/jpeg': 'jpg,jpeg,jpe',
    'image/png': 'png',
  }

  function set_defaults() {
    var $image = $addButton.find('img');

    $container.data('defaults', {
      id: $input.val(),
      src: $image.length ? $image.prop('src') : ''
    });
  }

  function add_image(values) {
    var $image = $addButton.find('img');

    if($image.length) {
      $image.remove();
    }

    $addButton
      .addClass('filled')
      .append('<img src="' + values.src + '" alt="" />');
    $input.val(values.id);
  }

  function remove_image() {
    $addButton
      .removeClass('filled')
      .find('img')
      .remove();
    $input.val('0');
  }

  // handle select image
  $addButton.on('click', function (e) {
    e.preventDefault();

    if ($addButton.hasClass('filled')) {
      return;
    }

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: 'Select an Image',
      multiple: false,
      button: data.buttonText ? { text: data.buttonText } : {},
      library: allowedTypes[data.type] ? { type: [data.type] } : {},
      uploader: allowedTypes[data.type] ? {
        plupload: {
          filters: {
            mime_types: [{
              extensions: allowedTypes[data.type]
            }]
          }
        }
      } : {}
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();

      add_image({
        id: attachment.id,
        src: attachment.sizes.thumbnail.url,
      });
      $container.trigger('change');
    });

    frame.open();
  });

  // handle remove image
  $removeButton.on('click', function (e) {
    e.stopPropagation();

    remove_image();
    $container.trigger('change')
  });

  // reset to default state
  $container.on('reset', () => {
    var defaults = $container.data('defaults');

    if (defaults.id === '0') {
      remove_image();
    } else {
      add_image(defaults);
    }
  });

  // commit changes
  $container.on('commit', set_defaults);

  // initialize
  set_defaults();
});
