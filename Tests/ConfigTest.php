<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Favicon;

use PHPUnit\Framework\TestCase;

/**
 * @package Test
 * @author  Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class ConfigTest extends TestCase
{
  /**
   * Test the getSize method with default arguments
   * 
   * @return  void
   */
  public function testGetSizes()
  {
    $sizes = Config::getSizes();
    $this->assertCount(23, $sizes);
  }
  
  /**
   * Test the getSize method with no old apple
   * 
   * @return  void
   */
  public function testGetSizesNoOldApple()
  {
    $sizes = Config::getSizes(true);
    $this->assertCount(19, $sizes);
    $this->assertArrayNotHasKey('apple-touch-icon-57x57.png', $sizes);
    $this->assertArrayNotHasKey('apple-touch-icon-60x60.png', $sizes);
    $this->assertArrayNotHasKey('apple-touch-icon-72x72.png', $sizes);
    $this->assertArrayNotHasKey('apple-touch-icon-114x114.png', $sizes);
  }

  /**
   * Test the getSize method with no android
   * 
   * @return  void
   */
  public function testgetSizesNoAndroid()
  {
    $sizes = Config::getSizes(false, true);
    $this->assertCount(18, $sizes);
    $this->assertArrayNotHasKey('android-chrome-36x36.png', $sizes);
    $this->assertArrayNotHasKey('android-chrome-48x48.png', $sizes);
    $this->assertArrayNotHasKey('android-chrome-72x72.png', $sizes);
    $this->assertArrayNotHasKey('android-chrome-96x96.png', $sizes);
    $this->assertArrayNotHasKey('android-chrome-144x144.png', $sizes);
  }

  /**
   * Test the getSize method with no ms
   * 
   * @return  void
   */
  public function testGetSizesNoMs()
  {
    $sizes = Config::getSizes(false, true);
    $this->assertCount(18, $sizes);
    $this->assertArrayNotHasKey('mstile-70x70', $sizes);
    $this->assertArrayNotHasKey('mstile-144x144', $sizes);
    $this->assertArrayNotHasKey('mstile-150x150', $sizes);
    $this->assertArrayNotHasKey('mstile-310x310', $sizes);
    $this->assertArrayNotHasKey('mstile-310x150', $sizes);
  }

  /**
   * Test the getSize method with all config
   * 
   * @return  void
   */
  public function testGetSizesNoAll()
  {
    $sizes = Config::getSizes(true, true, true);
    $this->assertCount(9, $sizes);
  }
  
  /**
   * Test the getTileSettings method
   * 
   * @return  void
   */
  public function testGetTileSettings()
  {
    $opt = Config::getTileSettings('mstile-310x310.png');
    $this->assertCount(5, $opt);
    $this->assertArrayHasKey('w', $opt);
    $this->assertArrayHasKey('h', $opt);
    $this->assertArrayHasKey('icon', $opt);
    $this->assertArrayHasKey('top', $opt);
    $this->assertArrayHasKey('left', $opt);
  }
  
  /**
   * Test the getSize method with no android
   * 
   * @return  void
   */
  public function testGetTileSettingsError()
  {
    $exceptionCaught = false;
    try {
      $opt = Config::getTileSettings('mstile-300x300.png');      
    } catch (\Exception $e){
      $exceptionCaught = true;
    }
    
    $this->assertTrue($exceptionCaught, 'No tile settings error caught.');
  }
}
