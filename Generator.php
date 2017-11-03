<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Favicon;

use JDZ\Filesystem\Folder;
use JDZ\Filesystem\File;
use JDZ\Filesystem\Path;

use JDZ\Favicon\Exception\ConfigException;
use JDZ\Favicon\Exception\GeneratorException;

use Exception;

/**
 * Favicon generator
 * 
 * @author      Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class Generator
{
  /**
   * Input file path
   *
   * @var string
   */
  protected $filePath;

  /**
   * Output folder which contains ico and png files
   * 
   */
  protected $destPath;

  /**
   * Include the 64x64 image in the ICO or not
   *
   * @var bool 
   */
  protected $use64Icon;

  /**
   * Include the 48x48 image in the ICO or not
   *
   * @var bool 
   */
  protected $use48Icon;

  /**
   * Exclude old apple touch images
   *
   * @var type 
   */
  protected $noOldApple;

  /**
   * Exclude manifest.json and Android images
   * 
   * @var type 
   */
  protected $noAndroid;

  /**
   * Exclude Windows and IE tile images
   *
   * @var type 
   */
  protected $noMs;

  /**
   * Android manifest app name
   *
   * @var string
   */
  protected $appName;

  /**
   * Android manifest app short name
   *
   * @var string
   */
  protected $appShortName;

  /**
   * Android manifest app language
   *
   * @var string
   */
  protected $appLanguage;

  /**
   * Android manifest app start url
   *
   * @var string
   */
  protected $appStartUrl;

  /**
   * Android manifest app theme color
   *
   * @var string
   */
  protected $appThemeColor;

  /**
   * Android manifest app background color
   *
   * @var string
   */
  protected $appBgColor;

  /**
   * Android manifest app display type
   *
   * @var string
   */
  protected $appDisplay;

  /**
   * Info Output buffer
   * 
   * Used to debug what happened during process
   *
   * @var array
   */
  protected $infoBuffer;

  /**
   * Constructor
   *
   * @param   array   $properties   Key/Value pairs
   */
  public function __construct(array $properties=[])
  {
    foreach($properties as $key => $value){
      $this->{$key} = $value;
    }
    
    $this->filePath   = Path::clean($this->filePath);
    $this->destPath   = Path::clean($this->destPath);
    $this->infoBuffer = [];
  }
  
  /**
   * Get the info buffer
   *
   * @return   array   The execution buffer infos
   */
  public function getInfoBuffer()
  {
    return $this->infoBuffer;
  }
  
  /**
   * Build favicons
   *
   * @return void
   * @throws GeneratorException
   */
  public function execute()
  {
    try {
      if ( !Folder::exists($this->destPath) ){
        Folder::create($this->destPath);
      }
    }
    catch(Exception $e){
      throw new GeneratorException('Error creating the root folder: '.$this->destPath);
    }
    
    try {
      if ( !Folder::exists($this->destPath.'favicon/') ){
        Folder::create($this->destPath.'favicon/');
      }
    }
    catch(Exception $e){
      throw new GeneratorException('Error creating the favicon folder in '.$this->destPath);
    }
    
    if ( '' === $this->filePath || !File::exists($this->filePath) ){
      throw new GeneratorException('Input file does not exist: '.$this->filePath);
    }
    
    try {
      Ico::checkDependencies();
      Png::checkDependencies();
    } catch(Exception $e){
      throw new GeneratorException($e->getMessage());
    }
    
    $this->generateIco();
    $this->generatePngs();
    
    if ( $this->noAndroid === false ){
      $this->generateManifestJson();
    }
    
    if ( $this->noMs === false ){
      $this->infoBuffer[] = 'browserconfig.xml (Microsoft)';
      $this->generateBrowserConfigXml();
    }
  }
  
  /**
   * Generate ICO icon
   * 
   * @return void
   * @throws GeneratorException
   */
  protected function generateIco()
  {
    $ico = new Ico();
    
    $ico->add($this->filePath, [16, 16]);
    
    if ( $this->use48Icon === true ){
      $ico->add($this->filePath, [48, 48]);
    }
    
    if ( $this->use64Icon === true ){
      $ico->add($this->filePath, [64, 64]);
    }
    
    $destPath = Path::clean($this->destPath.'favicon/favicon.ico');
    $ico->save($destPath);
    
    if ( !File::exists($destPath) ){
      throw new GeneratorException('Failed writing favicon/favicon.ico');
    }
    
    $this->infoBuffer[] = $destPath;
    
    $destPath2 = Path::clean($this->destPath.'favicon.ico');
    
    $this->infoBuffer[] = $destPath2;
    
    try {
      File::copy($destPath, $destPath2);
    }
    catch(Exception $e){
      throw new GeneratorException('Failed copying favicon/favicon.ico to favicon.ico ('.$e->getMessage().')');
    }
  }
  
  /**
   * Generate PNG files
   * 
   * @return void
   * @throws GeneratorException
   */
  protected function generatePngs()
  {
    try {
      $sizes = Config::getSizes($this->noOldApple, $this->noAndroid, $this->noMs);
    }
    catch(ConfigException $e){
      throw new GeneratorException('Config error: '.$e->getMessage());
    }
    
    foreach($sizes as $imageName => $size){
      if ( !is_array($size) ){
        $size = [ $size ];
      }
      
      if ( count($size) === 1 ){
        $size = [ $size[0], $size[0] ];
      }
      
      list($width, $height) = $size;
      
      $png = new Png($this->filePath);
      
      $imagePath = Path::clean($this->destPath.'/favicon/'.$imageName);
      if ( substr($imageName, 0, 6) === 'mstile' && $width !== 144 ){
        $png->tile($imagePath, $this->appThemeColor, $width, $height);
      }
      else {
        $png->square($imagePath, $width);
      }
      
      if ( !File::exists($imagePath) ){
        throw new GeneratorException('Failed writing '.$imageName);
      }
      
      $this->infoBuffer[] = $imagePath;
    }
  }
  
  /**
   * Generate The manifest.json file
   * 
   * @return void
   */
  protected function generateManifestJson()
  {
    $manifest = [];
    
    if ( $this->appName !== '' ){
      $manifest['name'] = $this->appName;
    }
    
    if ( $this->appShortName !== '' ){
      $manifest['short_name'] = $this->appShortName;
    }
    
    if ( $this->appLanguage !== '' ){
      $manifest['lang'] = $this->appLanguage;
    }
    
    if ( $this->appStartUrl !== '' ){
      $manifest['start_url'] = $this->appStartUrl;
    }
    
    $manifest['icons'] = [];
    
    if ( $this->appThemeColor !== '' ){
      $manifest['theme_color'] = $this->appThemeColor;
    }
    
    if ( $this->appBgColor !== '' ){
      $manifest['background_color'] = $this->appBgColor;
    }
    
    if ( $this->appDisplay !== '' ){
      $manifest['display'] = $this->appDisplay;
    }
    
    foreach(array(36, 48, 72, 96, 144, 192) as $size){
      $manifest['icons'][] = array(
        'src'     => '/favicon/android-chrome-'.$size.'x'.$size.'.png',
        'sizes'   => $size.'x'.$size,
        'type'    => 'image/png',
        'density' => round($size / 48.0, 2)
      );
    }
    
    $json = json_encode($manifest, JSON_PRETTY_PRINT);
    $jsonFilePath = Path::clean($this->destPath.'favicon/manifest.json');
    
    $this->infoBuffer[] = $jsonFilePath;
    
    try {
      File::write($jsonFilePath, $json);
    }
    catch(Exception $e){
      throw new GeneratorException('Failed writing manifest.json ('.$e->getMessage().')');
    }
  }
  
  /**
   * Generate The browserconfig.xml file
   * 
   * @return void
   */
  protected function generateBrowserConfigXml()
  {
    $xml  = '';
    $xml .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
    $xml .= "".'<browserconfig>'."\n";
    $xml .= "\t".'<msapplication>'."\n";
    $xml .= "\t\t".'<tile>'."\n";
    $xml .= "\t\t\t".'<square70x70logo src="/favicon/mstile-70x70.png" />'."\n";
    $xml .= "\t\t\t".'<square144x144logo src="/favicon/mstile-144x144.png "/>'."\n";
    $xml .= "\t\t\t".'<square150x150logo src="/favicon/mstile-150x150.png" />'."\n";
    $xml .= "\t\t\t".'<square310x310logo src="/favicon/mstile-310x310.png" />'."\n";
    $xml .= "\t\t\t".'<wide310x150logo src="/favicon/mstile-310x150.png" />'."\n";
    if ( $this->appThemeColor !== '' ){
      $xml .= "\t\t\t".'<TileColor>'.$this->appThemeColor.'</TileColor>'."\n";
    }
    $xml .= "\t\t".'</tile>'."\n";
    $xml .= "\t".'</msapplication>'."\n";
    $xml .= '</browserconfig>'."\n";
    
    $xmlFilePath = Path::clean($this->destPath.'favicon/browserconfig.xml');
    
    $this->infoBuffer[] = $xmlFilePath;
    
    try {
      File::write($xmlFilePath, $xml);
    }
    catch(Exception $e){
      throw new GeneratorException('Failed writing browserconfig.xml ('.$e->getMessage().')');
    }
  }
}
