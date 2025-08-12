<?php

/**
 * 通用异步任务处理器
 * 
 * 支持所有支付方式的异步任务处理
 * 
 * 使用方法:
 * 1. 命令行模式: php async_processor.php --type=alipay --limit=10
 * 2. HTTP模式: GET /async_processor.php?type=alipay&limit=10
 * 3. 定时任务: */5 * * * * php /path/to/async_processor.php --type=alipay --limit=50
 */

require_once __DIR__ . '/vendor/autoload.php';

use Common\Payment\AsyncProcessor;

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 创建日志目录
$logDir = __DIR__ . '/logs/async_queue';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// 日志函数
function logMessage($message, $type = 'INFO', $paymentType = 'ALL')
{
    global $logDir;
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] [$type] [$paymentType] $message\n";
    $logFile = $logDir . '/' . date('Y-m-d') . '_processor.log';
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
}

// 获取参数
$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    // 命令行参数解析
    $options = getopt('t:l:h', ['type:', 'limit:', 'help', 'all']);
    $type = $options['type'] ?? $options['t'] ?? 'all';
    $limit = intval($options['limit'] ?? $options['l'] ?? 10);
    $all = isset($options['all']) || isset($options['a']);
    $help = isset($options['help']) || isset($options['h']);
} else {
    // HTTP参数解析
    $type = $_GET['type'] ?? 'all';
    $limit = intval($_GET['limit'] ?? 10);
    $all = isset($_GET['all']);
    $help = isset($_GET['help']);
}

// 显示帮助信息
if ($help) {
    echo "通用异步任务处理器\n\n";
    echo "使用方法:\n";
    echo "1. 命令行: php async_processor.php [选项]\n";
    echo "2. HTTP: GET /async_processor.php?参数\n\n";
    echo "选项:\n";
    echo "  -t, --type=TYPE    支付方式类型 (alipay, wxpay, huaweipay, 等) 或 'all'\n";
    echo "  -l, --limit=NUM    处理任务数量限制 (默认: 10)\n";
    echo "  --all              处理所有支付方式\n";
    echo "  -h, --help         显示帮助信息\n\n";
    echo "示例:\n";
    echo "  php async_processor.php --type=alipay --limit=20\n";
    echo "  php async_processor.php --all --limit=50\n";
    echo "  curl 'http://your-domain.com/async_processor.php?type=wxpay&limit=10'\n";
    exit;
}

// 加载配置
$config = require __DIR__ . '/config/config.php';

// 支持的支付方式
$paymentTypes = [
    'alipay', 'wxpay', 'huaweipay', 'applepay', 'douyinpay', 
    'kuaishoupay', 'jdpay', 'pinduoduopay', 'meituanpay', 'cmbpay'
];

// 处理函数
function processPaymentType($paymentType, $config, $limit) {
    try {
        // 根据支付方式加载对应的服务类
        $serviceClass = getPaymentServiceClass($paymentType);
        if (!$serviceClass) {
            logMessage("不支持的支付方式: $paymentType", 'ERROR');
            return false;
        }
        
        $service = new $serviceClass();
        $processor = new AsyncProcessor($config, $paymentType, $service);
        
        logMessage("开始处理 $paymentType 的异步任务，限制: $limit");
        
        $results = $processor->processTaskQueue($limit);
        
        $total = count($results);
        $success = count(array_filter($results, function($r) { return $r['success']; }));
        
        logMessage("处理完成 - 总计: $total, 成功: $success, 支付方式: $paymentType");
        
        return [
            'payment_type' => $paymentType,
            'total' => $total,
            'success' => $success,
            'failed' => $total - $success,
            'results' => $results
        ];
        
    } catch (Exception $e) {
        logMessage("处理异常: " . $e->getMessage(), 'ERROR', $paymentType);
        return false;
    }
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

// 创建数据库表
function createDatabaseTables() {
    try {
        $config = require __DIR__ . '/config/config.php';
        $pdo = getDatabaseConnection($config);
        
        $sql = file_get_contents(__DIR__ . '/database/async_tasks.sql');
        $pdo->exec($sql);
        
        logMessage("数据库表创建成功");
        return true;
    } catch (Exception $e) {
        logMessage("数据库表创建失败: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// 获取数据库连接
function getDatabaseConnection($config) {
    $host = $config['db_host'] ?? 'localhost';
    $port = $config['db_port'] ?? 3306;
    $dbname = $config['db_name'] ?? 'easypay';
    $username = $config['db_user'] ?? 'root';
    $password = $config['db_pass'] ?? '';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}

// 主处理逻辑
logMessage("异步任务处理器启动");

// 检查是否需要创建数据库表
if (isset($_GET['setup']) || in_array('--setup', $argv ?? [])) {
    createDatabaseTables();
    exit;
}

// 处理逻辑
$results = [];

if ($type === 'all' || $all) {
    // 处理所有支付方式
    foreach ($paymentTypes as $paymentType) {
        $result = processPaymentType($paymentType, $config, $limit);
        if ($result) {
            $results[] = $result;
        }
    }
} else {
    // 处理指定支付方式
    if (!in_array($type, $paymentTypes)) {
        logMessage("无效的支付方式: $type", 'ERROR');
        echo "错误: 无效的支付方式 '$type'\n支持的支付方式: " . implode(', ', $paymentTypes) . "\n";
        exit(1);
    }
    
    $result = processPaymentType($type, $config, $limit);
    if ($result) {
        $results[] = $result;
    }
}

// 输出结果
$output = [
    'status' => 'success',
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results
];

if ($isCli) {
    echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($output, JSON_UNESCAPED_UNICODE);
}

logMessage("异步任务处理器完成");