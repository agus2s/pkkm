<?php
// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$nama_penilai = $_SESSION['nama_penilai'];
$nama_madrasah = $_SESSION['nama_madrasah'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PKKM - Penilaian Kinerja</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script>
        // Check for saved theme preference or use light as default
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <!-- Header -->
    <header class="header-gradient text-white py-3 mb-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <a href="dashboard.php" class="text-white text-decoration-none">
                        <h4 class="mb-0">
                            <i class="bi bi-clipboard-data me-2"></i>
                            PKKM System
                        </h4>
                    </a>
                </div>
                <div class="col-md-6 text-md-end">
                    <nav class="d-inline-flex gap-2">
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="bi bi-house me-1"></i>Dashboard
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-light rounded-pill px-3 dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                                    <?= strtoupper(substr($username, 0, 1)) ?>
                                </div>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($username); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li>
                                    <div class="px-3 py-2">
                                        <p class="text-muted small mb-0">Masuk sebagai:</p>
                                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($username); ?></p>
                                        <p class="text-primary small mb-0 mt-1">
                                            <i class="bi bi-building me-1"></i>
                                            <?php echo htmlspecialchars($nama_penilai); ?>
                                        </p>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2" href="dashboard.php">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="edit_data.php">
                                        <i class="bi bi-pencil-square me-2"></i>Edit Instrumen
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="laporan.php">
                                        <i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Hasil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <div class="dropdown-item py-2 d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-moon-stars me-2"></i>
                                            <span>Mode Gelap</span>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input cursor-pointer" type="checkbox" id="darkModeToggle" style="width: 2.5em;">
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Keluar Aplikasi
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <div class="container-fluid">

<script>
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const currentTheme = document.documentElement.getAttribute('data-bs-theme');
    
    // Set initial toggle state
    if (currentTheme === 'dark') {
        darkModeToggle.checked = true;
    }
    
    // Handle theme toggle
    darkModeToggle.addEventListener('change', function() {
        if (this.checked) {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            localStorage.setItem('theme', 'light');
        }
    });
});
</script>
