<?php
$conn = new mysqli("localhost", "root", "@Pesantren1", "pkkm");

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add_tugas') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $conn->query("INSERT INTO tugas_utama (kode, judul) VALUES ('$kode', '$judul')");
            $message = "Tugas Utama berhasil ditambahkan.";
            $messageType = "success";
        } elseif ($action === 'edit_tugas') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $conn->query("UPDATE tugas_utama SET judul='$judul' WHERE kode='$kode'");
            $message = "Tugas Utama berhasil diubah.";
            $messageType = "success";
        } elseif ($action === 'delete_tugas') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $conn->query("DELETE FROM tugas_utama WHERE kode='$kode'");
            $message = "Tugas Utama berhasil dihapus.";
            $messageType = "success";
        } elseif ($action === 'add_unsur') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $tugas_utama = $conn->real_escape_string($_POST['tugas_utama']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $conn->query("INSERT INTO unsur_tugas_utama (kode, tugas_utama, judul) VALUES ('$kode', '$tugas_utama', '$judul')");
            $message = "Unsur Tugas berhasil ditambahkan.";
            $messageType = "success";
        } elseif ($action === 'edit_unsur') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $tugas_utama = $conn->real_escape_string($_POST['tugas_utama']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $conn->query("UPDATE unsur_tugas_utama SET tugas_utama='$tugas_utama', judul='$judul' WHERE kode='$kode'");
            $message = "Unsur Tugas berhasil diubah.";
            $messageType = "success";
        } elseif ($action === 'delete_unsur') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $conn->query("DELETE FROM unsur_tugas_utama WHERE kode='$kode'");
            $message = "Unsur Tugas berhasil dihapus.";
            $messageType = "success";
        } elseif ($action === 'add_indikator') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $unsur_tugas_utama = $conn->real_escape_string($_POST['unsur_tugas_utama']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $data_kinerja = $conn->real_escape_string($_POST['data_kinerja']);
            $bukti_otentik = $conn->real_escape_string($_POST['bukti_otentik'] ?? '[]');
            $conn->query("INSERT INTO indikator_kerja (kode, unsur_tugas_utama, judul, data_kinerja, hasil_kinerja, bukti_otentik) VALUES ('$kode', '$unsur_tugas_utama', '$judul', '$data_kinerja', 0, '$bukti_otentik')");
            $message = "Indikator berhasil ditambahkan.";
            $messageType = "success";
        } elseif ($action === 'edit_indikator') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $unsur_tugas_utama = $conn->real_escape_string($_POST['unsur_tugas_utama']);
            $judul = $conn->real_escape_string($_POST['judul']);
            $data_kinerja = $conn->real_escape_string($_POST['data_kinerja']);
            $bukti_otentik = $conn->real_escape_string($_POST['bukti_otentik']);
            $conn->query("UPDATE indikator_kerja SET unsur_tugas_utama='$unsur_tugas_utama', judul='$judul', data_kinerja='$data_kinerja', bukti_otentik='$bukti_otentik' WHERE kode='$kode'");
            $message = "Indikator berhasil diubah.";
            $messageType = "success";
        } elseif ($action === 'delete_indikator') {
            $kode = $conn->real_escape_string($_POST['kode']);
            $conn->query("DELETE FROM indikator_kerja WHERE kode='$kode'");
            $message = "Indikator berhasil dihapus.";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $messageType = "error";
    }
}

$tugas_utama = $conn->query("SELECT * FROM tugas_utama ORDER BY kode ASC");
$tugas_list = [];
while($row = $tugas_utama->fetch_assoc()) { $tugas_list[] = $row; }

// Filter Unsur berdasarkan Tugas Utama
$f_tugas = $_GET['f_tugas'] ?? '';
$where_unsur = $f_tugas ? "WHERE tugas_utama = '" . $conn->real_escape_string($f_tugas) . "'" : "";
$unsur_tugas_utama = $conn->query("SELECT * FROM unsur_tugas_utama $where_unsur ORDER BY kode ASC");
$unsur_list = [];
while($row = $unsur_tugas_utama->fetch_assoc()) { $unsur_list[] = $row; }

// Filter Indikator berdasarkan Unsur Tugas Utama
$f_unsur = $_GET['f_unsur'] ?? '';
$where_indikator = $f_unsur ? "WHERE unsur_tugas_utama = '" . $conn->real_escape_string($f_unsur) . "'" : "";
$indikator_kerja = $conn->query("SELECT * FROM indikator_kerja $where_indikator ORDER BY kode ASC");
$indikator_list = [];
while($row = $indikator_kerja->fetch_assoc()) { $indikator_list[] = $row; }

$active_tab = $_GET['tab'] ?? 'tugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor Instrumen PKKM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a1a1a1; }
    </style>
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
            // Reset form when closing
            const formAction = document.getElementById('form_action').value;
            if (formAction.startsWith('edit_')) {
                const tab = formAction.split('_')[1];
                cancelEdit('add_' + tab);
            }
        }

        function fillEdit(action, data) {
            document.getElementById('form_action').value = action;
            const submitBtn = document.getElementById('form_submit_btn');
            if (submitBtn) submitBtn.innerText = 'Simpan Perubahan';
            
            const cancelBtn = document.getElementById('form_cancel_btn');
            if (cancelBtn) cancelBtn.classList.remove('hidden');
            
            for (const key in data) {
                const el = document.getElementById('input_' + key);
                if (el) {
                    el.value = data[key];
                    if (key === 'kode') el.readOnly = true;
                }
            }

            if (action === 'edit_indikator') {
                openModal('modal_indikator');
            } else {
                window.scrollTo(0, 0);
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-6 right-6 z-[100] p-4 rounded-2xl shadow-2xl flex items-center gap-3 transform transition-all duration-500 translate-y-12 opacity-0 ${type === 'success' ? 'bg-emerald-600 text-white shadow-emerald-200' : 'bg-rose-600 text-white shadow-rose-200'}`;
            
            const icon = type === 'success' ? 'check-circle' : 'alert-circle';
            toast.innerHTML = `
                <div class="bg-white/20 p-1.5 rounded-lg">
                    <i data-lucide="${icon}" class="w-5 h-5"></i>
                </div>
                <span class="font-bold text-sm pr-2">${message}</span>
            `;
            
            document.body.appendChild(toast);
            lucide.createIcons();

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-y-12', 'opacity-0');
            }, 10);

            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-y-12', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        function cancelEdit(defaultAction) {
            document.getElementById('form_action').value = defaultAction;
            document.getElementById('form_submit_btn').innerText = 'Tambah Data';
            const cancelBtn = document.getElementById('form_cancel_btn');
            if (cancelBtn) cancelBtn.classList.add('hidden');
            
            const inputs = document.querySelectorAll('form input, form textarea, form select');
            inputs.forEach(el => {
                if (el.name !== 'action' && el.type !== 'submit' && el.type !== 'button') {
                    el.value = '';
                    el.readOnly = false;
                }
            });
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-800 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Editor Instrumen PKKM</h1>
                <p class="text-slate-600 text-sm">Kelola Tugas Utama, Unsur Tugas, dan Indikator Kinerja</p>
            </div>
            <a href="index.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors shadow-sm">
                &larr; Kembali ke Aplikasi
            </a>
        </div>

        <?php if ($message): ?>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    showToast("<?= htmlspecialchars($message) ?>", "<?= $messageType ?>");
                });
            </script>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex border-b border-slate-300 mb-6">
            <a href="?tab=tugas" class="px-6 py-3 font-semibold text-sm <?= $active_tab === 'tugas' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-white rounded-t-lg' : 'text-slate-600 hover:text-slate-800' ?>">Tugas Utama</a>
            <a href="?tab=unsur" class="px-6 py-3 font-semibold text-sm <?= $active_tab === 'unsur' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-white rounded-t-lg' : 'text-slate-600 hover:text-slate-800' ?>">Unsur Tugas Utama</a>
            <a href="?tab=indikator" class="px-6 py-3 font-semibold text-sm <?= $active_tab === 'indikator' ? 'border-b-2 border-indigo-600 text-indigo-700 bg-white rounded-t-lg' : 'text-slate-600 hover:text-slate-800' ?>">Indikator Kerja</a>
        </div>

        <?php if ($active_tab === 'tugas'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 h-fit">
                <h2 class="text-lg font-bold mb-4">Form Tugas Utama</h2>
                <form method="POST">
                    <input type="hidden" name="action" id="form_action" value="add_tugas">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode</label>
                        <input type="text" name="kode" id="input_kode" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul Tugas</label>
                        <textarea name="judul" id="input_judul" required rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" id="form_submit_btn" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">Tambah Data</button>
                        <button type="button" id="form_cancel_btn" onclick="cancelEdit('add_tugas')" class="hidden bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-300">Batal</button>
                    </div>
                </form>
            </div>
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-300 overflow-auto max-h-[600px] custom-scrollbar">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Kode</th>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($tugas_list as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 align-top">
                                <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded-md border border-indigo-100 whitespace-nowrap">
                                    <?= htmlspecialchars($row['kode']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top"><?= htmlspecialchars($row['judul']) ?></td>
                            <td class="px-4 py-3 align-top flex gap-2">
                                <button onclick='fillEdit("edit_tugas", <?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-800"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <form method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                    <input type="hidden" name="action" value="delete_tugas">
                                    <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i data-lucide="trash" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($active_tab === 'unsur'): ?>
        <!-- Filter Bar Unsur -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-300 flex flex-wrap items-center gap-6 mb-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900 leading-tight">Unsur Tugas Utama</h2>
                <p class="text-slate-500 text-xs font-medium">Total <?= count($unsur_list) ?> Data</p>
            </div>
            
            <div class="flex items-center gap-3 md:border-l md:border-slate-200 md:pl-6">
                <div class="bg-indigo-50 p-2 rounded-lg text-indigo-600 hidden sm:block">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5 ml-1">Filter Tugas Utama</label>
                    <div class="flex items-center gap-2">
                        <select 
                            onchange="location.href='?tab=unsur&f_tugas=' + this.value" 
                            class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50 font-semibold text-slate-700 min-w-[200px] max-w-xs"
                        >
                            <option value="">Semua Tugas</option>
                            <?php foreach($tugas_list as $t): ?>
                            <option value="<?= $t['kode'] ?>" <?= $f_tugas == $t['kode'] ? 'selected' : '' ?>>
                                Tugas <?= $t['kode'] ?>: <?= htmlspecialchars(substr($t['judul'], 0, 40)) ?>...
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($f_tugas): ?>
                            <a href="?tab=unsur" class="p-1.5 text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Hapus Filter">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1 bg-white p-6 rounded-xl shadow-sm border border-slate-200 h-fit">
                <h2 class="text-lg font-bold mb-4">Form Unsur Tugas</h2>
                <form method="POST">
                    <input type="hidden" name="action" id="form_action" value="add_unsur">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode</label>
                        <input type="text" name="kode" id="input_kode" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tugas Utama</label>
                        <select name="tugas_utama" id="input_tugas_utama" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Pilih --</option>
                            <?php foreach($tugas_list as $t): ?>
                            <option value="<?= $t['kode'] ?>"><?= $t['kode'] ?> - <?= htmlspecialchars(substr($t['judul'], 0, 30)) ?>...</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Judul Unsur</label>
                        <textarea name="judul" id="input_judul" required rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" id="form_submit_btn" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700">Tambah Data</button>
                        <button type="button" id="form_cancel_btn" onclick="cancelEdit('add_unsur')" class="hidden bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-300">Batal</button>
                    </div>
                </form>
            </div>
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-slate-300 overflow-auto max-h-[600px] custom-scrollbar">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Kode</th>
                            <th class="px-4 py-3 font-medium">Tugas</th>
                            <th class="px-4 py-3 font-medium">Judul</th>
                            <th class="px-4 py-3 font-medium w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($unsur_list as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 align-top">
                                <span class="font-bold text-indigo-700 bg-indigo-50 px-2 py-1 rounded-md border border-indigo-100 whitespace-nowrap">
                                    <?= htmlspecialchars($row['kode']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top"><?= htmlspecialchars($row['tugas_utama']) ?></td>
                            <td class="px-4 py-3 align-top"><?= htmlspecialchars($row['judul']) ?></td>
                            <td class="px-4 py-3 align-top flex gap-2">
                                <button onclick='fillEdit("edit_unsur", <?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-800"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <form method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                    <input type="hidden" name="action" value="delete_unsur">
                                    <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i data-lucide="trash" class="w-4 h-4"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($active_tab === 'indikator'): ?>
        <div class="flex flex-col gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-300 flex flex-wrap justify-between items-center gap-4">
                <div class="flex flex-wrap items-center gap-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 leading-tight">Indikator Kerja</h2>
                        <p class="text-slate-500 text-xs font-medium">Total <?= count($indikator_list) ?> Data</p>
                    </div>
                    
                    <div class="flex items-center gap-3 md:border-l md:border-slate-200 md:pl-6">
                        <div class="bg-indigo-50 p-2 rounded-lg text-indigo-600 hidden sm:block">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                        </div>
                        <div class="flex flex-col">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5 ml-1">Filter Unsur</label>
                            <div class="flex items-center gap-2">
                                <select 
                                    onchange="location.href='?tab=indikator&f_unsur=' + this.value" 
                                    class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-indigo-500 bg-slate-50 font-semibold text-slate-700 min-w-[200px] max-w-xs"
                                >
                                    <option value="">Semua Unsur</option>
                                    <?php 
                                    $all_unsur_res = $conn->query("SELECT * FROM unsur_tugas_utama ORDER BY kode ASC");
                                    while($u = $all_unsur_res->fetch_assoc()): 
                                    ?>
                                    <option value="<?= $u['kode'] ?>" <?= $f_unsur == $u['kode'] ? 'selected' : '' ?>>
                                        <?= $u['kode'] ?> - <?= htmlspecialchars(substr($u['judul'], 0, 40)) ?>...
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <?php if($f_unsur): ?>
                                    <a href="?tab=indikator" class="p-1.5 text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Hapus Filter">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <button 
                    onclick="cancelEdit('add_indikator'); openModal('modal_indikator')"
                    class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all flex items-center gap-2"
                >
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                    Tambah Indikator
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-300 overflow-auto max-h-[700px] custom-scrollbar">
                <table class="w-full text-left text-sm min-w-[600px]">
                    <thead class="bg-slate-50 border-b border-slate-300 text-slate-600">
                        <tr>
                            <th class="px-6 py-4 font-bold">Kode</th>
                            <th class="px-6 py-4 font-bold">Unsur</th>
                            <th class="px-6 py-4 font-bold">Judul Indikator</th>
                            <th class="px-6 py-4 font-bold">Data Kinerja</th>
                            <th class="px-6 py-4 font-bold w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($indikator_list as $row): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 align-top">
                                <span class="font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-100 whitespace-nowrap text-xs">
                                    <?= htmlspecialchars($row['kode']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top font-medium text-slate-700"><?= htmlspecialchars($row['unsur_tugas_utama']) ?></td>
                            <td class="px-6 py-4 align-top text-slate-700 leading-relaxed"><?= htmlspecialchars($row['judul']) ?></td>
                            <td class="px-6 py-4 align-top text-slate-500 italic text-xs leading-relaxed"><?= htmlspecialchars($row['data_kinerja']) ?></td>
                            <td class="px-6 py-4 align-top flex gap-3">
                                <button onclick='fillEdit("edit_indikator", <?= json_encode($row) ?>)' class="text-blue-600 hover:text-blue-800 p-1 bg-blue-50 rounded" title="Edit">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                                <form method="POST" onsubmit="return confirm('Yakin hapus indikator ini?')">
                                    <input type="hidden" name="action" value="delete_indikator">
                                    <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 p-1 bg-red-50 rounded" title="Hapus">
                                        <i data-lucide="trash" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Modal Form Indikator Kerja -->
    <div id="modal_indikator" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-slate-900">Form Indikator Kerja</h2>
                <button onclick="closeModal('modal_indikator')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" id="form_action" value="add_indikator">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kode Indikator</label>
                        <input type="text" name="kode" id="input_kode" placeholder="Contoh: 1.1.1" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Unsur Tugas Utama</label>
                        <select name="unsur_tugas_utama" id="input_unsur_tugas_utama" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-slate-50">
                            <option value="">-- Pilih Unsur --</option>
                            <?php foreach($unsur_list as $u): ?>
                            <option value="<?= $u['kode'] ?>"><?= $u['kode'] ?> - <?= htmlspecialchars(substr($u['judul'], 0, 50)) ?>...</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Judul Indikator</label>
                        <textarea name="judul" id="input_judul" required rows="2" placeholder="Masukkan deskripsi indikator..." class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Data Kinerja yang Diharapkan</label>
                        <textarea name="data_kinerja" id="input_data_kinerja" rows="3" placeholder="Dokumen atau bukti yang diperlukan..." class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Bukti Otentik Kualitas Kinerja</label>
                        <textarea name="bukti_otentik" id="input_bukti_otentik" rows="3" placeholder="Masukkan bukti otentik kualitas kinerja..." class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"></textarea>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="button" onclick="closeModal('modal_indikator')" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl text-sm font-bold hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" id="form_submit_btn" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition-all">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    </div>
    <script>
        lucide.createIcons();
        
        // Mempertahankan tab setelah submit
        if(window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
