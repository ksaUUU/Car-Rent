<?php
session_start();
require_once("classes/Baza.php");
require_once("classes/UserManager.php");
require_once("classes/CarManager.php");
require_once("classes/User.php");

$db = new Baza("localhost", "root", "", "klienci");
$um = new UserManager();
$cm = new CarManager();

// 1. Weryfikacja sesji
$sessionId = session_id();
$loginData = $um->getLoggedInUser($db, $sessionId);

if ($loginData === -1) {
    header("location:processLogin.php");
    exit();
}

$currentUserId = $loginData['userId'];
$currentUser = $db->selectUserById($currentUserId, "users");
$isAdmin = ($currentUser->status == User::STATUS_ADMIN);

$sort = $_GET['sort'] ?? 'cena_doba';
$order = $_GET['order'] ?? 'DESC';

$cars = $cm->getAllCars($db, $sort, $order);

$statsTotal = count($cars);
$statsRented = 0;
foreach ($cars as $c) {
    if ($c->wynajmujacy_id) $statsRented++;
}
$statsFree = $statsTotal - $statsRented;
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Wypożyczalnia Aut</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <div>
                <h1>System Wypożyczalni</h1>
                <small>Panel: <?php echo $isAdmin ? "Administrator" : "Klient"; ?></small>
            </div>
            <div class="user-info">
                Witaj, <?php echo htmlspecialchars($currentUser->fullName); ?>
                <a href="processLogin.php?akcja=wyloguj" class="btn btn-danger">Wyloguj</a>
            </div>
        </header>

        <main>
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="msg-success"><?php echo $_SESSION['msg'];
                                            unset($_SESSION['msg']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="msg-error"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <?php if ($isAdmin): ?>
                <div class="stats-box">
                    <div class="stat-card">
                        <h3><?php echo $statsTotal; ?></h3>
                        <p>Wszystkie Auta</p>
                    </div>
                    <div class="stat-card border-green">
                        <h3><?php echo $statsFree; ?></h3>
                        <p>Dostępne</p>
                    </div>
                    <div class="stat-card border-red">
                        <h3><?php echo $statsRented; ?></h3>
                        <p>Wypożyczone</p>
                    </div>
                </div>

                <div class="actions" style="margin-bottom: 20px; text-align: right;">
                    <a href="car_form.php" class="btn btn-success">+ Dodaj Samochód</a>
                    <a href="admin_clients.php" class="btn btn-primary" style="background-color: #6f42c1;">📋 Raport Wypożyczeń</a>
                    <a href="admin_stats.php" class="btn btn-warning">📊 Statystyki</a>
                </div>
            <?php else: ?>
                <div class="actions" style="margin-bottom: 20px; text-align: right;">
                    <a href="my_rentals.php" class="btn btn-primary">📋 Historia Wypożyczeń</a>
                </div>
            <?php endif; ?>

            <form action="dashboard.php" method="GET" class="filter-bar">
                <div>
                    <label for="sort">Sortuj według:</label>
                    <select name="sort" id="sort">
                        <option value="cena_doba" <?php if ($sort == 'cena_doba') echo 'selected'; ?>>Cena za dobę</option>
                        <option value="marka" <?php if ($sort == 'marka') echo 'selected'; ?>>Marka i Model</option>
                        <option value="przebieg" <?php if ($sort == 'przebieg') echo 'selected'; ?>>Przebieg</option>
                        <option value="moc" <?php if ($sort == 'moc') echo 'selected'; ?>>Moc silnika</option>
                        <option value="status" <?php if ($sort == 'status') echo 'selected'; ?>>Dostępność</option>
                    </select>
                </div>

                <div>
                    <label for="order">Kolejność:</label>
                    <select name="order" id="order">
                        <option value="ASC" <?php if ($order == 'ASC') echo 'selected'; ?>>Rosnąco (A-Z, Najtańsze)</option>
                        <option value="DESC" <?php if ($order == 'DESC') echo 'selected'; ?>>Malejąco (Z-A, Najdroższe)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Zastosuj</button>
            </form>

            <div class="table-wrapper">
                <table class="desktop-only">
                    <thead>
                        <tr>
                            <th>Marka i Model</th>
                            <th>Dane Techniczne</th>
                            <th>Przebieg</th>
                            <th>Cena / Doba</th>
                            <th>Status / Koszty</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cars as $car): ?>
                            <?php
                            $isRented = !empty($car->wynajmujacy_id);
                            $rentedByMe = ($car->wynajmujacy_id == $currentUserId);
                            $currentBill = 0;
                            if ($isRented && $car->data_wynajmu) {
                                $start = new DateTime($car->data_wynajmu);
                                $now = new DateTime();
                                $days = $start->diff($now)->days;
                                if ($days == 0) $days = 1;
                                $currentBill = $days * $car->cena_doba;
                            }
                            ?>
                            <tr>
                                <td>
                                    <b style="font-size:1.1em"><?php echo htmlspecialchars($car->marka); ?></b><br>
                                    <?php echo htmlspecialchars($car->model); ?>
                                </td>
                                <td>
                                    Rocznik: <strong><?php echo $car->rok; ?></strong><br>
                                    Moc: <strong><?php echo $car->moc; ?> KM</strong><br>
                                    Skrzynia: <?php echo htmlspecialchars($car->skrzynia); ?><br>
                                    <small style="color:#666;">Kolor: <?php echo htmlspecialchars($car->kolor); ?></small>
                                </td>
                                <td><?php echo number_format($car->przebieg, 0, ',', ' '); ?> km</td>
                                <td><strong><?php echo number_format($car->cena_doba, 2); ?> PLN</strong></td>
                                <td>
                                    <?php if ($isRented): ?>
                                        <?php if ($rentedByMe): ?>
                                            <span style="color:green; font-weight:bold;">Twój koszt: <?php echo number_format($currentBill, 2); ?> PLN</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rented">Zajęty</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-badge status-free">Dostępny</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isAdmin): ?>
                                        <a href="car_form.php?edit=<?php echo $car->id; ?>" class="btn btn-primary">Edytuj</a>
                                        <a href="car_action.php?delete=<?php echo $car->id; ?>" class="btn btn-danger" onclick="return confirm('Usunąć?')">Usuń</a>
                                    <?php else: ?>
                                        <?php if (!$isRented): ?>
                                            <a href="car_action.php?rent=<?php echo $car->id; ?>" class="btn btn-success">Wypożycz</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="mobile-only">
                    <?php foreach ($cars as $car):
                        $isRented = !empty($car->wynajmujacy_id);
                        $rentedByMe = ($car->wynajmujacy_id == $currentUserId);
                        $currentBill = 0;
                        if ($isRented && $car->data_wynajmu) {
                            $start = new DateTime($car->data_wynajmu);
                            $now = new DateTime();
                            $days = $start->diff($now)->days;
                            $days = $days == 0 ? 1 : $days;
                            $currentBill = $days * $car->cena_doba;
                        }
                    ?>
                        <div class="mobile-card">
                            <div class="mobile-card-info">
                                <h3><?php echo htmlspecialchars($car->marka . ' ' . $car->model); ?></h3>
                                <p><?php echo number_format($car->cena_doba, 2); ?> PLN / doba</p>
                                <?php if (!$isRented): ?>
                                    <small style="color:green;">Dostępny</small>
                                <?php else: ?>
                                    <small style="color:red;">Zajęty</small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="openModal('modal-<?php echo $car->id; ?>')">Szczegóły</button>
                            </div>
                        </div>

                        <div id="modal-<?php echo $car->id; ?>" class="modal">
                            <div class="modal-content">
                                <span class="close-modal" onclick="closeModal('modal-<?php echo $car->id; ?>')">&times;</span>

                                <h2 style="margin-top:0; color:#007bff;"><?php echo htmlspecialchars($car->marka . ' ' . $car->model); ?></h2>

                                <div class="modal-details">
                                    <p><strong>Rocznik:</strong> <?php echo $car->rok; ?></p>

                                    <p><strong>Silnik:</strong> <?php echo $car->moc; ?> KM (<?php echo htmlspecialchars($car->skrzynia); ?>)</p>
                                    <p><strong>Kolor:</strong> <?php echo htmlspecialchars($car->kolor); ?></p>

                                    <p><strong>Przebieg:</strong> <?php echo number_format($car->przebieg, 0, ',', ' '); ?> km</p>
                                    <p><strong>Cena:</strong> <?php echo number_format($car->cena_doba, 2); ?> PLN / doba</p>
                                    <p><strong>Status:</strong>
                                        <?php if ($isRented): ?>
                                            <span style="color:red; font-weight:bold;">Wypożyczony</span>
                                            <?php if ($rentedByMe): ?>
                                                <br>Twoje naliczone opłaty: <b style="color:red;"><?php echo number_format($currentBill, 2); ?> PLN</b>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:green; font-weight:bold;">Dostępny</span>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="modal-actions">
                                    <?php if ($isAdmin): ?>
                                        <a href="car_form.php?edit=<?php echo $car->id; ?>" class="btn btn-primary">Edytuj Samochód</a>
                                        <a href="car_action.php?delete=<?php echo $car->id; ?>" class="btn btn-danger" onclick="return confirm('Usunąć?')">Usuń Samochód</a>
                                    <?php else: ?>
                                        <?php if (!$isRented): ?>
                                            <a href="car_action.php?rent=<?php echo $car->id; ?>" class="btn btn-success">Wypożycz Teraz</a>
                                        <?php elseif ($rentedByMe): ?>
                                            <button class="btn btn-disabled">Masz to auto</button>
                                        <?php else: ?>
                                            <button class="btn btn-disabled">Niedostępne</button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <button class="btn btn-warning" onclick="closeModal('modal-<?php echo $car->id; ?>')">Zamknij</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>

</html>