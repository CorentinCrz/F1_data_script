<?php
ini_set('max_execution_time', 0);
const DATA_DIR = 'data/';
const DATA_YEAR = '2017';

/**
 * function used to extract data from json file
 * deprecated because some json files were wrongly encoded
 * use getCsvData instead
 *
 * @param string $file
 * @return array
 */
function getJsonData(string $file): array
{
    $data = json_decode(file_get_contents('../' . $file . '.json'), true);

    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - Aucune erreur';
            break;
        case JSON_ERROR_DEPTH:
            echo ' - Profondeur maximale atteinte';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Inadéquation des modes ou underflow';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Erreur lors du contrôle des caractères';
            break;
        case JSON_ERROR_SYNTAX:
            echo ' - Erreur de syntaxe ; JSON malformé';
            break;
        case JSON_ERROR_UTF8:
            echo ' - Caractères UTF-8 malformés, probablement une erreur d\'encodage';
            break;
        default:
            echo ' - Erreur inconnue';
            break;
    }

    return $data;
}

/**
 * function used to extract data from csv file
 *
 * @param string $file
 * @return array
 */
function getCsvData(string $file): array
{
    echo 'Loading file : ' . $file . '.csv<br>';
    $csv = array_map('str_getcsv', file('csv/' . $file . '.csv'));
    array_walk($csv, function(&$a) use ($csv) {
        $a = array_combine($csv[0], $a);
    });
    array_shift($csv); # remove column header
    return $csv;
}

/**
 * return index's name depending on filename
 *
 * @param string $file
 * @return string
 */
function getIndexFromFile(string $file): string
{
    switch ($file) {
        case 'circuits':
            return 'circuitId';
            break;
        case 'drivers':
            return 'driverId';
            break;
        case 'constructors':
            return 'constructorId';
            break;
        default:
            return 'raceId';
            break;
    }
}

/**
 * for every element in $races, used raceId to select good elements
 * in $files array.
 * Creates a json file with selected data.
 *
 * @param array $races | array of race's data
 * @param array $files | array of filename (string)
 */
function getDataFromFiles(array $races, array $files): void
{
    $data = [];
    foreach ($files as $file) {
        $data[$file] = [
            // before is an array of csv file
            'before' => getCsvData($file),
            'after' => []
        ];
    }

    foreach ($races as $value) {
        foreach ($data as $key => &$item) {
            $index = getIndexFromFile($key);
            foreach ($item['before'] as $result) {
                if ((int)$result[$index] === (int)$value[$index]) {
                    $item['after'][] = $result;
                    $item['after'] = array_unique($item['after'], SORT_REGULAR );
                }
            }
        }
    }

    foreach ($data as $key => &$item) {
        echo 'Writing file : ' . $key . '.json<br>';
        file_put_contents(DATA_DIR . $key . '.json', json_encode(utf8ize($item['after'])));
    }
}

/**
 * encode every string in an array in utf8
 * used before json_encode
 *
 * @param $mixed
 * @return array|string
 */
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } else if (is_string ($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

echo 'Starting script with year : ' . DATA_YEAR . '<br>';
$temp = getCsvData('races');
$races = [];
foreach ($temp as $race) {
    if ($race['year'] === DATA_YEAR) {
        $races[] = $race;
    }
}
echo 'Writing file : races.json<br>';
file_put_contents(DATA_DIR . 'races.json', json_encode($races));
getDataFromFiles($races, [
    'results',
    'circuits',
    'constructorResults',
    'constructorStandings',
    'driverStandings',
    'lapTimes',
    'pitStops',
    'qualifying',
]);

$results = file_get_contents(DATA_DIR . 'results.json');
$results = json_decode($results, true);
getDataFromFiles($results, [
    'constructors',
    'drivers',
]);
echo 'Ending script';