<?php

namespace emagine\emstats\controllers;

use Craft;
use craft\web\Controller;
use emagine\emstats\EmStats;
use yii\web\Response;

class SettingsController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected array $allowAnonymous = [];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's settings action URL,
     * e.g.: actions/em-stats/settings/save-settings
     *
     * @return Response The result of the action.
     */
    
    public function actionSaveSettings(): Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // Load existing settings or create new ones
        $plugin = EmStats::getInstance();
        $settings = $plugin->getSettings();

        // Populate the settings with the posted values
        $postedSettings = $request->getBodyParam('settings', []);
        $settings->setAttributes($postedSettings, false);

        // Save settings
        if (!$plugin->saveSettings($settings)) {
            Craft::$app->getSession()->setError(Craft::t('em-stats', 'Couldn’t save settings.'));

            // Send the settings back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('em-stats', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    // Additional methods related to settings can be added here as well.
}
