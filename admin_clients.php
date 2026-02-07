<?php
session_start();
require_once("classes/Baza.php");
require_once("classes/UserManager.php");
require_once("classes/CarManager.php");
require_once("classes/User.php");

$db = new Baza("localhost", "root", "", "klienci");
$um = new UserManager();
$cm = new CarManager();

// Weryfikacja Admina
$sessionId = session_id();
$loginData = $um->getLoggedInUser($db, $sessionId);
if ($loginData === -1) {
    header("location:processLogin.php");
    exit();
}

// Pobierz dane usera, sprawdź czy Admin
$currentUser = $db->selectUserById($loginData['userId'], "users");
if ($currentUser->status != User::STATUS_ADMIN) {
    header("location:dashboard.php");
    exit();
}

// Pobierz raport
$rentals = $cm->getActiveRentalsReport($db);
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>Raport Wypożyczeń - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="container">
        <header>
            <div>
                <h1>Raport Wypożyczeń</h1>
                <small>Panel: Administrator</small>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-primary">← Powrót</a>
                <a href="admin_stats.php" class="btn btn-warning">📊 Statystyki</a>
                <a href="processLogin.php?akcja=wyloguj" class="btn btn-danger">Wyloguj</a>
            </div>
        </header>

        <main>
            <?php if (empty($rentals)): ?>
                <div class="msg-success">Aktualnie nikt nie wypożycza żadnego auta.</div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Klient</th>
                                <th>Samochód</th>
                                <th>Data Wypożyczenia</th>
                                <th>Czas trwania</th>
                                <th>Należność</th>
                                <th>Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $row):
                                $start = new DateTime($row->data_wynajmu);
                                $now = new DateTime();
                                $diff = $start->diff($now);

                                $days = $diff->days;
                                if ($days == 0) $days = 1;

                                $totalCost = $days * $row->cena_doba;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row->fullName); ?></strong><br>
                                        <small style="color:#666;"><?php echo htmlspecialchars($row->email); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row->marka . ' ' . $row->model); ?><br>
                                        <small><?php echo $row->cena_doba; ?> PLN/doba</small>
                                    </td>
                                    <td><?php echo $start->format('Y-m-d H:i'); ?></td>
                                    <td>
                                        <strong><?php echo $days; ?> dni</strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 1.2em; color: green; font-weight: bold;">
                                            <?php echo number_format($totalCost, 2); ?> PLN
                                        </span>
                                    </td>
                                    <td>
                                        <a href="car_action.php?return=<?php echo $row->car_id; ?>" class="btn btn-warning" onclick="return confirm('Zakończyć wynajem i przyjąć auto?')">Odbierz Auto</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>