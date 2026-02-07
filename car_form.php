<?php
session_start();
require_once("classes/Baza.php");
require_once("classes/UserManager.php");
require_once("classes/CarManager.php");

$db = new Baza("localhost", "root", "", "klienci");
$um = new UserManager();
$cm = new CarManager();

if ($um->getLoggedInUser($db, session_id()) === -1) {
    header("location:processLogin.php");
    exit();
}

$carData = null;
$isEdit = false;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $carData = $cm->getCarById($db, $id);
    if ($carData) $isEdit = true;
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? "Edytuj" : "Dodaj"; ?> Samochód</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container small-container">
        <h2><?php echo $isEdit ? "Edycja" : "Dodawanie"; ?> Auta</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="msg-error"><?php echo $_SESSION['error'];
                                    unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="car_action.php" method="post">
            <input type="hidden" name="id" value="<?php echo $isEdit ? $carData->id : ''; ?>">

            <div class="form-group">
                <label>Marka:</label>
                <input type="text" name="marka" required value="<?php echo $isEdit ? $carData->marka : ''; ?>">
            </div>

            <div class="form-group">
                <label>Model:</label>
                <input type="text" name="model" required value="<?php echo $isEdit ? $carData->model : ''; ?>">
            </div>

            <div class="form-group">
                <label>Moc (KM):</label>
                <input type="number" name="moc" required value="<?php echo $isEdit ? $carData->moc : ''; ?>">
            </div>

            <div class="form-group">
                <label>Kolor:</label>
                <input type="text" name="kolor" required value="<?php echo $isEdit ? $carData->kolor : ''; ?>">
            </div>

            <div class="form-group">
                <label>Skrzynia Biegów:</label>
                <select name="skrzynia" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    <option value="Manual" <?php if ($isEdit && $carData->skrzynia == 'Manual') echo 'selected'; ?>>Manualna</option>
                    <option value="Automat" <?php if ($isEdit && $carData->skrzynia == 'Automat') echo 'selected'; ?>>Automatyczna</option>
                </select>
            </div>
            <div class="form-group">
                <label>Rok produkcji:</label>
                <input type="number" name="rok" min="1900" max="2099" required value="<?php echo $isEdit ? $carData->rok : ''; ?>">
            </div>

            <div class="form-group">
                <label>Przebieg (km):</label>
                <input type="number" name="przebieg" min="0" required value="<?php echo $isEdit ? $carData->przebieg : ''; ?>">
            </div>

            <div class="form-group">
                <label>Cena za dobę (PLN):</label>
                <input type="number" step="0.01" name="cena" min="0" required value="<?php echo $isEdit ? $carData->cena_doba : ''; ?>">
            </div>

            <button type="submit" name="save_car" class="btn btn-success">Zapisz</button>
            <a href="dashboard.php" class="btn btn-danger">Anuluj</a>
        </form>
    </div>
</body>

</html>