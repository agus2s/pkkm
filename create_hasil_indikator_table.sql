-- Membuat tabel hasil_indikator
CREATE TABLE IF NOT EXISTS hasil_indikator (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    kode_indikator VARCHAR(50) NOT NULL,
    hasil_kerja INT NOT NULL,
    link_bukti VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE,
    INDEX idx_username (username),
    INDEX idx_kode_indikator (kode_indikator)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
