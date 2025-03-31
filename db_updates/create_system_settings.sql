CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(255) NOT NULL PRIMARY KEY,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初始化WxPusher配置
INSERT INTO system_settings (setting_key, setting_value) VALUES
    ('wxpusher_app_token', ''),
    ('wxpusher_admin_uid', ''),
    ('wxpusher_enabled', '0'),
    ('wxpusher_order_notify', '0'),
    ('wxpusher_payment_notify', '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);