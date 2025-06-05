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
  'jamesedmonston/graphql-authentication' => 
  array (
    'class' => 'jamesedmonston\\graphqlauthentication\\GraphqlAuthentication',
    'basePath' => $vendorDir . '/jamesedmonston/graphql-authentication/src',
    'handle' => 'graphql-authentication',
    'aliases' => 
    array (
      '@jamesedmonston/graphqlauthentication' => $vendorDir . '/jamesedmonston/graphql-authentication/src',
    ),
    'name' => 'GraphQL Authentication',
    'version' => '3.0.0-RC5',
    'description' => 'GraphQL authentication for your headless Craft CMS applications.',
    'developer' => 'James Edmonston',
    'developerUrl' => 'https://github.com/jamesedmonston',
    'documentationUrl' => 'https://github.com/jamesedmonston/graphql-authentication/blob/master/README.md',
    'changelogUrl' => 'https://raw.githubusercontent.com/jamesedmonston/graphql-authentication/master/CHANGELOG.md',
  ),
);
