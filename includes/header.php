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
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            border: none;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 2px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #667eea;
            background: white;
            border-bottom: 2px solid #667eea;
        }
        
        .nav-tabs .nav-link:hover {
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }
        
        .badge-indigo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .custom-scrollbar::-webkit-scrollbar { 
            width: 6px; 
            height: 6px; 
        }
        
        .custom-scrollbar::-webkit-scrollbar-track { 
            background: transparent; 
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb { 
            background: #dee2e6; 
            border-radius: 10px; 
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .custom-toast {
            min-width: 250px;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Navigation Drawer Styles */
        .nav-drawer {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100vh;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }
        
        .nav-drawer.open {
            left: 0;
        }
        
        .nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 1040;
        }
        
        .nav-overlay.show {
            display: block;
        }
        
        .nav-tugas {
            font-weight: bold;
            color: #667eea;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        
        .nav-unsur {
            font-weight: 500;
            color: #6c757d;
            padding: 6px 12px 6px 20px;
            font-size: 0.9rem;
        }
        
        .indicators-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 4px;
            padding: 8px 12px 8px 20px;
        }
        
        .indicator-btn {
            padding: 4px 8px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .indicator-btn:hover {
            background: #f8f9fa;
            border-color: #667eea;
        }
        
        .indicator-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .rating-group {
            display: flex;
            gap: 8px;
            margin: 10px 0;
        }
        
        .rating-btn {
            width: 50px;
            height: 50px;
            border: 2px solid #dee2e6;
            background: white;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .rating-btn:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .rating-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .save-status {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
        }
    </style>
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
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2"></i>
                                <?php echo htmlspecialchars($username); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">
                                        <i class="bi bi-person me-2"></i>
                                        <?php echo htmlspecialchars($username); ?>
                                    </h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="edit_data.php">
                                        <i class="bi bi-gear me-2"></i>Edit Data
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
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
