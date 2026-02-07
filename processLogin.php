<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once("classes/Baza.php");
require_once("classes/UserManager.php");
require_once("classes/User.php");

$db = new Baza("localhost", "root", "", "klienci");
$um = new UserManager();

// 1. Obsługa WYLOGOWANIA
if (filter_input(INPUT_GET, "akcja") == "wyloguj") {
    $um->logout($db);
    $_SESSION['success'] = "Zostałeś pomyślnie wylogowany.";
    header("Location: processLogin.php");
    exit();
}

// 2. Obsługa LOGOWANIA (POST)
if (filter_input(INPUT_POST, "zaloguj")) {
    $userId = $um->login($db);
    if ($userId >= 0) {
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Błędny login lub hasło!";
    }
}

// 3. Obsługa REJESTRACJI (POST)
if (filter_input(INPUT_POST, "zarejestruj")) {
    if ($um->registerUser($db)) {
        $_SESSION['success'] = "Konto utworzone! Możesz się teraz zalogować.";
        header("Location: processLogin.php");
        exit();
    }
}

// 4. Sprawdzenie czy użytkownik już jest zalogowany
$sessionId = session_id();
$userId = $um->getLoggedInUser($db, $sessionId);
if ($userId !== -1 && isset($userId['userId']) && $userId['userId'] > 0) {
    header("Location: dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Logowanie / Rejestracja</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <?php
    $akcja = filter_input(INPUT_GET, "akcja");

    if ($akcja == "rejestracja" || (isset($_POST['zarejestruj']) && isset($_SESSION['error']))) {
        $um->registrationForm();
    } else {
        $um->loginForm();
    }
    ?>

</body>

</html>