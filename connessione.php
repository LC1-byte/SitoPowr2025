<?php
// connessione.php
// Dati per la connessione al database MySQL
$host     = "localhost";
$utente   = "root";        // puoi usare anche "lettore" se lo preferisci
$password = "";            // <-- metti la password reale di MySQL (se root senza password, lascia vuoto)
$database = "eco_scambio";

// Crea la connessione
$conn = mysqli_connect($host, $utente, $password, $database);

// Controlla se la connessione è andata a buon fine
if (!$conn) {
    die("Errore nella connessione al database: " . mysqli_connect_error());
}

// Imposta charset (per accenti, simboli, ecc.)
mysqli_set_charset($conn, "utf8mb4");
?>
