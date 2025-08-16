<?php
session_start(); // Avvia la sessione per poterla distruggere

// Rimuovi tutte le variabili di sessione
$_SESSION = [];

// Distruggi la sessione
session_unset();
session_destroy();

// Rimuovi i cookie relativi alle credenziali
setcookie("eco_user", "", time() - 3600, "/");
setcookie("eco_pwd", "", time() - 3600, "/");

// Redirect alla pagina home
header("Location: home.php");
exit;
?>
