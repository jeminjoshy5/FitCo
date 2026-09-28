<?php
session_start();
include("../../assets/db.php");
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id = (int) $_SESSION['user']['user_id'];
$raw     = file_get_contents('php://input');
$data    = json_decode($raw, true);

$allowed_splits = ['ppl', 'bro', 'custom'];
$split_type = isset($data['split_type']) ? $data['split_type'] : '';
$exercises  = isset($data['exercises']) && is_array($data['exercises']) ? $data['exercises'] : [];

if (!in_array($split_type, $allowed_splits, true)) {
    echo json_encode(['success' => false, 'message' => 'Please choose a valid split type.']);
    exit();
}

if (empty($exercises)) {
    echo json_encode(['success' => false, 'message' => 'Add at least one exercise before saving.']);
    exit();
}

// Replace any previous split for this user with the new one.
mysqli_query($con, "DELETE FROM user_splits WHERE user_id=$user_id");

$split_type_esc = mysqli_real_escape_string($con, $split_type);
$insertSplit = "INSERT INTO user_splits (user_id, split_type) VALUES ($user_id, '$split_type_esc')";

if (!mysqli_query($con, $insertSplit)) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
    exit();
}

$split_id = mysqli_insert_id($con);
$rows = [];

foreach ($exercises as $body_part => $exerciseNames) {
    $body_part_esc = mysqli_real_escape_string($con, (string) $body_part);
    if (!is_array($exerciseNames)) continue;

    foreach ($exerciseNames as $exerciseName) {
        $name_esc = mysqli_real_escape_string($con, (string) $exerciseName);
        if ($name_esc === '') continue;
        $rows[] = "($split_id, '$body_part_esc', '$name_esc')";
    }
}

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'No exercises to save.']);
    exit();
}

$insertExercises = "INSERT INTO split_exercises (split_id, body_part, exercise_name) VALUES " . implode(', ', $rows);

if (mysqli_query($con, $insertExercises)) {
    echo json_encode(['success' => true, 'split_id' => $split_id, 'count' => count($rows)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($con)]);
}
