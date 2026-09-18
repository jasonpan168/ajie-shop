-- 后台登录失败计数表（用于 5 次失败锁定该 IP 15 分钟）
-- admin/login.php 会在运行时惰性创建同样的表，这份 SQL 供手动执行/审计参考。
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
  `ip` varchar(45) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `last_attempt` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
