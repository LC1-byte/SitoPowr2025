<?php
// Dati per la connessione al database
$host = "localhost";
$utente = "lettore";           // per leggere i dati
$password = "P@ssw0rd!";       // password di lettore come da script SQL
$database = "eco_scambio";

// Crea la connessione
$connessione = mysqli_connect($host, $utente, $password, $database);

// Controlla se la connessione è andata a buon fine
if (!$connessione) {
    die("Errore nella connessione al database: " . mysqli_connect_error());
}
?>
