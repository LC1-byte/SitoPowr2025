<?php
include "menu.php";

require_once "connessione.php"; // 👈 connessione centralizzata

$conn = getConnessione(); // Ottieni la connessione al database

// Funzione per validare con espressione regolare
function valida_input($valore, $pattern) {
    return preg_match($pattern, $valore);
}

$errore = "";
$successo = "";

// Se inviata registrazione azienda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registra_azienda'])) {
    $ragione = trim($_POST['ragione']);
    $indirizzo = trim($_POST['address2']);
    $nick = trim($_POST['nick']);
    $password = $_POST['password'];

    // Validazioni
    if (!valida_input($ragione, "/^[A-Z][A-Za-z0-9 &]{0,29}$/")) {
        $errore = "Ragione sociale non valida.";
    } elseif (!valida_input($indirizzo, "/^(Via|Corso) [A-Za-z ]+ [0-9]{1,3}$/")) {
        $errore = "Indirizzo non valido.";
    } elseif (!valida_input($nick, "/^[A-Za-z][A-Za-z0-9_-]{3,9}$/")) {
        $errore = "Username non valido.";
    } elseif (!valida_input($password, "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[.;+=]).{8,16}$/")) {
        $errore = "Password non valida.";
    } else {
        // Inserimento nel DB
        $nick_db = mysqli_real_escape_string($conn, $nick);
        $check = mysqli_query($conn, "SELECT * FROM UTENTI WHERE NICK = '$nick_db'");
        if (mysqli_num_rows($check) > 0) {
            $errore = "Username già in uso.";
        } else {
            $ragione_db = mysqli_real_escape_string($conn, $ragione);
            $indirizzo_db = mysqli_real_escape_string($conn, $indirizzo);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $ok1 = mysqli_query($conn, "INSERT INTO UTENTI (NICK, PASSWORD, TIPO) VALUES ('$nick_db', '$password_hash', 'azienda')");
            $id_utente = mysqli_insert_id($conn);
            $ok2 = mysqli_query($conn, "INSERT INTO DATI_AZIENDE (ID_UTENTE, RAGIONE, INDIRIZZO) VALUES ($id_utente, '$ragione_db', '$indirizzo_db')");

            if ($ok1 && $ok2) {
                $successo = "Registrazione azienda completata con successo.";
            } else {
                $errore = "Errore durante la registrazione.";
            }
        }
    }
}

// Se inviata registrazione artigiano
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registra_artigiano'])) {
    $nome = trim($_POST['name']);
    $cognome = trim($_POST['surname']);
    $nascita = trim($_POST['birthdate']);
    $credito = trim($_POST['credit']);
    $indirizzo = trim($_POST['address']);
    $nick = trim($_POST['nick']);
    $password = $_POST['password'];

    // Validazioni
    if (!valida_input($nome, "/^[A-Za-z ]{4,14}$/")) {
        $errore = "Nome non valido.";
    } elseif (!valida_input($cognome, "/^[A-Za-z' ]{4,16}$/")) {
        $errore = "Cognome non valido.";
    } elseif (!valida_input($nascita, "/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/")) {
        $errore = "Data di nascita non valida.";
    } elseif (!valida_input($credito, "/^[0-9]+(\.[05]{1,2})?$/")) {
        $errore = "Credito non valido.";
    } elseif (!valida_input($indirizzo, "/^(Via|Corso) [A-Za-z ]+ [0-9]{1,3}$/")) {
        $errore = "Indirizzo non valido.";
    } elseif (!valida_input($nick, "/^[A-Za-z][A-Za-z0-9_-]{3,9}$/")) {
        $errore = "Username non valido.";
    } elseif (!valida_input($password, "/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[.;+=]).{8,16}$/")) {
        $errore = "Password non valida.";
    } else {
        // Inserimento nel DB
        $nick_db = mysqli_real_escape_string($conn, $nick);
        $check = mysqli_query($conn, "SELECT * FROM UTENTI WHERE NICK = '$nick_db'");
        if (mysqli_num_rows($check) > 0) {
            $errore = "Username già in uso.";
        } else {
            $nome_db = mysqli_real_escape_string($conn, $nome);
            $cognome_db = mysqli_real_escape_string($conn, $cognome);
            $indirizzo_db = mysqli_real_escape_string($conn, $indirizzo);
            $nascita_db = mysqli_real_escape_string($conn, $nascita);
            $credito_val = floatval($credito);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $ok1 = mysqli_query($conn, "INSERT INTO UTENTI (NICK, PASSWORD, TIPO) VALUES ('$nick_db', '$password_hash', 'artigiano')");
            $id_utente = mysqli_insert_id($conn);
            $ok2 = mysqli_query($conn, "INSERT INTO DATI_ARTIGIANI (ID_UTENTE, NOME, COGNOME, NASCITA, CREDIT, INDIRIZZO) VALUES ($id_utente, '$nome_db', '$cognome_db', '$nascita_db', $credito_val, '$indirizzo_db')");

            if ($ok1 && $ok2) {
                $successo = "Registrazione artigiano completata con successo.";
            } else {
                $errore = "Errore durante la registrazione.";
            }
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>

<h1>Pagina di registrazione</h1>

<?php if ($errore !== ""): ?>
    <p style="color:red;"><?php echo htmlspecialchars($errore); ?></p>
<?php elseif ($successo !== ""): ?>
    <p style="color:green;"><?php echo htmlspecialchars($successo); ?></p>
<?php endif; ?>

<h2>Registrazione Azienda</h2>
<form method="post" action="registrazione.php">
    <label>Ragione Sociale: <input type="text" name="ragione" required></label><br>
    <label>Indirizzo (Via/Corso...): <input type="text" name="address2" required></label><br>
    <label>Username: <input type="text" name="nick" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <input type="submit" name="registra_azienda" value="Registrati come Azienda">
</form>

<h2>Registrazione Artigiano/Designer</h2>
<form method="post" action="registrazione.php">
    <label>Nome: <input type="text" name="name" required></label><br>
    <label>Cognome: <input type="text" name="surname" required></label><br>
    <label>Data di nascita (aaaa-mm-gg): <input type="date" name="birthdate" required></label><br>
    <label>Credito: <input type="text" name="credit" required></label><br>
    <label>Indirizzo (Via/Corso...): <input type="text" name="address" required></label><br>
    <label>Username: <input type="text" name="nick" required></label><br>
    <label>Password: <input type="password" name="password" required></label><br>
    <input type="submit" name="registra_artigiano" value="Registrati come Artigiano">
</form>

<footer>
    © 2025 Eco Scambio - Tutti i diritti riservati
</footer>

</body>
</html>
