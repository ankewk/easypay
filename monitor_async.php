<?php

/**
 * 异步处理监控脚本
 * 
 * 监控所有支付方式的异步任务队列状态
 */

require_once __DIR__ . '/vendor/autoload.php';

class AsyncMonitor
{
    private $config;
    
    public function __construct($configFile = null)
    {
        $configFile = $configFile ?? __DIR__ . '/config/async.php';
        $this->config = file_exists($configFile) ? require $configFile : $this->getDefaultConfig();
    }
    
    /**
     * 获取默认配置
     */
    private function getDefaultConfig()
    {
        return [
            'queue_type' => 'file',
            'max_retries' => 3,
            'batch_size' => 50,
            'log_file' => __DIR__ . '/logs/async_queue/async.log'
        ];
    }
    
    /**
     * 监控所有支付方式
     */
    public function monitorAll()
    {
        $paymentTypes = [
            'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay',
            'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
        ];
        
        echo "异步处理监控报告\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $totalPending = 0;
        $totalFailed = 0;
        
        foreach ($paymentTypes as $type) {
            $status = $this->getQueueStatus($type);
            echo strtoupper($type) . ":\n";
            echo "  待处理: " . $status['pending'] . "\n";
            echo "  重试中: " . $status['retry'] . "\n";
            echo "  失败: " . $status['failed'] . "\n\n";
            
            $totalPending += $status['pending'] + $status['retry'];
            $totalFailed += $status['failed'];
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "总计: 待处理 $totalPending, 失败 $totalFailed\n\n";
        
        // 显示系统状态
        $this->showSystemStatus();
    }
    
    /**
     * 获取队列状态
     */
    private function getQueueStatus($paymentType)
    {
        $queueType = $this->config['queue_type'] ?? 'file';
        
        switch ($queueType) {
            case 'redis':
                return $this->getRedisStatus($paymentType);
            case 'database':
                return $this->getDatabaseStatus($paymentType);
            case 'file':
            default:
                return $this->getFileStatus($paymentType);
        }
    }
    
    /**
     * 获取Redis队列状态
     */
    private function getRedisStatus($paymentType)
    {
        if (!class_exists('Redis')) {
            return ['pending' => 0, 'retry' => 0, 'failed' => 0];
        }
        
        try {
            $redis = new \Redis();
            $redis->connect(
                $this->config['redis']['host'] ?? '127.0.0.1',
                $this->config['redis']['port'] ?? 6379
            );
            
            if (!empty($this->config['redis']['password'])) {
                $redis->auth($this->config['redis']['password']);
            }
            
            $key = 'easypay_' . $paymentType . '_tasks';
            $failedKey = 'easypay_failed_tasks';
            
            return [
                'pending' => $redis->lLen($key),
                'retry' => 0,
                'failed' => $redis->lLen($failedKey)
            ];
            
        } catch (Exception $e) {
            return ['pending' => 0, 'retry' => 0, 'failed' => 0];
        }
    }
    
    /**
     * 获取数据库队列状态
     */
    private function getDatabaseStatus($paymentType)
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$this->config['database']['host']};port={$this->config['database']['port']};dbname={$this->config['database']['name']}",
                $this->config['database']['user'],
                $this->config['database']['pass']
            );
            
            $sql = "SELECT status, COUNT(*) as count FROM easypay_async_tasks 
                    WHERE payment_type = ? GROUP BY status";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$paymentType]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $status = ['pending' => 0, 'retry' => 0, 'failed' => 0];
            foreach ($rows as $row) {
                $status[$row['status']] = (int)$row['count'];
            }
            
            return $status;
            
        } catch (Exception $e) {
            return ['pending' => 0, 'retry' => 0, 'failed' => 0];
        }
    }
    
    /**
     * 获取文件队列状态
     */
    private function getFileStatus($paymentType)
    {
        $queueDir = __DIR__ . '/queue';
        $file = $queueDir . '/' . $paymentType . '_tasks.json';
        
        if (!file_exists($file)) {
            return ['pending' => 0, 'retry' => 0, 'failed' => 0];
        }
        
        $tasks = json_decode(file_get_contents($file), true) ?: [];
        
        $status = ['pending' => 0, 'retry' => 0, 'failed' => 0];
        
        foreach ($tasks as $task) {
            if (isset($task['status'])) {
                $status[$task['status']]++;
            } else {
                $status['pending']++;
            }
        }
        
        return $status;
    }
    
    /**
     * 显示系统状态
     */
    private function showSystemStatus()
    {
        echo "系统状态:\n";
        
        // 检查日志文件
        $logFile = $this->config['log_file'] ?? __DIR__ . '/logs/async_queue/async.log';
        if (file_exists($logFile)) {
            $size = filesize($logFile);
            echo "  日志文件: " . basename($logFile) . " (" . $this->formatBytes($size) . ")\n";
            
            // 显示最近的错误
            $this->showRecentErrors($logFile);
        }
        
        // 检查目录权限
        $dirs = ['queue', 'logs'];
        foreach ($dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (is_dir($path)) {
                $writable = is_writable($path) ? "可写" : "不可写";
                echo "  $dir 目录: $writable\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * 显示最近的错误
     */
    private function showRecentErrors($logFile)
    {
        $lines = $this->tailFile($logFile, 10);
        $errors = array_filter($lines, function($line) {
            return strpos($line, '"level":"ERROR"') !== false;
        });
        
        if (!empty($errors)) {
            echo "  最近错误:\n";
            foreach (array_slice($errors, 0, 3) as $error) {
                echo "    " . substr($error, 0, 100) . "...\n";
            }
        }
    }
    
    /**
     * 获取文件末尾内容
     */
    private function tailFile($filename, $lines = 10)
    {
        if (!file_exists($filename)) {
            return [];
        }
        
        $output = [];
        $file = fopen($filename, 'r');
        if ($file) {
            $lineCount = 0;
            $pos = -2;
            
            fseek($file, $pos, SEEK_END);
            while ($lineCount < $lines && ftell($file) > 0) {
                $char = fgetc($file);
                if ($char == "\n") {
                    $lineCount++;
                }
                fseek($file, --$pos, SEEK_END);
            }
            
            while (!feof($file)) {
                $output[] = trim(fgets($file));
            }
            fclose($file);
        }
        
        return array_filter($output);
    }
    
    /**
     * 格式化字节大小
     */
    private function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
    
    /**
     * 清理失败任务
     */
    public function cleanupFailed($paymentType = null)
    {
        if ($paymentType) {
            $this->cleanupPaymentType($paymentType);
        } else {
            $paymentTypes = [
                'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay',
                'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
            ];
            
            foreach ($paymentTypes as $type) {
                $this->cleanupPaymentType($type);
            }
        }
    }
    
    /**
     * 清理单个支付类型的失败任务
     */
    private function cleanupPaymentType($paymentType)
    {
        $queueType = $this->config['queue_type'] ?? 'file';
        
        switch ($queueType) {
            case 'file':
                $this->cleanupFileFailed($paymentType);
                break;
            case 'redis':
                // Redis失败任务保留在单独队列，不需要清理
                break;
            case 'database':
                $this->cleanupDatabaseFailed($paymentType);
                break;
        }
    }
    
    /**
     * 清理文件队列失败任务
     */
    private function cleanupFileFailed($paymentType)
    {
        $queueDir = __DIR__ . '/queue';
        $failedFile = $queueDir . '/failed_tasks.json';
        
        if (file_exists($failedFile)) {
            $failed = json_decode(file_get_contents($failedFile), true) ?: [];
            $remaining = array_filter($failed, function($task) use ($paymentType) {
                return $task['payment_type'] != $paymentType;
            });
            
            if (count($remaining) < count($failed)) {
                file_put_contents($failedFile, json_encode(array_values($remaining)));
                echo "清理了 " . (count($failed) - count($remaining)) . " 个 $paymentType 的失败任务\n";
            }
        }
    }
    
    /**
     * 清理数据库失败任务
     */
    private function cleanupDatabaseFailed($paymentType)
    {
        try {
            $pdo = new \PDO(
                "mysql:host={$this->config['database']['host']};port={$this->config['database']['port']};dbname={$this->config['database']['name']}",
                $this->config['database']['user'],
                $this->config['database']['pass']
            );
            
            $sql = "DELETE FROM easypay_async_tasks WHERE payment_type = ? AND status = 'failed'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$paymentType]);
            
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "清理了 $count 个 $paymentType 的失败任务\n";
            }
            
        } catch (Exception $e) {
            echo "清理失败: " . $e->getMessage() . "\n";
        }
    }
}

// 命令行接口
if (basename($_SERVER['PHP_SELF']) === 'monitor_async.php') {
    $monitor = new AsyncMonitor();
    
    $action = $_GET['action'] ?? $_SERVER['argv'][1] ?? 'monitor';
    $paymentType = $_GET['type'] ?? $_SERVER['argv'][2] ?? null;
    
    switch ($action) {
        case 'monitor':
        case 'status':
            $monitor->monitorAll();
            break;
            
        case 'cleanup':
            $monitor->cleanupFailed($paymentType);
            break;
            
        case 'help':
        default:
            echo "用法:\n";
            echo "  php monitor_async.php [action] [type]\n\n";
            echo "动作:\n";
            echo "  monitor/status - 显示所有支付方式状态\n";
            echo "  cleanup - 清理失败任务\n";
            echo "  help - 显示帮助\n\n";
            echo "示例:\n";
            echo "  php monitor_async.php monitor\n";
            echo "  php monitor_async.php cleanup alipay\n";
            echo "  ?action=status&type=wxpay - HTTP访问\n";
    }
}