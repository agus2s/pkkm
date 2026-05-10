</div>

    <!-- Navigation Overlay -->
    <div id="nav-overlay" class="nav-overlay" onclick="toggleDrawer()"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toast notification function
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            const toastId = 'toast-' + Date.now();
            const toastHTML = `
                <div id="${toastId}" class="toast custom-toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', toastHTML);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 4000
            });
            
            toast.show();
            
            // Remove from DOM after hiding
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }

        // Navigation drawer functions
        function toggleDrawer() {
            const drawer = document.getElementById('nav-drawer');
            const overlay = document.getElementById('nav-overlay');
            
            if (drawer.classList.contains('open')) {
                drawer.classList.remove('open');
                overlay.classList.remove('show');
            } else {
                drawer.classList.add('open');
                overlay.classList.add('show');
            }
        }

        function closeDrawer() {
            const drawer = document.getElementById('nav-drawer');
            const overlay = document.getElementById('nav-overlay');
            
            drawer.classList.remove('open');
            overlay.classList.remove('show');
        }

        // Auto-save functionality for indicators
        let saveTimeout;
        function autoSaveRating(indikatorKode, rating) {
            clearTimeout(saveTimeout);
            
            // Show saving indicator
            showSaveStatus('Menyimpan...', 'warning');
            
            saveTimeout = setTimeout(() => {
                fetch('api_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save_score&kode_indikator=${indikatorKode}&hasil_kerja=${rating}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSaveStatus('Tersimpan', 'success');
                    } else {
                        showSaveStatus('Gagal menyimpan', 'danger');
                    }
                })
                .catch(error => {
                    showSaveStatus('Error: ' + error.message, 'danger');
                });
            }, 1000);
        }

        function showSaveStatus(message, type) {
            const statusDiv = document.getElementById('save-status');
            if (statusDiv) {
                statusDiv.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                `;
                
                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(() => {
                        statusDiv.innerHTML = '';
                    }, 3000);
                }
            }
        }

        function navigateToIndicator(kode) {
            window.location.href = `view_indikator.php?kode=${kode}`;
        }

        // Keyboard shortcuts for rating
        document.addEventListener('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            
            const ratingButtons = document.querySelectorAll('.rating-btn');
            const key = parseInt(e.key);
            
            if (key >= 1 && key <= 4) {
                e.preventDefault();
                ratingButtons[key - 1].click();
            }
        });

        // Initialize tooltips if needed
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
