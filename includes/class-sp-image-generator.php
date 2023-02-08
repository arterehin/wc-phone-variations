<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SP_Image_Generator Class.
 */
class SP_Image_Generator {

  /**
   * Get width and height from GD image
   */
  private static function get_image_size($image) {
    return array(
      'w' => imagesx($image),
      'h' => imagesy($image),
    );
  }

  /**
   * Get transparent polygon from GD image
   */
  private static function get_transparency($image, $width, $height) {
    $result = array(
      'w' => null,
      'h' => null,
      'x' => array(
        'min' => null,
        'max' => null,
      ),
      'y' => array(
        'min' => null,
        'max' => null,
      ),
    );

    for ($x = 0; $x < $width; ++$x) {
      for ($y = 0; $y < $height; ++$y) {
        $rgba = imagecolorat($image, $x, $y);

        if (($rgba & 0x7F000000) >> 24) {
          $minX = $result['x']['min'];
          $maxX = $result['x']['max'];
          $minY = $result['y']['min'];
          $maxY = $result['y']['max'];

          if (is_null($minX) || $minX > $x) {
            $result['x']['min'] = $x;
          }

          if (is_null($maxX) || $maxX < $x) {
            $result['x']['max'] = $x;
          }

          if (is_null($minY) || $minY > $y) {
            $result['y']['min'] = $y;
          }

          if (is_null($maxY) || $maxY < $y) {
            $result['y']['max'] = $y;
          }
        }
      }
    }

    $result['w'] = $result['x']['max'] - $result['x']['min'];
    $result['h'] = $result['y']['max'] - $result['y']['min'];

    return $result;
  }

  /**
   * Place transparent cover over background
   */
  private static function merge_images($cover, $background) {
    $cover_size = self::get_image_size($cover);
    $background_size = self::get_image_size($background);
    $polygon = self::get_transparency($cover, $cover_size['w'], $cover_size['h']);
    $canvas = imagecreatetruecolor($cover_size['w'], $cover_size['h']);

    imagecopy(
      $canvas,
      $background,
      $polygon['x']['min'],
      $polygon['y']['min'],
      ($background_size['w'] / 2) - ($polygon['w'] / 2),
      ($background_size['h'] / 2) - ($polygon['h'] / 2),
      $cover_size['w'],
      $cover_size['h'],
    );

    imagecopy(
      $canvas,
      $cover,
      0,
      0,
      0,
      0,
      $cover_size['w'],
      $cover_size['h'],
    );

    return $canvas;
  }

  public static function generate_output_path($cover_file, $background_file) {
    $cover = pathinfo($cover_file, PATHINFO_FILENAME);
    $background = pathinfo($background_file, PATHINFO_FILENAME);

    return get_temp_dir() . $cover . '-' . $background . '.jpg';
  }

  /**
   * Generate and output image file
   */
  public static function generate_image($cover_file, $background_file) {
    $cover = imagecreatefrompng($cover_file);
    $background = imagecreatefromjpeg($background_file);

    if ($cover && $background) {
      $output = self::generate_output_path($cover_file, $background_file);
      $thumb = self::merge_images($cover, $background);

      if ($thumb) {
        $created = imagejpeg($thumb, $output, 100);

        imagedestroy($cover);
        imagedestroy($background);
        imagedestroy($thumb);

        if ($created) {
          return $output;
        }
      }
    }

    return false;
  }
}
