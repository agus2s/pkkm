<?php
session_start();
require_once 'db.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Check if this is registration or login
    $isRegistration = isset($_POST['nama_madrasah']) && !empty($_POST['nama_madrasah']);
    
    if ($isRegistration) {
        // Registration process
        $nama_madrasah = trim($_POST['nama_madrasah']);
        $nama_kepala_madrasah = trim($_POST['nama_kepala_madrasah']);
        $nama_penilai = trim($_POST['nama_penilai']);
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($username) || empty($password) || empty($nama_madrasah) || empty($nama_kepala_madrasah) || empty($nama_penilai)) {
            $_SESSION['error'] = 'Semua field harus diisi!';
            header('Location: login.php');
            exit;
        }
        
        if ($password !== $confirm_password) {
            $_SESSION['error'] = 'Password dan konfirmasi password tidak cocok!';
            header('Location: login.php');
            exit;
        }
        
        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password minimal 6 karakter!';
            header('Location: login.php');
            exit;
        }
        
        // Check if username already exists
        $check_sql = "SELECT username FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Username sudah digunakan!';
            header('Location: login.php');
            exit;
        }
        
        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO users (username, nama_madrasah, nama_kepala_madrasah, nama_penilai, password) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sssss", $username, $nama_madrasah, $nama_kepala_madrasah, $nama_penilai, $hashed_password);
        
        if ($insert_stmt->execute()) {
            // Get all indicator codes from indikator_kerja table
            $indikator_sql = "SELECT kode FROM indikator_kerja";
            $indikator_result = $conn->query($indikator_sql);
            
            if ($indikator_result && $indikator_result->num_rows > 0) {
                // Prepare insert statement for hasil_indikator
                $hasil_sql = "INSERT INTO hasil_indikator (username, kode_indikator, hasil_kerja) VALUES (?, ?, 0)";
                $hasil_stmt = $conn->prepare($hasil_sql);
                
                // Insert each indicator for the new user
                while ($row = $indikator_result->fetch_assoc()) {
                    $kode_indikator = $row['kode'];
                    $hasil_stmt->bind_param("ss", $username, $kode_indikator);
                    $hasil_stmt->execute();
                }
                $hasil_stmt->close();
            }
            
            $_SESSION['success'] = 'Registrasi berhasil! Silakan login.';
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['error'] = 'Registrasi gagal: ' . $conn->error;
            header('Location: login.php');
            exit;
        }
        
    } else {
        // Login process
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username dan password harus diisi!';
            header('Location: login.php');
            exit;
        }
        
        // Check user credentials
        $sql = "SELECT id, username, password, nama_madrasah FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_madrasah'] = $user['nama_madrasah'];
                
                header('Location: dashboard.php');
                exit;
            }
        }
        
        $_SESSION['error'] = 'Username atau password salah!';
        header('Location: login.php');
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PKKM System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .toggle-form a {
            color: #667eea;
            text-decoration: none;
        }
        
        .toggle-form a:hover {
            text-decoration: underline;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #cfc;
        }
        
        .registration-fields {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-header">
            <h2 id="form-title">Login PKKM</h2>
            <p id="form-subtitle">Silakan masuk ke akun Anda</p>
        </div>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="registration-fields" id="registration-fields">
                <div class="form-group">
                    <label for="nama_madrasah">Nama Madrasah</label>
                    <input type="text" id="nama_madrasah" name="nama_madrasah">
                </div>
                
                <div class="form-group">
                    <label for="nama_kepala_madrasah">Nama Kepala Madrasah</label>
                    <input type="text" id="nama_kepala_madrasah" name="nama_kepala_madrasah">
                </div>
                
                <div class="form-group">
                    <label for="nama_penilai">Nama Penilai</label>
                    <input type="text" id="nama_penilai" name="nama_penilai">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            
            <button type="submit" name="submit" class="btn" id="submit-btn">Login</button>
        </form>
        
        <div class="toggle-form">
            <p id="toggle-text">Belum punya akun? <a href="#" onclick="toggleForm()">Daftar di sini</a></p>
        </div>
    </div>
    
    <script>
        function toggleForm() {
            const regFields = document.getElementById('registration-fields');
            const formTitle = document.getElementById('form-title');
            const formSubtitle = document.getElementById('form-subtitle');
            const submitBtn = document.getElementById('submit-btn');
            const toggleText = document.getElementById('toggle-text');
            
            if (regFields.style.display === 'none' || regFields.style.display === '') {
                regFields.style.display = 'block';
                formTitle.textContent = 'Daftar PKKM';
                formSubtitle.textContent = 'Buat akun baru Anda';
                submitBtn.textContent = 'Daftar';
                toggleText.innerHTML = 'Sudah punya akun? <a href="#" onclick="toggleForm()">Login di sini</a>';
            } else {
                regFields.style.display = 'none';
                formTitle.textContent = 'Login PKKM';
                formSubtitle.textContent = 'Silakan masuk ke akun Anda';
                submitBtn.textContent = 'Login';
                toggleText.innerHTML = 'Belum punya akun? <a href="#" onclick="toggleForm()">Daftar di sini</a>';
            }
        }
    </script>
</body>
</html>

