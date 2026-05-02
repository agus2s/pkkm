<?php
$conn = new mysqli("localhost", "root", "@Pesantren1", "pkkm");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$jsonData = file_get_contents('data.json');
$data = json_decode($jsonData, true);

foreach ($data as $tugas) {
    $id_tugas = $tugas['id'];
    $judul_tugas = $conn->real_escape_string($tugas['title']);
    
    // insert tugas_utama
    $conn->query("INSERT IGNORE INTO tugas_utama (kode, judul) VALUES ('$id_tugas', '$judul_tugas')");
    
    foreach ($tugas['subTasks'] as $sub) {
        $kode_sub = $sub['code'];
        $judul_sub = $conn->real_escape_string($sub['title']);
        
        // insert unsur_tugas_utama
        $conn->query("INSERT IGNORE INTO unsur_tugas_utama (kode, tugas_utama, judul) VALUES ('$kode_sub', '$id_tugas', '$judul_sub')");
        
        foreach ($sub['indicators'] as $ind) {
            $kode_ind = $ind['code'];
            $judul_ind = $conn->real_escape_string($ind['title']);
            $data_kinerja = isset($ind['data']) ? $conn->real_escape_string($ind['data']) : '';
            
            // insert indikator_kerja
            $conn->query("INSERT IGNORE INTO indikator_kerja (kode, unsur_tugas_utama, judul, data_kinerja, hasil_kinerja, bukti_otentik) 
                          VALUES ('$kode_ind', '$kode_sub', '$judul_ind', '$data_kinerja', 0, '[]')");
        }
    }
}

echo "Import successful!";
$conn->close();
?>
