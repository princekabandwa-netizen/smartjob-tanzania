<?php
session_start();
require_once 'includes/init.php';

if (isset($_SESSION['user_id'])) {
    // Log logout action
    logAllUserActions($pdo, $_SESSION['user_id'], 'logout', "User logged out");
    createActionNotification($pdo, $_SESSION['user_id'], 'account', 'Logged Out', 
                            "You have been successfully logged out.", 
                            "login.php");
}

session_destroy();
header('Location: index.php');
exit();
?>