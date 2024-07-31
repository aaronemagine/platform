<?php

namespace emagine\emstats\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\UrlHelper;
use DateTime;


class StatsService extends Component
{
    // Fetch the total count of entries in the 'statistics' section
    public function getTotalVisitsCount($userId = null)
    {

        $query = Entry::find()->section('statistics'); 
        if ($userId) {
            $query->authorId($userId);
        }
        return $query->count();
    }


    // Define the language map
    public function getLanguageMap()
    {
        return [
            'en' => ['name' => 'English', 'color' => 'bg-amber-500', 'hex' => '#f59e0b'],
            'es' => ['name' => 'Spanish', 'color' => 'bg-lime-500' , 'hex' => '#84cc16'],
            'fr' => ['name' => 'French', 'color' => 'bg-emerald-500', 'hex' =>'#10b981'],
            'de' => ['name' => 'German', 'color' => 'bg-cyan-500', 'hex' => '#06b6d4'],
            'it' => ['name' => 'Italian', 'color' => 'bg-blue-500', 'hex' => '#3b82f6'],
            'pt' => ['name' => 'Portuguese', 'color' => 'bg-violet-500', 'hex' => '#8b5cf6'],
            'hr' => ['name' => 'Croatian', 'color' => 'bg-fuchsia-500', 'hex' => '#d946ef'],
            'ba' => ['name' => 'Bosnian', 'color' => 'bg-rose-500', 'hex' => '#f43f5e'],
            'no' => ['name' => 'Norwegian', 'color' => 'bg-rose-500', 'hex' => '#f43f5e'],

        ];
    }

    public function getClientsList(): array
    {
        // Fetch the user group by its handle
        $userGroup = Craft::$app->userGroups->getGroupByHandle('Clients');

        if (!$userGroup) {
            // Handle the case where the user group does not exist
            Craft::error('User group with handle "clients" not found.', __METHOD__);
            return [];
        }

        // Query users that belong to the 'clients' group
        $users = User::find()
        ->group($userGroup)
        ->all();

        // Prepare the list with the required information
        $clientsList = array_map(function($user) {
            return [
                'userId' => $user->id,
                'fullName' => $user->fullName,
                'companyName' => $user->getFieldValue('companyName') // Replace 'companyName' with the actual field handle
            ];
        }, $users);

        return $clientsList;
    }


    // Initialize data structures with zeros
    public function initializeData($dayLabels, $languages)
    {
        $languageDayData = [];
        $totalVisitsPerDay = [];
        foreach ($dayLabels as $day) {
            $languageDayData[$day] = array_fill_keys($languages, 0);
            $totalVisitsPerDay[$day] = 0;
        }
        return [$languageDayData, $totalVisitsPerDay];
    }

    public function getWeekNavigationUrls($dateParam = null): array
    {
        // Check if a specific date has been provided as a query parameter
        // If not, default to the current date
        $currentDate = $dateParam ? new DateTime($dateParam) : new DateTime();

        // Calculate the dates for the previous and next week
        $prevWeek = (clone $currentDate)->modify('-1 week')->format('Y-m-d');
        $nextWeek = (clone $currentDate)->modify('+1 week')->format('Y-m-d');

        // Get the current path without query parameters
        $currentPath = Craft::$app->getRequest()->getPathInfo();

        // Create URLs with query parameters for prev and next week
        $prevWeekUrl = UrlHelper::url($currentPath, ['date' => $prevWeek]);
        $nextWeekUrl = UrlHelper::url($currentPath, ['date' => $nextWeek]);

        return [
            'prevWeekUrl' => $prevWeekUrl,
            'nextWeekUrl' => $nextWeekUrl
        ];
    }


    public function calculateWeeklyStats($selectedDate = null, $dayLabels, $userId = null)
    {
        // Use the provided date or default to the current date
        $date = $selectedDate ? new DateTime($selectedDate) : new DateTime();

        // Find the start and end of the week based on the provided/current date
        $startOfWeek = (clone $date)->modify('Monday this week')->setTime(0, 0);
        $endOfWeek = (clone $date)->modify('Sunday this week')->setTime(23, 59, 59);

        // Query for entries within the week
        $query = Entry::find()
            ->section('statistics')
            ->visitStart(['and', '>= ' . $startOfWeek->format(DateTime::ATOM), '<= ' . $endOfWeek->format(DateTime::ATOM)]);

        if ($userId) {
            $query->authorId($userId);
        }

        $visitsThisWeek = $query->all();

        // Initialize the arrays to store the data
        [$languageDayData, $totalVisitsPerDay] = $this->initializeData($dayLabels, array_keys($this->getLanguageMap()));

        foreach ($visitsThisWeek as $entry) {
            $dayOfWeek = $entry->visitStart->format('l'); // 'l' format represents the full textual representation of a day
            //$language = $entry->visitLanguage;
            
            $language = strtolower($entry->visitLanguage);
            
            // Increment the language count for the day
            if (!isset($languageDayData[$dayOfWeek][$language])) {
                $languageDayData[$dayOfWeek][$language] = 0;
            }
            $languageDayData[$dayOfWeek][$language]++;
            
            // Increment the total visits for the day
            if (!isset($totalVisitsPerDay[$dayOfWeek])) {
                $totalVisitsPerDay[$dayOfWeek] = 0;
            }
            $totalVisitsPerDay[$dayOfWeek]++;
        }
        
        return [
            'languageDayData' => $languageDayData,
            'totalVisitsPerDay' => $totalVisitsPerDay
        ];
    }

    public function getMonthNavigationUrls($dateParam = null): array
    {
        // Use the provided date or default to the current date
        $date = $dateParam ? new DateTime($dateParam) : new DateTime();

        // Calculate the dates for the first day of the previous and next months
        $prevMonth = (clone $date)->modify('first day of last month')->format('Y-m');
        $nextMonth = (clone $date)->modify('first day of next month')->format('Y-m');

        // Generate URLs with query parameters for prev and next months
        $currentPath = Craft::$app->getRequest()->getPathInfo();
        $prevMonthUrl = UrlHelper::url($currentPath, ['month' => $prevMonth]);
        $nextMonthUrl = UrlHelper::url($currentPath, ['month' => $nextMonth]);

        return [
            'prevMonthUrl' => $prevMonthUrl,
            'nextMonthUrl' => $nextMonthUrl
        ];
    }



    public function calculateMonthlyStats($monthParam = null, $userId = null): array
    {
        $dateContext = $monthParam ? new DateTime($monthParam) : new DateTime();
        $startOfMonth = (clone $dateContext)->modify('first day of this month')->setTime(0, 0);
        $endOfMonth = (clone $dateContext)->modify('last day of this month')->setTime(23, 59, 59);

        $weekLabels = [];
        $weeklyLanguageVisits = [];
        $totalVisitsPerWeek = [];

        $languageMap = $this->getLanguageMap(); 

        //die($languageMap);

        for ($week = 1; $week <= 6; $week++) {
            $currentWeekStart = (clone $startOfMonth)->modify('+' . ($week - 1) . ' weeks');
            $currentWeekEnd = (clone $currentWeekStart)->modify('+6 days');

            if ($currentWeekStart > $endOfMonth) {
                break;
            }

            if ($currentWeekEnd > $endOfMonth) {
                $currentWeekEnd = $endOfMonth;
            }

            // Get the current site language
            $language = Craft::$app->getSites()->getCurrentSite()->language;

            // Set the week key based on the current site language
            if ($language === 'fr') {
                $weekKey = 'Semaine ' . $week;
            } else {
                $weekKey = 'Week ' . $week;
            }

            $weekLabels[] = $weekKey;

            // Query for entries within the week
            $query = Entry::find()
                ->section('statistics')
                ->visitStart(['and', '>= ' . $currentWeekStart->format(DateTime::ATOM), '<= ' . $currentWeekEnd->format(DateTime::ATOM)]);

            if ($userId) {
                $query->authorId($userId);
            }

            $visitsThisWeek = $query->all();

            // Initialize the counts for each language for the current week
            $weeklyCounts = array_fill_keys(array_keys($languageMap), 0);

            foreach ($visitsThisWeek as $entry) {
                //$language = $entry->visitLanguage;
                $language = strtolower($entry->visitLanguage);
                if (isset($languageMap[$language])) { // Ensure the language is in the map
                    $weeklyCounts[$language]++;
                }
            }

            // Store the counts for each language for the current week
            foreach ($weeklyCounts as $language => $count) {
                if (!isset($weeklyLanguageVisits[$language])) {
                    $weeklyLanguageVisits[$language] = [];
                }
                $weeklyLanguageVisits[$language][$weekKey] = $count;
            }

            // Store the total visits for the current week
            $totalVisitsPerWeek[$weekKey] = array_sum($weeklyCounts);
        }

        // Calculate the total counts for each language across all weeks
        $languageCounts = [];
        foreach ($languageMap as $language => $details) {
            $count = 0;
            foreach ($weekLabels as $weekLabel) {
                $count += $weeklyLanguageVisits[$language][$weekLabel] ?? 0;
            }
            $languageCounts[$language] = $count;
        }

        return [
            'weekLabels' => $weekLabels,
            'weeklyLanguageVisits' => $weeklyLanguageVisits,
            'totalVisitsPerWeek' => $totalVisitsPerWeek,
            'languageCounts' => $languageCounts
        ];
    }

    public function getDayNavigationUrls($dayParam = null): array
    {
        // Use the provided date or default to the current date
        $date = $dayParam ? new DateTime($dayParam) : new DateTime();

        // Calculate the dates for the first day of the previous and next months
        $prevDay= (clone $date)->modify('-1 day')->format('Y-m-d');
        $nextDay = (clone $date)->modify('+1 day')->format('Y-m-d');

        // Generate URLs with query parameters for prev and next months
        $currentPath = Craft::$app->getRequest()->getPathInfo();
        $prevdayUrl = UrlHelper::url($currentPath, ['day' => $prevDay]);
        $nextDayUrl = UrlHelper::url($currentPath, ['day' => $nextDay]);

        return [
            'prevDayUrl' => $prevdayUrl,
            'nextDayUrl' => $nextDayUrl
        ];
    }


    public function getTodayVisitsByTimeAndLanguage($dayParam = null, $userId = null)
    {

        $selectedDate = $dayParam ? new DateTime($dayParam) : new DateTime();
        $startOfDay = $selectedDate->setTime(0, 0, 0);
        $endOfDay = (clone $startOfDay)->modify('+1 day');

        $entries = Entry::find()
            ->section('statistics')
            ->authorId($userId)
            ->visitStart(['and', '>= ' . $startOfDay->format(DateTime::ATOM), '< ' . $endOfDay->format(DateTime::ATOM)])
            ->all();

        $visitsByTimeAndLanguage = [];

        foreach ($entries as $entry) {
            $visitStart = $entry->getFieldValue('visitStart');
            $visitLanguage = strtolower($entry->getFieldValue('visitLanguage'));
 
            $hour = $visitStart->format('H');
            $minute = $visitStart->format('i');
            $halfHourBlock = $minute < 30 ? '00' : '30';
            $timeBlock = $hour . ':' . $halfHourBlock;

            if (!isset($visitsByTimeAndLanguage[$timeBlock])) {
                $visitsByTimeAndLanguage[$timeBlock] = [];
            }

            if (!isset($visitsByTimeAndLanguage[$timeBlock][$visitLanguage])) {
                $visitsByTimeAndLanguage[$timeBlock][$visitLanguage] = 0;
            }

            $visitsByTimeAndLanguage[$timeBlock][$visitLanguage]++;
        }

        return $visitsByTimeAndLanguage;
    }


    public function getTotalVisitsPerLanguage($userId = null): array
    {
        $languageTotals = [];

        // Retrieve all entries from the 'statistics' section
        $query = Entry::find()->section('statistics');
        if ($userId) {
        $query->authorId($userId);
        }
        $entries = $query->all();

        // Iterate over entries to sum visits per language
        foreach ($entries as $entry) {
            // Assuming 'visitLanguage' is the field handle for the language of the visit
            $language = strtolower($entry->visitLanguage);
            if (!array_key_exists($language, $languageTotals)) {
                $languageTotals[$language] = 0;
            }
            $languageTotals[$language]++;
        }

        return $languageTotals;
    }

    public function getTotalVisitsPerVenue($userId = null): array
    {
        $venueTotals = [];

        $query = Entry::find()->section('statistics');
        $query->with('venue');
        if ($userId) {
        $query->authorId($userId);
        }
        $entries = $query->all();

        // Iterate over entries to sum visits per venue
        foreach ($entries as $entry) {
            // Assuming 'venue' is the relational field handle
            $venue = $entry->venue->one(); // 'one()' because it's a single relation
            if ($venue) {
                $venueName = $venue->title; // Assuming you want to use the title as the venue name
                if (!array_key_exists($venueName, $venueTotals)) {
                    $venueTotals[$venueName] = 0;
                }
                $venueTotals[$venueName]++;
            }
        }

        return $venueTotals;
    }

    public function getTotalVisitsPerMovie($userId = null): array
    {
        $movieTotals = [];

        $query = Entry::find()->section('statistics');
        $query->with('movie');
        if ($userId) {
        $query->authorId($userId);
        }
        $entries = $query->all();

        // Iterate over entries to sum visits per movie
        foreach ($entries as $entry) {
            // Assuming 'movie' is the relational field handle and it's a single relation (not multiple)
            $movie = $entry->movie->one(); // Fetch the related movie entry
            if ($movie) {
                $movieName = $movie->title; // Using the movie entry's title as its name
                if (!array_key_exists($movieName, $movieTotals)) {
                    $movieTotals[$movieName] = 0; // Initialize if not set
                }
                $movieTotals[$movieName]++; // Increment the count for this movie
            }
        }

        return $movieTotals;
    }

    public function getVisitsPerHeadsetForCurrentUser($userId = null): array
    {
        // Fallback to the current user ID if none is provided
        if ($userId === null) {
            $userId = Craft::$app->getUser()->getIdentity()->id;
        }

        // Fetch all entries from the 'statistics' section where the author is the given user
        $statisticsEntries = \craft\elements\Entry::find()
            ->section('statistics')
            ->authorId($userId)
            ->all();

        $visitsPerHeadset = [];

        // Iterate over the entries and tally visits per headset
        foreach ($statisticsEntries as $entry) {
            // Assuming 'headset' is a relational field (Entries Field)
            $headset = $entry->headset->one(); // Get the first related headset entry
            if ($headset) {
                $headsetId = $headset->id;
                $headsetTitle = $headset->title;

                if (!isset($visitsPerHeadset[$headsetId])) {
                    $visitsPerHeadset[$headsetId] = [
                        'id' => $headsetId,
                        'title' => $headsetTitle,
                        'count' => 0
                    ];
                }
                $visitsPerHeadset[$headsetId]['count']++;
            }
        }

        return $visitsPerHeadset;
    }


    // Add more methods to handle other statistics calculations...
}
