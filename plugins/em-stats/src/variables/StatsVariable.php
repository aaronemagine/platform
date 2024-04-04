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

    public function getTotalVisitsCount($userId = null)
    {
        return $this->_statsService->getTotalVisitsCount($userId);
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

    public function getWeeklyStats($selectedDate = null, $userId = null)
    {
        $selectedDate = Craft::$app->getRequest()->getParam('date');
        $dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return $this->_statsService->calculateWeeklyStats($selectedDate, $dayLabels, $userId);
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

    // Add more methods to expose other data to the templates...
}
