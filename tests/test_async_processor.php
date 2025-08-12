<?php

/**
 * 异步处理器测试脚本
 * 
 * 测试所有支付方式的异步处理功能
 */

require_once __DIR__ . '/../vendor/autoload.php';

class AsyncProcessorTester {
    
    private $testResults = [];
    private $config;
    
    public function __construct() {
        // 测试配置
        $this->config = [
            'queue_type' => 'file', // 测试使用文件模式
            'max_retries' => 2,
            'batch_size' => 5,
            'log_file' => __DIR__ . '/../logs/test_async.log'
        ];
        
        // 创建日志目录
        if (!is_dir(dirname($this->config['log_file']))) {
            mkdir(dirname($this->config['log_file']), 0755, true);
        }
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests() {
        echo "开始测试所有支付方式的异步处理功能...\n\n";
        
        $paymentTypes = [
            'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay',
            'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
        ];
        
        foreach ($paymentTypes as $type) {
            $this->testPaymentType($type);
        }
        
        $this->printSummary();
    }
    
    /**
     * 测试单个支付方式
     */
    private function testPaymentType($paymentType) {
        echo "测试支付方式: $paymentType\n";
        
        try {
            // 测试回调处理器
            $this->testCallbackHandler($paymentType);
            
            // 测试异步处理器
            $this->testAsyncProcessor($paymentType);
            
            // 测试任务队列
            $this->testTaskQueue($paymentType);
            
            $this->testResults[$paymentType] = '通过';
            echo "✓ $paymentType 测试通过\n\n";
            
        } catch (Exception $e) {
            $this->testResults[$paymentType] = '失败: ' . $e->getMessage();
            echo "✗ $paymentType 测试失败: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 测试回调处理器
     */
    private function testCallbackHandler($paymentType) {
        $handlerPath = __DIR__ . "/../callbacks/{$paymentType}/{$paymentType}_notify.php";
        
        if (!file_exists($handlerPath)) {
            throw new Exception("回调处理器文件不存在: $handlerPath");
        }
        
        $content = file_get_contents($handlerPath);
        
        // 检查是否包含异步处理器
        if (strpos($content, 'Common\Payment\AsyncProcessor') === false) {
            throw new Exception("未找到异步处理器引用");
        }
        
        if (strpos($content, 'processAsyncNotify') === false) {
            throw new Exception("未找到processAsyncNotify调用");
        }
    }
    
    /**
     * 测试异步处理器
     */
    private function testAsyncProcessor($paymentType) {
        $serviceClass = $this->getPaymentServiceClass($paymentType);
        if (!$serviceClass) {
            throw new Exception("找不到支付服务类");
        }
        
        // 模拟服务类
        $service = new $serviceClass();
        
        // 创建异步处理器
        $processor = new \Common\Payment\AsyncProcessor($this->config, $paymentType, $service);
        
        if (!$processor) {
            throw new Exception("无法创建异步处理器");
        }
    }
    
    /**
     * 测试任务队列
     */
    private function testTaskQueue($paymentType) {
        // 创建测试任务
        $testTask = [
            'order_no' => 'TEST_' . strtoupper($paymentType) . '_' . time(),
            'trade_no' => 'TRADE_' . time(),
            'trade_status' => 'SUCCESS',
            'total_amount' => 100.00,
            'payment_type' => $paymentType,
            'notify_time' => date('Y-m-d H:i:s'),
            'raw_data' => json_encode(['test' => true])
        ];
        
        // 模拟添加到队列
        $queueFile = __DIR__ . "/../queue/{$paymentType}_tasks.json";
        
        if (!is_dir(dirname($queueFile))) {
            mkdir(dirname($queueFile), 0755, true);
        }
        
        // 测试队列文件写入
        $tasks = [$testTask];
        if (!file_put_contents($queueFile, json_encode($tasks))) {
            throw new Exception("无法写入队列文件");
        }
        
        // 清理测试文件
        @unlink($queueFile);
    }
    
    /**
     * 获取支付服务类
     */
    private function getPaymentServiceClass($paymentType) {
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
    
    /**
     * 打印测试总结
     */
    private function printSummary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "测试总结:\n";
        echo str_repeat("=", 50) . "\n";
        
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $type => $result) {
            $status = strpos($result, '通过') !== false ? '✓' : '✗';
            echo "$status $type: $result\n";
            
            if (strpos($result, '通过') !== false) {
                $passed++;
            }
        }
        
        echo str_repeat("=", 50) . "\n";
        echo "总计: $passed/$total 通过\n";
        
        if ($passed === $total) {
            echo "🎉 所有支付方式异步处理功能测试通过!\n";
        } else {
            echo "⚠️  部分支付方式测试失败，请检查配置\n";
        }
    }
    
    /**
     * 生成测试数据
     */
    public function generateTestData() {
        echo "生成测试数据...\n";
        
        $paymentTypes = [
            'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay',
            'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
        ];
        
        foreach ($paymentTypes as $type) {
            $this->createTestTask($type);
        }
        
        echo "测试数据生成完成\n";
    }
    
    /**
     * 创建测试任务
     */
    private function createTestTask($paymentType) {
        $task = [
            'order_no' => 'TEST_' . strtoupper($paymentType) . '_' . time(),
            'trade_no' => 'TRADE_' . uniqid(),
            'trade_status' => 'SUCCESS',
            'total_amount' => mt_rand(1, 1000) / 100,
            'payment_type' => $paymentType,
            'notify_time' => date('Y-m-d H:i:s'),
            'raw_data' => json_encode([
                'test' => true,
                'payment_type' => $paymentType,
                'timestamp' => time()
            ])
        ];
        
        // 保存到测试数据目录
        $testDir = __DIR__ . '/test_data';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $file = "$testDir/{$paymentType}_test.json";
        file_put_contents($file, json_encode([$task], JSON_PRETTY_PRINT));
    }
    
    /**
     * 清理测试数据
     */
    public function cleanup() {
        $testDir = __DIR__ . '/test_data';
        $queueDir = __DIR__ . '/../queue';
        
        // 清理测试数据
        if (is_dir($testDir)) {
            $files = glob($testDir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($testDir);
        }
        
        // 清理队列文件
        if (is_dir($queueDir)) {
            $files = glob($queueDir . '/*.json');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($queueDir);
        }
        
        echo "测试数据清理完成\n";
    }
}

// 命令行接口
if (basename($_SERVER['PHP_SELF']) === 'test_async_processor.php') {
    $tester = new AsyncProcessorTester();
    
    $action = $_GET['action'] ?? $_SERVER['argv'][1] ?? 'test';
    
    switch ($action) {
        case 'test':
            $tester->runAllTests();
            break;
            
        case 'generate':
            $tester->generateTestData();
            break;
            
        case 'cleanup':
            $tester->cleanup();
            break;
            
        default:
            echo "用法:\n";
            echo "  php test_async_processor.php test      - 运行所有测试\n";
            echo "  php test_async_processor.php generate  - 生成测试数据\n";
            echo "  php test_async_processor.php cleanup   - 清理测试数据\n";
            echo "  ?action=test                           - HTTP访问测试\n";
    }
}