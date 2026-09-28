<?php
$host="localhost";
$username="root";
$password="";
$database="mp_db";
$con=new mysqli($host,$username,$password,$database); //connection creation
if($con->connect_error)
    {
        die("Connection failed: " . $con->connect_error);
    }
?>
