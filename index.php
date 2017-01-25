<?php

if (!file_exists(__DIR__ . '/data')) {
    if (!is_writable(__DIR__)) {
        throw new RuntimeException('The "data" directory is missing and the current path is not writable.');
    }

    if (!mkdir(__DIR__ . '/data')) {
        throw new RuntimeException('Could not create the "data" directory.');
    }
}

if (!is_writable(__DIR__ . '/data')) {
    throw new RuntimeException('The "data" directory is not writable.');
}

if (!function_exists('mb_strtoupper')) {
    throw new RuntimeException('You need the Multibyte String extension to run this script.');
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new RuntimeException('Run "composer install" before proceeding.');
}

require __DIR__ . '/vendor/autoload.php';

$countriesByCode = [];
$countryCodesByName = [];

$client = new GuzzleHttp\Client();

/**
 * ISO 3166 - Country codes
 *
 * http://www.statoids.com/wab.html
 */
$countryNamesMap = [
    'Åland'                                   => 'Åland Islands',
    'Côte d\'Ivoire'                          => 'Côte d’Ivoire',
    'British Indian OceanTerritory'           => 'British Indian Ocean Territory',
    'French Southern Lands'                   => 'French Southern Territories',
    'Reunion'                                 => 'Réunion',
    'Saint Vincent and theGrenadines'         => 'Saint Vincent and the Grenadines',
    'South Georgia and SouthSandwich Islands' => 'South Georgia and South Sandwich Islands',
    'Svalbard and Jan MayenIslands'           => 'Svalbard and Jan Mayen',
    'United States MinorOutlying Islands'     => 'United States Minor Outlying Islands',
    'Wallis and Futuna Islands'               => 'Wallis and Futuna',
];

$url = 'http://www.statoids.com/wab.html';
$res = $client->request('GET', $url);
$body = $res->getBody()->__toString();

// DOM Fixes
$body = str_replace('</h4></p>', '</h4>', $body);
$body = str_replace('</li></p>', '</p>', $body);
$body = str_replace(
    'http://www.itu.int/cgi-bin/htsh/mm/scripts/mm.list?_search=ITUstates&_languageid=1',
    'http://www.itu.int/cgi-bin/htsh/mm/scripts/mm.list?_search=ITUstates&amp;_languageid=1',
    $body
);

$dom = new DOMDocument();
$dom->loadHTML($body);

$table = $dom->getElementsByTagName('table')->item(0);

/** @var DOMElement $row */
foreach ($table->getElementsByTagName('tr') as $row) {
    if ($row->getAttribute('class') === 'hd' || $row->getAttribute('class') === 'cp') {
        continue;
    }

    $cells = $row->getElementsByTagName('td');

    $countryName = $cells->item(0)->textContent;
    $countryCode = $cells->item(1)->textContent;

    if (isset($countryNamesMap[$countryName])) {
        $countryName = $countryNamesMap[$countryName];
    }

    $countriesByCode[$countryCode] = [
        'name'              => $countryName,
        'code'              => $countryCode,
        'code3'             => $cells->item(2)->textContent,
        'num'               => $cells->item(3)->textContent,
        'phone_code'        => '+' . $cells->item(12)->textContent,
        'currency_name'     => '',
        'currency_code'     => '',
        'currency_number'   => '',
        'currency_decimals' => '',
    ];

    $countryCodesByName[mb_strtoupper($countryName)] = $countryCode;
}

/**
 * ISO 4217 - Currency codes
 *
 * https://www.currency-iso.org/en/home/tables/table-a1.html
 */
$url = 'https://www.currency-iso.org/dam/downloads/lists/list_one.xml';
$res = $client->request('GET', $url);
$body = $res->getBody()->__toString();

$xml = new DOMDocument();
$xml->loadXML($body);

$currencyCountryNameMap = [
    'BOLIVIA (PLURINATIONAL STATE OF)'                           => 'BOLIVIA',
    'BONAIRE, SINT EUSTATIUS AND SABA'                           => 'BONAIRE, SINT EUSTATIUSAND SABA',
    'CABO VERDE'                                                 => 'CAPE VERDE',
    'CONGO (THE DEMOCRATIC REPUBLIC OF THE)'                     => 'CONGO (KINSHASA)',
    'CONGO'                                                      => 'CONGO (BRAZZAVILLE)',
    'EUROPEAN UNION'                                             => null,
    'FALKLAND ISLANDS (THE) [MALVINAS]'                          => 'FALKLAND ISLANDS',
    'HEARD ISLAND AND McDONALD ISLANDS'                          => 'HEARD AND MCDONALD ISLANDS',
    'HOLY SEE'                                                   => 'VATICAN CITY',
    'INTERNATIONAL MONETARY FUND (IMF) '                         => null,
    'IRAN (ISLAMIC REPUBLIC OF)'                                 => 'IRAN',
    'KOREA (THE DEMOCRATIC PEOPLE’S REPUBLIC OF)'                => 'KOREA, NORTH',
    'KOREA (THE REPUBLIC OF)'                                    => 'KOREA, SOUTH',
    'LAO PEOPLE’S DEMOCRATIC REPUBLIC'                           => 'LAOS',
    'MACAO'                                                      => 'MACAU',
    'MACEDONIA (THE FORMER YUGOSLAV REPUBLIC OF)'                => 'MACEDONIA',
    'MEMBER COUNTRIES OF THE AFRICAN DEVELOPMENT BANK GROUP'     => null,
    'MICRONESIA (FEDERATED STATES OF)'                           => 'MICRONESIA',
    'MOLDOVA (THE REPUBLIC OF)'                                  => 'MOLDOVA',
    'PALESTINE, STATE OF'                                        => 'PALESTINE',
    'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA'               => 'SAINT HELENA',
    'SINT MAARTEN (DUTCH PART)'                                  => 'SINT MAARTEN',
    'SISTEMA UNITARIO DE COMPENSACION REGIONAL DE PAGOS "SUCRE"' => null,
    'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS'               => 'SOUTH GEORGIA AND SOUTH SANDWICH ISLANDS',
    'SYRIAN ARAB REPUBLIC'                                       => 'SYRIA',
    'TAIWAN (PROVINCE OF CHINA)'                                 => 'TAIWAN',
    'TANZANIA, UNITED REPUBLIC OF'                               => 'TANZANIA',
    'UNITED KINGDOM OF GREAT BRITAIN AND NORTHERN IRELAND'       => 'UNITED KINGDOM',
    'VENEZUELA (BOLIVARIAN REPUBLIC OF)'                         => 'VENEZUELA',
    'VIET NAM'                                                   => 'VIETNAM',
    'VIRGIN ISLANDS (BRITISH)'                                   => 'VIRGIN ISLANDS, BRITISH',
    'VIRGIN ISLANDS (U.S.)'                                      => 'VIRGIN ISLANDS, U.S.',
    'ZZ01_Bond Markets Unit European_EURCO'                      => null,
    'ZZ02_Bond Markets Unit European_EMU-6'                      => null,
    'ZZ03_Bond Markets Unit European_EUA-9'                      => null,
    'ZZ04_Bond Markets Unit European_EUA-17'                     => null,
    'ZZ06_Testing_Code'                                          => null,
    'ZZ07_No_Currency'                                           => null,
    'ZZ08_Gold'                                                  => null,
    'ZZ09_Palladium'                                             => null,
    'ZZ10_Platinum'                                              => null,
    'ZZ11_Silver'                                                => null,
];

/** @var DOMElement $countryRow */
foreach ($xml->getElementsByTagName('CcyNtry') as $countryRow) {
    $countryName = trim($countryRow->getElementsByTagName('CtryNm')->item(0)->textContent);

    $countryName = str_replace("'", '’', $countryName);
    if (substr($countryName, -6) === ' (THE)') {
        $countryName = substr($countryName, 0, -6);
    }

    if (array_key_exists($countryName, $currencyCountryNameMap)) {
        if ($currencyCountryNameMap[$countryName] === null) {
            continue;
        }

        $countryName = $currencyCountryNameMap[$countryName];
    }

    if (!isset($countryCodesByName[$countryName])) {
        throw new RuntimeException(sprintf('The country "%s" does not exist.', $countryName));
    }

    $countryCode = $countryCodesByName[$countryName];

    $currencyName = $countryRow->getElementsByTagName('CcyNm')->item(0);
    $currencyCode = $countryRow->getElementsByTagName('Ccy')->item(0);
    $currencyNumber = $countryRow->getElementsByTagName('CcyNbr')->item(0);
    $currencyDecimals = $countryRow->getElementsByTagName('CcyMnrUnts')->item(0);

    $countriesByCode[$countryCode]['currency_name'] = $currencyName ? $currencyName->textContent : '';
    $countriesByCode[$countryCode]['currency_code'] = $currencyCode ? $currencyCode->textContent : '';
    $countriesByCode[$countryCode]['currency_number'] = $currencyNumber ? $currencyNumber->textContent : '';
    $countriesByCode[$countryCode]['currency_decimals'] = $currencyDecimals ? $currencyDecimals->textContent : '';
}

/**
 * CSV Generation
 */
$csv = fopen(__DIR__ . '/data/country-code.csv', 'w');

fputcsv($csv, [
    'name'              => 'Name',
    'code'              => 'ISO Code 2',
    'code3'             => 'ISO Code 3',
    'num'               => 'ISO Number',
    'phone_code'        => 'Phone code',
    'currency_name'     => 'Currency Name',
    'currency_code'     => 'Currency Code',
    'currency_number'   => 'Currency Number',
    'currency_decimals' => 'Currency Decimals',
], ',', '"');

foreach ($countriesByCode as $country) {
    fputcsv($csv, $country, ',', '"');
}

/**
 * JSON Generation
 */
file_put_contents(
    __DIR__ . '/data/country-code.json',
    json_encode($countriesByCode, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
