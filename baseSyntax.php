<?php

function getExchangeRate($currencyCode)
{
    $url = "https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=$currencyCode&json";

    $response = file_get_contents($url);
    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    if (!isset($data[0]['rate'])) {
        return false;
    }
    $exchangeRate = $data[0]['rate'];

    return $exchangeRate;
}


echo "<h1>1. Продемонструвати базовий синтаксис PHP";
$barbershopChainName = "Frisor";
$slogan = "Ліга досвідчених чоловічих перукарів, де приходять до майстра, а не в перукарню";
$workTime = "Щодня 10:00 - 21:00";
$Email = "kyiv@frisor.ua";
$yearOfCreatingFranchise = 2014;

echo $student;
echo "<br>";


$timeParts = explode(" - ", $workTime);
[$openingTime, $closingTime] = explode(" - ", $workTime);

$barbershopBranches = array("Frisor I", "Frisor II", "Frisor III", "Frisor IV", "Frisor V", "Frisor VI", "Frisor VII", "Frisor VIII", "Frisor IX", "Frisor X", "Frisor XI", "Frisor XII", "Frisor XIII", "Frisor XIV", "Frisor XV", "Frisor XVI", "Frisor XVII", "Frisor XVIII", "Frisor XIX", "Frisor XX", "Frisor XXII", "Frisor XXIV");
$currentYear = date("Y");

if ($yearOfCreatingFranchise == $currentYear) {
    $year = "у цьому році";
} elseif ($yearOfCreatingFranchise < $currentYear) {
    $year = "понад " . ($currentYear - $yearOfCreatingFranchise) . " років";
} else {
    $year = "ніколи";
}
echo "<h2> Про перукарню </h2>";
echo "<b>" . $barbershopChainName . "</b>" . " поєднав у собі старовинні традиції з технологіями, кінематографічне освітлення для якісної геометрії в стрижках і класичні техніки з мистецтвом скульптури чоловічої стрижки, тільки так можна розкрити і створити індивідуальний, впевнений образ. Заснований <b>"
    . $year . "</b> він встиг здобути прихильність серед багатьох відвідувачів, адже ми керуємось одним правилом: '<b>" . $slogan . "</b>'." . "<br><br>"
    . "Час роботи: <br><b>" . $openingTime . " - " . $closingTime
    . "</b><br>Кількість філій: <b>" . count($barbershopBranches) . "</b>";;

$barbershopContacts = array(
    "Frisor I" => array(
        "street" => "ЄВГЕНА ЧИКАЛЕНКА, 9Б (ПУШКІНСЬКА)",
        "location" => "м. Театральна",
        "phone" => "096 688 06 97"
    ),
    "Frisor II" => array(
        "street" => "ВУЛ. САКСАГАНСЬКОГО, 27",
        "location" => "м. Олімпійська",
        "phone" => "098 120 80 32"
    ),
    "Frisor III" => array(
        "street" => "ЛЬВА ТОЛСТОГО, 23/1",
        "location" => "Ботанічний сад",
        "phone" => "067 355 75 01"
    ),
);

$services = [
    "Чоловіча стрижка", "Стрижкка бороди", "Стрижка під насадку (до двох насадок)", "Дитяча стрижка ( до 12 років)", "Депіляція воском (вуха, ніс, брови)",
    "Королівське гоління небезпечною бритвою", "Укладка зачіски", "Стрижка вусів"
];
$prices = array(
    $services[0] => 500,
    $services[1] => 250,
    $services[0] . " + " . $services[2] => 750,
    $services[2] => 300,
    $services[1] . " + " . $services[3] => 550,
    $services[3] => 400,
    $services[4] => 100,
    $services[5] => 300,
    $services[6] => 100,
    $services[7] => 100,
);

foreach ($barbershopBranches as $branch) {
    echo "<h3>Контакти для $branch:</h3>";
    if (isset($barbershopContacts[$branch])) {
        $contactInfo = $barbershopContacts[$branch];
        $contactDetails = implode("<br>", $contactInfo);
        echo "<p>$contactDetails</p>";
    } else {
        echo "<p>На даний момент відсутні контакти</p>";
    }
}

echo "<h2>Оберіть валюту:</h2>";
echo "<form action='' method='GET'>";
echo "<button type='submit' name='currency' value='₴'>₴</button>";
echo "<button type='submit' name='currency' value='$'>$</button>";
echo "<button type='submit' name='currency' value='€'>€</button>";
echo "</form>";


echo "<h2>Ціни</h2>";

$currencySymbol = isset($_GET['currency']) ? $_GET['currency'] : '₴';

foreach ($prices as $service => $price) {
    $formattedPrice = (float) number_format($price, 2);
    
    $exchangeRate = getExchangeRate('USD');
    switch ($currencySymbol) {
        case '₴':
            $convertedPrice = $formattedPrice;
            break;
        case '$':
            $convertedPrice = $formattedPrice / $exchangeRate;
            break;
        case '€':
            $exchangeRate = getExchangeRate('EUR');
            $convertedPrice = $formattedPrice / $exchangeRate;
            break;
        default:
            $convertedPrice = $formattedPrice;
            break;
    }
    $formattedConvertedPrice = number_format($convertedPrice, 2);
    
    echo "<p>Ціна за $service: $formattedConvertedPrice $currencySymbol</p>";
}