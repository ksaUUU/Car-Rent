<?php
require_once("classes/Car.php");

class CarManager {
    // 1. Pobieranie aut z sortowaniem i filtrowaniem
    public function getAllCars($db, $sort = 'cena_doba', $order = 'DESC') {
        $allowedColumns = ['marka', 'cena_doba', 'przebieg', 'status', 'moc', 'rok'];
        
        if (!in_array($sort, $allowedColumns)) {
            $sort = 'cena_doba';
        }

        if ($order !== 'ASC' && $order !== 'DESC') {
            $order = 'DESC';
        }

        if ($sort == 'status') {
            $sqlOrder = "wynajmujacy_id $order"; 
        } else {
            $sqlOrder = "$sort $order";
        }

        $sql = "SELECT * FROM samochody ORDER BY $sqlOrder";
        return $db->selectResult($sql);
    }

    // 2. Pobieranie jednego auta
    public function getCarById($db, $id) {
        $id = (int)$id;
        $sql = "SELECT * FROM samochody WHERE id = $id";
        $result = $db->selectResult($sql);
        return !empty($result) ? $result[0] : null;
    }

    // 3. Dodawanie auta
    public function addCar($db, Car $car) {
        $mysqli = $db->getMysqli();
        $marka = $mysqli->real_escape_string($car->getMarka());
        $model = $mysqli->real_escape_string($car->getModel());
        $rok = (int)$car->getRok();
        $przebieg = (int)$car->getPrzebieg();
        $moc = (int)$car->getMoc();
        $kolor = $mysqli->real_escape_string($car->getKolor());
        $skrzynia = $mysqli->real_escape_string($car->getSkrzynia());
        
        $cena = (float)$car->getCena();

        $sql = "INSERT INTO samochody (marka, model, rok, przebieg, moc, kolor, skrzynia, cena_doba) 
                VALUES ('$marka', '$model', $rok, $przebieg, $moc, '$kolor', '$skrzynia', $cena)";
        
        return $db->query($sql);
    }

    // 4. Aktualizacja auta
    public function updateCar($db, Car $car) {
        $mysqli = $db->getMysqli();
        $id = (int)$car->getId();
        $marka = $mysqli->real_escape_string($car->getMarka());
        $model = $mysqli->real_escape_string($car->getModel());
        $rok = (int)$car->getRok();
        $przebieg = (int)$car->getPrzebieg();
        $moc = (int)$car->getMoc();
        $kolor = $mysqli->real_escape_string($car->getKolor());
        $skrzynia = $mysqli->real_escape_string($car->getSkrzynia());
        
        $cena = (float)$car->getCena();

        $sql = "UPDATE samochody SET 
                marka='$marka', model='$model', rok=$rok, przebieg=$przebieg, 
                moc=$moc, kolor='$kolor', skrzynia='$skrzynia', cena_doba=$cena 
                WHERE id=$id";

        return $db->query($sql);
    }

    // 5. Usuwanie auta
    public function deleteCar($db, $id) {
        $id = (int)$id;
        $sql = "DELETE FROM samochody WHERE id = $id";
        return $db->delete($sql);
    }

    // 6. Wypożyczanie
    public function rentCar($db, $carId, $userId) {
        $carId = (int)$carId;
        $userId = (int)$userId;
        $now = date("Y-m-d H:i:s");
        
        $sql = "UPDATE samochody SET wynajmujacy_id = $userId, data_wynajmu = '$now' 
                WHERE id = $carId AND wynajmujacy_id IS NULL";
        
        $db->query($sql);
        return $db->getMysqli()->affected_rows > 0;
    }

    // 7. Zwrot auta (z zapisem do historii)
    public function returnCar($db, $carId) {
        $carId = (int)$carId;
        
        $this->saveRentalHistory($db, $carId);
        
        $sql = "UPDATE samochody SET wynajmujacy_id = NULL, data_wynajmu = NULL WHERE id = $carId";
        return $db->query($sql);
    }

    // 8. RAPORT DLA ADMINA
    public function getActiveRentalsReport($db) {
        $sql = "SELECT 
                    u.fullName, u.email, u.userName,
                    c.marka, c.model, c.cena_doba, c.data_wynajmu, c.id as car_id
                FROM samochody c
                JOIN users u ON c.wynajmujacy_id = u.id
                WHERE c.wynajmujacy_id IS NOT NULL
                ORDER BY c.data_wynajmu ASC";
        
        return $db->selectResult($sql);
    }

    // 9. Zapis do historii wypożyczeń
    public function saveRentalHistory($db, $carId) {
        $carId = (int)$carId;
        $mysqli = $db->getMysqli();
        
        // Pobierz dane auta i wypożyczającego
        $sql = "SELECT c.*, u.id as user_id 
                FROM samochody c 
                JOIN users u ON c.wynajmujacy_id = u.id 
                WHERE c.id = $carId AND c.wynajmujacy_id IS NOT NULL";
        
        $result = $mysqli->query($sql);
        if (!$result || $result->num_rows == 0) return false;
        
        $row = $result->fetch_object();
        
        // Oblicz dni i koszt
        $start = new DateTime($row->data_wynajmu);
        $end = new DateTime();
        $days = $start->diff($end)->days;
        if ($days == 0) $days = 1;
        $totalCost = $days * $row->cena_doba;
        
        $stmt = $mysqli->prepare("INSERT INTO historia_wypozyczen 
            (user_id, car_id, marka, model, data_rozpoczecia, data_zakonczenia, dni, cena_doba, koszt_calkowity) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $userId = $row->user_id;
        $marka = $row->marka;
        $model = $row->model;
        $dataStart = $row->data_wynajmu;
        $dataEnd = $end->format('Y-m-d H:i:s');
        $cenaDoba = $row->cena_doba;
        
        $stmt->bind_param("iissssidd", $userId, $carId, $marka, $model, $dataStart, $dataEnd, $days, $cenaDoba, $totalCost);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }

    // 10. Historia wypożyczeń klienta
    public function getClientRentalHistory($db, $userId) {
        $userId = (int)$userId;
        $sql = "SELECT * FROM historia_wypozyczen 
                WHERE user_id = $userId 
                ORDER BY data_zakonczenia DESC";
        return $db->selectResult($sql);
    }

    // 11. Suma wydatków klienta
    public function getClientTotalSpent($db, $userId) {
        $userId = (int)$userId;
        $mysqli = $db->getMysqli();
        $sql = "SELECT COALESCE(SUM(koszt_calkowity), 0) as total FROM historia_wypozyczen WHERE user_id = $userId";
        $result = $mysqli->query($sql);
        if ($result) {
            $row = $result->fetch_object();
            return $row->total;
        }
        return 0;
    }

    // 12. Statystyki dla admina
    public function getDashboardStats($db) {
        $mysqli = $db->getMysqli();
        $stats = [];
        
        // Całkowite przychody
        $sql = "SELECT COALESCE(SUM(koszt_calkowity), 0) as total FROM historia_wypozyczen";
        $result = $mysqli->query($sql);
        $stats['totalRevenue'] = $result->fetch_object()->total;
        
        // Liczba wszystkich wypożyczeń
        $sql = "SELECT COUNT(*) as count FROM historia_wypozyczen";
        $result = $mysqli->query($sql);
        $stats['totalRentals'] = $result->fetch_object()->count;
        
        // Średni czas wypożyczenia
        $sql = "SELECT COALESCE(AVG(dni), 0) as avg_days FROM historia_wypozyczen";
        $result = $mysqli->query($sql);
        $stats['avgDays'] = round($result->fetch_object()->avg_days, 1);
        
        // Aktywne wypożyczenia
        $sql = "SELECT COUNT(*) as count FROM samochody WHERE wynajmujacy_id IS NOT NULL";
        $result = $mysqli->query($sql);
        $stats['activeRentals'] = $result->fetch_object()->count;
        
        return $stats;
    }

    // 13. Przychody z ostatnich 30 dni (do wykresu)
    public function getRevenueChart($db) {
        $mysqli = $db->getMysqli();
        $sql = "SELECT DATE(data_zakonczenia) as day, SUM(koszt_calkowity) as revenue 
                FROM historia_wypozyczen 
                WHERE data_zakonczenia >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(data_zakonczenia) 
                ORDER BY day ASC";
        return $db->selectResult($sql);
    }

    // 14. Top 5 najpopularniejszych aut
    public function getTopCars($db, $limit = 5) {
        $limit = (int)$limit;
        $sql = "SELECT marka, model, COUNT(*) as rentals, SUM(koszt_calkowity) as revenue 
                FROM historia_wypozyczen 
                GROUP BY marka, model 
                ORDER BY rentals DESC 
                LIMIT $limit";
        return $db->selectResult($sql);
    }

    // 15. Top 5 najaktywniejszych klientów
    public function getTopClients($db, $limit = 5) {
        $limit = (int)$limit;
        $sql = "SELECT u.fullName, u.email, COUNT(h.id) as rentals, SUM(h.koszt_calkowity) as spent 
                FROM historia_wypozyczen h 
                JOIN users u ON h.user_id = u.id 
                GROUP BY h.user_id 
                ORDER BY spent DESC 
                LIMIT $limit";
        return $db->selectResult($sql);
    }
}
?>