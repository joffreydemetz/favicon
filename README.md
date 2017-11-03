# Favicon Generator
This package allows you to build the most common favicons from a single PNG source file.

It can include : 
- MS favicons & tiles, manifest.json
- old Apple favicons
- Android favicons, browserconfig.xml
- 64px icon
- 48px icon

## Basic usage

*[PATH_TO_SOURCE_FILE]* is the absolute path to your favicon source PNG file.

*[DESTINATION_PATH]* is the absolute path where you want the favicons to be stored (eg. the public directory of your website)

```
$config = array_merge([
  'filePath'      => '[PATH_TO_SOURCE_FILE]/favicon.png',
  'destPath'      => '[DESTINATION_PATH]/',
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

$generator = new \JDZ\Favicon\Generator($config);

try {
  $generator->execute();
  
  // the info buffer stores the written files in an array
  $list_of_written_files = $generator->getInfoBuffer();
  echo "<pre>";
  print_r($list_of_written_files);
  echo "</pre>";
}
catch(\JDZ\Favicon\Exception\GeneratorException $e){
  echo $e->getMessage();
}
```
