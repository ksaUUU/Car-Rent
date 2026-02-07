<?php
class Car {
    private $id;
    private $marka;
    private $model;
    private $rok;
    private $przebieg;
    private $moc;
    private $kolor;
    private $skrzynia;
    private $cenaDoba;
    
    public $wynajmujacy_id;
    public $data_wynajmu;

    public function __construct($marka, $model, $rok, $przebieg, $moc, $kolor, $skrzynia, $cenaDoba, $id = null) {
        $this->id = $id;
        $this->marka = $marka;
        $this->model = $model;
        $this->rok = $rok;
        $this->przebieg = $przebieg;
        $this->moc = $moc;
        $this->kolor = $kolor;
        $this->skrzynia = $skrzynia;
        $this->cenaDoba = $cenaDoba;
    }

    public function getId() { return $this->id; }
    public function getMarka() { return $this->marka; }
    public function getModel() { return $this->model; }
    public function getRok() { return $this->rok; }
    public function getPrzebieg() { return $this->przebieg; }
    public function getMoc() { return $this->moc; }
    public function getKolor() { return $this->kolor; }
    public function getSkrzynia() { return $this->skrzynia; }
    public function getCena() { return $this->cenaDoba; }

    public function validate() {
        $errors = [];
        if (empty($this->marka)) $errors[] = "Marka jest wymagana.";
        if (empty($this->model)) $errors[] = "Model jest wymagany.";
        if (!preg_match("/^(19|20)\d{2}$/", $this->rok)) $errors[] = "Niepoprawny rok.";
        if ($this->przebieg < 0) $errors[] = "Przebieg nie może być ujemny.";
        if ($this->moc <= 0) $errors[] = "Moc musi być dodatnia.";
        if (empty($this->kolor)) $errors[] = "Kolor jest wymagany.";
        if ($this->cenaDoba <= 0) $errors[] = "Cena musi być dodatnia.";
        return $errors;
    }
}
?>