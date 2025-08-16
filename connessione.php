<?php
session_start(); // Avvia la sessione

// Funzione per ottenere la connessione
function getConnessione() {
    // Recupera le credenziali dalla sessione
    if (!isset($_SESSION['db_user']) || !isset($_SESSION['db_pwd'])) {
        die("Errore: Credenziali non trovate nella sessione. Effettua il login.");
    }


    $host = "localhost";
    $utente = $_SESSION['db_user']; // Username dalla sessione
    $password = $_SESSION['db_pwd']; // Password dalla sessione
    $database = "eco_scambio";

    // Crea la connessione
    $connessione = mysqli_connect($host, $utente, $password, $database);

    // Controlla se la connessione è andata a buon fine
    if (!$connessione) {
        die("Errore nella connessione al database: " . mysqli_connect_error());
    }

    return $connessione;
}

?>
