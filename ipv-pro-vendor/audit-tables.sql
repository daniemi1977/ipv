-- IPV Pro Vendor - Audit Tables Schema
-- Versione: 1.4.0-optimized
-- Data: 2025-12-14

-- ============================================
-- API LOGS TABLE
-- ============================================
-- Traccia tutte le chiamate API per audit e analytics

CREATE TABLE IF NOT EXISTS wp_ipv_api_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  license_id BIGINT NULL,
  endpoint VARCHAR(100) NOT NULL,
  resource_id VARCHAR(100) NULL COMMENT 'video_id, transcript_id, etc.',
  status_code INT NOT NULL,
  response_size INT DEFAULT 0 COMMENT 'Bytes',
  attempts INT DEFAULT 1 COMMENT 'Numero tentativi prima del successo',
  cached TINYINT(1) DEFAULT 0 COMMENT '1 = cache hit, 0 = API call',
  ip_address VARCHAR(45) NULL COMMENT 'IPv4 or IPv6',
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  INDEX idx_license (license_id),
  INDEX idx_endpoint (endpoint),
  INDEX idx_created (created_at),
  INDEX idx_cached (cached),
  INDEX idx_status (status_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit log di tutte le chiamate API (transcript, AI, YouTube)';

-- ============================================
-- SECURITY LOG TABLE
-- ============================================
-- Traccia eventi di sicurezza (blocked requests, attacks, etc.)

CREATE TABLE IF NOT EXISTS wp_ipv_security_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  event_type VARCHAR(50) NOT NULL COMMENT 'blocked_user_agent, sql_injection_attempt, rate_limit_exceeded, etc.',
  event_data TEXT NULL COMMENT 'JSON con dettagli evento',
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  INDEX idx_event (event_type),
  INDEX idx_ip (ip_address),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log eventi di sicurezza e tentativi di attacco';

-- ============================================
-- PERFORMANCE STATS TABLE (Opzionale)
-- ============================================
-- Statistiche aggregate per monitoring dashboard

CREATE TABLE IF NOT EXISTS wp_ipv_performance_stats (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  date DATE NOT NULL,
  endpoint VARCHAR(100) NOT NULL,
  total_calls INT DEFAULT 0,
  cache_hits INT DEFAULT 0,
  avg_response_size INT DEFAULT 0,
  avg_attempts DECIMAL(4,2) DEFAULT 1.00,
  errors INT DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY unique_daily_stat (date, endpoint),
  INDEX idx_date (date),
  INDEX idx_endpoint (endpoint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Statistiche giornaliere aggregate per dashboard';

-- ============================================
-- ESEMPIO QUERY UTILI
-- ============================================

-- Cache hit rate giornaliero
-- SELECT
--     DATE(created_at) as date,
--     COUNT(*) as total_calls,
--     SUM(cached) as cache_hits,
--     ROUND(SUM(cached) / COUNT(*) * 100, 2) as cache_hit_rate_percent
-- FROM wp_ipv_api_logs
-- GROUP BY DATE(created_at)
-- ORDER BY date DESC
-- LIMIT 30;

-- Performance per endpoint (ultime 24h)
-- SELECT
--     endpoint,
--     COUNT(*) as total_calls,
--     SUM(cached) as cache_hits,
--     AVG(response_size) as avg_response_size,
--     AVG(attempts) as avg_attempts,
--     SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors,
--     ROUND(SUM(cached) / COUNT(*) * 100, 2) as cache_hit_rate
-- FROM wp_ipv_api_logs
-- WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
-- GROUP BY endpoint;

-- Top 10 license per utilizzo (ultima settimana)
-- SELECT
--     license_id,
--     COUNT(*) as total_calls,
--     SUM(CASE WHEN cached = 1 THEN 1 ELSE 0 END) as cache_hits,
--     SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as errors
-- FROM wp_ipv_api_logs
-- WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
--   AND license_id IS NOT NULL
-- GROUP BY license_id
-- ORDER BY total_calls DESC
-- LIMIT 10;

-- Eventi di sicurezza (ultime 24h)
-- SELECT
--     event_type,
--     COUNT(*) as count,
--     COUNT(DISTINCT ip_address) as unique_ips,
--     GROUP_CONCAT(DISTINCT ip_address ORDER BY ip_address SEPARATOR ', ') as ips
-- FROM wp_ipv_security_log
-- WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
-- GROUP BY event_type
-- ORDER BY count DESC;

-- Cleanup old logs (mantieni solo ultimi 90 giorni)
-- DELETE FROM wp_ipv_api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
-- DELETE FROM wp_ipv_security_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
