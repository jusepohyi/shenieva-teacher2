<?php

// delete_student.php

// Allow requests from any origin (for development purposes). 
// In production, restrict this to your Svelte app's origin.
header("Access-Control-Allow-Origin: *"); 

// Allow only the DELETE method.
header("Access-Control-Allow-Methods: DELETE");

// Allow specific headers.
header("Access-Control-Allow-Headers: Content-Type");

// Set content type to JSON.
header('Content-Type: application/json');

include 'conn.php';

// Get the raw input from the request body.
$data = json_decode(file_get_contents("php://input"), true);

// Check if idNo is provided.
if (!isset($data['idNo'])) {
    echo json_encode(["success" => false, "message" => "Missing student ID"]);
    exit;
}

$idNo = $data['idNo'];

// Prepare the delete statement.
$sql = "DELETE FROM students_table WHERE idNo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $idNo); // Assuming idNo is a string. Use "i" if it's an integer.

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Student deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error deleting student"]);
}

$stmt->close();
$conn->close();

?>
