<?php

trait LogTrait
{
    public function logMessage($message)
    {
        error_log($message);
    }
}

interface BarbershopInterface
{
    public function __toString();
    public function calculateYearsSinceCreation($currentYear);
    public static function getExchangeRate($currencyCode): float|int|false;
    public static function convertPrice($price, $toCurrency): float|string;
}

interface BranchInterface
{
    public function __toString();
    public function addService($serviceName, $price);
    public function updatePrice($serviceName, $newPrice);
}

class Barbershop implements BarbershopInterface
{
    use LogTrait;

    private static Barbershop $instance;

    public function __construct(
        private string $chainName,
        private string $slogan,
        public string $workTime,
        public string $email,
        protected int $yearOfCreatingFranchise
    ) {
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new \Exception("Неможливо десеріалізувати синглтон.");
    }

    public static function getInstance(
        string $chainName,
        string $slogan,
        string $workTime,
        string $email,
        int $yearOfCreatingFranchise
    ): static {
        if (!isset(self::$instance)) {
            self::$instance = new static($chainName, $slogan, $workTime, $email, $yearOfCreatingFranchise);
        }
        return self::$instance;
    }


    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            $this->logMessage("Поля $property не існує");
            return null;
        }
    }

    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            switch ($property) {
                case 'chainName':
                case 'slogan':
                case 'workTime':
                case 'email':
                    if (!is_string($value)) {
                        $this->logMessage("Значення для властивості $property має бути рядком");
                        return;
                    }
                    break;
                case 'yearOfCreatingFranchise':
                    if (!is_int($value)) {
                        $this->logMessage("Значення для властивості $property має бути цілим числом");
                        return;
                    }
                    break;
            }
            $this->$property = $value;
        } else {
            $this->logMessage("Поля $property не існує");
        }
    }

    public function __toString(): string
    {
        $openingTime = explode(" - ", $this->workTime)[0];
        $closingTime = explode(" - ", $this->workTime)[1];

        $currentYear = date("Y");
        $year = $this->calculateYearsSinceCreation($currentYear);

        return "<h2> Про перукарню </h2>
                <b>{$this->chainName}</b> поєднав у собі старовинні традиції з технологіями, кінематографічне освітлення для якісної геометрії в стрижках і класичні техніки з мистецтвом скульптури чоловічої стрижки, тільки так можна розкрити і створити індивідуальний, впевнений образ. Заснований <b>{$year}</b> він встиг здобути прихильність серед багатьох відвідувачів, адже ми керуємось одним правилом: '<b>{$this->slogan}</b>'.<br><br>
                Час роботи: <br><b>{$openingTime} - {$closingTime}</b>";
    }

    public function calculateYearsSinceCreation($currentYear): string
    {
        if ($this->yearOfCreatingFranchise == $currentYear) {
            return "цього року";
        } elseif ($this->yearOfCreatingFranchise < $currentYear) {
            return "понад " . ($currentYear - $this->yearOfCreatingFranchise) . " років";
        } else {
            return "ніколи";
        }
    }

    public static function getExchangeRate($currencyCode): float|int|false
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

    public static function convertPrice($price, $toCurrency): float|string
    {
        switch ($toCurrency) {
            case '₴':
                $convertedPrice = $price;
                break;
            case '$':
                $usdExchangeRate = self::getExchangeRate('USD');
                if ($usdExchangeRate === false) {
                    return "Error: Unable to retrieve USD exchange rate.";
                }
                $convertedPrice = $price / $usdExchangeRate;
                break;
            case '€':
                $eurExchangeRate = self::getExchangeRate('EUR');
                if ($eurExchangeRate === false) {
                    return "Error: Unable to retrieve EUR exchange rate.";
                }
                $convertedPrice = $price / $eurExchangeRate;
                break;
            default:
                $convertedPrice = $price;
                break;
        }

        return $convertedPrice;
    }
}

class Branch extends Barbershop implements BranchInterface
{
    use LogTrait;

    public function __construct(
        string $chainName,
        string $slogan,
        string $workTime,
        string $email,
        int $yearOfCreatingFranchise,
        public string $branchName,
        public string $branchAdress,
        public string $branchLocation,
        public string $branchPhone,
        private array $services = [],
        public array $prices = []
    ) {
        parent::__construct($chainName, $slogan, $workTime, $email, $yearOfCreatingFranchise);

        $this->services = [
            "Чоловіча стрижка",
            "Стрижкка бороди",
            "Стрижка під насадку (до двох насадок)",
            "Дитяча стрижка ( до 12 років)",
            "Депіляція воском (вуха, ніс, брови)",
            "Королівське гоління небезпечною бритвою",
            "Укладка зачіски",
            "Стрижка вусів"
        ];

        $this->setPrices();
    }

    private function setPrices(): void
    {
        $this->prices = [
            $this->services[0] => 500,
            $this->services[1] => 250,
            $this->services[0] . " + " . $this->services[1] => 750,
            $this->services[2] => 300,
            $this->services[1] . " + " . $this->services[2] => 550,
            $this->services[3] => 400,
            $this->services[4] => 100,
            $this->services[5] => 300,
            $this->services[6] => 100,
            $this->services[7] => 100,
        ];
    }

    public function addService($serviceName, $price)
    {
        if (array_key_exists($serviceName, $this->prices)) {
            $this->logMessage("Послуга '$serviceName' уже існує.");
        } else {
            $this->services[] = $serviceName;
            $this->prices[$serviceName] = $price;
        }
    }

    public function updatePrice($serviceName, $newPrice)
    {
        if (array_key_exists($serviceName, $this->prices)) {
            $this->prices[$serviceName] = $newPrice;
            $this->logMessage("Ціни за '$serviceName' успішно оновленні.");
        } else {
            $this->logMessage("Послуга '$serviceName' не існує.");
        }
    }

    public function __toString(): string
    {
        return "<h3>Контакти для {$this->branchName}</h3> <p>{$this->branchAdress}</p> <p>{$this->branchLocation}</p> <p>{$this->branchPhone}</p><br>";
    }
}

$barbershop = Barbershop::getInstance("Frisor", "Ліга досвідчених чоловічих перукарів, де приходять до майстра, а не в перукарню", "Щодня 10:00 - 21:00", "kyiv@frisor.ua", 2014);

echo $barbershop;

$branch1 = new Branch($barbershop->chainName, $barbershop->slogan, $barbershop->workTime, $barbershop->email, $barbershop->yearOfCreatingFranchise, "Frisor I", "ЄВГЕНА ЧИКАЛЕНКА, 9Б (ПУШКІНСЬКА)", "м. Театральна", "096 688 06 97");
$branch2 = new Branch($barbershop->chainName, $barbershop->slogan, $barbershop->workTime, $barbershop->email, $barbershop->yearOfCreatingFranchise, "Frisor II", "ВУЛ. САКСАГАНСЬКОГО, 27", "м. Олімпійська", "098 120 80 32");
$branch3 = new Branch($barbershop->chainName, $barbershop->slogan, $barbershop->workTime, $barbershop->email, $barbershop->yearOfCreatingFranchise, "Frisor III", "ЛЬВА ТОЛСТОГО, 23/1", "Ботанічний сад", "067 355 75 01");

echo $branch1;
echo $branch2;
echo $branch3;

echo "<h2>Ціни</h2>";

echo "<h2>Оберіть валюту:</h2>";
echo "<form action='' method='GET'>";
echo "<button type='submit' name='currency' value='₴'>₴</button>";
echo "<button type='submit' name='currency' value='$'>$</button>";
echo "<button type='submit' name='currency' value='€'>€</button>";
echo "</form>";

$currencySymbol = isset($_GET['currency']) ? $_GET['currency'] : '₴';

echo "<h2>У Frisor I</h2>";
foreach ($branch1->prices as $service => $price) {
    $convertedPrice = Barbershop::convertPrice($price, $currencySymbol);
    $formattedConvertedPrice = number_format($convertedPrice, 2);
    echo "<p>Ціна за $service: $formattedConvertedPrice $currencySymbol</p>";
}

foreach ($branch2->prices as $service => $price) {
    $newPrice = $price * 1.5;
    $branch2->updatePrice($service, $newPrice);
}

echo "<h2>У Frisor II</h2>";
foreach ($branch2->prices as $service => $price) {
    $convertedPrice = Barbershop::convertPrice($price, $currencySymbol);
    $formattedConvertedPrice = number_format($convertedPrice, 2);
    echo "<p>Ціна за $service: $formattedConvertedPrice $currencySymbol</p>";
}
