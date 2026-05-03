<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "@Pesantren1", "pkkm");

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    // Fetch all tugas_utama
    $tugas_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
    $data = [];
    while ($tugas = $tugas_res->fetch_assoc()) {
        $tugas_item = [
            'id' => is_numeric($tugas['kode']) ? (int)$tugas['kode'] : $tugas['kode'],
            'title' => $tugas['judul'],
            'subTasks' => []
        ];

        // Fetch unsur_tugas_utama for this tugas
        $unsur_res = $conn->query("SELECT * FROM unsur_tugas_utama WHERE tugas_utama = '{$tugas['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
        while ($unsur = $unsur_res->fetch_assoc()) {
            $subTask = [
                'code' => $unsur['kode'],
                'title' => $unsur['judul'],
                'indicators' => []
            ];

            // Fetch indikator_kerja for this unsur
            $ind_res = $conn->query("SELECT * FROM indikator_kerja WHERE unsur_tugas_utama = '{$unsur['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
            while ($ind = $ind_res->fetch_assoc()) {
                $indicator = [
                    'code' => $ind['kode'],
                    'title' => $ind['judul'],
                    'data' => $ind['data_kinerja'],
                    'score' => (int)$ind['hasil_kinerja'],
                    'requested_evidence' => $ind['bukti_otentik'],
                    'evidences' => json_decode($ind['tautan_bukti'] ?: '[]', true)
                ];
                $subTask['indicators'][] = $indicator;
            }
            $tugas_item['subTasks'][] = $subTask;
        }
        $data[] = $tugas_item;
    }
    echo json_encode($data);
} elseif ($action === 'save_score') {
    $kode = $conn->real_escape_string($_POST['code'] ?? '');
    $score = (int)($_POST['score'] ?? 0);
    if ($kode) {
        $conn->query("UPDATE indikator_kerja SET hasil_kinerja = $score WHERE kode = '$kode'");
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Missing code"]);
    }
} elseif ($action === 'save_evidences') {
    $kode = $conn->real_escape_string($_POST['code'] ?? '');
    $evidences = $_POST['evidences'] ?? '[]';
    // Validate json
    if (json_decode($evidences) !== null && $kode) {
        $evidences_esc = $conn->real_escape_string($evidences);
        $conn->query("UPDATE indikator_kerja SET tautan_bukti = '$evidences_esc' WHERE kode = '$kode'");
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid data"]);
    }
}

$conn->close();
?>
