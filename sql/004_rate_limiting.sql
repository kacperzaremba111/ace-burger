-- FAST FOOD — generyczna tabela do rate-limitingu (logowanie do panelu,
-- pozniej tez tworzenie zamowien w /api/zamowienia.php).

USE ace_burger;

CREATE TABLE rate_limit_hits (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket      VARCHAR(80) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rate_limit_bucket_time (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
