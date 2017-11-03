<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Favicon;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class GenerateTest extends TestCase
{
  private $umask;
  
  /**
   * The directory to test file creation
   * 
   * @var string
   */
  protected $workspace = null;

  /**
   * The Symfony Filesystem Component instance
   * 
   * @var Filesystem
   */
  protected $filesystem = null;
  
  /**
   * Setup some vars for the process
   */
  protected function setUp()
  {
    $this->umask = umask(0);
    
    $this->filesystem = new Filesystem();
    
    $this->workspace = sys_get_temp_dir().'/'.microtime(true).'.'.mt_rand();
    mkdir($this->workspace, 0777, true);
    $this->workspace = realpath($this->workspace);
  }
  
  /** 
   * Remove created folder & files and reset original umask
   */
  protected function tearDown()
  {
    $this->filesystem->remove($this->workspace);
    
    umask($this->umask);
  }

  /**
   * With all favicons (23)
   * 
   * @return  void
   */
  public function testGenerateAll()
  {
    $ok = $this->getTestGeneratorResult('all');
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no old apple favicons (19)
   * 
   * @return  void
   */
  public function testGenerateNoOldApple()
  {
    $ok = $this->getTestGeneratorResult('noOldApple', [ 
      'noOldApple' => true,
    ]);
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no android favicons (18)
   * 
   * @return  void
   */
  public function testGenerateNoAndroid()
  {
    $ok = $this->getTestGeneratorResult('noAndroid', [ 
      'noAndroid' => true,
    ]);
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no ms favicons (18)
   * 
   * @return  void
   */
  public function testGenerateNoMs()
  {
    $ok = $this->getTestGeneratorResult('noMs', [ 
      'noMs' => true,
    ]);
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no old apple, android or ms favicons (9)
   * 
   * @return  void
   */
  public function testGenerateNone()
  {
    $ok = $this->getTestGeneratorResult('none', [ 
      'noOldApple' => true,
      'noAndroid' => true,
      'noMs' => true,
    ]);
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no 64px icon
   * 
   * @return  void
   */
  public function testGenerateNo64Icon()
  {
    $ok = $this->getTestGeneratorResult('no64', [ 
      'use64Icon' => false,
    ]);
    
    $this->assertTrue($ok);
  }
  
  /**
   * With no 64px icon
   * 
   * @return  void
   */
  public function testGenerateNo48Icon()
  {
    $ok = $this->getTestGeneratorResult('no48', [ 
      'use48Icon' => false,
    ]);
    
    $this->assertTrue($ok);
  }
  
  
  protected function getTestGeneratorResult($case='all', array $testConfig=[])
  {
    $config = array_merge([
      'filePath'      => realpath(dirname(__FILE__)).'/favicon.png',
      'destPath'      => $this->workspace.'/',
      'appName'       => 'Application name',
      'appShortName'  => 'AppName',
      'appLanguage'   => 'fr-FR',
      'appStartUrl'   => './?manifest=true',
      'appThemeColor' => '#F9F9F9',
      'appBgColor'    => '#F9F9F9',
      'appDisplay'    => 'standalone',
      'use64Icon'     => true,
      'use48Icon'     => true,
      'noOldApple'    => false,
      'noAndroid'     => false,
      'noMs'          => false,
    ], $testConfig);
    
    $generator = new Generator($config);
    
    try {
      $generator->execute();
    }
    catch(Exception\GeneratorException $e){
      return false;
    }
    
    return true;
  }
}
