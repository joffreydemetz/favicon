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
 * Png favicon generator
 * 
 * @author      Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class Png 
{
	/**
	 * Flag to tell if the required functions exist.
	 * 
	 * @var boolean
	 */
	protected $valid;
  
	/**
	 * The source file
	 * 
	 * @var string
	 */
	protected $source;

	/**
	 * The image resource
	 * 
	 * @var resource
	 */
	protected $image;

  
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
			'imagecreatefrompng',
			'imagecreatefromgif',
			'imagecreatefromjpeg',
			'imagecreatetruecolor',
			'imagesx',
			'imagesy',
			'imagecopyresampled',
			'imagesavealpha',
			'imagepng',
			'imagealphablending',
			'imagefill',
			'imagecolorallocatealpha',
		];
    
		foreach($required_functions as $function){
			if ( !function_exists($function) ){
        throw new Exception('$function function does not exist, which is part of the GD library.');
			}
		}
  }
	/**
	 * Constructor 
	 * 
	 * @param   string    $source     Path to the source image file
   * @throws  GeneratorException
	 */
	public function __construct($source)
  {
    $this->source = $source;
    $this->valid  = false;
    
    $this->valid  = true;
    
    $image_info = getimagesize($this->source);
    $type = $image_info[2];
    
    if ( $type === IMAGETYPE_JPEG ){
      $this->image = imagecreatefromjpeg($this->source);
    } 
    elseif( $type === IMAGETYPE_GIF ){
      $this->image = imagecreatefromgif($this->source);
    } 
    elseif( $type === IMAGETYPE_PNG ){
      $this->image = imagecreatefrompng($this->source);
    }
  }
  
	/**
	 * Resize image to square
	 *
	 * @param   string    $file     Path to the destination file
	 * @param   int       $size     The destination size
   * @return  boolean
	 */
	public function square($file, $size)
  {
		if ( $this->valid === false ){
			return false;
    }
    
    $this->scale($size, $size);
    imagepng($this->image, $file);
		return true;
  }
  
	/**
	 * Create a ms tile
	 *
	 * @param   string    $file           Path to the destination file
	 * @param   string    $background     The background color (hex)
	 * @param   int       $width          The tile width
	 * @param   int       $height         The tile height
   * @return  boolean
	 */
	public function tile($file, $background, $width, $height)
  {
		if ( $this->valid === false ){
			return false;
    }
    
    if ( substr($background, 0, 1) === '#' ){
      $background = substr($background, 1);
    }
    
    // background
    $int = hexdec($background);
    $r = 0xFF & ($int >> 0x10);
    $v = 0xFF & ($int >> 0x8);
    $b = 0xFF & $int;
    // $r = 255;
    // $v = 255;
    // $b = 255;
    
    $blankbox = imagecreatetruecolor($width, $height);
    imagealphablending($blankbox, true);
    $background = imagecolorallocatealpha($blankbox, $r, $v, $b, 127);
		imagefill($blankbox, 0, 0, $background);
    imagealphablending($blankbox, false);
    imagesavealpha($blankbox, true);
    
    $size = min($width, $height);
    
    $innerWidth  = $size * 0.8;
    $innerHeight = $size * 0.8;
    
    // inner image
    $this->scale($innerWidth, $innerHeight);
    $inner = $this->image;
    imagealphablending($inner, true);
    imagesavealpha($inner, true);
    
    // calculate padding
    $x = floor(( $width - $innerWidth ) / 2);
    $y = floor(( $height - $innerHeight ) / 2);
    
    // buid & save
		imagecopyresampled($blankbox, $inner, $x, $y, 0, 0, $width, $height, $width, $height);
    imagepng($blankbox, $file);
    
		return true;
  }
  
	/**
	 * Scale image
	 *
	 * @param   int       $width          The destination width
	 * @param   int       $height         The destination height
   * @return  void
	 */
  protected function scale($width, $height)
  { 
    $w = $this->getWidth();
    $h = $this->getHeight();
    
    if ( $w > $h ){
      $ratio = $width / $w;
      $h = $h * $ratio;
      $this->resize($width, $h);
    }
    elseif ( $w < $h ){
      $ratio = $height / $h;
      $w = $w * $ratio;
      $this->resize($w, $height);
    }
    else {
      $this->resize($width, $height);
    }
  }  
  
	/**
	 * Resize image
	 *
   * Stores the image resource in $this->image
   * 
	 * @param   int       $width          The destination width
	 * @param   int       $height         The destination height
   * @return  void
	 */
  protected function resize($width, $height)
  {
    imagesavealpha($this->image, true);
    $im = imagecreatetruecolor($width, $height);
    
    $background = imagecolorallocatealpha($im, 255, 255, 255, 127);
    imagecolortransparent($im, $background);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    
    imagecopyresampled($im, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
    $this->image = $im;
  }
  
	/**
	 * Get the image resource width
	 *
   * @return  int   The image width
	 */
  protected function getWidth()
  {
    return imagesx($this->image);
  }
  
	/**
	 * Get the image resource height
	 *
   * @return  int   The image height
	 */
  protected function getHeight()
  {
    return imagesy($this->image);
  }
}