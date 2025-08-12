<?php

/**
 * 异步处理配置示例
 * 
 * 展示如何配置和使用所有支付方式的异步处理功能
 */

// 基础配置示例
$asyncConfig = [
    // 队列类型: redis, database, file
    'queue_type' => 'redis',
    
    // Redis配置
    'redis_host' => '127.0.0.1',
    'redis_port' => 6379,
    'redis_password' => '',
    'redis_database' => 0,
    
    // 数据库配置
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'easypay',
    'db_user' => 'root',
    'db_pass' => '',
    
    // 重试策略
    'max_retries' => 3,
    'retry_delay' => 5, // 秒
    
    // 日志配置
    'log_level' => 'INFO',
    'log_file' => __DIR__ . '/../logs/async_queue/async.log',
    
    // 批处理配置
    'batch_size' => 50,
    'process_interval' => 5, // 秒
];

// 创建异步处理器示例
function createAsyncProcessor($paymentType, $config) {
    // 加载支付服务类
    $serviceClass = getPaymentServiceClass($paymentType);
    if (!$serviceClass) {
        throw new Exception("不支持的支付方式: $paymentType");
    }
    
    $service = new $serviceClass();
    $processor = new \Common\Payment\AsyncProcessor($config, $paymentType, $service);
    
    return $processor;
}

// 获取支付服务类
function getPaymentServiceClass($paymentType) {
    $classMap = [
        'alipay' => 'Alipay\Payment\AlipayService',
        'wxpay' => 'Wxpay\Payment\WxPayService',
        'huaweipay' => 'Huaweipay\Payment\HuaweiPayService',
        'applepay' => 'Applepay\Payment\ApplePayService',
        'douyinpay' => 'Douyinpay\Payment\DouyinPayService',
        'kuaishoupay' => 'Kuaishoupay\Payment\KuaishouPayService',
        'jdpay' => 'Jdpay\Payment\JdPayService',
        'pinduoduopay' => 'Pinduoduopay\Payment\PinduoduoPayService',
        'meituanpay' => 'Meituanpay\Payment\MeituanPayService',
        'cmbpay' => 'Cmbpay\Payment\CmbPayService'
    ];
    
    return $classMap[$paymentType] ?? null;
}

// 使用示例

// 示例1: 处理单个支付方式的异步任务
function exampleSinglePayment() {
    global $asyncConfig;
    
    try {
        $processor = createAsyncProcessor('alipay', $asyncConfig);
        
        // 处理10个任务
        $results = $processor->processTaskQueue(10);
        
        echo "处理结果:\n";
        foreach ($results as $result) {
            echo "订单: {$result['order_no']}, 状态: " . ($result['success'] ? '成功' : '失败') . "\n";
        }
        
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . "\n";
    }
}

// 示例2: 批量处理所有支付方式
function exampleAllPayments() {
    global $asyncConfig;
    
    $paymentTypes = [
        'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay',
        'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
    ];
    
    foreach ($paymentTypes as $type) {
        try {
            $processor = createAsyncProcessor($type, $asyncConfig);
            $results = $processor->processTaskQueue(5);
            
            $count = count($results);
            echo "处理 $type 的 $count 个任务\n";
            
        } catch (Exception $e) {
            echo "处理 $type 失败: " . $e->getMessage() . "\n";
        }
    }
}

// 示例3: 定时任务配置
function exampleCronJob() {
    echo "// 定时任务配置示例\n";
    echo "// 每5分钟处理所有支付方式的异步任务\n";
    echo "*/5 * * * * /usr/bin/php /path/to/easypay/async_processor.php --all --limit=50\n\n";
    
    echo "// 每小时处理支付宝的异步任务\n";
    echo "0 * * * * /usr/bin/php /path/to/easypay/async_processor.php --type=alipay --limit=100\n\n";
    
    echo "// 每10分钟处理微信支付的任务\n";
    echo "*/10 * * * * /usr/bin/php /path/to/easypay/async_processor.php --type=wxpay --limit=30\n\n";
}

// 示例4: 数据库模式使用
function exampleDatabaseMode() {
    $dbConfig = [
        'queue_type' => 'database',
        'db_host' => 'localhost',
        'db_port' => 3306,
        'db_name' => 'easypay',
        'db_user' => 'root',
        'db_pass' => 'password',
        'max_retries' => 3,
        'batch_size' => 20
    ];
    
    try {
        $processor = createAsyncProcessor('wxpay', $dbConfig);
        $results = $processor->processTaskQueue(20);
        
        echo "数据库模式处理结果:\n";
        echo "处理任务数: " . count($results) . "\n";
        
    } catch (Exception $e) {
        echo "数据库模式错误: " . $e->getMessage() . "\n";
    }
}

// 示例5: 文件模式使用（无需额外配置）
function exampleFileMode() {
    $fileConfig = [
        'queue_type' => 'file',
        'max_retries' => 2,
        'batch_size' => 10
    ];
    
    try {
        $processor = createAsyncProcessor('huaweipay', $fileConfig);
        $results = $processor->processTaskQueue(10);
        
        echo "文件模式处理结果:\n";
        echo "处理任务数: " . count($results) . "\n";
        
    } catch (Exception $e) {
        echo "文件模式错误: " . $e->getMessage() . "\n";
    }
}

// 示例6: 监控和日志
function exampleMonitoring() {
    echo "// 监控脚本示例\n";
    echo "// 检查异步任务队列长度\n";
    echo "// 可以创建监控脚本来检查队列状态\n\n";
    
    echo "// 检查Redis队列长度\n";
    echo "$redis = new Redis();\n";
    echo "$redis->connect('127.0.0.1', 6379);\n";
    echo "$length = $redis->lLen('easypay_alipay_tasks');\n";
    echo "echo '支付宝队列长度: ' . $length;\n\n";
    
    echo "// 检查数据库待处理任务数\n";
    echo "$pdo = new PDO('mysql:host=localhost;dbname=easypay', 'root', 'password');\n";
    echo "$stmt = $pdo->query('SELECT COUNT(*) FROM easypay_async_tasks WHERE status = \"pending\"');\n";
    echo "$count = $stmt->fetchColumn();\n";
    echo "echo '数据库待处理任务数: ' . $count;\n";
}

// 示例7: 错误处理和降级
function exampleErrorHandling() {
    echo "// 错误处理和降级策略示例\n";
    echo "// 当Redis不可用时自动降级到文件队列\n\n";
    
    echo "// 配置示例\n";
    echo "$config = [\n";
    echo "    'queue_type' => 'redis',\n";
    echo "    'fallback_type' => 'file', // 降级到文件队列\n";
    echo "    'redis_host' => '127.0.0.1',\n";
    echo "    'redis_port' => 6379,\n";
    echo "    'max_retries' => 3\n";
    echo "];\n\n";
    
    echo "// 错误日志监控\n";
    echo "// 定期检查错误日志文件大小\n";
    echo "// tail -f logs/async_queue/2024-01-01_async.log\n";
}

// 运行示例
if (basename($_SERVER['PHP_SELF']) === 'async_config.php') {
    echo "异步处理配置示例\n";
    echo "================\n\n";
    
    echo "1. 定时任务配置:\n";
    exampleCronJob();
    
    echo "\n2. 监控和日志:\n";
    exampleMonitoring();
    
    echo "\n3. 错误处理:\n";
    exampleErrorHandling();
}