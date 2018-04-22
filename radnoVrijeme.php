<?php
$ch = curl_init('https://www.links.hr/hr/lokacije');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
error_reporting(E_ERROR | E_PARSE);
$htmlDocContent = curl_exec($ch);
curl_close($ch);

$timePattern = '(\d{1,2}:\d{1,2})';
preg_match_all("#<h2 data-keyword=.*?Links (.*?)\s*</a>.*?<span class=\"title\">Radno vrijeme:</span>.*?<span>Radni dan:</span>.*?<i>$timePattern.*?$timePattern</i>.*?<span>Subota:</span>.*?<i>$timePattern.*?$timePattern</i>#s", $htmlDocContent, $matches, PREG_SET_ORDER);
$locationsPerOpeningHours = [];
foreach ($matches as $m) {
    $locationsPerOpeningHours["$m[2]-$m[3],$m[4]-$m[5]"][] = $m[1];
}

$prevalentOpeningHours = null;
$prevalentLocations = null;
$maxNumOfLocationsWithSameOpeningHours = 0;
foreach ($locationsPerOpeningHours as $openingHours => $locations) {
    $currNumOfLocationsWithSameOpeningHours = count($locations);
    $locationsPerOpeningHours[$openingHours] = $locations = natural_language_join($locations);
    if ($maxNumOfLocationsWithSameOpeningHours < $currNumOfLocationsWithSameOpeningHours) {
        $maxNumOfLocationsWithSameOpeningHours = $currNumOfLocationsWithSameOpeningHours;
        $prevalentOpeningHours = $openingHours;
        $prevalentLocations = $locations;
    }
}

$englishMessageContent = 'For buyers, we are available in store in ';
unset($locationsPerOpeningHours[$prevalentOpeningHours]);
$firstValue = reset($locationsPerOpeningHours);
list($openingHoursWeekdays, $openingHoursSaturdays) = explode(',', key($locationsPerOpeningHours));
$englishMessageContent .= "$firstValue $openingHoursWeekdays during weekdays and $openingHoursSaturdays on Saturdays";
while ($locations = next($locationsPerOpeningHours)) {
    list($openingHoursWeekdays, $openingHoursSaturdays) = explode(',', key($locationsPerOpeningHours));
    $englishMessageContent .= ", in $locations $openingHoursWeekdays and $openingHoursSaturdays";
}
list($openingHoursWeekdays, $openingHoursSaturdays) = explode(',', $prevalentOpeningHours);
$englishMessageContent .= ", and in all the other locations $openingHoursWeekdays during weekdays as well as $openingHoursSaturdays on Satudays. On Sundays and holidays we are closed.";
$englishMessageContent = preg_replace(array('/0(\d:\d{1,2})/', '/(\d{1,2}):00/'), '$1', $englishMessageContent);

require_once 'interpretirajZahtjev.php';
$translatedInput = translateInput($englishMessageContent, 'en', 'hr', false);
if ($translatedInput && $translatedInput['status'] === 'OK') {
    $exactOpeningHours = $translatedInput['translate'];
}

/**
 * Join a string with a natural language conjunction at the end. 
 * https://gist.github.com/angry-dan/e01b8712d6538510dd9c
 */
function natural_language_join($list, $conjunction = 'and') {
    $last = array_pop($list);
    if ($list) {
        return implode(', ', $list) . " $conjunction $last";
    }
    return $last;
}
?>