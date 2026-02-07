<?php
require_once("classes/User.php");

class UserManager
{
    // --- 1. FORMULARZ LOGOWANIA ---
    public function loginForm()
    { ?>
        <div class="login-wrapper">
            <div class="login-card">
                <h3>Zaloguj się</h3>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="msg-error"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="msg-success"><?php echo $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <form action="processLogin.php" method="post">
                    <input type="text" name="login" placeholder="Login" required>
                    <input type="password" name="passwd" placeholder="Hasło" required>
                    <input type="submit" value="Zaloguj" name="zaloguj">
                </form>

                <a href="processLogin.php?akcja=rejestracja" class="toggle-link">
                    Nie masz konta? <b>Zarejestruj się</b>
                </a>
            </div>
        </div>
    <?php
    }

    // --- 2. FORMULARZ REJESTRACJI ---
    public function registrationForm()
    { ?>
        <div class="login-wrapper">
            <div class="login-card">
                <h3>Utwórz konto</h3>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="msg-error"><?php echo $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="processLogin.php" method="post">
                    <input type="text" name="fullName" placeholder="Imię i Nazwisko" required>
                    <input type="text" name="userName" placeholder="Login" required>
                    <input type="email" name="email" placeholder="Adres Email" required>
                    <input type="password" name="passwd" placeholder="Hasło" required>

                    <input type="submit" value="Zarejestruj się" name="zarejestruj">
                </form>

                <a href="processLogin.php" class="toggle-link">
                    Masz już konto? <b>Zaloguj się</b>
                </a>
            </div>
        </div>
<?php
    }

    // --- 3. LOGOWANIE ---
    public function login($db)
    {
        $args = [
            'login' => FILTER_SANITIZE_ADD_SLASHES,
            'passwd' => FILTER_SANITIZE_ADD_SLASHES
        ];
        $dane = filter_input_array(INPUT_POST, $args);

        $login = $dane["login"] ?? '';
        $passwd = $dane["passwd"] ?? '';

        if (empty($login) || empty($passwd)) return -1;

        $userId = $db->selectUser($login, $passwd, "users");

        if ($userId >= 0) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();

            $mysqli = $db->getMysqli();
            $mysqli->query("DELETE FROM logged_in_users WHERE userId=$userId");

            $_SESSION['userId'] = $userId;
            $sessionId = session_id();
            $lastUpdate = date("Y-m-d H:i:s");

            $stmt = $mysqli->prepare("INSERT INTO logged_in_users (userId, sessionId, lastUpdate) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $sessionId, $lastUpdate);
            $stmt->execute();
            $stmt->close();

            return $userId;
        }
        return -1;
    }

    // --- 4. REJESTRACJA ---
    public function registerUser($db)
    {

        $args = [
            'fullName' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS],
            'userName' => ['filter' => FILTER_SANITIZE_FULL_SPECIAL_CHARS],
            'email' => ['filter' => FILTER_VALIDATE_EMAIL],
            'passwd' => ['filter' => FILTER_UNSAFE_RAW]
        ];

        $dane = filter_input_array(INPUT_POST, $args);

        if (!$dane || in_array(null, $dane, true) || in_array(false, $dane, true)) {
            $_SESSION['error'] = "Niepoprawne dane formularza!";
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $dane['userName'])) {
            $_SESSION['error'] = "Login musi mieć 4-20 znaków";
            return false;
        }
        if (strlen($dane['passwd']) < 8) {
            $_SESSION['error'] = "Hasło musi mieć co najmniej 8 znaków!";
            return false;
        }

        if (strlen($dane['fullName']) < 6) {
            $_SESSION['error'] = "Imię i nazwisko jest za krótkie!";
            return false;
        }

        $mysqli = $db->getMysqli();
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE userName = ?");
        $stmt->bind_param("s", $dane['userName']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION['error'] = "Taki login jest już zajęty!";
            return false;
        }

        $newUser = new User(
            $dane['userName'],
            $dane['passwd'],
            $dane['fullName'],
            $dane['email']
        );

        if ($newUser->saveDB($db)) {
            return true;
        }

        $_SESSION['error'] = "Błąd bazy danych przy rejestracji.";
        return false;
    }


    public function logout($db)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $mysqli = $db->getMysqli();
        $sessionId = session_id();

        $sessionIdEsc = $mysqli->real_escape_string($sessionId);
        $mysqli->query("DELETE FROM logged_in_users WHERE sessionId='$sessionIdEsc'");

        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
    }

    public function getLoggedInUser($db, $sessionId)
    {
        $userId = -1;
        $lastUpdate = null;
        $mysqli = $db->getMysqli();
        $sessionIdEsc = $mysqli->real_escape_string($sessionId);

        $sql = "SELECT userId, lastUpdate FROM logged_in_users WHERE sessionId='$sessionIdEsc'";
        if ($result = $mysqli->query($sql)) {
            if ($result->num_rows == 1) {
                $row = $result->fetch_object();
                $userId = $row->userId;
                $lastUpdate = $row->lastUpdate;
            }
            $result->close();
        }

        if ($userId > 0) {
            return ['userId' => $userId, 'lastUpdate' => $lastUpdate];
        } else {
            return -1;
        }
    }
}
?>