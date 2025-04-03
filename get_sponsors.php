<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '1234', '1000hills_rugby');

if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT logo_path FROM sponsors ORDER BY created_at DESC";
$result = $conn->query($sql);

$sponsors = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $sponsors[] = $row;
    }
}

$conn->close();
echo json_encode($sponsors);
?>