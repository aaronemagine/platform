<?php

namespace emagine\emstats\variables;

use emagine\emstats\services\StatsService;
use Craft;

class StatsVariable
{
    private $_statsService;

    public function __construct()
    {
        $this->_statsService = new StatsService();
    }

    public function getTotalVisitsCount(?int $userId = null): int
    {
        return \emagine\emstats\EmStats::$plugin
            ->statsService
            ->getTotalVisitsCount($userId);
    }

    public function getWeekHourMatrix(?string $date = null, ?int $userId = null): array
    {
        return \emagine\emstats\EmStats::$plugin
            ->statsService
            ->getWeekHourMatrix($date, $userId);
    }

    public function getLanguageMap()
    {
        return $this->_statsService->getLanguageMap();
    }

    public function getWeekNavigationUrls($baseDate = null): array
    {
        return $this->_statsService->getWeekNavigationUrls($baseDate);
    }

    public function getMonthNavigationUrls($baseDate = null): array
    {
        return $this->_statsService->getMonthNavigationUrls($baseDate);
    }

    public function getDayNavigationUrls($baseDate = null): array
    {
        return $this->_statsService->getDayNavigationUrls($baseDate);
    }

    public function getWeeklyStats($selectedDate = null, $userId = null, $siteId = null)
    {
        $selectedDate = Craft::$app->getRequest()->getParam('date');

        // Determine the current site ID
        if ($siteId !== null) {
            // the caller explicitly passed it in
            $selectedSiteId = $siteId;
        } else {
            // no param → check plugin settings → fall back to current site
            $settings       = EmStats::getInstance()->getSettings();
            $selectedSiteId = $settings->siteId
                ?? Craft::$app->getSites()->getCurrentSite()->id;
        }


        // Determine the current site language
        $language = Craft::$app->getSites()->getCurrentSite()->language;

        // Initialize day labels based on the current site language
            $dayLabels = $language === 'fr' 
        ? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']
        : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    
        return $this->_statsService->calculateWeeklyStats($selectedDate, $dayLabels, $userId, $selectedSiteId);
    }

    public function getClientsList()
    {
        return $this->_statsService->getClientsList();
    }

    public function calculateMonthlyStats($monthParam = null, $userId = null)
    {
        return $this->_statsService->calculateMonthlyStats($monthParam, $userId);
    }


    public function getTodayVisitsByTimeAndLanguage($dayParam = null, $userId = null)
    {
        return $this->_statsService->getTodayVisitsByTimeAndLanguage($dayParam, $userId);
    }

    public function getTotalVisitsPerLanguage($userId = null)
    {
        return $this->_statsService->getTotalVisitsPerLanguage($userId);
    }

    public function getTotalVisitsPerVenue($userId = null)
    {
        return $this->_statsService->getTotalVisitsPerVenue($userId);
    }

    public function getTotalVisitsPerMovie($userId = null)
    {
        return $this->_statsService->getTotalVisitsPerMovie($userId);
    }

    public function getVisitsPerHeadsetForCurrentUser($userId = null)
    {
        return $this->_statsService->getVisitsPerHeadsetForCurrentUser($userId);
    }

    /**
     * @param string|null $from
     * @param string|null $to
     * @param int|null    $userId
     */
    public function getVenueVisitCounts(
        ?string $from = null,
        ?string $to   = null,
        ?int    $userId = null
    ): array {
        return \emagine\emstats\EmStats::$plugin
            ->statsService
            ->getVenueVisitCounts($from, $to, $userId);
    }

    /** @see StatsService::getMovieVisitCounts */
    public function getMovieVisitCounts(
        ?string $from = null,
        ?string $to   = null,
        ?int    $userId = null
    ): array {
        return \emagine\emstats\EmStats::$plugin
            ->statsService
            ->getMovieVisitCounts($from, $to, $userId);
    }

    /** @see StatsService::getHeadsetVisitCounts */
    public function getHeadsetVisitCounts(
        ?string $from = null,
        ?string $to   = null,
        ?int    $userId = null
    ): array {
        return \emagine\emstats\EmStats::$plugin
            ->statsService
            ->getHeadsetVisitCounts($from, $to, $userId);
    }


    // Add more methods to expose other data to the templates...
}
