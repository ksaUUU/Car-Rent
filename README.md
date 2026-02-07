# Car-Rent 🚗💨

Prosty system wypożyczalni samochodów napisany w **PHP** z wykorzystaniem **MySQL/MariaDB** (np. przez XAMPP) oraz **CSS**. Projekt zawiera część użytkownika (panel / moje wynajmy) oraz prosty panel administracyjny.

> Repo: https://github.com/ksaUUU/Car-Rent  [oai_citation:1‡GitHub](https://github.com/ksaUUU/Car-Rent)

---

## Funkcje

### Użytkownik
- logowanie
- podgląd dostępnych aut (dashboard)
- podgląd własnych wynajmów (`my_rentals.php`)

### Administrator
- podgląd klientów (`admin_clients.php`)
- statystyki / podsumowanie (`admin_stats.php`)
- dodawanie / edycja auta (`car_form.php`)
- akcje na autach / wynajmach (`car_action.php`)

*(Dokładny zakres zależy od implementacji w plikach PHP.)*  [oai_citation:2‡GitHub](https://github.com/ksaUUU/Car-Rent)

---

## Technologie

- PHP
- MySQL / MariaDB
- CSS  [oai_citation:3‡GitHub](https://github.com/ksaUUU/Car-Rent)

---

## Struktura projektu (skrót)

- `Database/` – pliki związane z bazą danych (np. dump `.sql`)  [oai_citation:4‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `clasess/` – klasy / logika aplikacji  [oai_citation:5‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `css/` – style  [oai_citation:6‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `processLogin.php` – obsługa logowania  [oai_citation:7‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `dashboard.php` – główny widok/panel  [oai_citation:8‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `my_rentals.php` – wynajmy użytkownika  [oai_citation:9‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `admin_clients.php`, `admin_stats.php` – panel admina  [oai_citation:10‡GitHub](https://github.com/ksaUUU/Car-Rent)
- `car_form.php`, `car_action.php` – zarządzanie autami  [oai_citation:11‡GitHub](https://github.com/ksaUUU/Car-Rent)

---

## Uruchomienie lokalnie (XAMPP)

### Wymagania
- XAMPP (Apache + MySQL/MariaDB)
- PHP (z XAMPP)
- phpMyAdmin (z XAMPP)

### Kroki
1. Sklonuj repo do katalogu serwera XAMPP:
   - macOS (XAMPP): zwykle `htdocs`
   - Windows: `C:\xampp\htdocs`

   ```bash
   git clone https://github.com/ksaUUU/Car-Rent.git
