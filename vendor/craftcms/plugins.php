<?php

$vendorDir = dirname(__DIR__);
$rootDir = dirname(dirname(__DIR__));

return array (
  'emagine-media-ltd/craft-em-stats' => 
  array (
    'class' => 'emagine\\emstats\\EmStats',
    'basePath' => $vendorDir . '/emagine-media-ltd/craft-em-stats/src',
    'handle' => '_em-stats',
    'aliases' => 
    array (
      '@emagine/emstats' => $vendorDir . '/emagine-media-ltd/craft-em-stats/src',
    ),
    'name' => 'Em Stats',
    'version' => 'dev-main',
    'description' => 'Statistics for VR',
    'developer' => 'Emagine Media Ltd.',
    'documentationUrl' => '',
  ),
  'emagine/craft-venue' => 
  array (
    'class' => 'emagine\\craftvenue\\Plugin',
    'basePath' => $vendorDir . '/emagine/craft-venue/src',
    'handle' => '_venue',
    'aliases' => 
    array (
      '@emagine/craftvenue' => $vendorDir . '/emagine/craft-venue/src',
    ),
    'name' => 'venue',
    'version' => 'dev-main',
    'description' => 'venue editing',
    'developer' => 'Emagine',
    'documentationUrl' => '',
  ),
);
