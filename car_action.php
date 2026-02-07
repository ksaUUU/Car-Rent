<?php
session_start();
require_once("classes/Baza.php");
require_once("classes/CarManager.php");
require_once("classes/UserManager.php");

$db = new Baza("localhost", "root", "", "klienci");
$cm = new CarManager();
$um = new UserManager();

// Weryfikacja logowania
$loginData = $um->getLoggedInUser($db, session_id());
if ($loginData === -1) {
    header("location:processLogin.php");
    exit();
}

// 1. AKCJA: Usuwanie
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $cm->deleteCar($db, $id);
    $_SESSION['msg'] = "Samochód został usunięty.";
    header("location:dashboard.php");
    exit();
}

// 2. AKCJA: Wypożyczenie
if (isset($_GET['rent'])) {
    $carId = (int)$_GET['rent'];
    $userId = $loginData['userId'];

    if ($cm->rentCar($db, $carId, $userId)) {
        $_SESSION['msg'] = "Pomyślnie wypożyczono samochód!";
    } else {
        $_SESSION['error'] = "To auto jest już zajęte!";
    }
    header("location:dashboard.php");
    exit();
}

// 3. AKCJA: Zwrot 
if (isset($_GET['return'])) {
    $carId = (int)$_GET['return'];
    $cm->returnCar($db, $carId);
    $_SESSION['msg'] = "Samochód został zwrócony i jest dostępny.";
    header("location:dashboard.php");
    exit();
}

// 4. AKCJA: Dodanie nowego auta do systemu
if (isset($_POST['save_car'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $marka = filter_input(INPUT_POST, 'marka', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $model = filter_input(INPUT_POST, 'model', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $rok = filter_input(INPUT_POST, 'rok', FILTER_VALIDATE_INT);
    $przebieg = filter_input(INPUT_POST, 'przebieg', FILTER_VALIDATE_INT);
    $moc = filter_input(INPUT_POST, 'moc', FILTER_VALIDATE_INT);
    $kolor = filter_input(INPUT_POST, 'kolor', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $skrzynia = filter_input(INPUT_POST, 'skrzynia', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $cena = filter_input(INPUT_POST, 'cena', FILTER_VALIDATE_FLOAT);

    $car = new Car($marka, $model, $rok, $przebieg, $moc, $kolor, $skrzynia, $cena, $id);

    $errors = $car->validate();

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        $redirect = $id ? "car_form.php?edit=$id" : "car_form.php";
        header("location:$redirect");
        exit();
    }

    if ($id) {
        $cm->updateCar($db, $car);
        $_SESSION['msg'] = "Dane samochodu zaktualizowane.";
    } else {
        $cm->addCar($db, $car);
        $_SESSION['msg'] = "Nowy samochód dodany.";
    }
    header("location:dashboard.php");
}
