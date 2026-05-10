<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

header('Content-Type: application/json');
require_once 'db.php';

$username = $_SESSION['username'];
$action = $_GET['action'] ?? 'get';

if ($action === 'save_score') {
    $kode = $conn->real_escape_string($_POST['code'] ?? '');
    $score = (int)($_POST['score'] ?? 0);
    
    if ($kode) {
        // Update hasil_indikator table for current user
        $stmt = $conn->prepare("UPDATE hasil_indikator SET hasil_kerja = ?, updated_at = CURRENT_TIMESTAMP WHERE username = ? AND kode_indikator = ?");
        $stmt->bind_param("iss", $score, $username, $kode);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update score"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Missing code"]);
    }
} elseif ($action === 'save_evidences') {
    $kode = $conn->real_escape_string($_POST['code'] ?? '');
    $evidences = $_POST['evidences'] ?? '[]';
    
    // Validate json
    if (json_decode($evidences) !== null && $kode) {
        // Update tautan_bukti in indikator_kerja table (shared across users)
        $evidences_esc = $conn->real_escape_string($evidences);
        $conn->query("UPDATE indikator_kerja SET tautan_bukti = '$evidences_esc' WHERE kode = '$kode'");
        
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
    }
} elseif ($action === 'get_user_scores') {
    // Get all scores for current user
    $stmt = $conn->prepare("SELECT kode_indikator, hasil_kerja FROM hasil_indikator WHERE username = ? ORDER BY kode_indikator");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $scores = [];
    while ($row = $result->fetch_assoc()) {
        $scores[$row['kode_indikator']] = $row['hasil_kerja'];
    }
    
    echo json_encode(["status" => "success", "scores" => $scores]);
    $stmt->close();
} elseif ($action === 'reset_user_scores') {
    // Reset all scores for current user
    $stmt = $conn->prepare("UPDATE hasil_indikator SET hasil_kerja = 0, updated_at = CURRENT_TIMESTAMP WHERE username = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to reset scores"]);
    }
    $stmt->close();
}

$conn->close();
?>
