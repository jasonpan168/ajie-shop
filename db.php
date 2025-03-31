<?php
/**
 * 数据库连接管理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 提供数据库连接和错误处理功能，使用单例模式管理数据库连接，
 * 包含错误日志记录和异常处理机制，确保数据库操作的安全性。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        global $db_host, $db_name, $db_user, $db_pass;
        try {
            $this->pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    public function query($sql) {
        return $this->pdo->query($sql);
    }
    
    private function handleError($e) {
        error_log("数据库错误: " . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            throw $e;
        } else {
            die("系统暂时无法处理您的请求，请稍后再试");
        }
    }
}

// 获取数据库实例
$db = Database::getInstance();
?>