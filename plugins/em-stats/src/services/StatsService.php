<?php
/**
 * EmStats—Statistics service (ElementQuery implementation)
 */

declare(strict_types=1);

namespace emagine\emstats\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\UrlHelper;
use DateTime;
use Illuminate\Support\Collection; // instal

final class StatsService extends Component
{
    /* ---------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------*/

    private function baseQuery(): \craft\elements\db\EntryQuery
    {
        return Entry::find()
            ->section('statistics')
            ->siteId('*')
            ->status(null)              // include drafts/revisions if needed
            ->with([ 'venue', 'movie', 'headset']);
    }

    public function getLanguageMap(): array
    {
        return [
            'en' => ['name' => 'English',  'color' => 'bg-amber-500',  'hex' => '#f59e0b'],
            'es' => ['name' => 'Spanish',  'color' => 'bg-lime-500',   'hex' => '#84cc16'],
            'fr' => ['name' => 'French',   'color' => 'bg-emerald-500','hex' => '#10b981'],
            'de' => ['name' => 'German',   'color' => 'bg-cyan-500',   'hex' => '#06b6d4'],
            'it' => ['name' => 'Italian',  'color' => 'bg-blue-500',   'hex' => '#3b82f6'],
            'pt' => ['name' => 'Portuguese','color'=> 'bg-violet-500', 'hex' => '#8b5cf6'],
            'hr' => ['name' => 'Croatian', 'color' => 'bg-fuchsia-500','hex' => '#d946ef'],
            'ba' => ['name' => 'Bosnian',  'color' => 'bg-rose-500',   'hex' => '#f43f5e'],
            'no' => ['name' => 'Norwegian','color' => 'bg-rose-500',   'hex' => '#f43f5e'],
            'gr' => ['name' => 'Greek','color' => 'bg-rose-500',   'hex' => '#f43f5e'],
        ];
    }


    /**
     * Count all “statistics” entries (optionally for one user).
     */
    public function getTotalVisitsCount(?int $userId = null): int
    {
        $userId ??= \Craft::$app->user->id;
        
        $query = $this->baseQuery();
        if ($userId) {
            $query->authorId($userId);
        }
        return (int) $query->count();
    }


    /* ---------------------------------------------------------------------
     * Weekly stats (ElementQuery+Collection)
     * -------------------------------------------------------------------*/

    public function calculateWeeklyStats(
        ?string $selectedDate,
        array   $dayLabels,
        ?int    $userId        = null,
        ?int    $selectedSiteId = null,
    ): array {
        $date      = $selectedDate ? new DateTime($selectedDate) : new DateTime('today');
        $weekStart = (clone $date)->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd   = (clone $weekStart)->modify('+6 days 23:59:59');

        [$languageDayData, $totalVisitsPerDay] = $this->initializeData(
            $dayLabels,
            array_keys($this->getLanguageMap())
        );

        $entries = $this->baseQuery()
            // remove 'visitStart' and 'visitLanguage' from ->with()
            ->with([
                'venue',
                'movie',
                'headset',
            ])
            ->visitStart([
                'and',
                '>= ' . $weekStart->format(DateTime::ATOM),
                '< '  . $weekEnd ->format(DateTime::ATOM),
            ])
            ->authorId($userId)
            ->siteId($selectedSiteId ?? '*')
            ->all();

        /** @var Entry $entry */
        foreach ($entries as $entry) {
            // 1) pull the raw DateTime from visitStart
            $visitStart = $entry->getFieldValue('visitStart');
            if (!($visitStart instanceof DateTime)) {
                \Craft::info(
                    "StatsService::calculateWeeklyStats → Entry #{$entry->id} → visitStart is "
                . (is_object($visitStart) ? get_class($visitStart) : gettype($visitStart))
                . "; skipping.",
                    __METHOD__
                );
                continue;
            }

            $dayKey = $visitStart->format('Y-m-d');

            // 2) pull visitLanguage as a plain string (it was a plain‐text field)
            $rawLang = $entry->getFieldValue('visitLanguage');
            if (is_string($rawLang)) {
                $lang = strtolower(trim($rawLang)) ?: 'unknown';
            } else {
                \Craft::info(
                    "StatsService::calculateWeeklyStats → Entry #{$entry->id} → visitLanguage is "
                . (is_object($rawLang) ? get_class($rawLang) : gettype($rawLang))
                . "; using \"unknown\".",
                    __METHOD__
                );
                $lang = 'unknown';
            }

            // 3) make sure that dayKey exists in our initialized arrays
            if (!isset($languageDayData[$dayKey])) {
                $languageDayData[$dayKey] = array_fill_keys(array_keys($this->getLanguageMap()), 0);
            }

            // 4) tally
            $languageDayData[$dayKey][$lang] = ($languageDayData[$dayKey][$lang] ?? 0) + 1;
            $totalVisitsPerDay[$dayKey]      = ($totalVisitsPerDay[$dayKey]      ?? 0) + 1;
        }

        return [
            'languageDayData'   => $languageDayData,
            'totalVisitsPerDay' => $totalVisitsPerDay,
        ];
    }

        /* ---------------------------------------------------------------------
        * Monthly stats (ElementQuery+Collection)
        * -------------------------------------------------------------------*/

        public function calculateMonthlyStats(?string $monthParam = null, ?int $userId = null): array
    {
        // 1) Determine the date range for the month
        $context   = $monthParam
            ? new DateTime($monthParam)
            : new DateTime('today');
        $from = (clone $context)->modify('first day of this month')->setTime(0, 0, 0);
        $to   = (clone $context)->modify('last day of this month')->setTime(23, 59, 59);

        $languageMap = $this->getLanguageMap();

        // 2) Fetch all “statistics” entries in that range, from any site,
        //    optionally filtered by authorId. Do NOT eager‐load visitLanguage.
        $entries = Entry::find()
            ->section('statistics')
            ->siteId('*')
            ->status(null)    // include drafts/revisions if needed
            ->with([          // eager‐load only non–Date/text fields
                'venue',
                'movie',
                'headset',
            ])
            ->visitStart([
                'and',
                '>= ' . $from->format(\DateTime::ATOM),
                '<= ' . $to->format(\DateTime::ATOM),
            ])
            ->authorId($userId)
            ->all();

        // 3) Group by ISO week number (so we can build “Week 22”, “Week 23”, etc.)
        $grouped = (new Collection($entries))
            ->groupBy(fn(Entry $e) => (int)$e->dateCreated->format('W'));

        $weekLabels           = [];
        $weeklyLanguageVisits = [];
        $totalVisitsPerWeek   = [];

        foreach ($grouped as $weekNum => $weekEntries) {
            // Build a label like “Week 23” (or “Semaine 23” if locale is French)
            $weekKey = Craft::$app->locale->id === 'fr'
                ? "Semaine $weekNum"
                : "Week $weekNum";
            $weekLabels[] = $weekKey;

            // Initialize every language’s count to zero for this week
            $langCounts = array_fill_keys(array_keys($languageMap), 0);

            /** @var Entry $entry */
            foreach ($weekEntries as $entry) {
                //
                // 3a) Resolve visitLanguage as a plain string (it’s a plaintext field)
                //
                $rawLang = $entry->getFieldValue('visitLanguage');
                if (\is_string($rawLang)) {
                    $langCode = trim($rawLang) !== ''
                        ? strtolower($rawLang)
                        : 'unknown';
                } else {
                    // If it somehow isn’t a string, log & treat as “unknown”
                    \Craft::info(
                        "StatsService::calculateMonthlyStats → "
                    . "Entry #{$entry->id} → visitLanguage is "
                    . (is_object($rawLang) ? get_class($rawLang) : gettype($rawLang))
                    . "; using “unknown”.",
                        __METHOD__
                    );
                    $langCode = 'unknown';
                }

                // Tally into this week’s bucket
                if (!isset($langCounts[$langCode])) {
                    // in case the field returned something unexpected
                    $langCounts[$langCode] = 0;
                }
                $langCounts[$langCode]++;
            }

            // 3b) Copy that week’s language counts into our final structure
            foreach ($langCounts as $code => $count) {
                $weeklyLanguageVisits[$code][$weekKey] = $count;
            }

            // 3c) Totals across all languages for this week
            $totalVisitsPerWeek[$weekKey] = array_sum($langCounts);
        }

        // 4) Also compute grand totals per language for the entire month
        $languageCounts = array_map(
            fn($code) => array_sum($weeklyLanguageVisits[$code] ?? []),
            array_keys($languageMap)
        );

        return [
            'weekLabels'           => $weekLabels,
            'weeklyLanguageVisits' => $weeklyLanguageVisits,
            'totalVisitsPerWeek'   => $totalVisitsPerWeek,
            'languageCounts'       => $languageCounts,
        ];
    }

    /* ---------------------------------------------------------------------
     * Today by half‑hour block
     * -------------------------------------------------------------------*/

    public function getTodayVisitsByTimeAndLanguage(?string $dayParam = null, ?int $userId = null): array
    {
        // 1) Build the “today” window (from 00:00:00 of $dayParam to 00:00:00 of next day)
        $day  = $dayParam
            ? new \DateTime($dayParam)
            : new \DateTime('today');
        $from = (clone $day)->setTime(0, 0, 0);      // e.g. 2025-06-02T00:00:00
        $to   = (clone $from)->modify('+1 day');     // e.g. 2025-06-03T00:00:00

        // 2) Fetch all “statistics” entries from any site in that window,
        //    optionally filtered by authorId.
        //    Note: remove 'visitLanguage' from ->with() so that getFieldValue('visitLanguage') returns a string
        $entries = \craft\elements\Entry::find()
            ->section('statistics')
            ->siteId('*')
            ->status(null)
            ->with([
                // 'visitLanguage',   ← removed here
                'venue',
                'movie',
                'headset',
            ])
            ->visitStart([
                'and',
                '>= ' . $from->format(\DateTime::ATOM),
                '< '  . $to->format(\DateTime::ATOM),
            ])
            ->authorId($userId)
            ->all();

        // 3) Initialize the output structure:
        //    [ "HH:MM" => [ "en" => count, "fr" => count, … ], … ]
        $out = [];

        foreach ($entries as $entry) {
            // 3a) Raw "visitStart" field value.
            $rawStart = $entry->getFieldValue('visitStart');
            if (!($rawStart instanceof \DateTime)) {
                // If it isn’t a DateTime, log and skip
                \Craft::info(
                    "StatsService::getTodayVisitsByTimeAndLanguage → "
                . "Entry #{$entry->id} → visitStart is "
                . (is_object($rawStart) ? get_class($rawStart) : gettype($rawStart))
                . "; skipping.",
                    __METHOD__
                );
                continue;
            }

            // 3b) Build “HH:MM” key (00 or 30).
            $hour   = (int) $rawStart->format('H');
            $minute = (int) $rawStart->format('i');
            $slot   = sprintf('%02d:%02d', $hour, $minute < 30 ? 0 : 30);

            // 3c) Grab visitLanguage as a plain string.
            $langField = $entry->getFieldValue('visitLanguage');
            // Now that we're not eager‐loading it, getFieldValue should be a string like "EN"
            if (is_string($langField)) {
                $langCode = strtolower(trim($langField));
                if ($langCode === '') {
                    $langCode = 'unknown';
                }
            } else {
                // If it somehow still isn't a string, dump its type and fall back to "unknown"
                \Craft::info(
                    "StatsService::getTodayVisitsByTimeAndLanguage → "
                . "Entry #{$entry->id} → visitLanguage is "
                . (is_object($langField) ? get_class($langField) : gettype($langField))
                . "; falling back to \"unknown\".",
                    __METHOD__
                );
                $langCode = 'unknown';
            }

            // 3d) Tally it into $out[$slot][$langCode]
            if (!isset($out[$slot])) {
                $out[$slot] = [];
            }
            if (!isset($out[$slot][$langCode])) {
                $out[$slot][$langCode] = 0;
            }
            $out[$slot][$langCode]++;
        }

        return $out;
    }

    /* ---------------------------------------------------------------------
     * Language / venue / movie totals (collection helpers)
     * -------------------------------------------------------------------*/

    public function getTotalVisitsPerLanguage(?int $userId = null): array
    {
        // 1) Build a fresh query without eager‐loading visitLanguage:
        $query = Entry::find()
            ->section('statistics')
            ->siteId('*')
            ->status(null)
            ->authorId($userId);

        // 2) Fetch all matching entries:
        $entries = $query->all();

        // 3) Tally up the counts by reading getFieldValue('visitLanguage') as a string:
        $counts = [];
        foreach ($entries as $entry) {
            // getFieldValue(...) will now return the raw text (e.g. "EN", "BA", …), or null if unset
            $rawLang = $entry->getFieldValue('visitLanguage');

            if (is_string($rawLang) && trim($rawLang) !== '') {
                $langCode = strtolower(trim($rawLang));
            } else {
                $langCode = 'unknown';
            }

            if (!isset($counts[$langCode])) {
                $counts[$langCode] = 0;
            }
            $counts[$langCode]++;
        }

        // 4) Sort descending and return
        arsort($counts);
        return $counts;
    }

        public function getTotalVisitsPerVenue(?int $userId = null): array
        {
            $userId ??= \Craft::$app->user->id;

            return $this->baseQuery()
                ->authorId($userId)
                ->collect()
                ->map(fn($e) => $e->venue->one()?->title)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->all();
        }


    /**
     * Returns an array of [ languageCode => count ] for all visits
     * to a single movie, filtered by the logged-in user and optional dates.
     *
     * @param string      $movieTitle  The exact title of the movie
     * @param string|null $from        Y-m-d or null = all time
     * @param string|null $to          Y-m-d or null = all time
     * @param int|null    $userId      Defaults to current user
     * @return array<string,int>       e.g. ['en'=>12,'fr'=>5,'es'=>0,…]
     */
    public function getVisitsPerMovie(
        string  $movieTitle,
        ?string $from   = null,
        ?string $to     = null,
        ?int    $userId = null
    ): array {
        // 1) Default to current user if none passed
        $userId ??= \Craft::$app->user->id;

        // 2) Build the base “statistics” query for that user,
        //    WITHOUT eager-loading visitLanguage (so it won’t come back as ElementCollection).
        $query = $this->baseQuery()
            ->authorId($userId);

        // 3) Apply optional date filtering
        if ($from || $to) {
            $start = $from
                ? \DateTime::createFromFormat('Y-m-d', $from)->setTime(0, 0, 0)
                : new \DateTime('1970-01-01');
            $end = $to
                ? \DateTime::createFromFormat('Y-m-d', $to)->setTime(23, 59, 59)
                : new \DateTime('now');

            $query->visitStart([
                'and',
                '>= ' . $start->format(\DateTime::ATOM),
                '<= ' . $end  ->format(\DateTime::ATOM),
            ]);
        }

        // 4) Tally only entries whose related movie's title matches $movieTitle
        $counts = [];
        foreach ($query->all() as $entry) {
            // 4a) Grab the “movie” field (Entries relationship)
            $movieField = $entry->getFieldValue('movie');
            $relatedMovieTitle = null;

            if ($movieField instanceof \craft\elements\db\ElementQueryInterface) {
                // Still an ElementQuery: fetch the first Entry
                $related = $movieField->one();
                if ($related instanceof \craft\elements\Entry) {
                    $relatedMovieTitle = $related->title;
                }
            } elseif ($movieField instanceof \craft\elements\ElementCollection) {
                // If this was eager-loaded as a collection for some reason:
                $related = $movieField->one();
                if ($related instanceof \craft\elements\Entry) {
                    $relatedMovieTitle = $related->title;
                }
            } elseif ($movieField instanceof \craft\elements\ElementInterface) {
                // If the field was set to return a single Entry
                $relatedMovieTitle = $movieField->title;
            } elseif (is_string($movieField) || is_numeric($movieField)) {
                // If it returned a plain string (unlikely for an Entries field)
                $relatedMovieTitle = (string)$movieField;
            }

            // 4b) If the movie title doesn’t match exactly, skip
            if ($relatedMovieTitle === null || $relatedMovieTitle !== $movieTitle) {
                continue;
            }

            // 4c) Now extract “visitLanguage” in raw form
            //     (we did NOT eager-load it, so it can be ElementQueryInterface, ElementCollection, Entry, or string)
            $langField = $entry->getFieldValue('visitLanguage');
            $langCode = 'unknown';

            if ($langField instanceof \craft\elements\db\ElementQueryInterface) {
                // Not yet fetched: call ->one()
                $relatedLangEntry = $langField->one();
                if ($relatedLangEntry instanceof \craft\elements\Entry) {
                    // Assuming your language entries use “slug” as the code
                    $langCode = (string)$relatedLangEntry->slug;
                }
            }
            elseif ($langField instanceof \craft\elements\ElementCollection) {
                // If it was eager-loaded as a collection, just take the first Entry
                $relatedLangEntry = $langField->one();
                if ($relatedLangEntry instanceof \craft\elements\Entry) {
                    $langCode = (string)$relatedLangEntry->slug;
                }
            }
            elseif ($langField instanceof \craft\elements\ElementInterface) {
                // If the field returns a single Entry
                $langCode = (string)$langField->slug;
            }
            elseif (is_string($langField) || is_numeric($langField)) {
                // Plain text (e.g. “EN”)
                $langCode = (string)$langField;
            }

            // 4d) Normalize: lowercase or “unknown” if blank
            $langCode = trim($langCode) !== ''
                    ? strtolower($langCode)
                    : 'unknown';

            // 4e) Increment tally
            $counts[$langCode] = ($counts[$langCode] ?? 0) + 1;
        }

        // 5) Ensure every known language appears (even if zero)
        foreach (array_keys($this->getLanguageMap()) as $lc) {
            if (!isset($counts[$lc])) {
                $counts[$lc] = 0;
            }
        }

        return $counts;
    }


    /**
     * @param string|null $from  Y-m-d or null = all time
     * @param string|null $to    Y-m-d or null = all time
     * @param int|null    $userId
     * @return array<int,array{id:int,title:string,count:int}>
     */
    public function getVisitsPerHeadsetForCurrentUser(
        ?string $from   = null,
        ?string $to     = null,
        ?int    $userId = null
    ): array {
        // 1) default to the logged-in user if none passed
        $userId ??= \Craft::$app->user->id;

        // 2) build the base “statistics” query for that user
        $visitQuery = $this->baseQuery()->authorId($userId);

        // 3) apply optional date filtering
        if ($from || $to) {
            $start = $from
                ? DateTime::createFromFormat('Y-m-d', $from)->setTime(0, 0, 0)
                : new DateTime('1970-01-01');
            $end = $to
                ? DateTime::createFromFormat('Y-m-d', $to)->setTime(23, 59, 59)
                : new DateTime('now');

            $visitQuery->visitStart([
                'and',
                '>= ' . $start->format(DateTime::ATOM),
                '<= ' . $end  ->format(DateTime::ATOM),
            ]);
        }

        // 4) tally visits per headset ID
        $counts = [];
        foreach ($visitQuery->all() as $entry) {
            $h = $entry->headset->one();
            if (!$h) {
                continue;
            }
            $id = $h->id;
            if (!isset($counts[$id])) {
                $counts[$id] = [
                    'id'    => $id,
                    'title' => $h->title,
                    'count' => 0,
                ];
            }
            $counts[$id]['count']++;
        }

        // 5) sort & title-case
        return (new Collection($counts))
            ->sortBy(function(array $h) {
                // primary: first integer in the title (or large fallback)
                if (preg_match('/\d+/', $h['title'], $m)) {
                    $num = (int)$m[0];
                } else {
                    $num = PHP_INT_MAX;
                }
                // secondary: alphabetical on the TITLE-CASED string
                $tc = mb_convert_case($h['title'], MB_CASE_TITLE, 'UTF-8');
                return [$num, $tc];
            })
            // transform every title into “Oceano 1 Sitting” style
            ->map(function(array $h) {
                $h['title'] = mb_convert_case($h['title'], MB_CASE_TITLE, 'UTF-8');
                return $h;
            })
            // re-index as [0=>…,1=>…, …] for JSON/Chart.js
            ->values()
            ->all();
    }


    /**
     * Hourxweekday visit counts for any week that contains $date.
     *
     * @return array<int,array<int,int>>  [weekday 0‑6][hour 0‑23] → count
     */
    public function getWeekHourMatrix(?string $date = null, ?int $userId = null): array
    {
        $pivot = $date ? new \DateTime($date) : new \DateTime('today');
        $monday = (clone $pivot)->modify('monday this week')->setTime(0,0);
        $sunday = (clone $monday)->modify('+7 days');

        // Init 7×24 array of zeros
        $matrix = array_fill(0, 7, array_fill(0, 24, 0));

        $entries = $this->baseQuery()
            ->visitStart([
                'and',
                '>= ' . $monday->format(\DateTime::ATOM),
                '< '  . $sunday->format(\DateTime::ATOM),
            ])
            ->authorId($userId)
            ->all();

        foreach ($entries as $e) {
            /** @var \DateTime $ts */
            $ts = $e->getFieldValue('visitStart');
            $weekday = (int)$ts->format('N') - 1;      // 0 = Monday
            $hour    = (int)$ts->format('H');
            $matrix[$weekday][$hour] += 1;
        }
        return $matrix;
    }


    /**
     * Return prev/next‐day URLs for the dashboard.
     * //@param string|null $dayParam  A Y‑m‑d date (or null = today).
     * //@return array{prevDayUrl:string,nextDayUrl:string}
     */
    public function getDayNavigationUrls(?string $dayParam = null): array
    {
        $date  = $dayParam ? new DateTime($dayParam) : new DateTime('today');
        $prev  = (clone $date)->modify('-1 day')->format('Y-m-d');
        $next  = (clone $date)->modify('+1 day')->format('Y-m-d');
        $path  = Craft::$app->getRequest()->getPathInfo();
        $base  = UrlHelper::siteUrl($path);

        return [
            'prevDayUrl' => $base . '?day=' . $prev,
            'nextDayUrl' => $base . '?day=' . $next,
        ];
    }

    /**
     * @param string|null $dateParam
     * @return array{prevWeekUrl:string,nextWeekUrl:string}
     */
    public function getWeekNavigationUrls(?string $dateParam = null): array
    {
        $date = $dateParam ? new \DateTime($dateParam) : new \DateTime('today');
        $prev = (clone $date)->modify('-1 week')->format('Y-m-d');
        $next = (clone $date)->modify('+1 week')->format('Y-m-d');
        $path = \Craft::$app->getRequest()->getPathInfo();
        $base = \craft\helpers\UrlHelper::siteUrl($path);

        return [
            'prevWeekUrl' => $base . '?date=' . $prev,
            'nextWeekUrl' => $base . '?date=' . $next,
        ];
    }


    /**
     * @param string|null $dateParam Any date Y-m-d or null = today
     * @param int|null    $userId    User to filter by
     * @param int|null    $siteId    Site ID to filter by, or null=all
     *
     * @return array{languageDayData:array,totalVisitsPerDay:array}
     */
    public function getWeeklyStats(
        ?string $dateParam = null,
        ?int    $userId    = null,
        ?int    $siteId    = null
        
    ): array {
        // Build day labels for Monday→Sunday
        $start   = $dateParam ? new DateTime($dateParam) : new DateTime('today');
        $monday  = (clone $start)->modify('monday this week');
        $labels  = [];
        for ($i = 0; $i < 7; $i++) {
            $labels[] = $monday->format('Y-m-d');
            $monday->modify('+1 day');
        }

        // Call the service
        [$languageDayData, $totalVisitsPerDay] = EmStats::$plugin
            ->statsService
            ->calculateWeeklyStats($dateParam, $labels, $userId, $siteId);

        return [
            'languageDayData'   => $languageDayData,
            'totalVisitsPerDay' => $totalVisitsPerDay,
        ];
    }

    /**
     * Prev/next‑month URLs for the monthly dashboard.
     *
     * @param string|null $monthParam  YYYY‑MM or null = current month
     * @return array{prevMonthUrl:string,nextMonthUrl:string}
     */
    public function getMonthNavigationUrls(?string $monthParam = null): array
    {
        // Parse the param or default to “today”
        $date = $monthParam ? new \DateTime($monthParam . '-01') : new \DateTime('today');

        // First day of previous / next month
        $prev = (clone $date)->modify('first day of last month')->format('Y-m');
        $next = (clone $date)->modify('first day of next month')->format('Y-m');

        $path = \Craft::$app->getRequest()->getPathInfo();
        $base = \craft\helpers\UrlHelper::siteUrl($path);

        return [
            'prevMonthUrl' => $base . '?month=' . $prev,
            'nextMonthUrl' => $base . '?month=' . $next,
        ];
    }


    /**
     * Counts visits per venue in a given date range (or all time).
     *
     * @param string|null $from   Y-m-d or null = all time
     * @param string|null $to     Y-m-d or null = all time
     * @param int|null    $userId Filter by author
     * @return array<string,int>  [ venueTitle => visitCount ]
     */
    public function getVenueVisitCounts(?string $from = null, ?string $to = null, ?int $userId = null): array
    {
        $query = $this->baseQuery();
        // filter by date
        if ($from || $to) {
            $start = $from ? new DateTime($from) : new DateTime('1970-01-01');
            $end   = $to   ? (new DateTime($to))->setTime(23,59,59) : new DateTime('now');
            $query->visitStart([
                'and',
                '>= '.$start->format(DateTime::ATOM),
                '<= '.$end  ->format(DateTime::ATOM),
            ]);
        }
        if ($userId) {
            $query->authorId($userId);
        }

        $counts = [];
        foreach ($query->all() as $entry) {
            // $entry->venue is an ElementQuery; grab the first related entry
            $venue = $entry->venue->one();
            if ($venue) {
                $title = $venue->title;
                $counts[$title] = ($counts[$title] ?? 0) + 1;
            }
        }
        arsort($counts);
        return $counts;
    }

     /**
     * Counts visits per movie Entry (via the `movie` relation) in a date range.
     *
     * @param string|null $from    Y-m-d or null = all time
     * @param string|null $to      Y-m-d or null = all time
     * @param int|null    $userId
     * @return array<string,int>   [ movieTitle => visitCount ]
     */
    public function getMovieVisitCounts(
        ?string $from = null,
        ?string $to   = null,
        ?int    $userId = null
    ): array {
        $query = $this->baseQuery();
        // date filtering
        if ($from || $to) {
            $start = $from ? new DateTime($from) : new DateTime('1970-01-01');
            $end   = $to   ? (new DateTime($to))->setTime(23,59,59) : new DateTime('now');
            $query->visitStart([
                'and',
                '>= '.$start->format(DateTime::ATOM),
                '<= '.$end  ->format(DateTime::ATOM),
            ]);
        }
        if ($userId) {
            $query->authorId($userId);
        }

        $counts = [];
        foreach ($query->all() as $entry) {
            // Use the `movie` relation (Entries field) instead of a text handle
            $movie = $entry->movie->one();
            if ($movie) {
                $title = $movie->title;
                $counts[$title] = ($counts[$title] ?? 0) + 1;
            }
        }

        arsort($counts);
        return $counts;
    }

    /**
     * Get visit counts per headset over a date range.
     *
     * @param string|null $from
     * @param string|null $to
     * @param int|null    $userId
     * @return array<string,int>   [ headsetTitle => count ]
     */
    public function getHeadsetVisitCounts(
        ?string $from = null,
        ?string $to   = null,
        ?int    $userId = null
    ): array {
        $start = $from ? new \DateTime($from) : new \DateTime('today');
        $end   = $to   ? new \DateTime($to)   : new \DateTime('today');
        $end->setTime(23,59,59);

        return $this->baseQuery()
            ->visitStart([
                'and',
                '>= '.$start->format(\DateTime::ATOM),
                '<= '.$end->format(\DateTime::ATOM),
            ])
            ->authorId($userId)
            ->collect()
            ->map(fn($entry) => $entry->headset->one()?->title)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->all();
    }

    /**
     * Returns an array of [ languageCode => count ] for all visits
     * to a single movie, filtered by the logged-in user and optional dates.
     *
     * @param string      $movieTitle  The exact title of the movie
     * @param string|null $from        Y-m-d (inclusive) or null = no start bound
     * @param string|null $to          Y-m-d (inclusive) or null = no end bound
     * @param int|null    $userId      Defaults to current user if null
     * @return array<string,int>       e.g. ['en'=>12,'fr'=>5,'es'=>0,…]
     */
    public function getVisitsPerLanguageForMovie(
        string  $movieTitle,
        ?string $from   = null,
        ?string $to     = null,
        ?int    $userId = null
    ): array {
        // 1) Default to current user if none passed
        $userId ??= \Craft::$app->user->id;

        // 2) Build the base “statistics” query for that user,
        //    WITHOUT eager-loading visitLanguage (so it won’t come back as ElementCollection).
        $query = $this->baseQuery()
            ->authorId($userId);

        // 3) Apply optional date filtering: from 00:00:00 of $from to 23:59:59 of $to
        if ($from || $to) {
            $start = $from
                ? \DateTime::createFromFormat('Y-m-d', $from)->setTime(0, 0, 0)
                : new \DateTime('1970-01-01');
            $end = $to
                ? \DateTime::createFromFormat('Y-m-d', $to)->setTime(23, 59, 59)
                : new \DateTime('now');

            $query->visitStart([
                'and',
                '>= ' . $start->format(\DateTime::ATOM),
                '<= ' . $end->format(\DateTime::ATOM),
            ]);
        }

        // 4) Loop through each “statistics” entry, but only count those whose “movie”
        //    relationship exactly matches $movieTitle.
        $counts = [];
        foreach ($query->all() as $entry) {
            // 4a) Resolve the “movie” field (Entries relationship)
            $movieField = $entry->getFieldValue('movie');
            $relatedMovieTitle = null;

            if ($movieField instanceof \craft\elements\db\ElementQueryInterface) {
                $rel = $movieField->one();
                if ($rel instanceof \craft\elements\Entry) {
                    $relatedMovieTitle = $rel->title;
                }
            }
            elseif ($movieField instanceof \craft\elements\ElementCollection) {
                $rel = $movieField->one();
                if ($rel instanceof \craft\elements\Entry) {
                    $relatedMovieTitle = $rel->title;
                }
            }
            elseif ($movieField instanceof \craft\elements\ElementInterface) {
                $relatedMovieTitle = $movieField->title;
            }
            elseif (is_string($movieField) || is_numeric($movieField)) {
                $relatedMovieTitle = (string)$movieField;
            }

            if ($relatedMovieTitle === null || $relatedMovieTitle !== $movieTitle) {
                // Skip any entry whose movie-title does not match exactly
                continue;
            }

            // 4b) Now inspect visitLanguage—but do NOT call strtolower() yet.
            $langField = $entry->getFieldValue('visitLanguage');

            // Log the raw return‐value, so you can inspect in storage/logs/web.log
            \Craft::info(
                "StatsService::getVisitsPerLanguageForMovie → Entry #{$entry->id} raw visitLanguage is "
                . (
                    is_object($langField)
                        ? get_class($langField)
                        : gettype($langField)
                )
                . " → “" . (string)$langField . "”",
                __METHOD__
            );

            $langCode = 'unknown';

            if ($langField instanceof \craft\elements\db\ElementQueryInterface) {
                // Not yet fetched: fetch the first related Entry
                $relLang = $langField->one();
                if ($relLang instanceof \craft\elements\Entry) {
                    $langCode = (string)$relLang->slug;
                }
            }
            elseif ($langField instanceof \craft\elements\ElementCollection) {
                // If it was eager-loaded for some reason:
                $relLang = $langField->one();
                if ($relLang instanceof \craft\elements\Entry) {
                    $langCode = (string)$relLang->slug;
                }
            }
            elseif ($langField instanceof \craft\elements\ElementInterface) {
                // If the field is set to return a single Entry:
                $langCode = (string)$langField->slug;
            }
            elseif (is_string($langField) || is_numeric($langField)) {
                // Plain-text (e.g. “EN”)
                $langCode = (string)$langField;
            }

            // 4c) Normalize: lowercase, or “unknown” if blank
            $langCode = trim($langCode) !== ''
                ? strtolower($langCode)
                : 'unknown';

            // 4d) Increment the tally
            $counts[$langCode] = ($counts[$langCode] ?? 0) + 1;
        }

        // 5) Ensure every known language appears (even if zero)
        foreach (array_keys($this->getLanguageMap()) as $lc) {
            if (!isset($counts[$lc])) {
                $counts[$lc] = 0;
            }
        }

        return $counts;
    }



    /* ---------------------------------------------------------------------
     * Generic helpers
     * -------------------------------------------------------------------*/

    public function initializeData(array $dayLabels, array $languages): array
    {
        $langDay = [];
        $totals  = [];
        foreach ($dayLabels as $d) {
            $langDay[$d] = array_fill_keys($languages, 0);
            $totals[$d]  = 0;
        }
        return [$langDay, $totals];
    }
}
