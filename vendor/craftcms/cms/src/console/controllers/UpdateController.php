<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Composer\Semver\VersionParser;
use Craft;
use craft\console\Controller;
use craft\elements\User;
use craft\errors\InvalidPluginException;
use craft\helpers\App;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Update as UpdateHelper;
use craft\models\Update;
use craft\models\Updates;
use craft\models\Updates as UpdatesModel;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Throwable;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use yii\validators\EmailValidator;

/**
 * Updates Craft and plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.38
 */
class UpdateController extends Controller
{
    use BackupTrait;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'update';

    /**
     * @var bool Whether to update expired licenses.
     *
     * NOTE: This will result in “License purchase required” messages in the control panel on public domains,
     * until the licenses have been renewed.
     *
     * @since 4.8.0
     */
    public bool $withExpired = false;

    /**
     * @var bool Whether only minor updates should be applied.
     * @since 5.5.0
     */
    public bool $minorOnly = false;

    /**
     * @var bool Whether only patch updates should be applied.
     * @since 5.5.0
     */
    public bool $patchOnly = false;

    /**
     * @var string[] Plugin handles to exclude
     * @since 5.5.0
     */
    public array $except = [];

    /**
     * @var bool Force the update if allowUpdates is disabled
     */
    public bool $force = false;

    /**
     * @var bool|null Backup the database before updating
     */
    public ?bool $backup = null;

    /**
     * @var bool Run new database migrations after completing the update
     */
    public bool $migrate = true;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        if ($actionID === 'update') {
            $options[] = 'withExpired';
            $options[] = 'minorOnly';
            $options[] = 'patchOnly';
            $options[] = 'except';
            $options[] = 'force';
            $options[] = 'backup';
            $options[] = 'migrate';
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        $aliases = parent::optionAliases();
        $aliases['f'] = 'force';
        return $aliases;
    }

    /**
     * Displays info about available updates.
     */
    public function actionInfo(): int
    {
        // Make sure they have a valid Craft license
        if (($exitCode = $this->_checkCraftLicense()) !== null) {
            return $exitCode;
        }

        $updates = $this->_getUpdates();

        if (($total = $updates->getTotal()) === 0) {
            $this->stdout('You’re all up to date!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('You’ve got ', Console::FG_GREEN);
        $this->stdout($total === 1 ? 'one' : $total, Console::FG_GREEN, Console::BOLD);
        $this->stdout(' available update' . ($total === 1 ? '' : 's') . ':' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

        if ($updates->cms->getHasReleases()) {
            $this->_outputUpdate('craft', Craft::$app->version, $updates->cms->getLatest()->version, $updates->cms->getHasCritical(), $updates->cms->status, $updates->cms->phpConstraint);
        }

        $pluginsService = Craft::$app->getPlugins();

        foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
            if ($pluginUpdate->getHasReleases()) {
                try {
                    $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                } catch (InvalidPluginException) {
                    continue;
                }
                if ($pluginInfo['isInstalled']) {
                    $this->_outputUpdate($pluginHandle, $pluginInfo['version'], $pluginUpdate->getLatest()->version, $pluginUpdate->getHasCritical(), $pluginUpdate->status, $pluginUpdate->phpConstraint);
                }
            }
        }

        $this->stdout(PHP_EOL . 'Run ');
        $this->outputCommand('update all');
        $this->stdout(' or ');
        $this->outputCommand('update <handle>');
        $this->stdout(' to perform an update.' . PHP_EOL . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Updates Craft and/or plugins.
     *
     * @param string|null $handle The update handle (`all`, `craft`, or a plugin handle).
     * You can pass multiple handles separated by spaces, and you can update to a specific
     * version using the syntax `<handle>:<version>`.
     * @return int
     */
    public function actionUpdate(?string $handle = null): int
    {
        $handles = array_filter(func_get_args());

        if (empty($handles)) {
            return $this->runAction('info');
        }

        // Make sure updates are allowed
        if (!$this->_allowUpdates()) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Make sure they have a valid Craft license
        if (($exitCode = $this->_checkCraftLicense()) !== null) {
            return $exitCode;
        }

        // Figure out the new requirements
        $requirements = $this->_getRequirements(...$handles);
        if (empty($requirements)) {
            return ExitCode::OK;
        }

        // Try to backup the DB
        if (!$this->backup($this->backup)) {
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run the update
        if (!$this->_performUpdate($requirements)) {
            $this->_revertComposerChanges();
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Run migrations?
        if (!$this->_migrate()) {
            if ($this->restore()) {
                $this->_revertComposerChanges();
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('Update complete!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Installs dependencies based on the current `composer.json` & `composer.lock`.
     *
     * @return int
     */
    public function actionComposerInstall(): int
    {
        $this->stdout('Performing Composer install ... ', Console::FG_YELLOW);
        $output = '';

        try {
            Craft::$app->getComposer()->install(null, function($type, $buffer) use (&$output) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $output . PHP_EOL . PHP_EOL);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Returns whether updates are allowed.
     *
     * @return bool
     */
    private function _allowUpdates(): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->allowUpdates && !$this->force) {
            if (!$this->interactive) {
                $this->stderr('Updates are disallowed for this environment. Pass --force to override.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }

            if (!$this->confirm('Updates are disallowed for this environment. Update anyway?')) {
                $this->stderr('Aborting update.' . PHP_EOL . PHP_EOL, Console::FG_RED);
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the new Composer requirements.
     *
     * @param string ...$handles
     * @return array
     */
    private function _getRequirements(string ...$handles): array
    {
        $constraints = [];
        $pluginsService = Craft::$app->getPlugins();

        if ($this->minorOnly || $this->patchOnly) {
            $cmsConstraint = $this->_constraint(Craft::$app->getVersion());
            if ($cmsConstraint !== null) {
                $constraints['cms'] = $cmsConstraint;
            }

            foreach ($pluginsService->getAllPlugins() as $plugin) {
                // don't update dev versions
                $version = $plugin->getVersion();
                if (VersionParser::parseStability($version) === 'dev') {
                    continue;
                }

                $pluginConstraint = $this->_constraint($version);
                if ($pluginConstraint !== null) {
                    $constraints[$plugin->id] = $pluginConstraint;
                }
            }
        }

        if ($handles !== ['all']) {
            // Look for any specific versions that were requested
            foreach ($handles as $handle) {
                if (str_contains($handle, ':')) {
                    [$handle, $to] = explode(':', $handle, 2);
                    if ($handle === 'craft') {
                        $handle = 'cms';
                    }
                    $constraints[$handle] = $to;
                }
            }
        }

        $updates = $this->_getUpdates($constraints);
        $info = [];
        $requirements = [];

        if ($handles === ['all']) {
            if (
                !in_array('craft', $this->except) &&
                ($latest = $updates->cms->getLatest()) !== null
            ) {
                $this->_updateRequirements($requirements, $info, 'craft', Craft::$app->version, $latest->version, 'craftcms/cms', $updates->cms);
            }

            foreach ($updates->plugins as $pluginHandle => $pluginUpdate) {
                if (
                    !in_array($pluginHandle, $this->except) &&
                    ($latest = $pluginUpdate->getLatest()) !== null
                ) {
                    try {
                        $pluginInfo = $pluginsService->getPluginInfo($pluginHandle);
                    } catch (InvalidPluginException) {
                        continue;
                    }
                    if ($pluginInfo['isInstalled']) {
                        $this->_updateRequirements($requirements, $info, $pluginHandle, $pluginInfo['version'], $latest->version, $pluginInfo['packageName'], $pluginUpdate);
                    }
                }
            }
        } else {
            foreach ($handles as $handle) {
                if (str_contains($handle, ':')) {
                    [$handle, $to] = explode(':', $handle, 2);
                } else {
                    $to = null;
                }

                if ($handle === 'craft') {
                    $this->_updateRequirements($requirements, $info, $handle, Craft::$app->version, $to, 'craftcms/cms', $updates->cms);
                } else {
                    $pluginInfo = null;
                    if (isset($updates->plugins[$handle])) {
                        try {
                            $pluginInfo = $pluginsService->getPluginInfo($handle);
                        } catch (InvalidPluginException) {
                        }
                    }

                    if ($pluginInfo === null || !$pluginInfo['isInstalled']) {
                        $this->stdout('No plugin exists with the handle “' . $handle . '”.' . PHP_EOL, Console::FG_RED);
                        continue;
                    }

                    $this->_updateRequirements($requirements, $info, $handle, $pluginInfo['version'], $to, $pluginInfo['packageName'], $updates->plugins[$handle]);
                }
            }
        }

        if (($total = count($requirements)) !== 0) {
            $this->stdout('Performing ', Console::FG_GREEN);
            $this->stdout($total === 1 ? 'one' : $total, Console::FG_GREEN, Console::BOLD);
            $this->stdout(' update' . ($total === 1 ? '' : 's') . ':' . PHP_EOL . PHP_EOL, Console::FG_GREEN);

            foreach ($info as [$handle, $from, $to, $critical, $status, $phpConstraint]) {
                $this->_outputUpdate($handle, $from, $to, $critical, $status, $phpConstraint);
            }

            $this->stdout(PHP_EOL);
        } else {
            $this->stdout('You’re all up to date!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return $requirements;
    }

    private function _constraint(string $version): ?string
    {
        if ($this->minorOnly) {
            // 1.5.7.0 => ^1.5.7.0
            return "^$version";
        }

        if ($this->patchOnly) {
            // 1.5.7.0 => ~1.5.7
            $version = (new VersionParser())->normalize($version);
            $parts = explode('.', $version);
            if (count($parts) === 4) {
                return sprintf('~%s.%s.%s', ...array_slice($parts, 0, 3));
            }
        }

        return null;
    }

    /**
     * Updates the requirements.
     *
     * @param array $requirements
     * @param array $info
     * @param string $handle
     * @param string $from
     * @param string|null $to
     * @param string $oldPackageName
     * @param Update $update
     */
    private function _updateRequirements(array &$requirements, array &$info, string $handle, string $from, ?string $to, string $oldPackageName, Update $update): void
    {
        if ($update->status === Update::STATUS_EXPIRED && !$this->withExpired) {
            $this->stdout($this->markdownToAnsi("Skipping `$handle` because its license has expired. Run with `--with-expired` to update anyway.") . PHP_EOL);
            return;
        }

        $phpConstraintError = null;
        if ($update->phpConstraint && !UpdateHelper::checkPhpConstraint($update->phpConstraint, $phpConstraintError)) {
            $this->stdout("Skipping $handle: $phpConstraintError" . PHP_EOL, Console::FG_GREY);
            return;
        }

        if ($to === null) {
            $to = $update->getLatest()->version ?? $from;
        }

        if ($to === $from) {
            $this->stdout("Skipping $handle because it’s already up to date." . PHP_EOL, Console::FG_GREY);
            return;
        }

        $requirements[$update->packageName] = $to;
        $info[] = [$handle, $from, $to, $update->getHasCritical(), $update->status, $update->phpConstraint];

        // Has the package name changed?
        if ($update->packageName !== $oldPackageName) {
            $requirements[$oldPackageName] = false;
        }
    }

    /**
     * Installs Composer packages.
     *
     * @param array $requirements
     * @return bool
     */
    private function _performUpdate(array $requirements): bool
    {
        $this->stdout('Performing update with Composer ... ', Console::FG_YELLOW);
        $composerService = Craft::$app->getComposer();
        $output = '';

        try {
            $composerService->install($requirements, function($type, $buffer) use (&$output) {
                if ($type === Process::OUT) {
                    $output .= $buffer;
                }
            });
        } catch (Throwable $e) {
            Craft::$app->getErrorHandler()->logException($e);
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $output . PHP_EOL . PHP_EOL);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Attempts to run new migrations.
     *
     * @return bool
     */
    private function _migrate(): bool
    {
        if ($this->migrate === false) {
            $this->stdout('Skipping applying new migrations.' . PHP_EOL, Console::FG_GREY);
            return true;
        }

        try {
            $script = $this->request->getScriptFile();
        } catch (InvalidConfigException $e) {
            $this->stderr('Can’t apply new migrations: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            $this->stdout('You can apply new migrations manually by running ');
            $this->outputCommand('migrate/all --no-content');
            $this->stdout(PHP_EOL);
            return false;
        }

        $this->stdout('Applying new migrations ... ', Console::FG_YELLOW);

        $php = App::phpExecutable() ?? 'php';
        $process = new Process([$php, $script, 'migrate/all', '--no-backup', '--no-content']);
        $process->setTimeout(null);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $process->getOutput() . PHP_EOL . PHP_EOL);
            return false;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return true;
    }

    /**
     * Reverts Composer changes.
     */
    private function _revertComposerChanges(): void
    {
        // See if we have composer.json and composer.lock backups
        $backupsDir = Craft::$app->getPath()->getComposerBackupsPath();
        $jsonBackup = $backupsDir . DIRECTORY_SEPARATOR . 'composer.json';
        $lockBackup = $backupsDir . DIRECTORY_SEPARATOR . 'composer.lock';

        if (!is_file($jsonBackup)) {
            $this->stdout("Can’t revert Composer changes because no composer.json backup exists in $backupsDir." . PHP_EOL, Console::FG_RED);
            return;
        }

        if (!is_file($lockBackup)) {
            $this->stdout("Can’t revert Composer changes because no composer.lock backup exists in $backupsDir." . PHP_EOL, Console::FG_RED);
            return;
        }

        $jsonContents = file_get_contents($jsonBackup);
        $lockContents = file_get_contents($lockBackup);

        // The composer.lock backup could be just a placeholder
        if (!array_key_exists('packages', Json::decode($lockContents))) {
            $this->stdout('Can’t revert Composer changes because no composer.lock file existed before the update.' . PHP_EOL, Console::FG_RED);
            return;
        }

        if ($this->interactive && !$this->confirm('Revert the Composer changes?', true)) {
            return;
        }

        $composerService = Craft::$app->getComposer();
        FileHelper::writeToFile($composerService->getJsonPath(), $jsonContents);
        FileHelper::writeToFile($composerService->getLockPath(), $lockContents);

        try {
            $script = $this->request->getScriptFile();
        } catch (InvalidConfigException $e) {
            $this->stderr('Can’t revert Composer changes: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            $this->stdout('You can revert Composer changes manually by running ');
            $this->outputCommand('update/composer-install');
            $this->stdout(PHP_EOL);
            return;
        }

        $this->stdout('Reverting Composer changes ... ', Console::FG_YELLOW);

        $php = App::phpExecutable() ?? 'php';
        $process = new Process([$php, $script, 'update/composer-install']);
        $process->setTimeout(null);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL . PHP_EOL, Console::FG_RED);
            $this->stdout('Output:' . PHP_EOL . PHP_EOL . $process->getOutput() . PHP_EOL . PHP_EOL);
            return;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
    }

    /**
     * Displays info for an update.
     *
     * @param string $handle
     * @param string $from
     * @param string $to
     * @param bool $critical
     * @param string $status
     * @param string|null $phpConstraint
     */
    private function _outputUpdate(string $handle, string $from, string $to, bool $critical, string $status, ?string $phpConstraint = null): void
    {
        $expired = $status === Update::STATUS_EXPIRED;
        $grey = $expired ? Console::FG_GREY : null;

        $this->stdout('    - ', $grey ?? Console::FG_BLUE);
        $this->stdout($handle . ' ', $grey ?? Console::FG_CYAN);
        $this->stdout($from, $grey ?? Console::FG_PURPLE);
        $this->stdout(' => ', $grey ?? Console::FG_BLUE);
        $this->stdout($to, $grey ?? Console::FG_PURPLE);

        if ($critical) {
            $this->stdout(' (CRITICAL)', $grey ?? Console::FG_RED);
        }

        if ($expired) {
            $this->stdout(' (EXPIRED)', Console::FG_RED);
        }

        // Make sure that the platform & composer.json PHP version are compatible
        $phpConstraintError = null;
        if ($phpConstraint && !UpdateHelper::checkPhpConstraint($phpConstraint, $phpConstraintError, false)) {
            $this->stdout(" ⚠️ $phpConstraintError", Console::FG_RED);
        }

        $this->stdout(PHP_EOL);
    }

    /**
     * Ensures that there is a valid Craft license.
     *
     * @return int|null
     */
    private function _checkCraftLicense(): ?int
    {
        if (!App::licenseKey()) {
            if (defined('CRAFT_LICENSE_KEY')) {
                $this->stderr('The license key defined by the CRAFT_LICENSE_KEY PHP constant is invalid.' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout('No license key found.' . PHP_EOL, Console::FG_YELLOW);
            $session = Craft::$app->getUser();
            $user = $session->getIdentity();

            if (!$user) {
                $email = $this->prompt('Enter your email address to request a new license key:', [
                    'validator' => fn(string $input, ?string & $error = null) => (new EmailValidator())->validate($input, $error),
                ]);
                $session->setIdentity(new User([
                    'email' => $email,
                ]));
            }

            $this->stdout('Requesting license... ');
            Craft::$app->getApi()->getLicenseInfo();

            if (!$user) {
                $session->setIdentity(null);
            }

            if (App::licenseKey() === null) {
                $this->stderr('License key creation was unsuccessful.' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout('success!' . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        }

        return null;
    }

    /**
     * Returns the available updates.
     *
     * @param string[] $constraints
     * @return Updates
     */
    private function _getUpdates(array $constraints = []): Updates
    {
        $this->stdout('Fetching available updates ... ', Console::FG_YELLOW);
        $updateData = Craft::$app->getApi()->getUpdates($constraints);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return new UpdatesModel($updateData);
    }
}
