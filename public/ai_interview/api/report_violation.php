<?php

header("Content-Type: application/json");

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config.php';
$conn = db_connect();

/*
|--------------------------------------------------------------------------
| CHECK CONNECTION
|--------------------------------------------------------------------------
*/

if ($conn->connect_error) {

    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| GET JSON DATA
|--------------------------------------------------------------------------
*/

$data = json_decode(
    file_get_contents("php://input"),
    true
);

/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    !$data ||
    !isset($data['message'])
) {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);

    exit;
}

if (
    !$data ||
    !isset($data['jobrole'])
) {

    echo json_encode([
        "status" => "error",
        "message" => "Invalid request"
    ]);

    exit;
}
/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$message = trim(
    $data['message']
);

$candidate_id = isset($data['candidate_id'])
    ? (int)$data['candidate_id']
    : 0;

$candidate_name = isset($data['candidate_name'])
    ? trim($data['candidate_name'])
    : "Unknown";
$jobrole = isset($data['jobrole'])
    ? trim($data['jobrole'])
    : "Unknown";
/*
|--------------------------------------------------------------------------
| INSERT INTO DATABASE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO violations
    (
        candidate_id,
        candidate_name,
        message,
        jobrole
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?
    )
");

/*
|--------------------------------------------------------------------------
| BIND PARAMETERS
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "isss",
    $candidate_id,
    $candidate_name,
    $message,
    $jobrole
);

/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

$success = $stmt->execute();

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

if ($success) {

    echo json_encode([
        "status" => "success",
        "message" => "Violation saved"
    ]);

} else {

    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed"
    ]);
}

/*
|--------------------------------------------------------------------------
| CLOSE
|--------------------------------------------------------------------------
*/

$stmt->close();

$conn->close();

?>