<?php
session_start();
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'guest';
class RequestWrapper
{
    public function __construct(
        private array $request = []
    ) {
    }

    public function get(string $key): mixed
    {
        return $this->request[$key] ?? null;
    }

    public function post(string $key): mixed
    {
        return $this->request[$key] ?? null;
    }

    public function getRequest(string $key): mixed
    {
        return $this->request[$key] ?? null;
    }

    public function getAllGet(): array
    {
        return $_GET;
    }

    public function getAllPost(): array
    {
        return $_POST;
    }

    public function getAllRequest(): array
    {
        return $this->request;
    }
}

$request = new RequestWrapper($_REQUEST);
$get = new RequestWrapper($_GET);
$post = new RequestWrapper($_POST);
$isAdmin = ($userType === 'admin');


$barbershopId = $get->get('barbershop_id');

$lastSearchQueries = isset($_COOKIE['last_search_queries']) ? unserialize($_COOKIE['last_search_queries']) : [];

if (!$barbershopId) {
    $searchQuery = $request->getRequest('search_query');
    if ($searchQuery && !in_array($searchQuery, $lastSearchQueries)) {
        array_unshift($lastSearchQueries, $searchQuery);

        if (count($lastSearchQueries) > 10) {
            array_pop($lastSearchQueries);
        }

        setcookie('last_search_queries', serialize($lastSearchQueries), time() + (86400 * 30), "/");
    }
    $dbconn = pg_connect("host=localhost port=5432 dbname=barbershop user=postgres password=2S6t3y4g8h9l-5")
        ?: die("Помилка з'єднання: " . pg_last_error());

    $query = 'SELECT id, chain_name FROM barbershops';

    if (!empty($searchQuery)) {
        $query .= " WHERE chain_name ILIKE '%$searchQuery%'";
    }

    $result = pg_query($dbconn, $query)
        ?: die('Query failed: ' . pg_last_error($dbconn));

    if (!empty($lastSearchQueries)) {
        echo "<p>Ви шукали:</p>";
        echo "<ul>";
        foreach ($lastSearchQueries as $query) {
            echo "<li>$query</li>";
        }
        echo "</ul>";
    }
    echo '<h2>Список барбершопів:</h2>';
    echo '<form method="post">';
    echo '<input type="text" name="search_query" placeholder="Пошук за назвою барбершопу" value="' . htmlspecialchars($searchQuery ?? '') . '">';
    echo '<button type="submit">Пошук</button>';
    echo '</form>';
    echo '<ul>';

    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            echo '<li><a href="httpRequests.php?barbershop_id=' . $row['id'] . '">' . $row['chain_name'] . '</a></li>';
        }
    } else {
        echo "<p>По вашому запиту нічого не знайдено.</p>";
    }

    echo '</ul>';

    pg_close($dbconn);
} else {
    $dbconn = pg_connect("host=localhost port=5432 dbname=barbershop user=postgres password=2S6t3y4g8h9l-5")
        ?: die("Помилка з'єднання: " . pg_last_error());
    $query = "SELECT chain_name, year_of_creating_franchise, slogan, work_time FROM barbershops WHERE id = $barbershopId";
    $result = pg_query($dbconn, $query)
        ?: die('Query failed: ' . pg_last_error($dbconn));
    if ($row = pg_fetch_assoc($result)) {
        $barbershopChainName = $row['chain_name'];
        $year = $row['year_of_creating_franchise'];
        $slogan = $row['slogan'];
        $workTime = $row['work_time'];
        echo "<h2> Про перукарню $barbershopChainName </h2>";
        echo "<b>" . $barbershopChainName . "</b>" . " поєднав у собі старовинні традиції з технологіями, кінематографічне освітлення для якісної геометрії в стрижках і класичні техніки з мистецтвом скульптури чоловічої стрижки, тільки так можна розкрити і створити індивідуальний, впевнений образ. Заснований <b>"
            . $year . "</b> він встиг здобути прихильність серед багатьох відвідувачів, адже ми керуємось одним правилом: '<b>" . $slogan . "</b>'." . "<br><br>"
            . "Час роботи: <br><b>" . $workTime . "</b>";

        $branchQuery = "SELECT id as branch_id, branch_name, branch_address, branch_location, branch_phone FROM branches WHERE barbershop_id = $barbershopId";
        $branchResult = pg_query($dbconn, $branchQuery)
            ?: die('Branch query failed: ' . pg_last_error($dbconn));

        if (pg_num_rows($branchResult) > 0) {
            if ($post->post('switch_to_admin') !== null) {
                $_SESSION['user_type'] = 'admin';
                $userType = 'admin';
            }

            if ($post->post('switch_to_guest') !== null) {
                $_SESSION['user_type'] = 'guest';
                $userType = 'guest';
            }
            echo '<form method="post">';
            if ($userType === 'guest') {
                echo '<button type="submit" name="switch_to_admin">Перемкнутись на режим адміністратора</button>';
            } elseif ($userType === 'admin') {
                echo '<button type="submit" name="switch_to_guest">Перемкнутись на гостьовий режим</button>';
            }
            echo '</form>';
            echo "<h3>Філії:</h3>";
            while ($branchRow = pg_fetch_assoc($branchResult)) {
                echo "<h3>Контакти для {$branchRow['branch_name']}</h3>";
                echo "<p>{$branchRow['branch_address']}</p>";
                echo "<p>{$branchRow['branch_location']}</p>";
                echo "<p>{$branchRow['branch_phone']}</p>";

                $serviceQuery = "SELECT s.service_id, s.service_name, bs.price 
                                        FROM branch_services bs 
                                        LEFT JOIN services s 
                                        ON bs.service_id = s.service_id 
                                        WHERE bs.branch_id = {$branchRow['branch_id']}";
                $serviceResult = pg_query($dbconn, $serviceQuery)
                    ?: die('Service query failed: ' . pg_last_error($dbconn));

                echo "<p>Послуги та ціни:</p>";
                echo "<ul>";
                while ($serviceRow = pg_fetch_assoc($serviceResult)) {
                    echo "<li>{$serviceRow['service_name']}: ";
                    if ($isAdmin) {
                        echo '<form method="post">';
                        echo '<input type="hidden" name="branch_id" value="' . $branchRow['branch_id'] . '">';
                        echo '<input type="hidden" name="service_id" value="' . $serviceRow['service_id'] . '">';
                        echo '<input type="text" name="new_price" value="' . $serviceRow['price'] . '">';
                        echo '<button type="submit" name="update_price">Змінити ціну</button>';
                        echo '</form>';
                    } else {
                        echo $serviceRow['price'];
                    }
                    echo "</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p>Цій перукарні поки що не належать жодні філії.</p>";
        }
    } else {
        echo "Барбершоп з ідентифікатором $barbershopId не знайдено.";
    }

    if ($isAdmin && $post->post('update_price') !== null) {
        $newPrice = $post->post('new_price');
        $branchId = $post->post('branch_id');
        $serviceId = $post->post('service_id');
        $updatePriceQuery = "UPDATE branch_services SET price = $newPrice WHERE branch_id = $branchId AND service_id = $serviceId";
        $updatePriceResult = pg_query($dbconn, $updatePriceQuery);
        if ($updatePriceResult) {
            echo "<p>Ціна успішно оновлена!</p>";
        } else {
            echo "<p>Помилка при оновленні ціни!</p>";
        }
    }
    pg_close($dbconn);
}
