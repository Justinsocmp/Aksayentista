<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Gatekeeper validation occurs during signup now. Redirecting to login.
header("Location: /login/");
exit();
?>
