<?php
include "Objects.php";

$username = "leadAdmin";
$firstname = "Luke";
$middlename = array("James", "Richards");
$lastname = "Barker";
$password = "Lumberjack12";

$passwordInfo = Tools::CreatePasswordHash($password);
echo var_dump($passwordInfo);
?>