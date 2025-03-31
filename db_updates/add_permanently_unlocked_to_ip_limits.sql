-- 为ip_limits表添加permanently_unlocked字段
ALTER TABLE ip_limits
ADD COLUMN permanently_unlocked TINYINT(1) NOT NULL DEFAULT 0 AFTER is_blocked;

-- 更新updateIpLimitRecord函数的触发器，确保永久解除限制的IP不会被重新封禁
DELIMITER //

CREATE TRIGGER before_ip_limits_update
BEFORE UPDATE ON ip_limits
FOR EACH ROW
BEGIN
    IF OLD.permanently_unlocked = 1 THEN
        SET NEW.is_blocked = 0;
        SET NEW.permanently_unlocked = 1;
    END IF;
END //

DELIMITER ;