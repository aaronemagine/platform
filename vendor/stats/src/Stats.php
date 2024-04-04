<?php

namespace emagine\craftstats;

use Craft;
use craft\base\Plugin;

/**
 * stats plugin
 *
 * @method static Stats getInstance()
 */
class Stats extends Plugin
{
    /** @var string The plugin’s schema version number */
    public string $schemaVersion = '1.0.0';

    /**
     * Returns the base config that the plugin should be instantiated with.
     *
     * It is recommended that plugins define their internal components from here:
     *
     * ```php
     * public static function config(): array
     * {
     *     return [
     *         'components' => [
     *             'myComponent' => ['class' => MyComponent::class],
     *             // ...
     *         ],
     *     ];
     * }
     * ```
     *
     * Doing that enables projects to customize the components as needed, by
     * overriding `\craft\services\Plugins::$pluginConfigs` in `config/app.php`:
     *
     * ```php
     * return [
     *     'components' => [
     *         'plugins' => [
     *             'pluginConfigs' => [
     *                 'my-plugin' => [
     *                     'components' => [
     *                         'myComponent' => [
     *                             'myProperty' => 'foo',
     *                             // ...
     *                         ],
     *                     ],
     *                 ],
     *             ],
     *         ],
     *     ],
     * ];
     * ```
     *
     * The resulting config will be passed to `\Craft::createObject()` to instantiate the plugin.
     *
     * @return array
     */
    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    /**
     * Initializes the module.
     *
     * This method is called after the module is created and initialized with property values
     * given in configuration. The default implementation will initialize [[controllerNamespace]]
     * if it is not set.
     *
     * If you override this method, please make sure you call the parent implementation.
     */
    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });


        // Hook into the onBeforeRequest event
        craft()->on('request.onBeforeRequest', function(Event $event) {
            craft()->yourcraftstats_redirect->redirectLoggedOutUsers();
        });
        }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
    }
}
