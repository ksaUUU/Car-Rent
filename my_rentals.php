<?php
session_start();
require_once("classes/Baza.php");
require_once("classes/UserManager.php");
require_once("classes/CarManager.php");
require_once("classes/User.php");

$db = new Baza("localhost", "root", "", "klienci");
$um = new UserManager();
$cm = new CarManager();

// Weryfikacja sesji
$sessionId = session_id();
$loginData = $um->getLoggedInUser($db, $sessionId);

if ($loginData === -1) {
    header("location:processLogin.php");
    exit();
}

$currentUserId = $loginData['userId'];
$currentUser = $db->selectUserById($currentUserId, "users");

// Pobierz historię wypożyczeń
$rentals = $cm->getClientRentalHistory($db, $currentUserId);
$totalSpent = $cm->getClientTotalSpent($db, $currentUserId);
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Wypożyczenia</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div>
                <h1>Historia Moich Wypożyczeń</h1>
                <small>Panel: Klient</small>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-primary">← Powrót</a>
                <a href="processLogin.php?akcja=wyloguj" class="btn btn-danger">Wyloguj</a>
            </div>
        </header>

        <main>
            <!-- Podsumowanie -->
            <div class="stats-box">
                <div class="stat-card border-green">
                    <h3><?php echo count($rentals); ?></h3>
                    <p>Łącznie Wypożyczeń</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo number_format($totalSpent, 2); ?> PLN</h3>
                    <p>Całkowite Wydatki</p>
                </div>
            </div>

            <?php if (empty($rentals)): ?>
                <div class="msg-success">
                    <strong>Brak historii.</strong> Nie masz jeszcze żadnych zakończonych wypożyczeń.
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="desktop-only">
                        <thead>
                            <tr>
                                <th>Samochód</th>
                                <th>Data wypożyczenia</th>
                                <th>Data zwrotu</th>
                                <th>Czas trwania</th>
                                <th>Cena/doba</th>
                                <th>Koszt całkowity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($rental->marka . ' ' . $rental->model); ?></strong>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($rental->data_rozpoczecia)); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($rental->data_zakonczenia)); ?></td>
                                    <td>
                                        <span class="status-badge status-free"><?php echo $rental->dni; ?> dni</span>
                                    </td>
                                    <td><?php echo number_format($rental->cena_doba, 2); ?> PLN</td>
                                    <td>
                                        <strong style="color: #4de6c9; font-size: 1.1em;">
                                            <?php echo number_format($rental->koszt_calkowity, 2); ?> PLN
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="mobile-only">
                        <?php foreach ($rentals as $rental): ?>
                            <div class="mobile-card" style="flex-direction: column; align-items: stretch;">
                                <div style="margin-bottom: 15px;">
                                    <h3 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($rental->marka . ' ' . $rental->model); ?></h3>
                                    <small style="color: var(--text-muted);">
                                        <?php echo date('d.m.Y', strtotime($rental->data_rozpoczecia)); ?>
                                        →
                                        <?php echo date('d.m.Y', strtotime($rental->data_zakonczenia)); ?>
                                    </small>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="status-badge status-free"><?php echo $rental->dni; ?> dni</span>
                                    <strong style="color: #4de6c9; font-size: 1.2em;">
                                        <?php echo number_format($rental->koszt_calkowity, 2); ?> PLN
                                    </strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>