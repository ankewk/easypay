<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Huaweipay\Payment\HuaweiPayService;
use Huaweipay\Payment\AsyncProcessor;

/**
 * 华为支付异步通知处理
 * 
 * 处理华为支付的异步通知，验证签名并更新订单状态
 */

// 日志函数
function logMessage($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../../logs/huaweipay';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/' . date('Y-m-d') . '.log';
    $time = date('Y-m-d H:i:s');
    $log = "[$time] [$level] $message\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
}

// 加载配置
$config = [
    'merchant_id' => 'your_merchant_id',
    'app_id' => 'your_app_id',
    'private_key' => 'your_private_key',
    'public_key' => 'your_public_key',
    'gateway_url' => 'https://pay-api.huawei.com/gateway',
    'notify_url' => 'https://your-domain.com/callbacks/huaweipay/huawei_notify.php',
];

try {
    logMessage("收到华为支付异步通知");
    
    // 获取通知数据
    $notifyData = $_POST;
    
    if (empty($notifyData)) {
        $input = file_get_contents('php://input');
        $notifyData = json_decode($input, true) ?: [];
    }
    
    logMessage("通知数据: " . json_encode($notifyData, JSON_UNESCAPED_UNICODE));
    
    // 创建异步处理器
    $asyncProcessor = new AsyncProcessor($config);
    
    // 处理异步通知
    $result = $asyncProcessor->processAsyncNotify($notifyData);
    
    if ($result['success']) {
        logMessage("异步处理成功 - 任务ID: " . $result['task_id']);
        echo "SUCCESS";
    } else {
        logMessage("异步处理失败: " . $result['message'], 'ERROR');
        echo "FAIL";
    }
    
} catch (Exception $e) {
    logMessage("异常: " . $e->getMessage(), 'ERROR');
    logMessage("异常追踪: " . $e->getTraceAsString(), 'ERROR');
    echo "FAIL";
}

/**
 * 更新订单状态示例函数
 * 
 * @param string $outTradeNo 商户订单号
 * @param string $status 订单状态
 * @param array $data 附加数据
 */
function updateOrderStatus($outTradeNo, $status, $data = []) {
    // 这里实现订单状态更新逻辑
    // 例如：更新数据库、发送通知等
    
    $logMessage = "更新订单状态 - 订单: $outTradeNo, 状态: $status";
    if (!empty($data)) {
        $logMessage .= ", 数据: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    logMessage($logMessage);
    
    // 示例：更新数据库
    // $pdo = new PDO('mysql:host=localhost;dbname=easypay', 'username', 'password');
    // $stmt = $pdo->prepare("UPDATE orders SET status = ?, transaction_id = ?, paid_amount = ?, paid_time = ? WHERE out_trade_no = ?");
    // $stmt->execute([$status, $data['transaction_id'] ?? '', $data['paid_amount'] ?? 0, $data['paid_time'] ?? null, $outTradeNo]);
    
    // 发送通知（邮件、短信等）
    // sendNotification($outTradeNo, $status, $data);
}

/**
 * 发送通知示例函数
 * 
 * @param string $outTradeNo 商户订单号
 * @param string $status 订单状态
 * @param array $data 附加数据
 */
function sendNotification($outTradeNo, $status, $data = []) {
    // 这里实现通知发送逻辑
    // 例如：发送邮件、短信、站内信等
    
    logMessage("发送通知 - 订单: $outTradeNo, 状态: $status");
    
    // 示例：发送邮件
    // $to = 'customer@example.com';
    // $subject = '订单支付成功通知';
    // $message = "您的订单 $outTradeNo 已支付成功，交易号：" . ($data['transaction_id'] ?? '');
    // mail($to, $subject, $message);
}

/**
 * 安全验证函数
 * 
 * @param array $notifyData 通知数据
 * @return bool 验证结果
 */
function securityCheck($notifyData) {
    // IP白名单检查
    $allowedIPs = [
        '123.58.182.0/24', // 华为支付服务器IP段
        '123.58.183.0/24',
    ];
    
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // 这里实现IP白名单验证逻辑
    // if (!ipInRange($clientIP, $allowedIPs)) {
    //     logMessage("IP地址不在白名单: $clientIP", 'ERROR');
    //     return false;
    // }
    
    // 频率限制检查
    // if (checkFrequencyLimit($notifyData['out_trade_no'] ?? '')) {
    //     logMessage("频率限制触发", 'WARNING');
    //     return false;
    // }
    
    return true;
}

/**
 * IP地址范围检查
 * 
 * @param string $ip IP地址
 * @param array $ranges IP范围数组
 * @return bool 检查结果
 */
function ipInRange($ip, $ranges) {
    foreach ($ranges as $range) {
        if (strpos($range, '/') !== false) {
            list($subnet, $mask) = explode('/', $range);
            if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
                return true;
            }
        } else {
            if ($ip === $range) {
                return true;
            }
        }
    }
    return false;
}

/**
 * 频率限制检查
 * 
 * @param string $outTradeNo 商户订单号
 * @return bool 是否触发限制
 */
function checkFrequencyLimit($outTradeNo) {
    // 这里实现频率限制逻辑
    // 例如：使用Redis或数据库记录通知次数
    return false;
}

// 设置响应头
header('Content-Type: text/plain; charset=utf-8');

// 记录通知完成
logMessage("华为支付异步通知处理完成");
?>