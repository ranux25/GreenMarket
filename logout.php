<?php
session_start();
if (isset($_SESSION) && !empty($_SESSION)) {
    session_unset();
    session_destroy();
}
header("Location: signin.php");
exit;
?>