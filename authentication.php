<?php
session_start();
    if (!isset($_SESSION['authenticated'])){
        $_SESSION['status'] = "Please Login First.";
        header("page_title");
        exit(0);
        
    }
?>