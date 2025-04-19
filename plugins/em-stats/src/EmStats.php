<?php
/**
 * EmStats—main plugin class
 */

declare(strict_types=1);

namespace emagine\emstats;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\web\Application;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use emagine\emstats\models\Settings;
use emagine\emstats\services\StatsService;
use emagine\emstats\variables\StatsVariable;



/**
 * Class EmStats
 *
 * @property-read Settings $settings
 */
final class EmStats extends Plugin
{
    /** @var self Static reference to this plugin */
    public static EmStats $plugin;

    /** @inheritdoc */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // 🔹 Register the StatsService component so $this->statsService exists
        $this->setComponents([
            'statsService' => StatsService::class,
        ]);

        // 1.  Register site & CP URL rules ----------------------------------
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            static function (RegisterUrlRulesEvent $event) {
                $event->rules['em-stats/handle-form'] = 'em-stats/settings/handle-form';
            }
        );

        // 2.  Defer heavy boot‑strapping until Craft is fully initialised ----
        Event::on(
            Application::class,
            Application::EVENT_INIT,
            function () {
                $this->attachEventHandlers();

                // Expose “emstats” variable to Twig
                Event::on(
                    CraftVariable::class,
                    CraftVariable::EVENT_INIT,
                    static fn(Event $e) => $e->sender->set('emstats', StatsVariable::class),
                );
            }
        );
    }

    /* -------------------------------------------------------------------- */
    /*  Convenience getters                                                 */
    /* -------------------------------------------------------------------- */

    public function getStatsService(): StatsService
    {
        /** @var StatsService $service */
        return $this->get('statsService');
    }

    // ---------------------------------------------------------------------
    // Settings
    // ---------------------------------------------------------------------

    /** @inheritdoc */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /** @inheritdoc */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            '_em-stats/_settings.twig',
            [
                'plugin'   => $this,
                'settings' => $this->getSettings(),
            ],
        );
    }

    // ---------------------------------------------------------------------
    // Internal helpers
    // ---------------------------------------------------------------------

    /** Attach any additional event handlers here. */
    private function attachEventHandlers(): void
    {
        // Example:
        // Event::on(Entry::class, Entry::EVENT_AFTER_SAVE, fn() => Craft::info('Entry saved', __METHOD__));
    }
}
