<?php
include('controllo_login.php');

session_unset();
session_destroy();

setcookie("ricorda_username", "", time() - 3600);
setcookie("ricorda_password", "", time() - 3600);

header("Location: home.php");
exit;
?>
