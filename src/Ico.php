<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Favicon;

use Exception;

/**
 * Ico favicon generator
 * 
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class Ico 
{
  /**
   * Images in the BMP format.
   * 
   * @var array
   */
  protected $images;

  /**
   * Flag to tell if the required functions exist.
   * 
   * @var boolean
   */
  protected $valid;
  
  /**
   * Check the dependencies
   * 
   * In case composer is not used to check dependencies
   * 
   * @throws Exception
   */
  public static function checkDependencies()
  {
    $required_functions = [
      'getimagesize',
      'imagecreatefromstring',
      'imagecreatetruecolor',
      'imagecolortransparent',
      'imagecolorallocatealpha',
      'imagealphablending',
      'imagesavealpha',
      'imagesx',
      'imagesy',
      'imagecopyresampled',
    ];
    
    foreach($required_functions as $function){
      if ( !function_exists($function) ){
        throw new Exception('$function function does not exist, which is part of the GD library.');
      }
    }
  }
  
  /**
   * Constructor - Create a new ICO generator.
   * 
   * If the constructor is not passed a file, a file will need to be supplied using the {@link PHP_ICO::add_image}
   * function in order to generate an ICO file.
   * 
   * @param   string    $file     Optional. Path to the source image file.
   * @param   array     $sizes    Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file.
   *                              If sizes are not supplied, the size of the source image will be used.
   * @throws  GeneratorException
   */
  public function __construct($file=false, array $sizes=[])
  {
    $this->images = [];
    $this->valid  = false;
    
    $this->valid = true;
    
    if ( false !== $file ){
      $this->add($file, $sizes);
    }
  }
  
  /**
   * Add an image to the generator.
   *
   * This function adds a source image to the generator. It serves two main purposes: add a source image if one was
   * not supplied to the constructor and to add additional source images so that different images can be supplied for
   * different sized images in the resulting ICO file. For instance, a small source image can be used for the small
   * resolutions while a larger source image can be used for large resolutions.
   *
   * @param   string    $file     Path to the source image file.
   * @param   array     $sizes    Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file.
   *                              If sizes are not supplied, the size of the source image will be used.
   * @return  boolean   True on success
   */
  public function add($file, array $sizes=[])
  {
    if ( $this->valid === false ){
      return false;
    }

    if ( false === ($im=$this->load_image_file($file)) ){
      return false;
    }

    if ( empty($sizes) ){
      $sizes = [ imagesx( $im ), imagesy( $im ) ];
    }
    
    // If just a single size was passed, put it in array.
    if ( !is_array($sizes[0]) ){
      $sizes = [ $sizes ];
    }
    
    foreach((array) $sizes as $size){
      list($width, $height) = $size;

      $new_im = imagecreatetruecolor($width, $height);

      imagecolortransparent($new_im, imagecolorallocatealpha( $new_im, 0, 0, 0, 127 ));
      imagealphablending($new_im, false);
      imagesavealpha($new_im, true);

      $source_width  = imagesx($im);
      $source_height = imagesy($im);

      if ( false === imagecopyresampled($new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height) ){
        continue;
      }
    
      $this->add_image_data($new_im);
    }

    return true;
  }
  
  /**
   * Write the ICO file data to a file path.
   *
   * @param   string    $file Path to save the ICO file data into
   * @return  boolean   true on success 
   */
  public function save($file)
  {
    if ( $this->valid === false ){
      return false;
    }
    
    if ( false === ($data=$this->_get_ico_data()) ){
      return false;
    }

    if ( false === ($fh=fopen($file, 'w')) ){
      return false;
    }

    if ( false === (fwrite($fh, $data)) ){
      fclose($fh);
      return false;
    }
    
    fclose($fh);
    
    return true;
  }

  /**
   * Generate the final ICO data by creating a file header and adding the image data.
   *
   * @return  string   The file as a string
   */
  protected function _get_ico_data()
  {
    if ( !is_array($this->images) || empty($this->images) ){
      return false;
    }

    $data = pack('vvv', 0, 1, count($this->images));
    $pixel_data = '';

    $icon_dir_entry_size = 16;

    $offset = 6 + ( $icon_dir_entry_size * count($this->images) );

    foreach($this->images as $image){
      $data .= pack('CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset);
      $pixel_data .= $image['data'];
      
      $offset += $image['size'];
    }
    
    $data .= $pixel_data;
    unset($pixel_data);
    
    return $data;
  }

  /**
   * Take a GD image resource and change it into a raw BMP format.
   *
   * @param   resource  $im   The image resource
   * @return  void
   */
  protected function add_image_data($im)
  {
    $width  = imagesx($im);
    $height = imagesy($im);
    
    $pixel_data = [];
    
    $opacity_data = [];
    $current_opacity_val = 0;
    
    for($y=$height-1; $y >= 0; $y--){
      for($x=0; $x < $width; $x++){
        $color = imagecolorat($im, $x, $y);

        $alpha = ( $color & 0x7F000000 ) >> 24;
        $alpha = ( 1 - ( $alpha / 127 ) ) * 255;

        $color &= 0xFFFFFF;
        $color |= 0xFF000000 & ( $alpha << 24 );

        $pixel_data[] = $color;

        $opacity = ( $alpha <= 127 ) ? 1 : 0;

        $current_opacity_val = ( $current_opacity_val << 1 ) | $opacity;

        if ( (($x+1) % 32) == 0 ){
          $opacity_data[] = $current_opacity_val;
          $current_opacity_val = 0;
        }
      }

      if ( ($x % 32) > 0 ){
        while (($x++ % 32) > 0){
          $current_opacity_val = $current_opacity_val << 1;
        }

        $opacity_data[] = $current_opacity_val;
        $current_opacity_val = 0;
      }
    }

    $image_header_size = 40;
    $color_mask_size   = $width * $height * 4;
    $opacity_mask_size = ( ceil($width/32) * 4 ) * $height;

    $data = pack('VVVvvVVVVVV', 40, $width, ( $height * 2 ), 1, 32, 0, 0, 0, 0, 0, 0);

    foreach($pixel_data as $color){
      $data .= pack( 'V', $color );
    }
    
    foreach($opacity_data as $opacity){
      $data .= pack('N', $opacity);
    }
    
    $image = [
      'width'                => $width,
      'height'               => $height,
      'color_palette_colors' => 0,
      'bits_per_pixel'       => 32,
      'size'                 => $image_header_size + $color_mask_size + $opacity_mask_size,
      'data'                 => $data,
    ];
    
    $this->images[] = $image;
  }

  /**
   * Read in the source image file and convert it into a GD image resource.
   *
   * @param   string    $file   The image file path
   * @return  resource  The image resource
   */
  protected function load_image_file($file)
  {
    // Run a cheap check to verify that it is an image file.
    if ( false === ($size=getimagesize($file)) ){
      return false;
    }

    if ( false === ($file_data=file_get_contents($file)) ){
      return false;
    }
    
    // debugMe($file_data);

    if ( false === ($im=imagecreatefromstring($file_data)) ){
      return false;
    }

    unset($file_data);
    
    return $im;
  }
}