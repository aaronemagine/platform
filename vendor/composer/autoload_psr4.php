<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    'yii\\symfonymailer\\' => array($vendorDir . '/yiisoft/yii2-symfonymailer/src'),
    'yii\\shell\\' => array($vendorDir . '/yiisoft/yii2-shell'),
    'yii\\queue\\sync\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/sync'),
    'yii\\queue\\stomp\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/stomp'),
    'yii\\queue\\sqs\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/sqs'),
    'yii\\queue\\redis\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/redis'),
    'yii\\queue\\gearman\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/gearman'),
    'yii\\queue\\file\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/file'),
    'yii\\queue\\db\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/db'),
    'yii\\queue\\beanstalk\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/beanstalk'),
    'yii\\queue\\amqp_interop\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp_interop'),
    'yii\\queue\\amqp\\' => array($vendorDir . '/yiisoft/yii2-queue/src/drivers/amqp'),
    'yii\\queue\\' => array($vendorDir . '/yiisoft/yii2-queue/src'),
    'yii\\debug\\' => array($vendorDir . '/yiisoft/yii2-debug/src'),
    'yii\\composer\\' => array($vendorDir . '/yiisoft/yii2-composer'),
    'yii\\' => array($vendorDir . '/yiisoft/yii2'),
    'yii2tech\\ar\\softdelete\\' => array($vendorDir . '/craftcms/cms/lib/ar-softdelete/src'),
    'voku\\helper\\' => array($vendorDir . '/voku/urlify/src/voku/helper', $vendorDir . '/voku/email-check/src/voku/helper', $vendorDir . '/voku/anti-xss/src/voku/helper'),
    'voku\\' => array($vendorDir . '/voku/stop-words/src/voku', $vendorDir . '/voku/portable-utf8/src/voku', $vendorDir . '/voku/portable-ascii/src/voku'),
    'samdark\\log\\tests\\' => array($vendorDir . '/samdark/yii2-psr-log-target/tests'),
    'samdark\\log\\' => array($vendorDir . '/samdark/yii2-psr-log-target/src'),
    'phpseclib3\\' => array($vendorDir . '/phpseclib/phpseclib/phpseclib'),
    'phpDocumentor\\Reflection\\' => array($vendorDir . '/phpdocumentor/reflection-docblock/src', $vendorDir . '/phpdocumentor/type-resolver/src', $vendorDir . '/phpdocumentor/reflection-common/src'),
    'modules\\' => array($baseDir . '/modules'),
    'mikehaertl\\shellcommand\\' => array($vendorDir . '/mikehaertl/php-shellcommand/src'),
    'jamesedmonston\\graphqlauthentication\\' => array($vendorDir . '/jamesedmonston/graphql-authentication/src'),
    'enshrined\\svgSanitize\\' => array($vendorDir . '/enshrined/svg-sanitize/src'),
    'emagine\\emstats\\' => array($vendorDir . '/emagine-media-ltd/craft-em-stats/src'),
    'emagine\\craftvenue\\' => array($vendorDir . '/emagine/craft-venue/src'),
    'creocoder\\nestedsets\\' => array($vendorDir . '/creocoder/yii2-nested-sets/src'),
    'craft\\generator\\' => array($vendorDir . '/craftcms/generator/src'),
    'craft\\composer\\' => array($vendorDir . '/craftcms/plugin-installer/src'),
    'craft\\' => array($vendorDir . '/craftcms/cms/src'),
    'cebe\\markdown\\' => array($vendorDir . '/cebe/markdown'),
    'Webmozart\\Assert\\' => array($vendorDir . '/webmozart/assert/src'),
    'Webauthn\\' => array($vendorDir . '/web-auth/webauthn-lib/src'),
    'Twig\\' => array($vendorDir . '/twig/twig/src'),
    'TheNetworg\\OAuth2\\Client\\' => array($vendorDir . '/thenetworg/oauth2-azure/src'),
    'TheIconic\\NameParser\\' => array($vendorDir . '/theiconic/name-parser/src', $vendorDir . '/theiconic/name-parser/tests'),
    'Symfony\\Polyfill\\Uuid\\' => array($vendorDir . '/symfony/polyfill-uuid'),
    'Symfony\\Polyfill\\Php84\\' => array($vendorDir . '/symfony/polyfill-php84'),
    'Symfony\\Polyfill\\Php81\\' => array($vendorDir . '/symfony/polyfill-php81'),
    'Symfony\\Polyfill\\Php80\\' => array($vendorDir . '/symfony/polyfill-php80'),
    'Symfony\\Polyfill\\Mbstring\\' => array($vendorDir . '/symfony/polyfill-mbstring'),
    'Symfony\\Polyfill\\Intl\\Normalizer\\' => array($vendorDir . '/symfony/polyfill-intl-normalizer'),
    'Symfony\\Polyfill\\Intl\\Idn\\' => array($vendorDir . '/symfony/polyfill-intl-idn'),
    'Symfony\\Polyfill\\Intl\\Grapheme\\' => array($vendorDir . '/symfony/polyfill-intl-grapheme'),
    'Symfony\\Polyfill\\Iconv\\' => array($vendorDir . '/symfony/polyfill-iconv'),
    'Symfony\\Polyfill\\Ctype\\' => array($vendorDir . '/symfony/polyfill-ctype'),
    'Symfony\\Contracts\\Service\\' => array($vendorDir . '/symfony/service-contracts'),
    'Symfony\\Contracts\\HttpClient\\' => array($vendorDir . '/symfony/http-client-contracts'),
    'Symfony\\Contracts\\EventDispatcher\\' => array($vendorDir . '/symfony/event-dispatcher-contracts'),
    'Symfony\\Component\\Yaml\\' => array($vendorDir . '/symfony/yaml'),
    'Symfony\\Component\\VarDumper\\' => array($vendorDir . '/symfony/var-dumper'),
    'Symfony\\Component\\Uid\\' => array($vendorDir . '/symfony/uid'),
    'Symfony\\Component\\TypeInfo\\' => array($vendorDir . '/symfony/type-info'),
    'Symfony\\Component\\String\\' => array($vendorDir . '/symfony/string'),
    'Symfony\\Component\\Serializer\\' => array($vendorDir . '/symfony/serializer'),
    'Symfony\\Component\\PropertyInfo\\' => array($vendorDir . '/symfony/property-info'),
    'Symfony\\Component\\PropertyAccess\\' => array($vendorDir . '/symfony/property-access'),
    'Symfony\\Component\\Process\\' => array($vendorDir . '/symfony/process'),
    'Symfony\\Component\\Mime\\' => array($vendorDir . '/symfony/mime'),
    'Symfony\\Component\\Mailer\\' => array($vendorDir . '/symfony/mailer'),
    'Symfony\\Component\\HttpClient\\' => array($vendorDir . '/symfony/http-client'),
    'Symfony\\Component\\Filesystem\\' => array($vendorDir . '/symfony/filesystem'),
    'Symfony\\Component\\EventDispatcher\\' => array($vendorDir . '/symfony/event-dispatcher'),
    'Symfony\\Component\\DomCrawler\\' => array($vendorDir . '/symfony/dom-crawler'),
    'Symfony\\Component\\CssSelector\\' => array($vendorDir . '/symfony/css-selector'),
    'Symfony\\Component\\Console\\' => array($vendorDir . '/symfony/console'),
    'Stringy\\' => array($vendorDir . '/voku/stringy/src'),
    'SpomkyLabs\\Pki\\' => array($vendorDir . '/spomky-labs/pki-framework/src'),
    'Seld\\CliPrompt\\' => array($vendorDir . '/seld/cli-prompt/src'),
    'Psy\\' => array($vendorDir . '/psy/psysh/src'),
    'Psr\\SimpleCache\\' => array($vendorDir . '/psr/simple-cache/src'),
    'Psr\\Log\\' => array($vendorDir . '/psr/log/src'),
    'Psr\\Http\\Message\\' => array($vendorDir . '/psr/http-factory/src', $vendorDir . '/psr/http-message/src'),
    'Psr\\Http\\Client\\' => array($vendorDir . '/psr/http-client/src'),
    'Psr\\EventDispatcher\\' => array($vendorDir . '/psr/event-dispatcher/src'),
    'Psr\\Container\\' => array($vendorDir . '/psr/container/src'),
    'Psr\\Clock\\' => array($vendorDir . '/psr/clock/src'),
    'Psr\\Cache\\' => array($vendorDir . '/psr/cache/src'),
    'PragmaRX\\Recovery\\' => array($vendorDir . '/pragmarx/recovery/src'),
    'PragmaRX\\Random\\' => array($vendorDir . '/pragmarx/random/src'),
    'PragmaRX\\Google2FA\\' => array($vendorDir . '/pragmarx/google2fa/src'),
    'PhpParser\\' => array($vendorDir . '/nikic/php-parser/lib/PhpParser'),
    'PhpOption\\' => array($vendorDir . '/phpoption/phpoption/src/PhpOption'),
    'ParagonIE\\ConstantTime\\' => array($vendorDir . '/paragonie/constant_time_encoding/src'),
    'PHPStan\\PhpDocParser\\' => array($vendorDir . '/phpstan/phpdoc-parser/src'),
    'Monolog\\' => array($vendorDir . '/monolog/monolog/src/Monolog'),
    'Money\\' => array($vendorDir . '/moneyphp/money/src'),
    'Masterminds\\' => array($vendorDir . '/masterminds/html5/src'),
    'LitEmoji\\' => array($vendorDir . '/elvanto/litemoji/src'),
    'League\\Uri\\' => array($vendorDir . '/league/uri', $vendorDir . '/league/uri-interfaces'),
    'League\\OAuth2\\Client\\' => array($vendorDir . '/league/oauth2-facebook/src', $vendorDir . '/league/oauth2-client/src'),
    'Lcobucci\\JWT\\' => array($vendorDir . '/lcobucci/jwt/src'),
    'Lcobucci\\Clock\\' => array($vendorDir . '/lcobucci/clock/src'),
    'Imagine\\' => array($vendorDir . '/pixelandtonic/imagine/src'),
    'Illuminate\\Support\\' => array($vendorDir . '/illuminate/collections', $vendorDir . '/illuminate/macroable', $vendorDir . '/illuminate/conditionable'),
    'Illuminate\\Contracts\\' => array($vendorDir . '/illuminate/contracts'),
    'GuzzleHttp\\Psr7\\' => array($vendorDir . '/guzzlehttp/psr7/src'),
    'GuzzleHttp\\Promise\\' => array($vendorDir . '/guzzlehttp/promises/src'),
    'GuzzleHttp\\' => array($vendorDir . '/guzzlehttp/guzzle/src'),
    'GraphQL\\' => array($vendorDir . '/webonyx/graphql-php/src'),
    'GrahamCampbell\\ResultType\\' => array($vendorDir . '/graham-campbell/result-type/src'),
    'Google\\Service\\' => array($vendorDir . '/google/apiclient-services/src'),
    'Google\\Auth\\' => array($vendorDir . '/google/auth/src'),
    'Google\\' => array($vendorDir . '/google/apiclient/src'),
    'Firebase\\JWT\\' => array($vendorDir . '/firebase/php-jwt/src'),
    'Egulias\\EmailValidator\\' => array($vendorDir . '/egulias/email-validator/src'),
    'Dotenv\\' => array($vendorDir . '/vlucas/phpdotenv/src'),
    'Doctrine\\Deprecations\\' => array($vendorDir . '/doctrine/deprecations/src'),
    'Doctrine\\Common\\Lexer\\' => array($vendorDir . '/doctrine/lexer/src'),
    'Doctrine\\Common\\Collections\\' => array($vendorDir . '/doctrine/collections/src'),
    'Defuse\\Crypto\\' => array($vendorDir . '/defuse/php-encryption/src'),
    'DASPRiD\\Enum\\' => array($vendorDir . '/dasprid/enum/src'),
    'Cose\\' => array($vendorDir . '/web-auth/cose-lib/src'),
    'Composer\\Semver\\' => array($vendorDir . '/composer/semver/src'),
    'Composer\\CaBundle\\' => array($vendorDir . '/composer/ca-bundle/src'),
    'CommerceGuys\\Addressing\\' => array($vendorDir . '/commerceguys/addressing/src'),
    'CBOR\\' => array($vendorDir . '/spomky-labs/cbor-php/src'),
    'Brick\\Math\\' => array($vendorDir . '/brick/math/src'),
    'BaconQrCode\\' => array($vendorDir . '/bacon/bacon-qr-code/src'),
    'Arrayy\\' => array($vendorDir . '/voku/arrayy/src'),
    'Abraham\\TwitterOAuth\\' => array($vendorDir . '/abraham/twitteroauth/src'),
);
