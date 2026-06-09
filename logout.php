<?php
session_start();
session_destroy();

// Limpiar cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

header('Location: signin.php');
exit;
?>