<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$username = $_SESSION['username'] ?? null;
if (!$username) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['reset_hasil'])) {
    $reset_sql = "UPDATE hasil_indikator SET hasil_kerja = 0 WHERE username = ?";
    $reset_stmt = $conn->prepare($reset_sql);
    $reset_stmt->bind_param("s", $username);
    if ($reset_stmt->execute()) {
        header("Location: laporan.php?reset=success");
        exit;
    }
}

require_once 'includes/header.php';

// Fetch all tugas_utama
$tugas_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
$report_data = [];

$total_score = 0;
$total_max = 0;

while ($t = $t_res = $tugas_res->fetch_assoc()) {
    $t_kode = $t['kode'];
    
    // Get all indicators for this tugas_utama and their scores
    $sql = "SELECT hi.hasil_kerja 
            FROM hasil_indikator hi 
            JOIN indikator_kerja ik ON hi.kode_indikator = ik.kode 
            JOIN unsur_tugas_utama u ON ik.unsur_tugas_utama = u.kode 
            WHERE hi.username = ? AND u.tugas_utama = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $t_kode);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $t_score = 0;
    $t_count = 0;
    while ($row = $res->fetch_assoc()) {
        $t_score += $row['hasil_kerja'];
        $t_count++;
    }
    
    $t_max = $t_count * 4;
    $t_avg = $t_count > 0 ? $t_score / $t_count : 0;
    $t_percentage = $t_max > 0 ? ($t_score / $t_max) * 100 : 0;
    
    $predikat = "Kurang";
    if ($t_percentage > 90) $predikat = "Amat Baik";
    elseif ($t_percentage > 75) $predikat = "Baik";
    elseif ($t_percentage > 60) $predikat = "Cukup";
    elseif ($t_percentage > 50) $predikat = "Sedang";
    
    $report_data[] = [
        'no' => $t_kode,
        'judul' => $t['judul'],
        'nilai' => round($t_avg, 2),
        'score' => $t_score,
        'max' => $t_max,
        'percentage' => round($t_percentage, 2),
        'predikat' => $predikat
    ];
    
    $total_score += $t_score;
    $total_max += $t_max;
}

$overall_avg = $total_max > 0 ? ($total_score / ($total_max / 4)) : 0;
$overall_percentage = $total_max > 0 ? ($total_score / $total_max) * 100 : 0;
$overall_predikat = "Kurang";
if ($overall_percentage > 90) $overall_predikat = "Amat Baik";
elseif ($overall_percentage > 75) $overall_predikat = "Baik";
elseif ($overall_percentage > 60) $overall_predikat = "Cukup";
elseif ($overall_percentage > 50) $overall_predikat = "Sedang";

?>

<div class="container py-4">
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show d-print-none" role="alert">
            <i class="bi bi-check-circle me-2"></i>Semua hasil penilaian telah di-reset ke nol.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1">Laporan Hasil Penilaian (PKKM)</h2>
                    <p class="text-muted mb-0">Rekapitulasi nilai per Tugas Utama</p>
                </div>
                <div class="d-print-none d-flex gap-2">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetModal">
                        <i class="bi bi-trash me-2"></i>Reset Hasil
                    </button>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="bi bi-printer me-2"></i>Cetak Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetModalLabel">Konfirmasi Reset Hasil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <i class="bi bi-exclamation-triangle text-danger display-1 mb-3"></i>
                    <p class="h5 mb-3">Apakah Anda yakin ingin me-reset semua data penilaian?</p>
                    <p class="text-muted">Tindakan ini akan mengosongkan (menjadi nol) semua hasil kerja yang telah Anda isi. Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST">
                        <button type="submit" name="reset_hasil" class="btn btn-danger">Ya, Reset Sekarang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 60px;">No</th>
                            <th>Tugas Utama</th>
                            <th class="text-center" style="width: 150px;">Nilai Rata-rata</th>
                            <th class="text-center" style="width: 120px;">Presentase</th>
                            <th class="text-center" style="width: 150px;">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $data): ?>
                        <tr>
                            <td class="text-center"><?= $data['no'] ?></td>
                            <td><?= htmlspecialchars($data['judul']) ?></td>
                            <td class="text-center">
                                <span class="fw-bold fs-5"><?= number_format($data['nilai'], 2) ?></span>
                                <div class="text-muted small">Skor: <?= $data['score'] ?> / <?= $data['max'] ?></div>
                            </td>
                            <td class="text-center"><?= $data['percentage'] ?>%</td>
                            <td class="text-center">
                                <?php
                                $badgeClass = 'bg-danger';
                                if ($data['predikat'] === 'Amat Baik') $badgeClass = 'bg-success';
                                elseif ($data['predikat'] === 'Baik') $badgeClass = 'bg-primary';
                                elseif ($data['predikat'] === 'Cukup') $badgeClass = 'bg-info text-white';
                                elseif ($data['predikat'] === 'Sedang') $badgeClass = 'bg-warning text-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $data['predikat'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                            <td colspan="2" class="text-end py-3">RATA-RATA KESELURUHAN</td>
                            <td class="text-center py-3">
                                <span class="h4 mb-0 fw-bold text-primary"><?= number_format($overall_avg, 2) ?></span>
                                <div class="text-muted small">Total Skor: <?= $total_score ?> / <?= $total_max ?></div>
                            </td>
                            <td class="text-center py-3">
                                <span class="h5 mb-0 fw-bold"><?= round($overall_percentage, 2) ?>%</span>
                            </td>
                            <td class="text-center py-3">
                                <?php
                                $overallBadgeClass = 'bg-danger';
                                if ($overall_predikat === 'Amat Baik') $overallBadgeClass = 'bg-success';
                                elseif ($overall_predikat === 'Baik') $overallBadgeClass = 'bg-primary';
                                elseif ($overall_predikat === 'Cukup') $overallBadgeClass = 'bg-info text-white';
                                elseif ($overall_predikat === 'Sedang') $overallBadgeClass = 'bg-warning text-dark';
                                ?>
                                <span class="badge <?= $overallBadgeClass ?> fs-6"><?= $overall_predikat ?></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 row">
        <div class="col-md-6">
            <div class="card border-0 bg-light shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 small text-muted text-uppercase tracking-wider">Keterangan Predikat:</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success" style="width: 15px; height: 15px; padding: 0;"> </span>
                                <span class="small">90 < NK < 100 : Amat Baik</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary" style="width: 15px; height: 15px; padding: 0;"> </span>
                                <span class="small">75 < NK ≤ 90 : Baik</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-info" style="width: 15px; height: 15px; padding: 0;"> </span>
                                <span class="small">60 < NK ≤ 75 : Cukup</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning" style="width: 15px; height: 15px; padding: 0;"> </span>
                                <span class="small">50 < NK ≤ 60 : Sedang</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger" style="width: 15px; height: 15px; padding: 0;"> </span>
                                <span class="small">NK ≤ 50 : Kurang</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-4 mt-md-0">
            <div class="pe-4 pt-4">
                <p class="mb-0">Dicetak pada: <?= date('d/m/Y H:i') ?></p>
                <p class="mb-5">Oleh: <?= htmlspecialchars($username) ?></p>
                <div class="mt-5 pt-3">
                    <p class="border-top d-inline-block pt-1 px-4 fw-bold">( ........................................ )</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
