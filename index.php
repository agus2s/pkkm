<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Kinerja Kepala Madrasah (PKKM)</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (via CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons (via CDN) -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Sembunyikan elemen input radio asli untuk kustomisasi styling skor */
        .score-radio-input:checked + div {
            background-color: #4f46e5; /* indigo-600 */
            border-color: #4f46e5;
            color: white;
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 pb-20">

    <div id="app-root"></div>

    <script>
        // --- DATA MASTER PKKM ---
        let pkkmData = [];

        // --- STATE MANAGEMENT ---
        let state = {
            activeTab: 1,
            scores: {},       // { "1.1.1": 4 }
            evidences: {},    // { "1.1.1": [{id: 1, title: "Doc", url: "http.."}] }
            expandedSubTasks: {} // { "1.1": true }
        };

        // --- ACTIONS ---
        function setActiveTab(tabId) {
            state.activeTab = tabId;
            renderApp();
            window.scrollTo(0, 0);
        }

        function toggleSubTask(code) {
            state.expandedSubTasks[code] = !state.expandedSubTasks[code];
            renderApp();
        }

        function setScore(indicatorCode, score) {
            state.scores[indicatorCode] = parseInt(score);
            renderApp();

            // Save to DB
            const formData = new FormData();
            formData.append('code', indicatorCode);
            formData.append('score', score);
            fetch('api.php?action=save_score', { method: 'POST', body: formData }).catch(e => console.error(e));
        }

        function addEvidenceLink(indicatorCode) {
            const titleInput = document.getElementById(`title-${indicatorCode}`);
            const urlInput = document.getElementById(`url-${indicatorCode}`);
            
            const title = titleInput.value.trim();
            let url = urlInput.value.trim();

            if (!title || !url) return;

            // Validasi format URL sederhana
            if (!/^https?:\/\//i.test(url)) {
                url = 'https://' + url;
            }

            if (!state.evidences[indicatorCode]) {
                state.evidences[indicatorCode] = [];
            }

            state.evidences[indicatorCode].push({
                id: Date.now(),
                title: title,
                url: url
            });

            // Re-render
            renderApp();

            // Save to DB
            const formData = new FormData();
            formData.append('code', indicatorCode);
            formData.append('evidences', JSON.stringify(state.evidences[indicatorCode]));
            fetch('api.php?action=save_evidences', { method: 'POST', body: formData }).catch(e => console.error(e));
        }

        function removeEvidenceLink(indicatorCode, linkId) {
            state.evidences[indicatorCode] = state.evidences[indicatorCode].filter(link => link.id !== linkId);
            renderApp();

            // Save to DB
            const formData = new FormData();
            formData.append('code', indicatorCode);
            formData.append('evidences', JSON.stringify(state.evidences[indicatorCode]));
            fetch('api.php?action=save_evidences', { method: 'POST', body: formData }).catch(e => console.error(e));
        }

        function handlePrint() {
            window.print();
        }

        function calculateStats() {
            let totalIndicators = 0;
            let answeredIndicators = 0;
            let totalScore = 0;

            pkkmData.forEach(task => {
                task.subTasks.forEach(sub => {
                    sub.indicators.forEach(ind => {
                        totalIndicators++;
                        if (state.scores[ind.code]) {
                            answeredIndicators++;
                            totalScore += state.scores[ind.code];
                        }
                    });
                });
            });

            const maxScore = totalIndicators * 4;
            const percentage = maxScore > 0 ? ((totalScore / maxScore) * 100).toFixed(1) : 0;
            
            let grade = "Kurang";
            let gradeColor = "text-red-600";
            if (percentage >= 91) { grade = "Amat Baik"; gradeColor = "text-green-600"; }
            else if (percentage >= 76) { grade = "Baik"; gradeColor = "text-blue-600"; }
            else if (percentage >= 61) { grade = "Cukup"; gradeColor = "text-yellow-600"; }

            return { totalIndicators, answeredIndicators, totalScore, maxScore, percentage, grade, gradeColor };
        }

        // --- RENDERING ---
        function renderHeader() {
            let tabsHtml = pkkmData.map(task => `
                <button
                    onclick="setActiveTab(${task.id})"
                    class="whitespace-nowrap px-4 py-2 rounded-t-lg font-medium text-xs transition-colors duration-200 
                    ${state.activeTab === task.id 
                        ? 'bg-slate-50 text-indigo-700 border-t-2 border-indigo-500' 
                        : 'text-indigo-100 hover:bg-indigo-600 border-t-2 border-transparent'}"
                >
                    TUGAS ${task.id}
                </button>
            `).join('');

            return `
                <header class="bg-indigo-700 text-white shadow-md print:hidden sticky top-0 z-20">
                    <div class="max-w-7xl mx-auto px-4 py-2 flex justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <h1 class="text-lg font-bold">Aplikasi PKKM</h1>
                            <p class="text-indigo-200 text-xs hidden sm:block border-l border-indigo-500/50 pl-4">Penilaian Kinerja Kepala Madrasah</p>
                        </div>
                        <div class="flex gap-2">
                            <button 
                                onclick="setActiveTab('summary')"
                                class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 px-3 py-1.5 rounded-md transition-colors text-xs font-medium border border-indigo-500"
                            >
                                <i data-lucide="award" class="w-3.5 h-3.5"></i>
                                Hasil
                            </button>
                            <button 
                                onclick="handlePrint()"
                                class="flex items-center gap-1.5 bg-white text-indigo-700 hover:bg-indigo-50 px-3 py-1.5 rounded-md transition-colors text-xs font-medium shadow-sm"
                            >
                                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                                Cetak
                            </button>
                        </div>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 overflow-x-auto">
                        <div class="flex space-x-1">
                            ${tabsHtml}
                        </div>
                    </div>
                </header>
            `;
        }

        function renderTaskContent(task) {
            let subTasksHtml = task.subTasks.map(subTask => {
                const isExpanded = state.expandedSubTasks[subTask.code];
                
                let indicatorsHtml = '';
                if (isExpanded) {
                    indicatorsHtml = `<div class="divide-y divide-slate-100">`;
                    
                    subTask.indicators.forEach(indicator => {
                        const currentScore = state.scores[indicator.code];
                        const evidences = state.evidences[indicator.code] || [];
                        
                        let evidenceListHtml = evidences.map(link => `
                            <li class="flex items-center gap-2 bg-indigo-50/50 border border-indigo-100 p-2 rounded-lg text-sm">
                                <i data-lucide="link" class="w-4 h-4 text-indigo-500 flex-shrink-0"></i>
                                <a href="${link.url}" target="_blank" rel="noreferrer" class="text-indigo-600 hover:underline flex-1 truncate">
                                    ${link.title}
                                </a>
                                <button 
                                    onclick="removeEvidenceLink('${indicator.code}', ${link.id})"
                                    class="text-red-400 hover:text-red-600 p-1 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
                                    title="Hapus Tautan"
                                >
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </li>
                        `).join('');

                        let scoreRadioHtml = [1, 2, 3, 4].map(val => `
                            <label class="cursor-pointer group flex flex-col items-center">
                                <input 
                                    type="radio" 
                                    name="score-${indicator.code}"
                                    value="${val}"
                                    class="sr-only score-radio-input"
                                    ${currentScore === val ? 'checked' : ''}
                                    onchange="setScore('${indicator.code}', ${val})"
                                />
                                <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 border-slate-300 bg-white text-slate-500 font-semibold group-hover:border-indigo-400 transition-all shadow-sm">
                                    ${val}
                                </div>
                            </label>
                        `).join('');

                        indicatorsHtml += `
                            <div class="p-5 flex flex-col lg:flex-row gap-6 hover:bg-slate-50/50 transition-colors print:p-2 print:border-b">
                                <!-- Info Indikator -->
                                <div class="flex-1">
                                    <div class="flex gap-3 mb-2">
                                        <span class="font-medium text-slate-500 whitespace-nowrap">${indicator.code}</span>
                                        <p class="text-slate-700">${indicator.title}</p>
                                    </div>
                                    
                                    <!-- Area Bukti Fisik (Sembunyi saat diprint) -->
                                    <div class="ml-10 mt-4 print:hidden">
                                        <div class="text-sm font-medium text-slate-500 mb-2 flex items-center gap-2">
                                            <i data-lucide="file-text" class="w-4 h-4"></i>
                                            Bukti Fisik (Link Google Drive)
                                        </div>
                                        
                                        ${evidences.length > 0 ? `<ul class="space-y-2 mb-3">${evidenceListHtml}</ul>` : ''}

                                        <!-- Form Tambah Bukti -->
                                        <div class="flex gap-2 items-center flex-wrap sm:flex-nowrap">
                                            <input 
                                                type="text" 
                                                id="title-${indicator.code}"
                                                placeholder="Nama Dokumen (SK, Foto, dll)" 
                                                class="flex-1 min-w-[150px] text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                            />
                                            <input 
                                                type="text" 
                                                id="url-${indicator.code}"
                                                placeholder="Link Google Drive" 
                                                class="flex-1 min-w-[150px] text-sm border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                            />
                                            <button 
                                                onclick="addEvidenceLink('${indicator.code}')"
                                                class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 p-2 rounded-lg font-medium text-sm flex items-center gap-1 transition-colors whitespace-nowrap"
                                            >
                                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Tampilan Bukti Fisik Saat Diprint -->
                                    <div class="hidden print:block ml-10 mt-2 text-sm">
                                        <strong>Bukti Fisik:</strong> ${evidences.length > 0 ? evidences.map(e => e.title).join(', ') : '-'}
                                    </div>
                                </div>

                                <!-- Area Penilaian -->
                                <div class="lg:w-64 bg-slate-50 p-4 rounded-xl border border-slate-200 print:border-none print:bg-transparent print:p-0 print:w-auto">
                                    <div class="text-center font-semibold text-slate-600 mb-3 text-sm print:hidden">Nilai (1-4)</div>
                                    <div class="flex justify-between items-center print:hidden">
                                        ${scoreRadioHtml}
                                    </div>
                                    <!-- Nilai Saat Diprint -->
                                    <div class="hidden print:flex h-full items-center">
                                        <span class="font-bold text-lg mr-2">Nilai:</span>
                                        <span class="text-xl inline-block w-10 text-center border-b-2 border-black">
                                            ${currentScore || ''}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    indicatorsHtml += `</div>`;
                }

                return `
                    <div class="bg-white rounded-xl shadow-sm border border-slate-300 overflow-hidden print:border-none print:shadow-none print:mb-6">
                        <!-- Header Sub-Tugas -->
                        <div 
                            class="bg-slate-100/50 p-4 border-b border-slate-300 flex justify-between items-center cursor-pointer hover:bg-slate-100 transition-colors print:bg-transparent print:border-b-2 print:border-slate-800"
                            onclick="toggleSubTask('${subTask.code}')"
                        >
                            <h3 class="font-semibold text-slate-800 text-lg flex items-start gap-3">
                                <span class="text-indigo-600 mt-1 whitespace-nowrap">${subTask.code}</span>
                                ${subTask.title}
                            </h3>
                            <button class="text-slate-400 print:hidden">
                                <i data-lucide="${isExpanded ? 'chevron-down' : 'chevron-right'}" class="w-6 h-6"></i>
                            </button>
                        </div>
                        ${indicatorsHtml}
                    </div>
                `;
            }).join('');

            const currentIndex = pkkmData.findIndex(t => t.id === state.activeTab);
            const prevDisabled = currentIndex === 0 ? 'disabled class="opacity-50 cursor-not-allowed"' : '';
            const nextTab = currentIndex < pkkmData.length - 1 ? pkkmData[currentIndex + 1].id : "'summary'";
            const nextText = currentIndex === pkkmData.length - 1 ? 'Lihat Hasil' : 'Selanjutnya';

            return `
                <div class="mb-6 border-b pb-4">
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                        <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-md text-xl">${task.id}</span>
                        ${task.title}
                    </h2>
                </div>
                <div class="space-y-6">
                    ${subTasksHtml}
                </div>
                <!-- Form Actions (Navigasi Bawah) -->
                <div class="flex justify-between items-center pt-6 mt-6 print:hidden">
                    <button 
                        onclick="setActiveTab(${currentIndex > 0 ? pkkmData[currentIndex - 1].id : 1})"
                        ${prevDisabled}
                        class="px-6 py-2.5 rounded-lg font-medium text-slate-600 hover:bg-slate-200 transition-colors"
                    >
                        Sebelumnya
                    </button>
                    <button 
                        onclick="setActiveTab(${nextTab})"
                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 shadow-sm transition-colors"
                    >
                        ${nextText}
                    </button>
                </div>
            `;
        }

        function renderSummary() {
            const stats = calculateStats();
            
            return `
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden print:shadow-none print:border-none">
                    <div class="bg-indigo-600 p-8 text-white text-center print:bg-white print:text-black print:p-0 print:mb-8">
                        <i data-lucide="award" class="w-12 h-12 mx-auto mb-4 opacity-90 print:hidden"></i>
                        <h2 class="text-3xl font-bold mb-2 print:text-2xl print:border-b-2 print:border-black print:pb-2">Hasil Penilaian Kinerja</h2>
                        <p class="text-indigo-100 print:text-gray-700">Rekapitulasi total capaian dari seluruh instrumen</p>
                    </div>
                    
                    <div class="p-8 print:p-0">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10 print:grid-cols-2 print:gap-4">
                            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 text-center">
                                <div class="text-slate-500 text-sm font-medium mb-1">Progres Pengisian</div>
                                <div class="text-3xl font-bold text-slate-800">
                                    ${stats.answeredIndicators} / ${stats.totalIndicators}
                                </div>
                            </div>
                            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 text-center">
                                <div class="text-slate-500 text-sm font-medium mb-1">Skor Diperoleh</div>
                                <div class="text-3xl font-bold text-indigo-600 print:text-black">${stats.totalScore}</div>
                            </div>
                            <div class="bg-slate-50 p-6 rounded-xl border border-slate-100 text-center">
                                <div class="text-slate-500 text-sm font-medium mb-1">Skor Maksimal</div>
                                <div class="text-3xl font-bold text-slate-800">${stats.maxScore}</div>
                            </div>
                            <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 text-center relative overflow-hidden print:bg-white print:border-gray-300">
                                <div class="text-indigo-600 text-sm font-medium mb-1 print:text-black">Nilai Akhir (PKKM)</div>
                                <div class="text-3xl font-bold text-indigo-700 print:text-black">${stats.percentage}%</div>
                                <div class="absolute top-0 right-0 w-16 h-16 bg-indigo-200 rounded-bl-full -z-10 opacity-50 print:hidden"></div>
                            </div>
                        </div>

                        <div class="text-center mb-8">
                            <div class="inline-block px-8 py-4 bg-slate-50 rounded-2xl border-2 border-slate-200 border-dashed">
                                <div class="text-slate-500 mb-2">Predikat Kinerja:</div>
                                <div class="text-4xl font-extrabold tracking-tight ${stats.gradeColor} print:text-black">
                                    ${stats.grade}
                                </div>
                            </div>
                        </div>

                        <!-- Area Tanda Tangan Khusus Print -->
                        <div class="hidden print:flex justify-between mt-20 pt-10 px-10">
                            <div class="text-center">
                                <p class="mb-20">Mengetahui,<br/>Kepala Kantor Kemenag / Pengawas</p>
                                <p class="font-bold border-b border-black inline-block px-4">.......................................</p>
                                <p>NIP.</p>
                            </div>
                            <div class="text-center">
                                <p class="mb-20">....................., .................... ${new Date().getFullYear()}<br/>Kepala Madrasah Yang Dinilai</p>
                                <p class="font-bold border-b border-black inline-block px-4">.......................................</p>
                                <p>NIP.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function renderApp() {
            const root = document.getElementById('app-root');
            
            let mainContentHtml = '';
            if (state.activeTab === 'summary') {
                mainContentHtml = renderSummary();
            } else {
                const activeTaskData = pkkmData.find(t => t.id === state.activeTab);
                mainContentHtml = renderTaskContent(activeTaskData);
            }

            root.innerHTML = `
                ${renderHeader()}
                <main class="max-w-7xl mx-auto px-4 py-8">
                    <!-- Header Print (Hanya tampil saat dicetak) -->
                    <div class="hidden print:block text-center mb-8 pb-4 border-b-2 border-slate-800">
                        <h1 class="text-2xl font-bold">INSTRUMEN PENILAIAN KINERJA KEPALA MADRASAH</h1>
                        <p class="text-lg">Tahun Penilaian: ${new Date().getFullYear()}</p>
                    </div>
                    
                    ${mainContentHtml}
                </main>
            `;

            // Merender ulang ikon-ikon Lucide setelah manipulasi DOM
            lucide.createIcons();
        }

        // Inisialisasi awal aplikasi
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch('api.php?action=get');
                if (!response.ok) throw new Error('Gagal mengambil data dari server');
                
                pkkmData = await response.json();
                
                // Inisialisasi semua subtask agar terbuka secara default dan memuat data dari DB
                pkkmData.forEach(task => {
                    task.subTasks.forEach(sub => {
                        state.expandedSubTasks[sub.code] = true;
                        sub.indicators.forEach(ind => {
                            if (ind.score) state.scores[ind.code] = ind.score;
                            if (ind.evidences && ind.evidences.length > 0) state.evidences[ind.code] = ind.evidences;
                        });
                    });
                });
                
                renderApp();
            } catch (error) {
                console.error("Gagal memuat data PKKM:", error);
                document.getElementById('app-root').innerHTML = `
                    <div class="p-8 text-center mt-10">
                        <div class="inline-block bg-red-100 text-red-600 px-6 py-4 rounded-xl border border-red-200">
                            <strong>Gagal memuat data indikator.</strong><br/> 
                            Pastikan Anda mengakses aplikasi ini melalui Web Server (localhost).
                        </div>
                    </div>`;
            }
        });

    </script>
</body>
</html>