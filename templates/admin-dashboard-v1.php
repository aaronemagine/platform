{% extends 'includes/main.twig' %}

{% block content %}

{% if not currentUser %}
    {% redirect 'index' %}
{% endif %}

{% set userId = currentUser.id %} {# Get the ID of the logged in user #}

{# -- count total overall visits -- #}
{% set statisticsEntriesCount = craft.entries.section('statistics').count() %}

{% set totalVisits = craft.emstats.getTotalEntriesCount %}

{# -- Set up language map and labels -- #}
{% set languageMap = {
    'en': {'name': 'English', 'color': 'bg-amber-500', 'hex': '#f59e0b'},
    'es': {'name': 'Spanish', 'color': 'bg-lime-500' , 'hex': '#84cc16'},
    'fr': {'name': 'French', 'color': 'bg-emerald-500', 'hex': '#10b981'},
    'de': {'name': 'German', 'color': 'bg-cyan-500', 'hex': '#06b6d4'},
    'it': {'name': 'Italian', 'color': 'bg-blue-500', 'hex': '#3b82f6'},
    'pt': {'name': 'Portuguese', 'color': 'bg-violet-500', 'hex': '#8b5cf6'},
    'cr': {'name': 'Croatian', 'color': 'bg-fuchsia-500', 'hex': '#d946ef'},
    'ba': {'name': 'Bosnian', 'color': 'bg-rose-500', 'hex': '#f43f5e'},
} %}
{% set dayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] %}
{% set languages = languageMap|keys %}

{# -- Initialize data structures -- #}
{% set languageDayData = {} %}
{% set totalVisitsPerDay = {} %}
{% set weeklyLanguageVisits = {} %}
{% set totalVisitsPerWeek = {} %}
{% set weekLabels = [] %}

{# -- Initialize languageDayData and totalVisitsPerDay with zeros -- #}
{% for day in dayLabels %}
    {% set languageDayData = languageDayData|merge({ (day): {} }) %}
    {% set totalVisitsPerDay = totalVisitsPerDay|merge({ (day): 0 }) %}
    {% for language in languages %}
        {% set languageDayData = languageDayData|merge({ (day): languageDayData[day]|merge({ (language): 0 }) }) %}
    {% endfor %}
{% endfor %}

{# -- Get the start and end of the week -- #}
{% set startOfWeek = now|date_modify('monday this week') %}
{% set endOfWeek = now|date_modify('sunday this week').modify('+1 day midnight') %}

{# -- Get the start and end of the month -- #}
{% set startOfMonth = now|date_modify('first day of this month')|date('Y-m-d') %}
{% set endOfMonth = now|date_modify('last day of this month')|date('Y-m-d') %}
{% set weeksInMonth = (endOfMonth|date('W')) - (startOfMonth|date('W')) + 1 %}

{# -- Query for entries this week and count visits -- #}
{% set visitsThisWeek = craft.entries.section('statistics').visitStart(['and', '>= ' ~ startOfWeek|atom, '<= ' ~ endOfWeek|atom]).all() %}
{% for entry in visitsThisWeek %}
    {% set dayOfWeek = entry.visitStart|date('N') %}
    {% set language = entry.visitLanguage %}
    {% set dayIndex = dayOfWeek - 1 %}
    
    {# Increment the language count for the day #}
    {% set dayData = languageDayData[dayLabels[dayIndex]] %}
    {% set dayData = dayData|merge({ (language): (dayData[language]|default(0) + 1) }) %}
    {% set languageDayData = languageDayData|merge({ (dayLabels[dayIndex]): dayData }) %}
    
    {# Increment the total visits for the day #}
    {% set totalVisitsPerDay = totalVisitsPerDay|merge({ (dayLabels[dayIndex]): (totalVisitsPerDay[dayLabels[dayIndex]]|default(0) + 1) }) %}
{% endfor %}

{# -- Loop through each week of the month -- #}
{% for week in 1..weeksInMonth %}
    {% set weekKey = 'Week ' ~ week %}
    {% set weekLabels = weekLabels|merge([weekKey]) %}
    {% set currentWeekStart = startOfMonth|date_modify(('+' ~ (week - 1) ~ ' weeks'))|date('Y-m-d') %}
    {% set currentWeekEnd = currentWeekStart|date_modify('+6 days')|date('Y-m-d') %}
    {% if currentWeekEnd > endOfMonth %}
        {% set currentWeekEnd = endOfMonth %}
    {% endif %}
    
    {# Query for entries within the current week #}
    {% set visitsThisWeek = craft.entries.section('statistics').visitStart(['and', '>= ' ~ currentWeekStart|atom, '<= ' ~ currentWeekEnd|atom]).all() %}
    
    {# Initialize weeklyLanguageVisits for the week #}
    {% set weeklyCounts = {} %}
    {% for language in languages %}
        {% set weeklyCounts = weeklyCounts|merge({ (language): 0 }) %}
    {% endfor %}
    
    {# Count visits per language this week #}
    {% for entry in visitsThisWeek %}
        {% set language = entry.visitLanguage %}
        {% set count = weeklyCounts[language] %}
        {% set weeklyCounts = weeklyCounts|merge({ (language): count + 1 }) %}
    {% endfor %}
    
    {# Merge the counts into weeklyLanguageVisits #}
    {% for language, count in weeklyCounts %}
        {% if weeklyLanguageVisits[language] is not defined %}
            {% set weeklyLanguageVisits = weeklyLanguageVisits|merge({ (language): {} }) %}
        {% endif %}
        {% set weeklyLanguageVisits = weeklyLanguageVisits|merge({
            (language): weeklyLanguageVisits[language]|merge({ (weekKey): count })
        }) %}
    {% endfor %}
    
    {# Calculate total visits for the week #}
    {% set totalVisits = 0 %}
    {% for count in weeklyCounts %}
        {% set totalVisits = totalVisits + count %}
    {% endfor %}
    
    {# Record total visits for the week #}
    {% set totalVisitsPerWeek = totalVisitsPerWeek|merge({ (weekKey): totalVisits }) %}
{% endfor %}

{# -- Count overall visits per language -- #}
{% set languageCounts = {} %}
{% for language in languages %}
    {% set count = 0 %}
    {% for week in weekLabels %}
        {% set count = count + weeklyLanguageVisits[language][week]|default(0) %}
    {% endfor %}
    {% set languageCounts = languageCounts|merge({ (language): count }) %}
{% endfor %}


<div class="flex min-h-screen">

    {% include 'includes/admin-sidebar.twig' %}

    {#-- Main Content Area --#}
    <div class="flex-grow w-full bg-grey-500 p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
       
            <div class="p-4 border rounded-lg shadow-2xl col-span-2 border-gray-700 sm:p-6 bg-gray-800 text-white">
                <h3 class="text-xl font-bold mb-2">Total Visits: {{totalVisits}}</h3>
                <h3 class="text-xl font-bold mb-2 ">Visits By Language: </h3>
                {% for languageCode, count in languageCounts %}
                    {# Extract language name and color from the languageMap #}
                    {% set languageName = languageMap[languageCode].name ?: languageCode %}
                    {% set languageColor = languageMap[languageCode].color ?: 'bg-gray-300' %}
                    {% set languageFill = languageMap[languageCode].hex ?: 'bg-gray-300' %} 
                    <span class="float-left text-left mr-4">
                        {{ languageName }}: {{ count }}{{ not loop.last ? ',' : '' }}
                    </span>
                {% endfor %}
                <h3 class="text-xl font-bold mb-2 ">Visits By Venue: </h3>

                <h3 class="text-xl font-bold mb-2 ">Visits By Movie: </h3>
            </div>

            <div class="p-4 border rounded-lg shadow-2xl col-span-1 border-gray-700 sm:p-6 bg-gray-800 text-white">
                <div class="flex">
                    <div class="w-1/3">
                       
                    </div>

                    {#-- Canvas element where the pie chart will be rendered --#}
                    <div class="w-2/3">
                        <canvas id="languagePieChart"></canvas>
                    </div>
                </div>
            </div>

         </div>

   
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mt-4 p-4 border rounded-lg shadow-sm  border-gray-700 bg-gray-800 text-white">
            <!-- Card 1 -->
            <div>
                <h3 class="text-xl font-bold mb-2">Visits This Week</h3>
                <canvas id="visitsThisWeekChart"></canvas> 
            </div>

            <!-- Card 2 -->
            <div>
                <h3 class="text-xl font-bold mb-2">Visits This Month</h3>
                <canvas id="weeklyVisitsChart"></canvas>
            </div>
        </div>

{#         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mt-4 p-4 border rounded-lg shadow-sm  border-gray-700 bg-gray-800 text-white">
            <!-- Card 1 -->
            <div>
                <h3 class="text-xl font-bold mb-2">Income This Week</h3>
                <canvas id="barWeekChart"></canvas> 
            </div>

            <!-- Card 2 -->
            <div>
                <h3 class="text-xl font-bold mb-2">Income This Month</h3>
                <canvas id="barMonthChart"></canvas>
            </div>
        </div> #}
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if languageMap is defined
    const languageMap = {{ languageMap|json_encode|raw }};
    if (typeof languageMap === 'undefined') {
        console.error('languageMap is not defined');
        return; // Stop execution if languageMap is not defined
    }

    // Extracting data for the pie chart
    const languageCounts = {{ languageCounts|json_encode|raw }};
    const labels = [];
    const data = [];
    const colors = [];

    // Loop through languageCounts and populate labels, data, and colors
    Object.keys(languageCounts).forEach(code => {
        if (languageMap[code]) { // Check if the language code exists in languageMap
            labels.push(languageMap[code].name);
            data.push(languageCounts[code]);
            colors.push(languageMap[code].hex);
        } else {
            console.error('Language code ' + code + ' does not exist in languageMap');
        }
    });

    // Creating the pie chart
    const pieCtx = document.getElementById('languagePieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: colors }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true, position: 'left' }
            }
        }
    });

    // Visits This Week Chart

    const dayLabels = {{ dayLabels|json_encode|raw }};
    const languageDayData = {{ languageDayData|json_encode|raw }};
    const totalVisitsPerDay = {{ totalVisitsPerDay|json_encode|raw }};
    const totalVisitsPerDayArray = dayLabels.map(day => totalVisitsPerDay[day]);

    datasets = [];

    datasets.push({
        type: 'line', // This specifies that it's a line dataset
        label: 'Total Visits',
        data: totalVisitsPerDayArray,
        backgroundColor: 'rgba(255, 255, 255, 0)', 
        borderColor: 'white',
        borderWidth: 2,
        fill: false
    });

    // Add bar chart datasets for each language
    Object.keys(languageMap).forEach(language => {
        var color = languageMap[language].hex;
        var languageData = dayLabels.map(dayLabel => {
        let formattedDayLabel = dayLabel.charAt(0).toUpperCase() + dayLabel.slice(1);
        let visitCount = languageDayData[formattedDayLabel] && languageDayData[formattedDayLabel][language] ? languageDayData[formattedDayLabel][language] : 0; 
        return visitCount;
    });

        datasets.push({
            label: languageMap[language].name,
            data: languageData,
            backgroundColor: color,
            borderColor: color,
            borderWidth: 1
        });
    });

    new Chart(visitsCtx, {
        type: 'bar',
        data: { 
            labels: dayLabels,
            datasets: datasets 
        },
        options: {
            indexAxis: 'x',
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });

    // Weekly Visits Chart
    const weeklyCtx = document.getElementById('weeklyVisitsChart').getContext('2d');
    const weekLabels = {{ weekLabels|json_encode|raw }};
    const weeklyLanguageVisits = {{ weeklyLanguageVisits|json_encode|raw }};
    const totalVisitsPerWeek = {{ totalVisitsPerWeek|json_encode|raw }};

    // Reset datasets for the weekly visits chart
    datasets = [];

    // Add the line graph dataset for total visits per week
    datasets.push({
        type: 'line', // This specifies that it's a line dataset
        label: 'Total Visits',
        data: Object.values(totalVisitsPerWeek), // Use the values from the totalVisitsPerWeek object
        backgroundColor: 'rgba(255, 255, 255, 0)', 
        borderColor: 'white',
        borderWidth: 2,
        fill: false
    });

    // Append datasets for each language for the weekly visits chart
    Object.keys(languageMap).forEach(language => {
        // Check if the language has weekly visit data
        if (!weeklyLanguageVisits[language]) {
            console.error(`Weekly visit data for '${language}' is not defined.`);
            return; // Skip this iteration if the weekly visit data is not defined
        }

        let languageData = weekLabels.map(weekLabel => {
            // Access the visit count for the current language in the current week
            return weeklyLanguageVisits[language][weekLabel] || 0;
        });

        datasets.push({
            label: languageMap[language].name,
            data: languageData,
            backgroundColor: languageMap[language].hex,
            borderColor: languageMap[language].hex,
            borderWidth: 1
        });
    });

    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weekLabels,
            datasets: datasets
        },
        options: {
            indexAxis: 'x',
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });
});
</script>




{% endblock %}




