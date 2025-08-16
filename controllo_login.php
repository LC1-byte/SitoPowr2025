<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function accesso_riservato($tipo_necessario = null) {
    // Controllo se utente loggato
    if (!isset($_SESSION['nick'])) {
        echo '<p>Attenzione! Questa pagina è riservata agli utenti registrati. <a href="login.php">Accedi qui</a> prima di procedere.</p>';
        exit();
    }
    // Se specificato tipo, controllo se corrisponde
    if ($tipo_necessario !== null) {
        if (!isset($_SESSION['artigiano'])) {
            echo '<p>Errore nel controllo del tipo utente.</p>';
            exit();
        }
        if ($tipo_necessario === 'artigiano' && $_SESSION['artigiano'] != 1) {
            echo '<p>Attenzione! Questa pagina è riservata agli artigiani registrati. <a href="login.php">Accedi qui</a>.</p>';
            exit();
        }
        if ($tipo_necessario === 'azienda' && $_SESSION['artigiano'] != 0) {
            echo '<p>Attenzione! Questa pagina è riservata alle aziende registrate. <a href="login.php">Accedi qui</a>.</p>';
            exit();
        }
    }
}

// Richiama la funzione in ogni pagina passando "artigiano" o "azienda" se vuoi
?>
