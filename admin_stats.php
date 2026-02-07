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

$currentUser = $db->selectUserById($loginData['userId'], "users");
if ($currentUser->status != User::STATUS_ADMIN) {
    header("location:dashboard.php");
    exit();
}

$stats = $cm->getDashboardStats($db);
$revenueChart = $cm->getRevenueChart($db);
$topCars = $cm->getTopCars($db, 5);
$topClients = $cm->getTopClients($db, 5);

$chartLabels = [];
$chartData = [];
foreach ($revenueChart as $day) {
    $chartLabels[] = date('d.m', strtotime($day->day));
    $chartData[] = $day->revenue;
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statystyki - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .big-stat-card {
            background: linear-gradient(145deg, #2a2a45, #24243e);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            border-top: 4px solid var(--primary);
        }
        
        .big-stat-card.revenue { border-top-color: #38ef7d; }
        .big-stat-card.rentals { border-top-color: #667eea; }
        .big-stat-card.avg-days { border-top-color: #f7971e; }
        .big-stat-card.active { border-top-color: #ff416c; }
        
        .big-stat-card .icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .big-stat-card .value {
            font-size: 2.2em;
            font-weight: bold;
            color: #fff;
            margin: 10px 0;
        }
        
        .big-stat-card .label {
            color: var(--text-muted);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .chart-container {
            background: var(--bg-card);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .chart-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.3em;
            color: #fff;
        }
        
        .ranking-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .ranking-box {
            background: var(--bg-card);
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        
        .ranking-box h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ranking-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .ranking-item:last-child {
            border-bottom: none;
        }
        
        .ranking-position {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 0.9em;
        }
        
        .ranking-position.gold { background: linear-gradient(135deg, #f7971e, #ffd200); color: #000; }
        .ranking-position.silver { background: linear-gradient(135deg, #bdc3c7, #95a5a6); color: #000; }
        .ranking-position.bronze { background: linear-gradient(135deg, #cd7f32, #b87333); }
        
        .ranking-info {
            flex: 1;
        }
        
        .ranking-info .name {
            font-weight: 600;
            color: #fff;
        }
        
        .ranking-info .details {
            font-size: 0.85em;
            color: var(--text-muted);
        }
        
        .ranking-value {
            font-weight: bold;
            color: #4de6c9;
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <div>
                <h1>Statystyki</h1>
                <small>Panel: Administrator</small>
            </div>
            <div class="user-info">
                <a href="dashboard.php" class="btn btn-primary">← Powrót</a>
                <a href="admin_clients.php" class="btn btn-warning">📋 Wypożyczenia</a>
                <a href="processLogin.php?akcja=wyloguj" class="btn btn-danger">Wyloguj</a>
            </div>
        </header>

        <main>
            <!-- Karty statystyk -->
            <div class="dashboard-grid">
                <div class="big-stat-card revenue">
                    <div class="icon">💰</div>
                    <div class="value"><?php echo number_format($stats['totalRevenue'], 2); ?> PLN</div>
                    <div class="label">Całkowite Przychody</div>
                </div>
                <div class="big-stat-card rentals">
                    <div class="icon">🚗</div>
                    <div class="value"><?php echo $stats['totalRentals']; ?></div>
                    <div class="label">Zakończone Wypożyczenia</div>
                </div>
                <div class="big-stat-card active">
                    <div class="icon">🔑</div>
                    <div class="value"><?php echo $stats['activeRentals']; ?></div>
                    <div class="label">Aktywne Wypożyczenia</div>
                </div>
            </div>

            <!-- Rankingi -->
            <div class="ranking-section">
                <!-- Top Samochody -->
                <div class="ranking-box">
                    <h3>🏆 Najpopularniejsze Samochody</h3>
                    <?php if (empty($topCars)): ?>
                        <p style="color: var(--text-muted);">Brak danych</p>
                    <?php else: ?>
                        <?php $pos = 1; foreach ($topCars as $car): ?>
                            <div class="ranking-item">
                                <div class="ranking-position <?php 
                                    if ($pos == 1) echo 'gold';
                                    elseif ($pos == 2) echo 'silver';
                                    elseif ($pos == 3) echo 'bronze';
                                ?>"><?php echo $pos; ?></div>
                                <div class="ranking-info">
                                    <div class="name"><?php echo htmlspecialchars($car->marka . ' ' . $car->model); ?></div>
                                    <div class="details"><?php echo $car->rentals; ?> wypożyczeń</div>
                                </div>
                                <div class="ranking-value"><?php echo number_format($car->revenue, 0); ?> PLN</div>
                            </div>
                        <?php $pos++; endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Top Klienci -->
                <div class="ranking-box">
                    <h3>👥 Najlepsi Klienci</h3>
                    <?php if (empty($topClients)): ?>
                        <p style="color: var(--text-muted);">Brak danych</p>
                    <?php else: ?>
                        <?php $pos = 1; foreach ($topClients as $client): ?>
                            <div class="ranking-item">
                                <div class="ranking-position <?php 
                                    if ($pos == 1) echo 'gold';
                                    elseif ($pos == 2) echo 'silver';
                                    elseif ($pos == 3) echo 'bronze';
                                ?>"><?php echo $pos; ?></div>
                                <div class="ranking-info">
                                    <div class="name"><?php echo htmlspecialchars($client->fullName); ?></div>
                                    <div class="details"><?php echo $client->rentals; ?> wypożyczeń • <?php echo htmlspecialchars($client->email); ?></div>
                                </div>
                                <div class="ranking-value"><?php echo number_format($client->spent, 0); ?> PLN</div>
                            </div>
                        <?php $pos++; endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php if (!empty($chartData)): ?>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Przychody (PLN)',
                    data: <?php echo json_encode($chartData); ?>,
                    borderColor: '#38ef7d',
                    backgroundColor: 'rgba(56, 239, 125, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#38ef7d',
                    pointBorderColor: '#fff',
                    pointRadius: 5,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#e0e0e0' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#a0a0a0' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        ticks: { 
                            color: '#a0a0a0',
                            callback: function(value) { return value + ' PLN'; }
                        },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>

</html>
