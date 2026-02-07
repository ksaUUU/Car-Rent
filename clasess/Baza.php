<?php
class Baza
{
    private $mysqli; //uchwyt do BD
    public function __construct($serwer, $user, $pass, $baza)
    {
        $this->mysqli = new mysqli($serwer, $user, $pass, $baza);
        if ($this->mysqli->connect_errno) {
            printf(
                "Nie udało sie połączenie z serwerem: %s\n",
                $this->mysqli->connect_error
            );
            exit();
        }
        if ($this->mysqli->set_charset("utf8")) {
        }
    }

    function __destruct()
    {
        $this->mysqli->close();
    }
    public function select($sql, $pola)
    {
        $tresc = "";
        if ($result = $this->mysqli->query($sql)) {
            $ilepol = count($pola);
            $ile = $result->num_rows; 
            $tresc .= "<table><tbody>";
            while ($row = $result->fetch_object()) {
                $tresc .= "<tr>";
                for ($i = 0; $i < $ilepol; $i++) {
                    $p = $pola[$i];
                    $tresc .= "<td>" . $row->$p . "</td>";
                }
                $tresc .= "</tr>";
            }
            $tresc .= "</table></tbody>";
            $result->close();
        }
        return $tresc;
    }

    public function insert($sql)
    {
        if ($this->mysqli->query($sql)) return true;
        else return false;
    }
    public function getMysqli()
    {
        return $this->mysqli;
    }

    public function dodajdoBD($bd)
    {
        $mysqli = $bd->getMysqli();

        $username = trim($_POST['username']);
        $nazwisko = trim($_POST['nazwisko']);
        $wiek = (int)$_POST['wiek'];
        $panstwo = trim($_POST['panstwo']);
        $email = trim($_POST['email']);
        $jezyki = isset($_POST['jezyki']) ? implode(',', $_POST['jezyki']) : '';
        $platnosc = trim($_POST['platnosc']);

        $username = $mysqli->real_escape_string($username);
        $sql = "SELECT 1 FROM klienci WHERE Username='$username'";
        $result = $mysqli->query($sql);

        if ($result && $result->num_rows > 0) {
            echo "<p>Nie można dodać taki username już istnieje!</p>";
            return;
        } else {
            echo "<p>AAAAAA $username</p>";
            $nazwisko = $mysqli->real_escape_string($nazwisko);
            $panstwo = $mysqli->real_escape_string($panstwo);
            $email = $mysqli->real_escape_string($email);
            $jezyki = $mysqli->real_escape_string($jezyki);
            $platnosc = $mysqli->real_escape_string($platnosc);

            $insert = "INSERT INTO klienci (Username, Nazwisko, Wiek, Panstwo, Email, Zamowienie, Platnosc) 
                VALUES ('$username', '$nazwisko', $wiek, '$panstwo', '$email', '$jezyki', '$platnosc')";

            if ($mysqli->query($insert)) {
                echo "<p>Użytkownik został dodany.</p>";
            } else {
                echo "<p>Błąd przy dodawaniu użytkownika!</p>";
            }
        }
    }

    public function selectUser($login, $passwd, $tabela)
    {
        $id = -1;
        $sql = "SELECT * FROM $tabela WHERE userName='$login'";
        if ($result = $this->mysqli->query($sql)) {
            $ile = $result->num_rows;
            if ($ile == 1) {
                $row = $result->fetch_object();
                $hash = $row->passwd;
                if (password_verify($passwd, $hash))
                    $id = $row->id;
            }
        }
        return $id; //id zalogowanego użytkownika(>0) lub -1
    }

    public function selectUserById($id, $tabela)
    {
        $userData = null;
        $idEsc = (int)$id;
        $sql = "SELECT userName, fullName, email, status, date FROM $tabela WHERE id=$idEsc";

        if ($result = $this->mysqli->query($sql)) {
            if ($result->num_rows == 1) {
                $userData = $result->fetch_object();
            }
            $result->close();
        }
        return $userData;
    }

    public function query($sql)
    {
        return $this->mysqli->query($sql);
    }

    public function delete($sql)
    {
        return $this->mysqli->query($sql);
    }

    public function selectResult($sql)
    {
        $result = $this->mysqli->query($sql);
        if (!$result) return [];

        $rows = [];
        while ($row = $result->fetch_object()) {
            $rows[] = $row;
        }
        return $rows;
    }
} //koniec klasy Baza