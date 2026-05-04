<?php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    // 1. Fetch all data at once to avoid N+1 query problem
    $tugas_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
    $unsur_res = $conn->query("SELECT * FROM unsur_tugas_utama ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
    $ind_res = $conn->query("SELECT * FROM indikator_kerja ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");

    $tugas_list = [];
    $unsur_map = []; // To group unsurs by tugas_utama
    $ind_map = [];   // To group indicators by unsur_tugas_utama

    // 2. Map indicators
    while ($ind = $ind_res->fetch_assoc()) {
        $u_kode = $ind['unsur_tugas_utama'];
        if (!isset($ind_map[$u_kode])) $ind_map[$u_kode] = [];
        $ind_map[$u_kode][] = [
            'code' => $ind['kode'],
            'title' => $ind['judul'],
            'data' => $ind['data_kinerja'],
            'score' => (int)$ind['hasil_kinerja'],
            'requested_evidence' => $ind['bukti_otentik'],
            'evidences' => json_decode($ind['tautan_bukti'] ?: '[]', true)
        ];
    }

    // 3. Map unsurs
    while ($unsur = $unsur_res->fetch_assoc()) {
        $t_kode = $unsur['tugas_utama'];
        if (!isset($unsur_map[$t_kode])) $unsur_map[$t_kode] = [];
        $unsur_map[$t_kode][] = [
            'code' => $unsur['kode'],
            'title' => $unsur['judul'],
            'indicators' => $ind_map[$unsur['kode']] ?? []
        ];
    }

    // 4. Build final hierarchy
    while ($tugas = $tugas_res->fetch_assoc()) {
        $tugas_list[] = [
            'id' => is_numeric($tugas['kode']) ? (int)$tugas['kode'] : $tugas['kode'],
            'title' => $tugas['judul'],
            'subTasks' => $unsur_map[$tugas['kode']] ?? []
        ];
    }

    echo json_encode($tugas_list);
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
} elseif ($action === 'reset_all') {
    $conn->query("UPDATE indikator_kerja SET hasil_kinerja = 0, tautan_bukti = '[]'");
    echo json_encode(["status" => "success"]);
}

$conn->close();
?>
