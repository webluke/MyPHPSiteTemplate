<?php
session_start();
session_destroy();

header("Location: index.php");
die("Redirecting to Home Page");