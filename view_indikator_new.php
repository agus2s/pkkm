<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

// Get kode_indikator from URL
$kode_indikator = isset($_GET['kode']) ? $_GET['kode'] : '';

if (empty($kode_indikator)) {
    $_SESSION['error'] = 'Kode indikator tidak valid.';
    header('Location: dashboard.php');
    exit;
}

// Fetch all indicator codes in order to determine prev/next
$all_codes_res = $conn->query("SELECT kode FROM indikator_kerja ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
$all_codes = [];
while ($row = $all_codes_res->fetch_assoc()) {
    $all_codes[] = $row['kode'];
}

if (!$kode_indikator && !empty($all_codes)) {
    $kode_indikator = $all_codes[0];
}

$currentIndex = array_search($kode_indikator, $all_codes);
$prevCode = ($currentIndex > 0) ? $all_codes[$currentIndex - 1] : null;
$nextCode = ($currentIndex !== false && $currentIndex < count($all_codes) - 1) ? $all_codes[$currentIndex + 1] : null;

// Get current data for this indicator
$username = $_SESSION['username'];
$sql = "SELECT hi.hasil_kerja, ik.judul, ik.data_kinerja, ik.bukti_otentik, ik.tautan_bukti,
        i.*, u.judul as unsur_judul, t.judul as tugas_judul, t.kode as tugas_kode
        FROM hasil_indikator hi 
        LEFT JOIN indikator_kerja ik ON hi.kode_indikator = ik.kode 
        LEFT JOIN indikator_kerja i ON ik.kode = i.kode
        LEFT JOIN unsur_tugas_utama u ON i.unsur_tugas_utama = u.kode
        LEFT JOIN tugas_utama t ON u.tugas_utama = t.kode
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

$indicator = $result->fetch_assoc();

// Fetch Hierarchy for Navigation
$hierarchy = [];
$t_res = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
while ($t = $t_res->fetch_assoc()) {
    $t_item = ['kode' => $t['kode'], 'judul' => $t['judul'], 'unsurs' => []];
    $u_res = $conn->query("SELECT * FROM unsur_tugas_utama WHERE tugas_utama = '{$t['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
    while ($u = $u_res->fetch_assoc()) {
        $u_item = ['kode' => $u['kode'], 'judul' => $u['judul'], 'indicators' => []];
        $i_res = $conn->query("SELECT kode, judul FROM indikator_kerja WHERE unsur_tugas_utama = '{$u['kode']}' ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
        while ($i = $i_res->fetch_assoc()) {
            $u_item['indicators'][] = $i;
        }
        $t_item['unsurs'][] = $u_item;
    }
    $hierarchy[] = $t_item;
}

$requested_evidence = $indicator['bukti_otentik'];
$evidences = json_decode($indicator['tautan_bukti'] ?: '[]', true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $hasil_kerja = intval($_POST['hasil_kerja']);
    
    // Update hasil_indikator table
    $update_sql = "UPDATE hasil_indikator SET hasil_kerja = ?, updated_at = CURRENT_TIMESTAMP 
                   WHERE username = ? AND kode_indikator = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iss", $hasil_kerja, $username, $kode_indikator);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = 'Data indikator berhasil diperbarui!';
        header('Location: dashboard.php');
        exit;
    } else {
        $error_message = 'Gagal memperbarui data: ' . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Indikator - <?php echo $indicator['kode_indikator']; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0f172a',
                            card: '#1e293b',
                            border: '#334155'
                        }
                    }
                }
            }
        }
    </script>

    <script>
        // Pre-initialization to prevent flash
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass-card {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.05);
        }
        .score-radio-input:checked + div {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        .dark .score-radio-input:checked + div {
            background-color: #6366f1;
            border-color: #6366f1;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 min-h-screen pb-12 transition-colors duration-300">

    <!-- Navigation Drawer -->
    <div id="nav-drawer" class="fixed inset-y-0 left-0 w-80 bg-white dark:bg-slate-900 shadow-2xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-slate-200 dark:border-slate-800 flex flex-col">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <h2 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <i data-lucide="menu" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                Navigasi Instrumen
            </h2>
            <button onclick="toggleDrawer()" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg text-slate-400">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            <div class="space-y-6">
                <?php foreach ($hierarchy as $t): ?>
                <div class="space-y-2">
                    <div class="flex items-start gap-2 px-2 py-1 text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest" title="<?php echo htmlspecialchars($t['judul']); ?>">
                        <span class="text-indigo-600 dark:text-indigo-400 shrink-0">Tugas <?php echo $t['kode']; ?></span>
                    </div>
                    <div class="space-y-1 ml-2 border-l-2 border-slate-100 dark:border-slate-800 pl-2">
                        <?php foreach ($t['unsurs'] as $u): ?>
                        <div class="space-y-1">
                            <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 py-1 px-2 flex items-start gap-2" title="<?php echo htmlspecialchars($u['judul']); ?>">
                                <span class="shrink-0 text-indigo-400 dark:text-indigo-500">Unsur <?php echo $u['kode']; ?></span>
                            </div>
                            <div class="flex flex-wrap gap-1 ml-2 px-2">
                                <?php foreach ($u['indicators'] as $i): ?>
                                <a href="?kode=<?php echo $i['kode']; ?>" 
                                   title="<?php echo htmlspecialchars($i['judul']); ?>"
                                   class="px-2 py-1 rounded-md text-[10px] font-bold transition-all 
                                   <?php echo ($i['kode'] === $kode_indikator) 
                                       ? 'bg-indigo-600 text-white shadow-md' 
                                       : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 hover:text-indigo-600 dark:hover:text-indigo-400'; ?>">
                                    <?php echo $i['kode']; ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div id="drawer-overlay" onclick="toggleDrawer()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300"></div>

    <!-- Navbar -->
    <nav class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-1 sm:gap-3">
                <button onclick="toggleDrawer()" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-indigo-600 dark:text-indigo-400 transition-colors mr-1">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <a href="dashboard.php" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors text-slate-500 dark:text-slate-400 hidden sm:flex">
                    <i data-lucide="home" class="w-5 h-5"></i>
                </a>
                <h1 class="font-bold text-slate-800 dark:text-white text-sm sm:text-base">Detail Indikator</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-4">
                <button 
                    onclick="toggleShortcutModal()"
                    class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors text-slate-500 dark:text-slate-400"
                    title="Shortcut Keyboard"
                >
                    <i data-lucide="keyboard" class="w-5 h-5"></i>
                </button>
                <button 
                    onclick="toggleTheme()"
                    class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-full transition-colors text-slate-500 dark:text-slate-400"
                    title="Ganti Tema"
                >
                    <i data-lucide="moon" id="theme-icon" class="w-5 h-5"></i>
                </button>
                <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded tracking-tighter sm:tracking-normal">
                    <?php echo ($currentIndex + 1); ?> / <?php echo count($all_codes); ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Breadcrumb / Context -->
        <div class="mb-6 space-y-1">
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                <span>Tugas <?php echo $indicator['tugas_kode']; ?></span>
                <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300 dark:text-slate-700"></i>
                <span>Unsur <?php echo $indicator['unsur_tugas_utama']; ?></span>
            </div>
            <h2 class="text-slate-500 dark:text-slate-400 text-sm font-medium leading-relaxed">
                <?php echo $indicator['tugas_judul']; ?> — <?php echo $indicator['unsur_judul']; ?>
            </h2>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="flex flex-col md:flex-row md:items-start gap-6 mb-10">
                    <div class="bg-indigo-600 dark:bg-indigo-500 text-white <?php echo strlen($indicator['kode_indikator']) > 5 ? 'text-2xl' : 'text-3xl'; ?> font-bold min-w-[5rem] w-fit h-20 px-4 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-indigo-200 dark:shadow-none transition-all">
                        <?php echo $indicator['kode_indikator']; ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-3">
                            <?php echo $indicator['judul']; ?>
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                <i data-lucide="database" class="w-3 h-3 mr-1.5"></i>
                                Data Kinerja yang Diharapkan
                            </span>
                        </div>
                        <p class="mt-3 text-slate-600 dark:text-slate-400 leading-relaxed italic">
                            "<?php echo $indicator['data_kinerja']; ?>"
                        </p>
                    </div>
                </div>

                <!-- Section: Bukti Otentik yang Diminta -->
                <div class="mb-10 p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                            <i data-lucide="info" class="w-5 h-5"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 dark:text-white">Bukti Otentik Kualitas Kinerja</h4>
                    </div>
                    <?php 
                        $formatted_evidence = preg_replace('/^\((\d+)\)/m', '🔵 $1.', $requested_evidence);
                    ?>
                    <div class="text-slate-600 dark:text-slate-300 leading-normal whitespace-pre-line bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200/50 dark:border-slate-800 shadow-sm"><?php echo htmlspecialchars($formatted_evidence); ?></div>
                </div>

                <hr class="border-slate-100 dark:border-slate-800 mb-10">

                <div class="space-y-12">
                    <!-- Section: Bukti Otentik yang Diinput -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Section: Bukti Otentik yang Diinput -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                    <i data-lucide="link" class="w-5 h-5"></i>
                                </div>
                                <h4 class="font-bold text-slate-800 dark:text-white">Bukti Otentik yang Diinput</h4>
                            </div>
                            <button onclick="toggleEvidenceForm()" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold text-sm flex items-center gap-1">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Link
                            </button>
                        </div>

                        <div id="evidence-list" class="space-y-3">
                            <?php if (empty($evidences)): ?>
                                <div class="p-6 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-center text-slate-400 dark:text-slate-600">
                                    <i data-lucide="file-x" class="w-8 h-8 mx-auto mb-2 opacity-20"></i>
                                    <p class="text-sm">Belum ada link bukti</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($evidences as $evidence): ?>
                                <div class="flex items-center gap-3 p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl group hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-md transition-all">
                                    <div class="p-2 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-indigo-500 dark:text-indigo-400">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a href="<?php echo $evidence['url']; ?>" target="_blank" class="block font-medium text-slate-700 dark:text-slate-200 text-sm truncate hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                            <?php echo $evidence['title']; ?>
                                        </a>
                                        <span class="text-[10px] text-slate-400 dark:text-slate-500 block truncate"><?php echo $evidence['url']; ?></span>
                                    </div>
                                    <button onclick="removeEvidence('<?php echo $indicator['kode_indikator']; ?>', <?php echo $evidence['id']; ?>)" class="text-slate-300 dark:text-slate-600 hover:text-red-500 p-2 rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Form Tambah Bukti -->
                        <div id="evidence-form" class="mt-4 hidden p-4 bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-900/50 rounded-2xl shadow-xl shadow-indigo-50 dark:shadow-none animate-in fade-in slide-in-from-top-2 duration-300">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <input type="text" id="ev-title" placeholder="Nama Dokumen" class="text-sm border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-800 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 border outline-none">
                                <input type="text" id="ev-url" placeholder="Link URL" class="text-sm border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-800 dark:text-white rounded-xl focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 border outline-none">
                            </div>
                            <div class="flex gap-2 justify-end">
                                <button onclick="toggleEvidenceForm()" class="px-4 py-1.5 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold text-xs rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-all">Batal</button>
                                <button onclick="saveEvidence('<?php echo $indicator['kode_indikator']; ?>')" class="px-6 bg-indigo-600 dark:bg-indigo-500 text-white font-bold text-xs py-1.5 rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all">Simpan Link</button>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Score Level -->
                    <div class="bg-slate-50/50 dark:bg-slate-950/50 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 h-fit">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                <i data-lucide="star" class="w-5 h-5"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 dark:text-white">Level Nilai</h4>
                        </div>
                        
                        <div class="grid grid-cols-4 gap-3">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                            <label class="cursor-pointer group relative">
                                <input 
                                    type="radio" 
                                    name="score"
                                    value="<?php echo $i; ?>"
                                    class="sr-only score-radio-input"
                                    <?php echo ($indicator['hasil_kerja'] == $i) ? 'checked' : ''; ?>
                                    onchange="updateScore('<?php echo $indicator['kode_indikator']; ?>', <?php echo $i; ?>)"
                                />
                                <div class="aspect-square rounded-xl flex items-center justify-center border-2 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-400 dark:text-slate-600 font-black text-xl group-hover:border-indigo-300 dark:group-hover:border-indigo-700 group-hover:text-indigo-400 dark:group-hover:text-indigo-500 transition-all shadow-sm">
                                    <?php echo $i; ?>
                                </div>
                            </label>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-between">
                            <div id="save-status" class="text-[10px] font-bold text-green-500 hidden items-center gap-1 uppercase tracking-wider">
                                <i data-lucide="check" class="w-3 h-3"></i> Tersimpan
                            </div>
                            <button onclick="updateScore('<?php echo $indicator['kode_indikator']; ?>', 0); document.querySelectorAll('input[name=\'score\']').forEach(r => r.checked = false);" class="text-[10px] font-bold text-slate-400 hover:text-red-500 transition-colors uppercase tracking-widest flex items-center gap-1">
                                <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Reset Nilai
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Navigation -->
            <div class="bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 p-6 flex items-center justify-between">
                <div>
                    <?php if ($prevCode): ?>
                    <a href="?kode=<?php echo $prevCode; ?>" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:border-indigo-200 dark:group-hover:border-indigo-800 transition-all shadow-sm">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase">Sebelumnya</span>
                            <span class="block text-sm font-bold text-slate-600 dark:text-slate-200"><?php echo $prevCode; ?></span>
                        </div>
                    </a>
                    <?php else: ?>
                    <div class="opacity-30 flex items-center gap-3 cursor-not-allowed">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-700">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="text-center hidden md:block">
                    <a href="dashboard.php" class="text-xs font-bold text-slate-400 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors uppercase tracking-widest">
                        Kembali ke Dashboard
                    </a>
                </div>

                <div>
                    <?php if ($nextCode): ?>
                    <a href="?kode=<?php echo $nextCode; ?>" class="flex items-center gap-3 group text-right">
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase">Selanjutnya</span>
                            <span class="block text-sm font-bold text-slate-600 dark:text-slate-200"><?php echo $nextCode; ?></span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-400 dark:text-slate-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 group-hover:border-indigo-200 dark:group-hover:border-indigo-800 transition-all shadow-sm">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </div>
                    </a>
                    <?php else: ?>
                    <a href="dashboard.php" class="flex items-center gap-3 group">
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-indigo-400 uppercase">Selesai</span>
                            <span class="block text-sm font-bold text-indigo-600 dark:text-indigo-400 tracking-tight">Lihat Hasil</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-500 group-hover:text-white transition-all shadow-sm shadow-indigo-100 dark:shadow-none">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Shortcut Help Modal -->
    <div id="shortcut-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="toggleShortcutModal()"></div>
        <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden relative z-10 animate-in zoom-in duration-200">
            <div class="p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <i data-lucide="keyboard" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white">Shortcut Keyboard</h3>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between group">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Navigasi Halaman</span>
                        <div class="flex gap-1">
                            <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">←</kbd>
                            <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">→</kbd>
                        </div>
                    </div>
                    <div class="flex items-center justify-between group">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Pilih Skor (Level 1-4)</span>
                        <div class="flex gap-1">
                            <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">1</kbd>
                            <span class="text-slate-300">-</span>
                            <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">4</kbd>
                        </div>
                    </div>
                    <div class="flex items-center justify-between group">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Reset Nilai (Skor 0)</span>
                        <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">0</kbd>
                    </div>
                    <div class="flex items-center justify-between group pt-2 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-sm text-slate-500 dark:text-slate-400">Ganti Tema</span>
                        <kbd class="px-2 py-1 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md text-[10px] font-bold text-slate-600 dark:text-slate-300 shadow-sm">T</kbd>
                    </div>
                </div>

                <button onclick="toggleShortcutModal()" class="w-full mt-8 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 py-3 rounded-2xl font-bold text-sm transition-all">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleShortcutModal() {
            const modal = document.getElementById('shortcut-modal');
            modal.classList.toggle('hidden');
        }

        function toggleDrawer() {
            const drawer = document.getElementById('nav-drawer');
            const overlay = document.getElementById('drawer-overlay');
            const isOpen = drawer.classList.contains('translate-x-0');

            if (isOpen) {
                drawer.classList.remove('translate-x-0');
                drawer.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                overlay.classList.remove('opacity-100');
            } else {
                drawer.classList.remove('-translate-x-full');
                drawer.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
            }
        }

        function updateThemeIcon() {
            const icon = document.getElementById('theme-icon');
            if (document.documentElement.classList.contains('dark')) {
                icon.setAttribute('data-lucide', 'sun');
            } else {
                icon.setAttribute('data-lucide', 'moon');
            }
            lucide.createIcons();
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcon();
        }

        // Initialize icon on load
        document.addEventListener('DOMContentLoaded', updateThemeIcon);

        // --- NAVIGATION SHORTCUTS ---
        const prevCode = "<?php echo $prevCode; ?>";
        const nextCode = "<?php echo $nextCode; ?>";

        document.addEventListener('keydown', (e) => {
            // Jangan aktifkan shortcut jika sedang mengetik di input/textarea
            if (['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

            // Shortcut Navigasi
            if (e.key === 'ArrowLeft' && prevCode) {
                window.location.href = '?kode=' + prevCode;
            } else if (e.key === 'ArrowRight' && nextCode) {
                window.location.href = '?kode=' + nextCode;
            }

            // Shortcut Penilaian (0-4)
            if (['0', '1', '2', '3', '4'].includes(e.key)) {
                const score = parseInt(e.key);
                const code = "<?php echo $indicator['kode_indikator']; ?>";
                
                // Update Radio Button visual
                const radios = document.querySelectorAll('input[name="score"]');
                radios.forEach(r => {
                    r.checked = (parseInt(r.value) === score);
                });

                // Kirim ke server
                updateScore(code, score);
            }

            // Shortcut Ganti Tema (T)
            if (e.key.toLowerCase() === 't') {
                toggleTheme();
            }
        });

        function updateScore(code, score) {
            const formData = new FormData();
            formData.append('code', code);
            formData.append('score', score);

            const status = document.getElementById('save-status');
            status.classList.remove('hidden');
            status.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3 animate-spin"></i> Menyimpan...';
            lucide.createIcons();

            fetch('api_user.php?action=save_score', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    status.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i> Tersimpan otomatis';
                    lucide.createIcons();
                    setTimeout(() => status.classList.add('hidden'), 2000);
                }
            })
            .catch(err => {
                status.innerHTML = '<span class="text-red-500">Gagal menyimpan</span>';
            });
        }

        function toggleEvidenceForm() {
            const form = document.getElementById('evidence-form');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('ev-title').focus();
            }
        }

        let currentEvidences = <?php echo json_encode($evidences); ?>;

        function saveEvidence(code) {
            const title = document.getElementById('ev-title').value.trim();
            let url = document.getElementById('ev-url').value.trim();

            if (!title || !url) {
                alert('Judul dan URL harus diisi');
                return;
            }

            if (!/^https?:\/\//i.test(url)) {
                url = 'https://' + url;
            }

            const newEvidence = {
                id: Date.now(),
                title: title,
                url: url
            };

            currentEvidences.push(newEvidence);
            syncEvidences(code);
        }

        function removeEvidence(code, id) {
            if (confirm('Hapus bukti ini?')) {
                currentEvidences = currentEvidences.filter(e => e.id !== id);
                syncEvidences(code);
            }
        }

        function syncEvidences(code) {
            const formData = new FormData();
            formData.append('code', code);
            formData.append('evidences', JSON.stringify(currentEvidences));

            fetch('api_user.php?action=save_evidences', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                }
            });
        }
    </script>
</body>
</html>
