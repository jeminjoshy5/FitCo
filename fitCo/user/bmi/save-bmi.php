<?php
session_start();
include("../../assets/db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id  = (int) $_SESSION['user']['user_id'];
$height   = isset($_POST['height']) ? (float) $_POST['height'] : 0;
$weight   = isset($_POST['weight']) ? (float) $_POST['weight'] : 0;
$bmi      = isset($_POST['bmi']) ? (float) $_POST['bmi'] : 0;
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

if ($height <= 0 || $weight <= 0 || $bmi <= 0 || $category === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid BMI data.']);
    exit();
}

$category_esc = mysqli_real_escape_string($con, $category);

$sql = "INSERT INTO bmi_records (user_id, height, weight, bmi, category)
        VALUES ($user_id, $height, $weight, $bmi, '$category_esc')";

if (mysqli_query($con, $sql)) {
    echo json_encode(['success' => true, 'bmi_id' => mysqli_insert_id($con)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
}
