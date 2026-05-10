<?php
require_once 'db.php';
require_once 'includes/header.php';
?>

<div id="app-root">
    <!-- Loading State -->
    <div class="d-flex align-items-center justify-content-center" style="min-height: 400px;">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Memuat instrumen...</p>
        </div>
    </div>
</div>

<script>
    // --- DATA & STATE ---
    let pkkmData = [];
    let state = {
        activeTab: 1, // tugas_utama id
        scores: {}
    };

    // --- ACTIONS ---
    function setActiveTab(tabId) {
        state.activeTab = tabId;
        renderApp();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function calculateStats() {
        let totalIndicators = 0;
        let answeredIndicators = 0;
        let totalScore = 0;
        let taskStats = [];

        pkkmData.forEach(task => {
            let taskTotalIndicators = 0;
            let taskAnsweredIndicators = 0;
            let taskTotalScore = 0;

            task.subTasks.forEach(sub => {
                sub.indicators.forEach(ind => {
                    totalIndicators++;
                    taskTotalIndicators++;
                    if (state.scores[ind.code]) {
                        answeredIndicators++;
                        taskAnsweredIndicators++;
                        totalScore += state.scores[ind.code];
                        taskTotalScore += state.scores[ind.code];
                    }
                });
            });

            taskStats.push({
                id: task.id,
                title: task.title,
                score: taskTotalScore,
                max: taskTotalIndicators * 4,
                progress: taskTotalIndicators > 0 ? (taskAnsweredIndicators / taskTotalIndicators) * 100 : 0
            });
        });

        const maxScore = totalIndicators * 4;
        const percentage = maxScore > 0 ? ((totalScore / maxScore) * 100).toFixed(1) : 0;
        
        let grade = "Kurang";
        let badgeClass = "bg-danger";
        if (percentage >= 91) { grade = "Amat Baik"; badgeClass = "bg-success"; }
        else if (percentage >= 76) { grade = "Baik"; badgeClass = "bg-primary"; }
        else if (percentage >= 61) { grade = "Cukup"; badgeClass = "bg-warning"; }

        return { totalIndicators, answeredIndicators, totalScore, maxScore, percentage, grade, badgeClass, taskStats };
    }

    async function resetAll() {
        if (!confirm('Apakah Anda yakin ingin menghapus semua nilai dan bukti yang telah diinput? Tindakan ini tidak dapat dibatalkan.')) return;
        
        try {
            const response = await fetch('api.php?action=reset_all');
            const result = await response.json();
            if (result.status === 'success') {
                state.scores = {};
                pkkmData.forEach(task => {
                    task.subTasks.forEach(sub => {
                        sub.indicators.forEach(ind => {
                            ind.score = 0;
                            ind.evidences = [];
                        });
                    });
                });
                renderApp();
                alert('Semua nilai telah direset.');
            }
        } catch (error) {
            console.error('Reset error:', error);
            alert('Gagal mereset nilai.');
        }
    }

    // --- COMPONENTS ---
    function renderTabs() {
        const tabsHtml = pkkmData.map(task => `
            <li class="nav-item">
                <button
                    onclick="setActiveTab(${task.id})"
                    class="nav-link ${state.activeTab === task.id ? 'active fw-bold' : ''}"
                >
                    Tugas ${task.id}
                </button>
            </li>
        `).join('');

        return `
            <div class="card mb-4 shadow-sm">
                <div class="card-body p-2">
                    <ul class="nav nav-pills nav-fill">
                        ${tabsHtml}
                        <li class="nav-item">
                            <button 
                                onclick="setActiveTab('summary')"
                                class="nav-link ${state.activeTab === 'summary' ? 'active fw-bold' : ''}"
                            >
                                <i class="bi bi-bar-chart-fill me-1"></i> Rekap Akhir
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        `;
    }

    function renderMenuContent(task) {
        const cardsHtml = task.subTasks.map(subTask => {
            const firstCode = subTask.indicators.length > 0 ? subTask.indicators[0].code : '';
            const total = subTask.indicators.length;
            const done = subTask.indicators.filter(i => state.scores[i.code]).length;
            const progress = total > 0 ? (done / total) * 100 : 0;
            const isDone = done === total;

            return `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0 border-top border-4 ${isDone ? 'border-success' : 'border-primary'}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge ${isDone ? 'bg-success' : 'bg-light text-primary'} border">Unsur ${subTask.code}</span>
                                <small class="text-muted fw-bold">
                                    <i class="bi ${isDone ? 'bi-check-circle-fill text-success' : 'bi-circle'}"></i>
                                    ${done}/${total}
                                </small>
                            </div>
                            <h6 class="card-title fw-bold mb-3">${subTask.title}</h6>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Progress</span>
                                    <span>${Math.round(progress)}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar ${isDone ? 'bg-success' : 'bg-primary'}" role="progressbar" style="width: ${progress}%"></div>
                                </div>
                            </div>
                            
                            <a href="view_indicator.php?code=${firstCode}" class="btn btn-sm ${isDone ? 'btn-outline-success' : 'btn-outline-primary'} w-100 fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> Buka Instrumen
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="mb-4">
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <h4 class="fw-bold mb-1 text-dark">Tugas Utama ${task.id}</h4>
                        <p class="text-muted mb-0 small">${task.title}</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-white text-dark border p-2 shadow-sm">
                            <span class="text-muted small me-2">TOTAL UNSUR:</span>
                            <span class="h6 mb-0 fw-bold">${task.subTasks.length}</span>
                        </span>
                    </div>
                </div>

                <div class="row">
                    ${cardsHtml}
                </div>
            </div>
        `;
    }

    function renderSummary() {
        const stats = calculateStats();
        return `
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 text-center">
                    <i class="bi bi-award-fill h1"></i>
                    <h3 class="fw-bold mb-1">Hasil Penilaian Kinerja</h3>
                    <p class="mb-0 opacity-75">Rekapitulasi total capaian instrumen PKKM</p>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Progres</small>
                                <span class="h4 fw-bold mb-0">${stats.answeredIndicators} / ${stats.totalIndicators}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Skor Total</small>
                                <span class="h4 fw-bold mb-0 text-primary">${stats.totalScore}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Maksimal</small>
                                <span class="h4 fw-bold mb-0">${stats.maxScore}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-3 border rounded bg-light text-center">
                                <small class="text-muted d-block text-uppercase fw-bold">Persentase</small>
                                <span class="h4 fw-bold mb-0 text-primary">${stats.percentage}%</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 rounded border-2 border-dashed bg-light text-center mb-4">
                        <small class="text-muted d-block text-uppercase fw-bold mb-2">Predikat Kinerja</small>
                        <h1 class="display-3 fw-bold mb-0 text-primary">${stats.grade}</h1>
                    </div>

                    <h5 class="fw-bold mb-3">
                        <i class="bi bi-list-check me-2"></i> Rekap Per Tugas Utama
                    </h5>
                    <div class="list-group mb-4">
                        ${stats.taskStats.map(task => `
                            <div class="list-group-item p-3 shadow-sm mb-2 border rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded p-2 me-3 fw-bold" style="width: 40px; text-align:center;">
                                            ${task.id}
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">${task.title}</h6>
                                            <small class="text-muted">Progres: ${Math.round(task.progress)}%</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="h5 mb-0 fw-bold">
                                            <span class="text-primary">${task.score}</span>
                                            <span class="text-muted small">/ ${task.max}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button onclick="window.print()" class="btn btn-dark px-4 py-2 fw-bold">
                            <i class="bi bi-printer me-1"></i> Cetak Hasil
                        </button>
                        <button onclick="setActiveTab(1)" class="btn btn-outline-secondary px-4 py-2 fw-bold">
                            Kembali ke Instrumen
                        </button>
                        <button onclick="resetAll()" class="btn btn-outline-danger px-4 py-2 fw-bold">
                            <i class="bi bi-trash me-1"></i> Reset Semua Nilai
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function renderApp() {
        const root = document.getElementById('app-root');
        
        let content = '';
        if (state.activeTab === 'summary') {
            content = renderSummary();
        } else {
            const activeTask = pkkmData.find(t => t.id === state.activeTab);
            if (activeTask) {
                content = renderMenuContent(activeTask);
            }
        }

        root.innerHTML = `
            ${renderTabs()}
            <main>
                ${content}
            </main>
        `;
    }

    // --- INIT ---
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const response = await fetch('api.php?action=get');
            if (!response.ok) throw new Error('Network response was not ok');
            pkkmData = await response.json();
            
            // Extract scores from data
            pkkmData.forEach(task => {
                task.subTasks.forEach(sub => {
                    sub.indicators.forEach(ind => {
                        if (ind.score) state.scores[ind.code] = ind.score;
                    });
                });
            });

            renderApp();
        } catch (error) {
            console.error('Fetch error:', error);
            document.getElementById('app-root').innerHTML = `
                <div class="container py-5 text-center">
                    <div class="alert alert-danger shadow-sm py-5">
                        <i class="bi bi-exclamation-triangle display-4"></i>
                        <h4 class="mt-3 fw-bold">Gagal Memuat Data</h4>
                        <p class="text-muted">Terjadi kesalahan saat mengambil data instrumen dari server. Pastikan database sudah terhubung.</p>
                        <button onclick="location.reload()" class="btn btn-danger px-4 mt-3">Coba Lagi</button>
                    </div>
                </div>
            `;
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>