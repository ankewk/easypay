<?php

/**
 * 华为支付异步任务处理器
 * 
 * 用于处理华为支付的异步回调和订单状态更新
 * 支持多种队列实现：Redis、数据库、文件队列
 * 
 * 使用方法：
 * 1. 作为HTTP接口：直接访问此文件
 * 2. 作为CLI脚本：php async_processor.php [--limit=10]
 * 3. 作为定时任务：crontab -e 添加：*/5 * * * * /usr/bin/php /path/to/async_processor.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Huaweipay\Payment\AsyncProcessor;

// 加载配置
$config = [
    'merchant_id' => 'your_merchant_id',
    'app_id' => 'your_app_id',
    'private_key' => 'your_private_key',
    'public_key' => 'your_public_key',
    'gateway_url' => 'https://pay-api.huawei.com/gateway',
    'notify_url' => 'https://your-domain.com/callbacks/huaweipay/huawei_notify.php',
    
    // 队列配置
    'queue_type' => 'file', // 可选：redis, database, file
    
    // Redis配置（可选）
    'redis_host' => '127.0.0.1',
    'redis_port' => 6379,
    'redis_password' => '',
    
    // 数据库配置（可选）
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'easypay',
    'db_user' => 'root',
    'db_pass' => '',
];

/**
 * 处理异步通知入口
 */
function handleAsyncNotify()
{
    global $config;
    
    try {
        $processor = new AsyncProcessor($config);
        
        // 获取通知数据
        $notifyData = $_POST;
        if (empty($notifyData)) {
            $input = file_get_contents('php://input');
            $notifyData = json_decode($input, true) ?: [];
        }
        
        if (empty($notifyData)) {
            throw new Exception('没有收到通知数据');
        }
        
        // 处理异步通知
        $result = $processor->processAsyncNotify($notifyData);
        
        if ($result['success']) {
            echo "SUCCESS";
        } else {
            echo "FAIL";
        }
        
    } catch (Exception $e) {
        error_log('华为支付异步处理错误: ' . $e->getMessage());
        echo "FAIL";
    }
}

/**
 * 处理任务队列
 */
function processTaskQueue($limit = 10)
{
    global $config;
    
    try {
        $processor = new AsyncProcessor($config);
        
        // 处理任务队列
        $results = $processor->processTaskQueue($limit);
        
        echo "处理完成，共处理任务: " . count($results) . "\n";
        
        foreach ($results as $result) {
            $status = $result['success'] ? '成功' : '失败';
            echo "任务 {$result['order_no']}: $status\n";
        }
        
    } catch (Exception $e) {
        echo "处理错误: " . $e->getMessage() . "\n";
    }
}

/**
 * 创建数据库表（如果需要使用数据库队列）
 */
function createDatabaseTables()
{
    global $config;
    
    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['db_name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 使用数据库
        $pdo->exec("USE {$config['db_name']}");
        
        // 创建任务表
        $sql = "CREATE TABLE IF NOT EXISTS async_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            task_type VARCHAR(50) NOT NULL,
            order_no VARCHAR(100) NOT NULL,
            data JSON NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            retry_count INT DEFAULT 0,
            max_retries INT DEFAULT 3,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_task_type (task_type),
            INDEX idx_status (status),
            INDEX idx_order_no (order_no),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "数据库表创建完成\n";
        
    } catch (Exception $e) {
        echo "创建数据库表失败: " . $e->getMessage() . "\n";
    }
}

/**
 * 显示帮助信息
 */
function showHelp()
{
    echo "华为支付异步任务处理器\n\n";
    echo "使用方法:\n";
    echo "  php async_processor.php [--action=notify|--action=process|--action=setup] [--limit=N]\n\n";
    echo "参数:\n";
    echo "  --action=notify   处理HTTP异步通知（默认）\n";
    echo "  --action=process  处理任务队列\n";
    echo "  --action=setup    创建数据库表\n";
    echo "  --limit=N         处理任务数量限制（默认10）\n";
    echo "  --help            显示帮助信息\n\n";
    echo "示例:\n";
    echo "  php async_processor.php --action=process --limit=5\n";
    echo "  php async_processor.php --action=setup\n";
}

// 主程序
if (php_sapi_name() === 'cli') {
    // CLI模式
    $options = getopt("", ["action:", "limit:", "help"]);
    
    if (isset($options['help'])) {
        showHelp();
        exit;
    }
    
    $action = $options['action'] ?? 'process';
    $limit = intval($options['limit'] ?? 10);
    
    switch ($action) {
        case 'process':
            processTaskQueue($limit);
            break;
        case 'setup':
            createDatabaseTables();
            break;
        case 'notify':
            handleAsyncNotify();
            break;
        default:
            echo "未知操作: $action\n";
            showHelp();
    }
} else {
    // HTTP模式
    handleAsyncNotify();
}
?>