<?php
require_once 'connessione.php'; // Include il file di connessione

$error_message = "";
$user = "";
$pwd = "";
$ricordami = false;

// Precompila dal cookie "ricordami"
if (isset($_COOKIE['eco_user']) && isset($_COOKIE['eco_pwd'])) {
    $user = $_COOKIE['eco_user'];
    $pwd = $_COOKIE['eco_pwd'];
    $ricordami = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = isset($_POST['user']) ? trim($_POST['user']) : "";
    $pwd  = isset($_POST['pwd'])  ? trim($_POST['pwd'])  : "";
    $ricordami = isset($_POST['ricordami']);

    // Prova a connetterti con le credenziali fornite
    $conn = mysqli_connect('localhost', $user, $pwd, 'eco_scambio');
    if (!$conn) {
        $error_message = "Errore di connessione al database. Credenziali errate.";
    } else {
        // Uso query preparata per sicurezza
        $stmt = mysqli_prepare($conn, "SELECT ID, PASSWORD, ARTIGIANO FROM UTENTI WHERE NICK = ?");
        mysqli_stmt_bind_param($stmt, "s", $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_utente, $password_db, $artigiano);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        if ($password_db === null) {
            // Utente non trovato
            $error_message = "Utente o password errati, riprova.";
        } else {
            // Verifico password (qui semplice confronto perché password in chiaro nel DB)
            if ($pwd === $password_db) {
                // Login OK: imposto sessione
                session_regenerate_id(true); // per sicurezza
                $_SESSION['loggedin'] = true;
                $_SESSION['nick'] = $user;
                $_SESSION['id'] = $id_utente;
                $_SESSION['artigiano'] = $artigiano;

                // Memorizza le credenziali nella sessione
                $_SESSION['db_user'] = $user;
                $_SESSION['db_pwd'] = $pwd;

                // Gestione cookie "ricordami"
                if ($ricordami) {
                    setcookie("eco_user", $user, time() + 72 * 3600);
                    setcookie("eco_pwd", $pwd, time() + 72 * 3600);
                } else {
                    setcookie("eco_user", "", time() - 3600);
                    setcookie("eco_pwd", "", time() - 3600);
                }

                // Redirect in base al tipo utente
                if ($artigiano) {
                    header("Location: domanda.php");
                    exit();
                } else {
                    header("Location: offerta.php");
                    exit();
                }
            } else {
                // Password errata
                $error_message = "Utente o password errati, riprova.";
            }
        }
    }
}
?>







<!-- HTML CODE -->

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Login - Eco Scambio</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body class="login">
    <nav class="menu">
        <?php include("menu.php"); ?>
    </nav>

    <main class="login-main">
        <h1 class="login-titolo">Login</h1>

        <?php if ($error_message !== "") { ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php } ?>

        <div class="login-contenuto">
            <form method="post" action="login.php">
                <p>
                    <label for="user">Username:</label>
                    <input type="text" id="user" name="user" value="<?php echo htmlspecialchars($user); ?>" required />
                </p>
                <p>
                    <label for="pwd">Password:</label>
                    <input type="password" id="pwd" name="pwd" value="<?php echo htmlspecialchars($pwd); ?>" required />
                </p>
                <p class="ricordami">
                    <input type="checkbox" id="ricordami" name="ricordami" <?php if ($ricordami) echo "checked"; ?> />
                    <label for="ricordami">Ricordami</label>
                </p>
                <p>
                    <input type="reset" value="Cancella" />
                    <input type="submit" value="Invia" />
                </p>
            </form>

            
        </div>
        <div class="login-messaggio">
  <p>Ogni accesso rappresenta una scelta consapevole contro lo spreco.<br>
  Grazie per contribuire attivamente a un futuro più sostenibile.</p>
</div>
    </main>

    <footer>
        © 2025 Eco Scambio - Tutti i diritti riservati
    </footer>
</body>
</html>
