<?php
session_start();
require_once __DIR__ . '/connessione.php';  // usa la connessione centralizzata

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

    // Prepara query utente
    $stmt = mysqli_prepare($conn, "SELECT id, password, artigiano FROM utenti WHERE nick = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id_utente, $password_db, $artigiano);
        $found = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $found = false;
    }

    if (!$found) {
        $error_message = "Utente o password errati, riprova.";
    } else {
        // Confronto diretto (senza hash, come richiesto per ora)
        if ($pwd === $password_db) {
            session_regenerate_id(true);
            $_SESSION['loggedin']  = true;
            $_SESSION['nick']      = $user;
            $_SESSION['id']        = $id_utente;
            $_SESSION['artigiano'] = (int)$artigiano === 1;

            // Saldo: se artigiano leggo la tabella DATI_ARTIGIANI
            if ($_SESSION['artigiano']) {
                $saldo = 0;
                $q2 = "SELECT credit FROM dati_artigiani WHERE id_utente = " . intval($id_utente) . " LIMIT 1";
                if ($res2 = mysqli_query($conn, $q2)) {
                    if ($row2 = mysqli_fetch_assoc($res2)) {
                        $saldo = (float)$row2['credit'];
                    }
                    mysqli_free_result($res2);
                }
                $_SESSION['saldo'] = $saldo;
            } else {
                $_SESSION['saldo'] = 0;
            }

            // Cookie ricordami
            if ($ricordami) {
                setcookie("eco_user", $user, time() + 72 * 3600, "/");
                setcookie("eco_pwd",  $pwd,  time() + 72 * 3600, "/");
            } else {
                setcookie("eco_user", "", time() - 3600, "/");
                setcookie("eco_pwd",  "", time() - 3600, "/");
            }

            // Redirect
            if ($_SESSION['artigiano']) {
                header("Location: domanda.php");
                exit();
            } else {
                header("Location: offerta.php");
                exit();
            }
        } else {
            $error_message = "Utente o password errati, riprova.";
        }
    }
}
?>
