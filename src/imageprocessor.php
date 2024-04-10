<?php
header("Content-Type: application/json");

// Directory where uploaded images are saved
$uploadDir = "uploads/"; // Ensure this directory exists and is writable
$responseArray = ["success" => false, "url" => "", "error" => ""];
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $responseArray["error"] = "This script only handles POST requests";
    echo json_encode($responseArray);
    exit();
}

if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
    $responseArray["error"] = "File upload error or no file uploaded";
    echo json_encode($responseArray);
    exit();
}

// File type validation
$allowedTypes = ["image/jpeg", "image/png", "image/gif"];
$fileType = $_FILES["image"]["type"];
if (!in_array($fileType, $allowedTypes)) {
    $responseArray["error"] = "Invalid file type";
    echo json_encode($responseArray);
    exit();
}

// Rename the uploaded file
$newFileName =
    $uploadDir .
    uniqid("", true) .
    "." .
    pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);

// Move the file to the upload directory
if (!move_uploaded_file($_FILES["image"]["tmp_name"], $newFileName)) {
    $responseArray["error"] = "Failed to save file";
    echo json_encode($responseArray);
    exit();
}

$responseArray["success"] = true;
$responseArray["url"] = $newFileName;
echo json_encode($responseArray);
