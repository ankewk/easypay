<?php

/**
 * 异步处理功能安装脚本
 * 
 * 自动创建必要的目录和配置文件
 */

class AsyncInstaller
{
    private $baseDir;
    
    public function __construct($baseDir = null)
    {
        $this->baseDir = $baseDir ?? __DIR__;
    }
    
    /**
     * 运行安装
     */
    public function install()
    {
        echo "开始安装异步处理功能...\n\n";
        
        // 创建必要目录
        $this->createDirectories();
        
        // 创建配置文件
        $this->createConfigFile();
        
        // 创建数据库表
        $this->createDatabaseTables();
        
        // 设置权限
        $this->setPermissions();
        
        echo "\n✅ 异步处理功能安装完成！\n";
        echo "\n下一步操作：\n";
        echo "1. 编辑 config/async.php 配置您的队列设置\n";
        echo "2. 设置定时任务：crontab -e\n";
        echo "3. 运行测试：php tests/test_async_processor.php test\n";
    }
    
    /**
     * 创建必要目录
     */
    private function createDirectories()
    {
        $dirs = [
            'queue',
            'logs/async_queue',
            'config',
            'examples',
            'tests/test_data'
        ];
        
        foreach ($dirs as $dir) {
            $path = $this->baseDir . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
                echo "✓ 创建目录: $dir\n";
            }
        }
    }
    
    /**
     * 创建配置文件
     */
    private function createConfigFile()
    {
        $configContent = <?<'EOF'
<?php

/**
 * 异步处理配置文件
 * 
 * 支持三种队列存储方式：file, redis, database
 */

return [
    // 队列类型: file, redis, database
    'queue_type' => 'file',
    
    // Redis配置（当queue_type为redis时使用）
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
    ],
    
    // 数据库配置（当queue_type为database时使用）
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'easypay',
        'user' => 'root',
        'pass' => '',
    ],
    
    // 处理配置
    'processing' => [
        'max_retries' => 3,      // 最大重试次数
        'retry_delay' => 5,      // 重试延迟（秒）
        'batch_size' => 50,      // 批处理大小
        'process_interval' => 5, // 处理间隔（秒）
    ],
    
    // 日志配置
    'logging' => [
        'level' => 'INFO',
        'file' => __DIR__ . '/../logs/async_queue/async.log',
        'max_file_size' => 100 * 1024 * 1024, // 100MB
    ],
    
    // 支付方式配置
    'payment_types' => [
        'alipay',
        'wxpay',
        'huaweipay',
        'applepay',
        'douyinpay',
        'kuaishoupay',
        'jdpay',
        'pinduoduopay',
        'meituanpay',
        'cmbpay'
    ],
];
EOF;

        $configFile = $this->baseDir . '/config/async.php';
        file_put_contents($configFile, $configContent);
        echo "✓ 创建配置文件: config/async.php\n";
    }
    
    /**
     * 创建数据库表
     */
    private function createDatabaseTables()
    {
        $sqlFile = $this->baseDir . '/config/async_tables.sql';
        
        $sql = <<<EOF
-- 异步任务表
CREATE TABLE IF NOT EXISTS easypay_async_tasks (
    id VARCHAR(50) PRIMARY KEY,
    payment_type VARCHAR(20) NOT NULL,
    data JSON NOT NULL,
    status ENUM('pending', 'processing', 'retry', 'failed', 'completed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    next_retry DATETIME,
    failed_at DATETIME,
    INDEX idx_payment_type (payment_type),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- 任务统计表
CREATE TABLE IF NOT EXISTS easypay_async_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type VARCHAR(20) NOT NULL,
    date DATE NOT NULL,
    total_tasks INT DEFAULT 0,
    success_tasks INT DEFAULT 0,
    failed_tasks INT DEFAULT 0,
    retry_tasks INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_payment_date (payment_type, date)
);
EOF;

        file_put_contents($sqlFile, $sql);
        echo "✓ 创建数据库SQL文件: config/async_tables.sql\n";
    }
    
    /**
     * 设置权限
     */
    private function setPermissions()
    {
        $paths = [
            'queue',
            'logs',
            'config'
        ];
        
        foreach ($paths as $path) {
            $fullPath = $this->baseDir . '/' . $path;
            if (is_dir($fullPath)) {
                chmod($fullPath, 0755);
            }
        }
        
        echo "✓ 设置目录权限\n";
    }
}

// 命令行运行
if (basename($_SERVER['PHP_SELF']) === 'install_async.php') {
    $installer = new AsyncInstaller();
    $installer->install();
}