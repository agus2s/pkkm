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
                                            <?php echo htmlspecialchars($nama_madrasah); ?>
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
