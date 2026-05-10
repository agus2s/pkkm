<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Get kode_indikator from URL
$kode_indikator = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode_indikator)) {
    $_SESSION['error'] = 'Kode indikator tidak valid.';
    header('Location: dashboard.php');
    exit;
}

// Get username
$username = $_SESSION['username'] ?? '';

// Handle form submission FIRST (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $hasil_kerja = intval($_POST['hasil_kerja']);
    $link_bukti = trim($_POST['link_bukti'] ?? '');
    $next_kode = $_POST['next_kode'] ?? '';
    
    // Update hasil_indikator table
    $update_sql = "UPDATE hasil_indikator SET hasil_kerja = ?, link_bukti = ?, updated_at = CURRENT_TIMESTAMP 
                   WHERE username = ? AND kode_indikator = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isss", $hasil_kerja, $link_bukti, $username, $kode_indikator);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = 'Data indikator berhasil diperbarui!';
        
        // Redirect to next indicator if specified, otherwise refresh current page
        if (!empty($next_kode)) {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?kode=' . urlencode($next_kode));
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?kode=' . urlencode($kode_indikator));
        }
        exit;
    } else {
        $error_message = 'Gagal memperbarui data: ' . $conn->error;
    }
}

// Now include header after all potential redirects
require_once 'includes/header.php';

// Get current data for this indicator
$sql = "SELECT hi.hasil_kerja, hi.link_bukti, ik.judul, ik.data_kinerja, ik.bukti_otentik 
        FROM hasil_indikator hi 
        LEFT JOIN indikator_kerja ik ON hi.kode_indikator = ik.kode 
        WHERE hi.username = ? AND hi.kode_indikator = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $kode_indikator);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Data indikator tidak ditemukan.';
    header('Location: dashboard.php');
    exit;
}

$indikator_data = $result->fetch_assoc();

// Fetch Hierarchy to find current Tugas Utama
$hierarchy = [];
$t_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
while ($t = $t_res->fetch_assoc()) {
    $t_item = ['kode' => $t['kode'], 'judul' => $t['judul'], 'unsurs' => []];
    $u_res = $conn->query("SELECT * FROM unsur_tugas_utama WHERE tugas_utama = '{$t['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
    while ($u = $u_res->fetch_assoc()) {
        $u_item = ['kode' => $u['kode'], 'judul' => $u['judul'], 'indicators' => []];
        $i_res = $conn->query("SELECT ik.kode, ik.judul, hi.hasil_kerja 
            FROM indikator_kerja ik 
            LEFT JOIN hasil_indikator hi ON ik.kode = hi.kode_indikator AND hi.username = '$username'
            WHERE ik.unsur_tugas_utama = '{$u['kode']}' 
            ORDER BY 
            CAST(SUBSTRING_INDEX(ik.kode, '.', 1) AS UNSIGNED), 
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ik.kode, '.', 2), '.', -1) AS UNSIGNED), 
            CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ik.kode, '.', 3), '.', -1) AS UNSIGNED)");
        while ($i = $i_res->fetch_assoc()) {
            $u_item['indicators'][] = $i;
        }
        $t_item['unsurs'][] = $u_item;
    }
    $hierarchy[] = $t_item;
}

// Find current active Tugas Utama
$current_tugas = '';
foreach ($hierarchy as $tugas) {
    foreach ($tugas['unsurs'] as $unsur) {
        foreach ($unsur['indicators'] as $indikator) {
            if ($indikator['kode'] === $kode_indikator) {
                $current_tugas = $tugas['kode'];
                break 3;
            }
        }
    }
}

// Get all indicator codes for navigation
$all_codes = [];
$all_res = $conn->query("SELECT kode FROM indikator_kerja ORDER BY 
    CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), 
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), 
    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
while ($row = $all_res->fetch_assoc()) {
    $all_codes[] = $row['kode'];
}
$currentIndex = array_search($kode_indikator, $all_codes);
$prevCode = $currentIndex > 0 ? $all_codes[$currentIndex - 1] : null;
$nextCode = $currentIndex < count($all_codes) - 1 ? $all_codes[$currentIndex + 1] : null;
?>



        <div class="row g-4 p-4">
            <!-- Sidebar -->
            <div class="col-md-4 col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-content">
                        <?php foreach ($hierarchy as $t): ?>
                            <?php if ($t['kode'] === $current_tugas): ?>
                                <div class="sidebar-tugas">
                                    <i class="bi bi-folder me-1"></i>
                                    Tugas <?php echo $t['kode']; ?>
                                </div>
                                <?php foreach ($t['unsurs'] as $u): ?>
                                    <div class="sidebar-unsur">
                                        <i class="bi bi-folder2-open me-1"></i>
                                        Unsur <?php echo $u['kode']; ?>
                                    </div>
                                    <div class="sidebar-indicators">
                                        <?php foreach ($u['indicators'] as $i): ?>
                                            <button class="sidebar-indicator-btn <?php echo ($i['kode'] === $kode_indikator) ? 'active' : ''; ?> <?php echo ($i['hasil_kerja'] > 0) ? 'filled' : ''; ?>"
                                                    onclick="navigateToIndicator('<?php echo $i['kode']; ?>')">
                                                <?php echo $i['kode']; ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-8 col-lg-9 p-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                    <div class="card-header card-header-custom">
                        <h3 class="h5 mb-0">
                            <i class="bi bi-clipboard-data me-2"></i>
                            Edit Data Indikator
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Indicator Info -->
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <div class="indicator-code <?php echo ($indikator_data['hasil_kerja'] > 0) ? 'filled' : ''; ?>">
                                    <?php echo htmlspecialchars($kode_indikator); ?>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <h4 class="h5 text-primary">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <?php echo htmlspecialchars($indikator_data['judul'] ?? 'Tidak tersedia'); ?>
                                </h4>
                                <div class="mb-3">
                                    <span class="badge bg-secondary text-white">
                                        <i class="bi bi-database me-1"></i>
                                        Data Kinerja
                                    </span>
                                </div>
                                <p class="text-muted fst-italic">
                                    "<?php echo htmlspecialchars($indikator_data['data_kinerja'] ?? 'Tidak tersedia'); ?>"
                                </p>
                            </div>
                        </div>
                        
                        <!-- Bukti Otentik Section -->
                        <div class="card mb-4 bg-light">
                            <div class="card-body">
                                <h5 class="h6 text-primary mb-3">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Bukti Otentik Kualitas Kinerja
                                </h5>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Informasi bukti otentik yang diperlukan
                                </div>
                                <div class="bg-white p-3 rounded border">
                                    <?php 
                                    $formatted_evidence = preg_replace('/^\((\d+)\)/m', '🔵 $1.', $indikator_data['bukti_otentik'] ?? 'Tidak tersedia');
                                    echo nl2br(htmlspecialchars($formatted_evidence));
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form -->
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?kode=' . urlencode($kode_indikator)); ?>">
                            <div class="row">
                                <div class="col-12 px-4">
                                    <!-- Link Bukti First -->
                                    <div class="card bg-light border-0 mb-5">
                                        <div class="card-body p-4">
                                            <label class="form-label fw-bold mb-3">
                                                <i class="bi bi-link-45deg me-1"></i>
                                                Link Google Drive Bukti Kinerja
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-primary border-0 shadow-sm">
                                                    <i class="bi bi-google"></i>
                                                </span>
                                                <input type="url" 
                                                       class="form-control border-0 shadow-sm py-2" 
                                                       id="link_bukti" 
                                                       name="link_bukti" 
                                                       placeholder="https://drive.google.com/drive/folders/..."
                                                       value="<?php echo htmlspecialchars($indikator_data['link_bukti'] ?? ''); ?>"
                                                       onchange="autoSaveLink()">
                                                <?php if (!empty($indikator_data['link_bukti'])): ?>
                                                    <a href="<?php echo htmlspecialchars($indikator_data['link_bukti']); ?>" 
                                                       target="_blank" 
                                                       class="btn btn-white border-0 shadow-sm px-3"
                                                       title="Buka Link">
                                                        <i class="bi bi-box-arrow-up-right text-primary"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted mt-2">
                                                <small><i class="bi bi-info-circle me-1"></i>Pastikan akses link diatur "Siapa saja dengan link dapat melihat"</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rating Selection Second -->
                                    <div class="rating-section bg-white rounded-3 p-4 border mb-4">
                                        <label class="form-label fw-bold text-center d-block mb-4">
                                            <i class="bi bi-star me-1"></i>
                                            Pilih Rating Hasil Kerja
                                        </label>
                                        <div class="rating-group mb-3">
                                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                                <button type="button" 
                                                        class="rating-btn <?php echo ($indikator_data['hasil_kerja'] == $i) ? 'active' : ''; ?>" 
                                                        data-rating="<?php echo $i; ?>"
                                                        onclick="selectRating(<?php echo $i; ?>)">
                                                    <?php echo $i; ?>
                                                </button>
                                            <?php endfor; ?>
                                        </div>
                                        <input type="hidden" 
                                               id="hasil_kerja" 
                                               name="hasil_kerja" 
                                               value="<?php echo htmlspecialchars($indikator_data['hasil_kerja']); ?>" 
                                               required>
                                        <div class="text-center text-muted">
                                            <small>Pilih rating dari 1 (terendah) hingga 4 (tertinggi)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for navigation -->
                            <input type="hidden" name="next_kode" id="next_kode" value="">
                            
                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <div>
                                    <?php if ($prevCode): ?>
                                        <button type="submit" name="submit" value="prev" 
                                                onclick="document.getElementById('next_kode').value='<?php echo $prevCode; ?>'" 
                                                class="btn btn-outline-primary btn-lg">
                                            <i class="bi bi-chevron-left me-2"></i>
                                            Sebelumnya
                                        </button>
                                    <?php else: ?>
                                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="bi bi-house me-2"></i>
                                            Dashboard
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        <?php 
                                        $currentIndex = array_search($kode_indikator, $all_codes);
                                        $totalCount = count($all_codes);
                                        echo ($currentIndex + 1) . ' / ' . $totalCount;
                                        ?>
                                    </small>
                                </div>
                                
                                <div>
                                    <?php if ($nextCode): ?>
                                        <button type="submit" name="submit" value="next" 
                                                onclick="document.getElementById('next_kode').value='<?php echo $nextCode; ?>'" 
                                                class="btn btn-primary btn-lg">
                                            Berikutnya
                                            <i class="bi bi-chevron-right ms-2"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="dashboard.php" class="btn btn-success btn-lg">
                                            <i class="bi bi-check-circle me-2"></i>
                                            Selesai
                                        </a>
                                    <?php endif; ?>
                            </form>
                    </div> <!-- card-body -->
                </div> <!-- card shadow -->
            </div> <!-- col-12 -->
        </div> <!-- row inside main content -->
    </div> <!-- col-md-8 col-lg-9 -->
</div> <!-- main row g-4 -->

<!-- Auto-save Status Container -->
<div id="save-status" style="display: none;"></div>

<script>
    function navigateToIndicator(kode) {
            window.location.href = '?kode=' + kode;
        }
        
        function selectRating(rating) {
            // Remove active class from all buttons
            document.querySelectorAll('.rating-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to selected button
            document.querySelector(`[data-rating="${rating}"]`).classList.add('active');
            
            // Update hidden input value
            document.getElementById('hasil_kerja').value = rating;
            
            // Auto-save
            autoSaveAll();
        }
        
        function autoSaveLink() {
            autoSaveAll();
        }
        
        function autoSaveAll() {
            const rating = document.getElementById('hasil_kerja').value;
            const link = document.getElementById('link_bukti').value;
            
            const formData = new FormData();
            formData.append('code', '<?php echo $kode_indikator; ?>');
            formData.append('score', rating);
            formData.append('link_bukti', link);
            
            // Show saving indicator
            showSaveStatus('Menyimpan...', 'warning');
            
            fetch('api_user.php?action=save_score', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showSaveStatus('Tersimpan otomatis', 'success');
                    setTimeout(() => hideSaveStatus(), 2000);
                } else {
                    showSaveStatus('Gagal menyimpan', 'danger');
                    setTimeout(() => hideSaveStatus(), 3000);
                }
            })
            .catch(err => {
                showSaveStatus('Gagal menyimpan', 'danger');
                setTimeout(() => hideSaveStatus(), 3000);
            });
        }
        
        function showSaveStatus(message, type) {
            const statusDiv = document.getElementById('save-status');
            statusDiv.className = `alert alert-${type} position-fixed`;
            statusDiv.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 200px;
                opacity: 0;
                transition: opacity 0.3s;
            `;
            statusDiv.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'arrow-repeat' : 'exclamation-triangle'} me-2"></i>
                ${message}
            `;
            statusDiv.style.display = 'block';
            
            // Fade in
            setTimeout(() => {
                statusDiv.style.opacity = '1';
            }, 10);
        }
        
        function hideSaveStatus() {
            const statusDiv = document.getElementById('save-status');
            statusDiv.style.opacity = '0';
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 300);
        }
        
        // Add keyboard shortcuts for rating selection and navigation
        document.addEventListener('keydown', function(e) {
            // Only handle shortcuts if not typing in an input field
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            // Rating selection (1-4)
            if (e.key >= '1' && e.key <= '4') {
                selectRating(parseInt(e.key));
            }
            
            // Navigation (left/right arrows) - Save then navigate
            <?php if ($prevCode): ?>
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                saveAndNavigate('<?php echo $prevCode; ?>');
            }
            <?php endif; ?>
            
            <?php if ($nextCode): ?>
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                saveAndNavigate('<?php echo $nextCode; ?>');
            }
            <?php endif; ?>
            
            // Toggle drawer with 'M' key
            if (e.key.toLowerCase() === 'm') {
                toggleDrawer();
            }
        });
        
        // Save current rating and then navigate
        function saveAndNavigate(targetKode) {
            const currentRating = document.getElementById('hasil_kerja').value;
            
            // Show saving indicator
            showSaveStatus('Menyimpan...', 'warning');
            
            // Create form data for saving
            const formData = new FormData();
            formData.append('code', '<?php echo $kode_indikator; ?>');
            formData.append('score', currentRating);
            
            // Save via API
            fetch('api_user.php?action=save_score', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showSaveStatus('Tersimpan', 'success');
                    // Navigate after successful save
                    setTimeout(() => {
                        window.location.href = '?kode=' + targetKode;
                    }, 500);
                } else {
                    showSaveStatus('Gagal menyimpan', 'danger');
                    // Still navigate even if save fails
                    setTimeout(() => {
                        window.location.href = '?kode=' + targetKode;
                    }, 1000);
                }
            })
            .catch(err => {
                console.error('Save error:', err);
                // Navigate anyway on error
                window.location.href = '?kode=' + targetKode;
            });
        }
    </script>

<?php
require_once 'includes/footer.php';
?>
