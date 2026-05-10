<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// If user is logged in, redirect to dashboard
header('Location: dashboard.php');
exit;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard PKKM - Penilaian Kinerja Kepala Madrasah</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
        .glass-header {
            background: rgba(79, 70, 229, 0.95);
            backdrop-filter: blur(10px);
        }
        .dark .glass-header {
            background: rgba(30, 27, 75, 0.9);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-200 min-h-screen transition-colors duration-300">

    <div id="app-root">
        <!-- Loading State -->
        <div class="flex items-center justify-center min-h-screen">
            <div class="flex flex-col items-center gap-4">
                <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 border-t-indigo-600 rounded-full animate-spin"></div>
                <p class="text-slate-400 dark:text-slate-500 font-medium animate-pulse">Memuat instrumen...</p>
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

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            renderApp();
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
            let gradeColor = "text-red-600";
            let bgGrade = "bg-red-50";
            if (percentage >= 91) { grade = "Amat Baik"; gradeColor = "text-emerald-600"; bgGrade = "bg-emerald-50"; }
            else if (percentage >= 76) { grade = "Baik"; gradeColor = "text-blue-600"; bgGrade = "bg-blue-50"; }
            else if (percentage >= 61) { grade = "Cukup"; gradeColor = "text-amber-600"; bgGrade = "bg-amber-50"; }

            return { totalIndicators, answeredIndicators, totalScore, maxScore, percentage, grade, gradeColor, bgGrade, taskStats };
        }

        async function resetAll() {
            if (!confirm('Apakah Anda yakin ingin menghapus semua nilai dan bukti yang telah diinput? Tindakan ini tidak dapat dibatalkan.')) return;
            
            try {
                const response = await fetch('api.php?action=reset_all');
                const result = await response.json();
                if (result.status === 'success') {
                    state.scores = {};
                    // Also need to refresh pkkmData or just clear scores in it
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
        function renderHeader() {
            const isDark = document.documentElement.classList.contains('dark');
            const tabsHtml = pkkmData.map(task => `
                <button
                    onclick="setActiveTab(${task.id})"
                    class="whitespace-nowrap px-6 py-4 font-bold text-xs tracking-widest uppercase transition-all relative
                    ${state.activeTab === task.id 
                        ? 'text-white' 
                        : 'text-indigo-200 hover:text-white'}"
                >
                    Tugas ${task.id}
                    ${state.activeTab === task.id ? '<div class="absolute bottom-0 left-0 w-full h-1 bg-white rounded-t-full"></div>' : ''}
                </button>
            `).join('');

            return `
                <header class="glass-header text-white sticky top-0 z-40 shadow-lg print:hidden">
                    <div class="max-w-6xl mx-auto px-6">
                        <div class="flex justify-between items-center h-16 border-b border-white/10">
                            <div class="flex items-center gap-3">
                                <div class="bg-white p-1.5 rounded-lg shadow-inner">
                                    <i data-lucide="shield-check" class="w-6 h-6 text-indigo-600"></i>
                                </div>
                                <div>
                                    <h1 class="text-lg font-black tracking-tighter leading-none">Aplikasi PKKM</h1>
                                    <p class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest opacity-80">Penilaian Kinerja Kepala Madrasah</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    onclick="toggleTheme()"
                                    class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-all border border-white/10"
                                    title="Ganti Tema"
                                >
                                    <i data-lucide="${isDark ? 'sun' : 'moon'}" class="w-4 h-4"></i>
                                </button>
                                <button 
                                    onclick="setActiveTab('summary')"
                                    class="flex items-center gap-2 bg-white/10 hover:bg-white/20 px-4 py-2 rounded-xl transition-all text-xs font-bold border border-white/10"
                                >
                                    <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                                    Hasil Akhir
                                </button>
                            </div>
                        </div>
                        <div class="flex overflow-x-auto custom-scrollbar">
                            ${tabsHtml}
                        </div>
                    </div>
                </header>
            `;
        }

        function renderMenuContent(task) {
            const cardsHtml = task.subTasks.map(subTask => {
                const firstCode = subTask.indicators.length > 0 ? subTask.indicators[0].code : '';
                const total = subTask.indicators.length;
                const done = subTask.indicators.filter(i => state.scores[i.code]).length;
                const progress = total > 0 ? (done / total) * 100 : 0;

                return `
                    <a href="view_indicator.php?code=${firstCode}" class="group bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-3xl shadow-sm hover:shadow-xl hover:border-indigo-400 dark:hover:border-indigo-500 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-1 rounded-lg uppercase tracking-widest">Unsur ${subTask.code}</span>
                            <div class="flex items-center gap-1.5 text-xs font-bold ${done === total ? 'text-emerald-500' : 'text-slate-400 dark:text-slate-500'}">
                                <i data-lucide="${done === total ? 'check-circle' : 'circle'}" class="w-4 h-4"></i>
                                ${done}/${total}
                            </div>
                        </div>
                        <h3 class="text-slate-800 dark:text-slate-100 font-bold leading-snug mb-6 group-hover:text-indigo-700 dark:group-hover:text-indigo-400 transition-colors">
                            ${subTask.title}
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                <span>Progress</span>
                                <span>${Math.round(progress)}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                <div class="bg-indigo-500 h-full transition-all duration-700" style="width: ${progress}%"></div>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');

            return `
                <div class="max-w-6xl mx-auto px-6 py-10">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-2">Tugas Utama ${task.id}</h2>
                            <p class="text-slate-500 dark:text-slate-400 font-medium max-w-2xl leading-relaxed">${task.title}</p>
                        </div>
                        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl shadow-sm flex items-center gap-4 shrink-0">
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Total Unsur</p>
                                <p class="text-xl font-black text-indigo-600 dark:text-indigo-400 leading-none">${task.subTasks.length}</p>
                            </div>
                            <div class="w-px h-10 bg-slate-100 dark:bg-slate-800"></div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Indikator</p>
                                <p class="text-xl font-black text-slate-800 dark:text-slate-200 leading-none">
                                    ${task.subTasks.reduce((acc, curr) => acc + curr.indicators.length, 0)}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        ${cardsHtml}
                    </div>
                </div>
            `;
        }

        function renderSummary() {
            const stats = calculateStats();
            return `
                <div class="max-w-6xl mx-auto px-6 py-12">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-slate-200 dark:shadow-none border border-slate-100 dark:border-slate-800 overflow-hidden">
                        <div class="bg-indigo-600 p-12 text-center text-white relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                                <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full -mr-20 -mt-20 blur-3xl"></div>
                                <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-400 rounded-full -ml-32 -mb-32 blur-3xl"></div>
                            </div>
                            <i data-lucide="award" class="w-16 h-16 mx-auto mb-6 text-indigo-200"></i>
                            <h2 class="text-4xl font-black mb-3 tracking-tight">Hasil Penilaian Kinerja</h2>
                            <p class="text-indigo-100 font-medium max-w-lg mx-auto leading-relaxed">Rekapitulasi total capaian dari seluruh instrumen penilaian kinerja kepala madrasah</p>
                        </div>

                        <div class="p-12">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                                <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Progres</p>
                                    <p class="text-2xl font-black text-slate-800 dark:text-slate-100">${stats.answeredIndicators} / ${stats.totalIndicators}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Skor Total</p>
                                    <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400">${stats.totalScore}</p>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 text-center">
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Maksimal</p>
                                    <p class="text-2xl font-black text-slate-800 dark:text-slate-100">${stats.maxScore}</p>
                                </div>
                                <div class="${stats.bgGrade} dark:bg-indigo-900/20 p-6 rounded-3xl border border-indigo-100 dark:border-indigo-900/30 text-center">
                                    <p class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-2">Persentase</p>
                                    <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300">${stats.percentage}%</p>
                                </div>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-950 rounded-[2rem] p-10 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 mb-12">
                                <p class="text-slate-500 dark:text-slate-400 font-bold uppercase tracking-[0.2em] text-xs mb-4">Predikat Kinerja</p>
                                <div class="text-6xl font-black tracking-tighter ${stats.gradeColor} dark:text-indigo-400 mb-2">
                                    ${stats.grade}
                                </div>
                            </div>

                            <div class="mb-12">
                                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                                    <i data-lucide="list-checks" class="w-6 h-6 text-indigo-600"></i>
                                    Rekap Per Tugas Utama
                                </h3>
                                <div class="grid grid-cols-1 gap-4">
                                    ${stats.taskStats.map(task => `
                                        <div class="flex items-center justify-between p-6 bg-slate-50 dark:bg-slate-950 rounded-3xl border border-slate-100 dark:border-slate-800">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 bg-indigo-600 text-white rounded-2xl flex items-center justify-center font-black text-lg">
                                                    ${task.id}
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-slate-800 dark:text-slate-100 leading-tight">${task.title}</h4>
                                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Progres: ${Math.round(task.progress)}%</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Skor / Maks</p>
                                                <p class="text-xl font-black text-slate-900 dark:text-white">
                                                    <span class="text-indigo-600 dark:text-indigo-400">${task.score}</span>
                                                    <span class="text-slate-300 dark:text-slate-700 mx-1">/</span>
                                                    <span>${task.max}</span>
                                                </p>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row justify-center gap-4 print:hidden">
                                <button onclick="window.print()" class="flex items-center justify-center gap-2 px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-bold hover:bg-slate-800 dark:hover:bg-slate-100 transition-all shadow-xl shadow-slate-200 dark:shadow-none">
                                    <i data-lucide="printer" class="w-5 h-5"></i>
                                    Cetak Hasil
                                </button>
                                <button onclick="setActiveTab(1)" class="flex items-center justify-center gap-2 px-8 py-3 border-2 border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-bold hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
                                    Kembali ke Instrumen
                                </button>
                                <button onclick="resetAll()" class="flex items-center justify-center gap-2 px-8 py-3 border-2 border-red-200 dark:border-red-900/30 text-red-600 dark:text-red-400 rounded-2xl font-bold hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                                    Reset Semua Nilai
                                </button>
                            </div>
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
                content = renderMenuContent(activeTask);
            }

            root.innerHTML = `
                ${renderHeader()}
                <main>
                    ${content}
                </main>
            `;
            
            lucide.createIcons();
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
                    <div class="flex flex-col items-center justify-center min-h-screen p-6 text-center">
                        <div class="bg-red-50 border border-red-100 p-8 rounded-[2rem] max-w-md shadow-xl">
                            <i data-lucide="alert-triangle" class="w-12 h-12 text-red-500 mx-auto mb-4"></i>
                            <h2 class="text-xl font-black text-slate-900 mb-2">Gagal Memuat Data</h2>
                            <p class="text-slate-500 text-sm mb-6 leading-relaxed">Terjadi kesalahan saat mengambil data instrumen dari server. Pastikan database sudah terhubung.</p>
                            <button onclick="location.reload()" class="w-full bg-red-600 text-white py-3 rounded-xl font-bold shadow-lg shadow-red-100">Coba Lagi</button>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>