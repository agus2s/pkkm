<?php
$conn = new mysqli("localhost", "root", "@Pesantren1", "pkkm");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$code = $_GET['code'] ?? '';

// Fetch all indicator codes in order to determine prev/next
$all_codes_res = $conn->query("SELECT kode FROM indikator_kerja ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
$all_codes = [];
while ($row = $all_codes_res->fetch_assoc()) {
    $all_codes[] = $row['kode'];
}

if (!$code && !empty($all_codes)) {
    $code = $all_codes[0];
}

$currentIndex = array_search($code, $all_codes);
$prevCode = ($currentIndex > 0) ? $all_codes[$currentIndex - 1] : null;
$nextCode = ($currentIndex !== false && $currentIndex < count($all_codes) - 1) ? $all_codes[$currentIndex + 1] : null;

// Fetch current indicator details
$stmt = $conn->prepare("
    SELECT 
        i.*, 
        u.judul as unsur_judul, 
        t.judul as tugas_judul,
        t.kode as tugas_kode
    FROM indikator_kerja i
    JOIN unsur_tugas_utama u ON i.unsur_tugas_utama = u.kode
    JOIN tugas_utama t ON u.tugas_utama = t.kode
    WHERE i.kode = ?
");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$indicator = $result->fetch_assoc();

if (!$indicator) {
    die("Indikator tidak ditemukan.");
}

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Indikator - <?php echo $indicator['kode']; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .score-radio-input:checked + div {
            background-color: #4f46e5;
            border-color: #4f46e5;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen pb-12">

    <!-- Navigation Drawer -->
    <div id="nav-drawer" class="fixed inset-y-0 left-0 w-80 bg-white shadow-2xl z-50 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-slate-200 flex flex-col">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="menu" class="w-5 h-5 text-indigo-600"></i>
                Navigasi Instrumen
            </h2>
            <button onclick="toggleDrawer()" class="p-2 hover:bg-slate-200 rounded-lg text-slate-400">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            <div class="space-y-6">
                <?php foreach ($hierarchy as $t): ?>
                <div class="space-y-2">
                    <div class="flex items-start gap-2 px-2 py-1 text-xs font-black text-slate-400 uppercase tracking-widest" title="<?php echo htmlspecialchars($t['judul']); ?>">
                        <span class="text-indigo-600 shrink-0">Tugas <?php echo $t['kode']; ?></span>
                    </div>
                    <div class="space-y-1 ml-2 border-l-2 border-slate-100 pl-2">
                        <?php foreach ($t['unsurs'] as $u): ?>
                        <div class="space-y-1">
                            <div class="text-[11px] font-bold text-slate-500 py-1 px-2 flex items-start gap-2" title="<?php echo htmlspecialchars($u['judul']); ?>">
                                <span class="shrink-0 text-indigo-400">Unsur <?php echo $u['kode']; ?></span>
                            </div>
                            <div class="flex flex-wrap gap-1 ml-2 px-2">
                                <?php foreach ($u['indicators'] as $i): ?>
                                <a href="?code=<?php echo $i['kode']; ?>" 
                                   title="<?php echo htmlspecialchars($i['judul']); ?>"
                                   class="px-2 py-1 rounded-md text-[10px] font-bold transition-all 
                                   <?php echo ($i['kode'] === $code) 
                                       ? 'bg-indigo-600 text-white shadow-md' 
                                       : 'bg-slate-100 text-slate-500 hover:bg-indigo-100 hover:text-indigo-600'; ?>">
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
    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-1 sm:gap-3">
                <button onclick="toggleDrawer()" class="p-2 hover:bg-slate-100 rounded-lg text-indigo-600 transition-colors mr-1">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <a href="index.php" class="p-2 hover:bg-slate-100 rounded-full transition-colors text-slate-500 hidden sm:flex">
                    <i data-lucide="home" class="w-5 h-5"></i>
                </a>
                <h1 class="font-bold text-slate-800 text-sm sm:text-base">Detail Indikator</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded tracking-tighter sm:tracking-normal">
                    <?php echo ($currentIndex + 1); ?> / <?php echo count($all_codes); ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Breadcrumb / Context -->
        <div class="mb-6 space-y-1">
            <div class="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                <span>Tugas <?php echo $indicator['tugas_kode']; ?></span>
                <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
                <span>Unsur <?php echo $indicator['unsur_tugas_utama']; ?></span>
            </div>
            <h2 class="text-slate-500 text-sm font-medium leading-relaxed">
                <?php echo $indicator['tugas_judul']; ?> — <?php echo $indicator['unsur_judul']; ?>
            </h2>
        </div>

        <!-- Main Content Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden">
            <div class="p-8 md:p-10">
                <div class="flex flex-col md:flex-row md:items-start gap-6 mb-10">
                    <div class="bg-indigo-600 text-white text-3xl font-bold w-20 h-20 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-indigo-200">
                        <?php echo $indicator['kode']; ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3">
                            <?php echo $indicator['judul']; ?>
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                <i data-lucide="database" class="w-3 h-3 mr-1.5"></i>
                                Data Kinerja yang Diharapkan
                            </span>
                        </div>
                        <p class="mt-3 text-slate-600 leading-relaxed italic">
                            "<?php echo $indicator['data_kinerja']; ?>"
                        </p>
                    </div>
                </div>

                <!-- Section: Bukti Otentik yang Diminta -->
                <div class="mb-10 p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                            <i data-lucide="info" class="w-5 h-5"></i>
                        </div>
                        <h4 class="font-bold text-slate-800">Bukti Otentik Kualitas Kinerja</h4>
                    </div>
                    <?php 
                        $formatted_evidence = preg_replace('/^\((\d+)\)/m', '☑️ $1.', $requested_evidence);
                    ?>
                    <div class="text-slate-600 leading-normal whitespace-pre-line bg-white p-4 rounded-xl border border-slate-200/50 shadow-sm"><?php echo htmlspecialchars($formatted_evidence); ?></div>
                </div>

                <hr class="border-slate-100 mb-10">

                <div class="space-y-12">
                    <!-- Section: Bukti Otentik yang Diinput -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Section: Bukti Otentik yang Diinput -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-2">
                                <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                                    <i data-lucide="link" class="w-5 h-5"></i>
                                </div>
                                <h4 class="font-bold text-slate-800">Bukti Otentik yang Diinput</h4>
                            </div>
                            <button onclick="toggleEvidenceForm()" class="text-indigo-600 hover:text-indigo-700 font-semibold text-sm flex items-center gap-1">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Link
                            </button>
                        </div>

                        <div id="evidence-list" class="space-y-3">
                            <?php if (empty($evidences)): ?>
                                <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl text-center text-slate-400">
                                    <i data-lucide="file-x" class="w-8 h-8 mx-auto mb-2 opacity-20"></i>
                                    <p class="text-sm">Belum ada link bukti</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($evidences as $evidence): ?>
                                <div class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl group hover:border-indigo-200 hover:shadow-md transition-all">
                                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-500">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a href="<?php echo $evidence['url']; ?>" target="_blank" class="block font-medium text-slate-700 text-sm truncate hover:text-indigo-600 transition-colors">
                                            <?php echo $evidence['title']; ?>
                                        </a>
                                        <span class="text-[10px] text-slate-400 block truncate"><?php echo $evidence['url']; ?></span>
                                    </div>
                                    <button onclick="removeEvidence('<?php echo $indicator['kode']; ?>', <?php echo $evidence['id']; ?>)" class="text-slate-300 hover:text-red-500 p-2 rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Form Tambah Bukti -->
                        <div id="evidence-form" class="mt-4 hidden p-4 bg-white border border-indigo-200 rounded-2xl shadow-xl shadow-indigo-50 animate-in fade-in slide-in-from-top-2 duration-300">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <input type="text" id="ev-title" placeholder="Nama Dokumen" class="text-sm border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 border outline-none">
                                <input type="text" id="ev-url" placeholder="Link URL" class="text-sm border-slate-200 rounded-xl focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2 border outline-none">
                            </div>
                            <div class="flex gap-2 justify-end">
                                <button onclick="toggleEvidenceForm()" class="px-4 py-1.5 border border-slate-200 text-slate-600 font-bold text-xs rounded-lg hover:bg-slate-50 transition-all">Batal</button>
                                <button onclick="saveEvidence('<?php echo $indicator['kode']; ?>')" class="px-6 bg-indigo-600 text-white font-bold text-xs py-1.5 rounded-lg hover:bg-indigo-700 transition-all">Simpan Link</button>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Score Level -->
                    <div class="bg-slate-50/50 p-6 rounded-3xl border border-slate-100 h-fit">
                        <div class="flex items-center gap-2 mb-6">
                            <div class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg">
                                <i data-lucide="star" class="w-5 h-5"></i>
                            </div>
                            <h4 class="font-bold text-slate-800">Level Nilai</h4>
                        </div>
                        
                        <div class="grid grid-cols-4 gap-3">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                            <label class="cursor-pointer group">
                                <input 
                                    type="radio" 
                                    name="score"
                                    value="<?php echo $i; ?>"
                                    class="sr-only score-radio-input"
                                    <?php echo ($indicator['hasil_kinerja'] == $i) ? 'checked' : ''; ?>
                                    onchange="updateScore('<?php echo $indicator['kode']; ?>', <?php echo $i; ?>)"
                                />
                                <div class="aspect-square rounded-xl flex items-center justify-center border-2 border-slate-200 bg-white text-slate-400 font-black text-xl group-hover:border-indigo-300 group-hover:text-indigo-400 transition-all shadow-sm">
                                    <?php echo $i; ?>
                                </div>
                            </label>
                            <?php endfor; ?>
                        </div>
                        
                        <div id="save-status" class="mt-4 text-[10px] font-bold text-green-500 hidden items-center gap-1 uppercase tracking-wider">
                            <i data-lucide="check" class="w-3 h-3"></i> Tersimpan
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Navigation -->
            <div class="bg-slate-50 border-t border-slate-100 p-6 flex items-center justify-between">
                <div>
                    <?php if ($prevCode): ?>
                    <a href="?code=<?php echo $prevCode; ?>" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 group-hover:border-indigo-200 transition-all shadow-sm">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Sebelumnya</span>
                            <span class="block text-sm font-bold text-slate-600"><?php echo $prevCode; ?></span>
                        </div>
                    </a>
                    <?php else: ?>
                    <div class="opacity-30 flex items-center gap-3 cursor-not-allowed">
                        <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-300">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="text-center hidden md:block">
                    <a href="index.php" class="text-xs font-bold text-slate-400 hover:text-indigo-600 transition-colors uppercase tracking-widest">
                        Kembali ke Dashboard
                    </a>
                </div>

                <div>
                    <?php if ($nextCode): ?>
                    <a href="?code=<?php echo $nextCode; ?>" class="flex items-center gap-3 group text-right">
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Selanjutnya</span>
                            <span class="block text-sm font-bold text-slate-600"><?php echo $nextCode; ?></span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 group-hover:text-indigo-600 group-hover:border-indigo-200 transition-all shadow-sm">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </div>
                    </a>
                    <?php else: ?>
                    <a href="index.php" class="flex items-center gap-3 group">
                        <div class="hidden sm:block">
                            <span class="block text-[10px] font-bold text-indigo-400 uppercase">Selesai</span>
                            <span class="block text-sm font-bold text-indigo-600 tracking-tight">Lihat Hasil</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm shadow-indigo-100">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                        </div>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

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

        function updateScore(code, score) {
            const formData = new FormData();
            formData.append('code', code);
            formData.append('score', score);

            const status = document.getElementById('save-status');
            status.classList.remove('hidden');
            status.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3 animate-spin"></i> Menyimpan...';
            lucide.createIcons();

            fetch('api.php?action=save_score', {
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

            fetch('api.php?action=save_evidences', {
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
