<?php
require_once 'db.php';
require_once 'includes/header.php';
?>

<style>
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-empty {
        background: #fee;
        color: #dc2626;
    }
    
    .status-filled {
        background: #dcfce7;
        color: #16a34a;
    }
        
        .edit-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .edit-link:hover {
            color: #4f46e5;
            text-decoration: underline;
        }
        
        .menu-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- User Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title h4 mb-3">
                    <i class="bi bi-person-badge me-2"></i>
                    Informasi Pengguna
                </h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <small class="text-muted d-block">Username</small>
                            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-4 border-primary">
                            <small class="text-muted d-block">Nama Madrasah</small>
                            <strong><?php echo htmlspecialchars($_SESSION['nama_madrasah']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Fetch user's indicator data grouped by hierarchy
        $username = $_SESSION['username'];
        
        // Get hierarchy structure with user scores
        $hierarchy_data = [];
        $t_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
        
        while ($t = $t_res->fetch_assoc()) {
            $t_item = [
                'kode' => $t['kode'], 
                'judul' => $t['judul'], 
                'unsurs' => [],
                'total_score' => 0
            ];
            
            $u_res = $conn->query("SELECT * FROM unsur_tugas_utama WHERE tugas_utama = '{$t['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
            
            while ($u = $u_res->fetch_assoc()) {
                $u_item = [
                    'kode' => $u['kode'], 
                    'judul' => $u['judul'], 
                    'indicators' => [],
                    'total_score' => 0
                ];
                
                // Get indicators with user scores for this unsur
                $i_sql = "SELECT hi.kode_indikator, hi.hasil_kerja, ik.judul, ik.data_kinerja 
                          FROM hasil_indikator hi 
                          LEFT JOIN indikator_kerja ik ON hi.kode_indikator = ik.kode 
                          WHERE hi.username = ? AND ik.unsur_tugas_utama = ? 
                          ORDER BY hi.kode_indikator";
                $i_stmt = $conn->prepare($i_sql);
                $i_stmt->bind_param("ss", $username, $u['kode']);
                $i_stmt->execute();
                $i_result = $i_stmt->get_result();
                
                while ($i = $i_result->fetch_assoc()) {
                    $u_item['indicators'][] = $i;
                    $u_item['total_score'] += $i['hasil_kerja'];
                    $t_item['total_score'] += $i['hasil_kerja'];
                }
                
                $t_item['unsurs'][] = $u_item;
            }
            
            $hierarchy_data[] = $t_item;
        }
        
        // Calculate grand total and maximum possible scores
        $grand_total = 0;
        $max_grand_total = 0;
        foreach ($hierarchy_data as &$tugas) {
            $grand_total += $tugas['total_score'];
            // Calculate max possible for this tugas
            $tugas_max = 0;
            foreach ($tugas['unsurs'] as &$unsur) {
                $unsur_max = count($unsur['indicators']) * 4; // 4 is max score per indicator
                $unsur['max_score'] = $unsur_max;
                $tugas_max += $unsur_max;
            }
            $tugas['max_score'] = $tugas_max;
            $max_grand_total += $tugas_max;
        }
        unset($tugas, $unsur); // Unset references
        
        // Calculate predikat for grand total
        $average_score = $max_grand_total > 0 ? ($grand_total / $max_grand_total) * 4 : 0;
        $overall_predikat = '';
        $overall_badge = '';
        if ($average_score >= 3.5) {
            $overall_predikat = 'Amat Baik';
            $overall_badge = 'bg-success';
        } elseif ($average_score >= 2.5) {
            $overall_predikat = 'Baik';
            $overall_badge = 'bg-info';
        } elseif ($average_score >= 1.5) {
            $overall_predikat = 'Cukup';
            $overall_badge = 'bg-warning';
        } elseif ($average_score >= 0.5) {
            $overall_predikat = 'Kurang';
            $overall_badge = 'bg-danger';
        } else {
            $overall_predikat = 'Belum Dinilai';
            $overall_badge = 'bg-secondary';
        }
        ?>
        
        <!-- Indikator Grouped by Hierarchy -->
        <div class="card mb-4">
            <div class="card-header card-header-custom">
                <h3 class="h5 mb-0">
                    <i class="bi bi-diagram-3 me-2"></i>
                    Peta Penilaian Indikator
                </h3>
            </div>
            <div class="card-body">
                <!-- Grand Total -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-primary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="bi bi-trophy me-2"></i>
                                        Total Nilai Keseluruhan
                                    </h5>
                                    <span class="badge <?php echo $overall_badge; ?> fs-6 mt-2">
                                        <i class="bi bi-star-fill me-1"></i><?php echo $overall_predikat; ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <h2 class="mb-0"><?php echo $grand_total; ?>/<?php echo $max_grand_total; ?></h2>
                                    <small class="text-muted">Total Nilai/Maksimal</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php foreach ($hierarchy_data as $tugas_index => $tugas): ?>
                    <!-- Tugas Utama -->
                    <div class="mb-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white" style="cursor: pointer;" onclick="toggleTugas(<?php echo $tugas_index; ?>)">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-folder me-2"></i>
                                        Tugas <?php echo $tugas['kode']; ?> - <?php echo htmlspecialchars($tugas['judul']); ?>
                                        <i class="bi bi-chevron-down ms-2" id="tugas-chevron-<?php echo $tugas_index; ?>"></i>
                                    </h5>
                                    <div class="badge bg-white text-primary fs-6">
                                        Total: <?php echo $tugas['total_score']; ?>/<?php echo $tugas['max_score']; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body" id="tugas-body-<?php echo $tugas_index; ?>" style="<?php echo $tugas_index > 0 ? 'display: none;' : ''; ?>">
                                <?php foreach ($tugas['unsurs'] as $unsur): ?>
                                    <!-- Unsur Tugas Utama -->
                                    <div class="mb-3">
                                        <div class="card border-secondary">
                                            <div class="card-header bg-secondary text-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">
                                                        <i class="bi bi-folder2-open me-2"></i>
                                                        Unsur <?php echo $unsur['kode']; ?> - <?php echo htmlspecialchars($unsur['judul']); ?>
                                                    </h6>
                                                    <?php 
                                                    $unsur_max = count($unsur['indicators']) * 4;
                                                    ?>
                                                    <div class="badge bg-white text-secondary">
                                                        Total: <?php echo $unsur['total_score']; ?>/<?php echo $unsur_max; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row">
                                                    <?php foreach ($unsur['indicators'] as $indikator): ?>
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="card h-100">
                                                                <div class="card-body p-3">
                                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                                        <div>
                                                                            <strong class="text-primary"><?php echo htmlspecialchars($indikator['kode_indikator']); ?></strong>
                                                                        </div>
                                                                        <div>
                                                                            <?php 
                                                                            $hasil = $indikator['hasil_kerja'];
                                                                            $max_score = 4;
                                                                            
                                                                            // Determine predikat
                                                                            $predikat = '';
                                                                            $badge_class = '';
                                                                            if ($hasil == 1) {
                                                                                $predikat = 'Kurang';
                                                                                $badge_class = 'bg-danger';
                                                                            } elseif ($hasil == 2) {
                                                                                $predikat = 'Cukup';
                                                                                $badge_class = 'bg-warning';
                                                                            } elseif ($hasil == 3) {
                                                                                $predikat = 'Baik';
                                                                                $badge_class = 'bg-info';
                                                                            } elseif ($hasil == 4) {
                                                                                $predikat = 'Amat Baik';
                                                                                $badge_class = 'bg-success';
                                                                            } else {
                                                                                $badge_class = 'bg-secondary';
                                                                            }
                                                                            
                                                                            if ($hasil > 0): ?>
                                                                                <span class="badge <?php echo $badge_class; ?>">
                                                                                    <?php echo $hasil; ?>/<?php echo $max_score; ?> - <?php echo $predikat; ?>
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="badge bg-secondary">
                                                                                    0/<?php echo $max_score; ?> - Belum
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                    <div class="small text-muted mb-2">
                                                                        <?php echo htmlspecialchars(substr($indikator['judul'], 0, 60)) . '...'; ?>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <div>
                                                                            <?php if ($hasil > 0): ?>
                                                                                <span class="status-badge status-filled">
                                                                                    <i class="bi bi-check-circle me-1"></i>Terisi
                                                                                </span>
                                                                            <?php else: ?>
                                                                                <span class="status-badge status-empty">
                                                                                    <i class="bi bi-circle me-1"></i>Kosong
                                                                                </span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <a href="view_indikator.php?kode=<?php echo urlencode($indikator['kode_indikator']); ?>" class="edit-link">
                                                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Menu Cards -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card h-100 menu-card">
                    <div class="card-body text-center">
                        <div class="display-4 text-primary mb-3">
                            <i class="bi bi-clipboard-data"></i>
                        </div>
                        <h5 class="card-title">Input Hasil Indikator</h5>
                        <p class="card-text text-muted">Masukkan data hasil kerja untuk setiap indikator PKKM</p>
                        <a href="input_hasil.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Input Data
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card h-100 menu-card">
                    <div class="card-body text-center">
                        <div class="display-4 text-success mb-3">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h5 class="card-title">Laporan Hasil</h5>
                        <p class="card-text text-muted">Lihat dan cetak laporan hasil indikator PKKM</p>
                        <a href="laporan.php" class="btn btn-success">
                            <i class="bi bi-file-earmark-text me-1"></i>Lihat Laporan
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card h-100 menu-card">
                    <div class="card-body text-center">
                        <div class="display-4 text-info mb-3">
                            <i class="bi bi-gear"></i>
                        </div>
                        <h5 class="card-title">Pengaturan</h5>
                        <p class="card-text text-muted">Kelola pengaturan akun dan sistem</p>
                        <a href="pengaturan.php" class="btn btn-info">
                            <i class="bi bi-sliders me-1"></i>Pengaturan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function toggleTugas(index) {
    const body = document.getElementById('tugas-body-' + index);
    const chevron = document.getElementById('tugas-chevron-' + index);
    
    if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.className = 'bi bi-chevron-up ms-2';
    } else {
        body.style.display = 'none';
        chevron.className = 'bi bi-chevron-down ms-2';
    }
}
</script>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
