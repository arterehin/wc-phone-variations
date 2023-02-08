<?php

/**
 * Admin View: Edit Phone Taxanomy
 */

if (!defined('ABSPATH')) {
  exit;
}

$attribute_info = $this->get_attribute_info($taxanomy->term_id);

?>

<tr class="form-field term-image-wrap">
  <th scope="row">
    <label>Cover image</label>
  </th>
  <td>
    <span id="sp_media" data-button-text="Set cover image" data-type="image/png">
      <span class="sp_image <?php if ($attribute_info['cover_image']) echo 'filled'; ?>">
        <?php if ($attribute_info['cover_image']) : ?>
          <img src="<?php echo esc_url($attribute_info['cover_image']); ?>" />
        <?php endif; ?>
        <span class="sp_image_remove">Remove</span>
      </span>
      <input type="hidden" name="sp_cover_image" value="<?php echo esc_attr($attribute_info['cover_id']); ?>" />
    </span>
  </td>
</tr>