<?php
require_once 'db.php';
require_once 'includes/header.php';

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

$tugas_utama = $conn->query("SELECT * FROM tugas_utama ORDER BY CAST(kode AS UNSIGNED) ASC, kode ASC");
$tugas_list = [];
while($row = $tugas_utama->fetch_assoc()) { $tugas_list[] = $row; }

// Filter Unsur berdasarkan Tugas Utama
$f_tugas = $_GET['f_tugas'] ?? '';
$where_unsur = $f_tugas ? "WHERE tugas_utama = '" . $conn->real_escape_string($f_tugas) . "'" : "";
$unsur_tugas_utama = $conn->query("SELECT * FROM unsur_tugas_utama $where_unsur ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
$unsur_list = [];
while($row = $unsur_tugas_utama->fetch_assoc()) { $unsur_list[] = $row; }

// Filter Indikator berdasarkan Unsur Tugas Utama
$f_unsur = $_GET['f_unsur'] ?? '';
$where_indikator = $f_unsur ? "WHERE unsur_tugas_utama = '" . $conn->real_escape_string($f_unsur) . "'" : "";
$indikator_kerja = $conn->query("SELECT * FROM indikator_kerja $where_indikator ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1) AS UNSIGNED)");
$indikator_list = [];
while($row = $indikator_kerja->fetch_assoc()) { $indikator_list[] = $row; }

$active_tab = $_GET['tab'] ?? 'tugas';
?>


<script>
    function openModal(id) {
        const modalElement = document.getElementById(id);
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }

    function closeModal(id) {
        const modalElement = document.getElementById(id);
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
        // Reset form when closing
        const formAction = document.getElementById('form_action');
        if (formAction && formAction.value.startsWith('edit_')) {
            const tab = formAction.value.split('_')[1];
            cancelEdit('add_' + tab);
        }
    }

    function fillEdit(action, data) {
        if (action === 'edit_indikator') {
            const modalFormAction = document.getElementById('modal_form_action');
            if (modalFormAction) modalFormAction.value = action;
            
            const submitBtn = document.getElementById('modal_form_submit_btn');
            if (submitBtn) submitBtn.innerText = 'Simpan Perubahan';
            
            for (const key in data) {
                const el = document.getElementById('modal_input_' + key);
                if (el) {
                    el.value = data[key];
                    if (key === 'kode') el.readOnly = true;
                }
            }
            openModal('modal_indikator');
        } else if (action === 'edit_unsur') {
            const modalFormAction = document.getElementById('modal_unsur_form_action');
            if (modalFormAction) modalFormAction.value = action;
            
            const submitBtn = document.getElementById('modal_unsur_submit_btn');
            if (submitBtn) submitBtn.innerText = 'Simpan Perubahan';
            
            for (const key in data) {
                const el = document.getElementById('modal_input_unsur_' + key);
                if (el) {
                    el.value = data[key];
                    if (key === 'kode') el.readOnly = true;
                }
            }
            openModal('modal_unsur');
        } else {
            const formAction = document.getElementById('form_action');
            if (formAction) formAction.value = action;
            
            const submitBtn = document.getElementById('form_submit_btn');
            if (submitBtn) submitBtn.innerText = 'Simpan Perubahan';
            
            const cancelBtn = document.getElementById('form_cancel_btn');
            if (cancelBtn) cancelBtn.style.display = 'block';
            
            for (const key in data) {
                const el = document.getElementById('input_' + key);
                if (el) {
                    el.value = data[key];
                    if (key === 'kode') el.readOnly = true;
                }
            }
            window.scrollTo(0, 0);
        }
    }

    function showToast(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            // Fallback if header toast function is not available
            alert(message);
        }
    }

    function cancelEdit(defaultAction) {
        if (defaultAction === 'add_indikator') {
            const modalFormAction = document.getElementById('modal_form_action');
            if (modalFormAction) {
                modalFormAction.value = defaultAction;
                const submitBtn = document.getElementById('modal_form_submit_btn');
                if (submitBtn) submitBtn.innerText = 'Tambah Data';
                
                const inputs = document.querySelectorAll('#modal_indikator input, #modal_indikator textarea, #modal_indikator select');
                inputs.forEach(el => {
                    if (el.name !== 'action') {
                        el.value = '';
                        el.readOnly = false;
                    }
                });
            }
        } else if (defaultAction === 'add_unsur') {
            const modalFormAction = document.getElementById('modal_unsur_form_action');
            if (modalFormAction) {
                modalFormAction.value = defaultAction;
                const submitBtn = document.getElementById('modal_unsur_submit_btn');
                if (submitBtn) submitBtn.innerText = 'Tambah Data';
                
                const inputs = document.querySelectorAll('#modal_unsur input, #modal_unsur textarea, #modal_unsur select');
                inputs.forEach(el => {
                    if (el.name !== 'action') {
                        el.value = '';
                        el.readOnly = false;
                    }
                });
            }
        } else {
            const formAction = document.getElementById('form_action');
            if (formAction) {
                formAction.value = defaultAction;
                const submitBtn = document.getElementById('form_submit_btn');
                if (submitBtn) submitBtn.innerText = 'Tambah Data';
                
                const cancelBtn = document.getElementById('form_cancel_btn');
                if (cancelBtn) cancelBtn.style.display = 'none';
                
                const inputs = document.querySelectorAll('form:not(#modal_indikator form) input, form:not(#modal_indikator form) textarea, form:not(#modal_indikator form) select');
                inputs.forEach(el => {
                    if (el.name !== 'action' && el.type !== 'submit' && el.type !== 'button') {
                        el.value = '';
                        el.readOnly = false;
                    }
                });
            }
        }
    }
</script>

    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="h3 mb-1">Editor Instrumen PKKM</h1>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    showToast("<?= htmlspecialchars($message) ?>", "<?= $messageType ?>");
                });
            </script>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'tugas' ? 'active' : '' ?>" href="?tab=tugas">
                    <i class="bi bi-folder me-2"></i>Tugas Utama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'unsur' ? 'active' : '' ?>" href="?tab=unsur">
                    <i class="bi bi-folder2-open me-2"></i>Unsur Tugas Utama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'indikator' ? 'active' : '' ?>" href="?tab=indikator">
                    <i class="bi bi-list-check me-2"></i>Indikator Kerja
                </a>
            </li>
        </ul>

        <?php if ($active_tab === 'tugas'): ?>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0">Form Tugas Utama</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" id="form_action" value="add_tugas">
                            <div class="mb-3">
                                <label for="input_kode" class="form-label">Kode</label>
                                <input type="text" name="kode" id="input_kode" required class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="input_judul" class="form-label">Judul Tugas</label>
                                <textarea name="judul" id="input_judul" required rows="3" class="form-control"></textarea>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" id="form_submit_btn" class="btn btn-primary flex-fill">Tambah Data</button>
                                <button type="button" id="form_cancel_btn" onclick="cancelEdit('add_tugas')" class="btn btn-secondary" style="display: none;">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0">Daftar Tugas Utama</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive custom-scrollbar" style="max-height: 600px;">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Judul</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tugas_list as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-indigo">
                                                <?= htmlspecialchars($row['kode']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['judul']) ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button onclick='fillEdit("edit_tugas", <?= json_encode($row) ?>)' class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Yakin hapus data ini?')" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_tugas">
                                                    <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($active_tab === 'unsur'): ?>
        <!-- Header Bar Unsur -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-0">Unsur Tugas Utama</h5>
                        <small class="text-muted">Total <?= count($unsur_list) ?> Data</small>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-funnel text-primary"></i>
                            </span>
                            <select 
                                onchange="location.href='?tab=unsur&f_tugas=' + this.value" 
                                class="form-select"
                            >
                                <option value="">Semua Tugas</option>
                                <?php foreach($tugas_list as $t): ?>
                                <option value="<?= $t['kode'] ?>" <?= (string)$f_tugas === (string)$t['kode'] ? 'selected' : '' ?>>
                                    Tugas <?= $t['kode'] ?>: <?= htmlspecialchars(substr($t['judul'], 0, 40)) ?>...
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if($f_tugas): ?>
                                <a href="?tab=unsur" class="btn btn-outline-danger" title="Hapus Filter">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button 
                            onclick="cancelEdit('add_unsur'); openModal('modal_unsur')"
                            class="btn btn-primary w-100 w-md-auto"
                        >
                            <i class="bi bi-plus-circle me-2"></i>
                            Tambah Unsur
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive custom-scrollbar" style="max-height: 700px;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Kode</th>
                                <th style="width: 100px;">Tugas</th>
                                <th>Judul Unsur</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($unsur_list as $row): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-indigo">
                                        <?= htmlspecialchars($row['kode']) ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small">Tugas</span> <?= htmlspecialchars($row['tugas_utama']) ?></td>
                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button onclick='fillEdit("edit_unsur", <?= json_encode($row) ?>)' class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Yakin hapus data ini?')" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_unsur">
                                            <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($active_tab === 'indikator'): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-0">Indikator Kerja</h5>
                        <small class="text-muted">Total <?= count($indikator_list) ?> Data</small>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-funnel text-primary"></i>
                            </span>
                            <select 
                                onchange="location.href='?tab=indikator&f_unsur=' + this.value" 
                                class="form-select"
                            >
                                <option value="">Semua Unsur</option>
                                <?php 
                                $all_unsur_res = $conn->query("SELECT * FROM unsur_tugas_utama ORDER BY CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED), CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1) AS UNSIGNED)");
                                while($u = $all_unsur_res->fetch_assoc()): 
                                ?>
                                <option value="<?= $u['kode'] ?>" <?= (string)$f_unsur === (string)$u['kode'] ? 'selected' : '' ?>>
                                    <?= $u['kode'] ?> - <?= htmlspecialchars(substr($u['judul'], 0, 40)) ?>...
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if($f_unsur): ?>
                                <a href="?tab=indikator" class="btn btn-outline-danger" title="Hapus Filter">
                                    <i class="bi bi-x-circle"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button 
                            onclick="cancelEdit('add_indikator'); openModal('modal_indikator')"
                            class="btn btn-primary w-100 w-md-auto"
                        >
                            <i class="bi bi-plus-circle me-2"></i>
                            Tambah Indikator
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive custom-scrollbar" style="max-height: 700px;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Unsur</th>
                                <th>Judul Indikator</th>
                                <th>Data Kinerja</th>
                                <th style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($indikator_list as $row): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-indigo small">
                                        <?= htmlspecialchars($row['kode']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['unsur_tugas_utama']) ?></td>
                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($row['data_kinerja']) ?></small></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button onclick='fillEdit("edit_indikator", <?= json_encode($row) ?>)' class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Yakin hapus indikator ini?')" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_indikator">
                                            <input type="hidden" name="kode" value="<?= $row['kode'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Modal Unsur -->
    <div class="modal fade" id="modal_unsur" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-custom">
                    <h5 class="modal-title text-white">Form Unsur Tugas Utama</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="modal_unsur_form_action" value="add_unsur">
                        <div class="mb-3">
                            <label for="modal_input_unsur_kode" class="form-label">Kode Unsur</label>
                            <input type="text" name="kode" id="modal_input_unsur_kode" required class="form-control" placeholder="Contoh: 1.1">
                        </div>
                        <div class="mb-3">
                            <label for="modal_input_unsur_tugas_utama" class="form-label">Tugas Utama</label>
                            <select name="tugas_utama" id="modal_input_unsur_tugas_utama" required class="form-select">
                                <option value="">-- Pilih Tugas Utama --</option>
                                <?php foreach($tugas_list as $t): ?>
                                <option value="<?= $t['kode'] ?>"><?= $t['kode'] ?> - <?= htmlspecialchars(substr($t['judul'], 0, 50)) ?>...</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="modal_input_unsur_judul" class="form-label">Judul Unsur</label>
                            <textarea name="judul" id="modal_input_unsur_judul" required rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="modal_unsur_submit_btn" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Form Indikator Kerja -->
    <div class="modal fade" id="modal_indikator" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-custom">
                    <h5 class="modal-title text-white">Form Indikator Kerja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="modal_form_action" value="add_indikator">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_input_kode" class="form-label">Kode Indikator</label>
                                <input type="text" name="kode" id="modal_input_kode" placeholder="Contoh: 1.1.1" required class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_input_unsur_tugas_utama" class="form-label">Unsur Tugas Utama</label>
                                <select name="unsur_tugas_utama" id="modal_input_unsur_tugas_utama" required class="form-select">
                                    <option value="">-- Pilih Unsur --</option>
                                    <?php foreach($unsur_list as $u): ?>
                                    <option value="<?= $u['kode'] ?>"><?= $u['kode'] ?> - <?= htmlspecialchars(substr($u['judul'], 0, 50)) ?>...</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="modal_input_judul" class="form-label">Judul Indikator</label>
                                <textarea name="judul" id="modal_input_judul" required rows="2" placeholder="Masukkan deskripsi indikator..." class="form-control"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_input_data_kinerja" class="form-label">Data Kinerja yang Diharapkan</label>
                                <textarea name="data_kinerja" id="modal_input_data_kinerja" rows="3" placeholder="Dokumen atau bukti yang diperlukan..." class="form-control"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_input_bukti_otentik" class="form-label">Bukti Otentik Kualitas Kinerja</label>
                                <textarea name="bukti_otentik" id="modal_input_bukti_otentik" rows="3" placeholder="Masukkan bukti otentik kualitas kinerja..." class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="modal_form_submit_btn" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div> <!-- Closing container -->

<?php
require_once 'includes/footer.php';
$conn->close();
?>
