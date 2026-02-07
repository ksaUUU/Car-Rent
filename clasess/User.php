<?php
class User
{
    public const STATUS_ADMIN = 1;
    public const STATUS_USER  = 2;

    private string $userName;
    private string $passwd;  
    private string $fullName;
    private string $email;
    private DateTime $date;
    private int $status;

    public function __construct(
        string $userName,
        string $passwd,
        string $fullName,
        string $email,
        int $status = self::STATUS_USER
    ) {
        $this->userName = $userName;
        $this->passwd = password_hash($passwd, PASSWORD_DEFAULT);
        $this->fullName = $fullName;
        $this->email = $email;
        $this->date = new DateTime();
        $this->status = $status;
    }

    public function getFullName(): string { return $this->fullName; }
    public function getEmail(): string { return $this->email; }
    public function getDate(): DateTime { return $this->date; }
    public function getStatus(): int { return $this->status; }
    public function setUserName($userName1) { $this->userName = $userName1; }
    public function getUserName(): string { return $this->userName; }

    public function saveDB($db): bool
    {
        $mysqli = $db->getMysqli();

        $userName = $mysqli->real_escape_string($this->userName);
        $fullName = $mysqli->real_escape_string($this->fullName);
        $email    = $mysqli->real_escape_string($this->email);
        $passwd   = $mysqli->real_escape_string($this->passwd);
        $status   = (int)$this->status;
        $date     = $this->date->format('Y-m-d H:i:s');

        $sql = "INSERT INTO `users` (`userName`, `fullName`, `email`, `passwd`, `status`, `date`)
                VALUES ('{$userName}', '{$fullName}', '{$email}', '{$passwd}', {$status}, '{$date}')";

        return $db->insert($sql);
    }

    public static function getAllUsersFromDB($db): void
    {
        $sql = "SELECT id, userName, fullName, email, passwd, status, date FROM `users` ORDER BY id";
        $pola = ["id", "userName", "fullName", "email", "passwd", "status", "date"];

        $html = $db->select($sql, $pola);

        if ($html === "") {
            echo "<p>Brak rekordów lub błąd zapytania.</p>";
        } else {
            echo $html;
        }
    }
}
