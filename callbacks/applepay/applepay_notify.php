<?php

/**
 * Apple Pay 异步回调处理
 * 
 * 处理 Apple Pay 的异步通知和支付结果验证
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Applepay\Payment\ApplePayService;
use Common\Payment\AsyncProcessor;

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 创建日志文件
$logFile = __DIR__ . '/applepay_notify.log';

// 日志函数
function logMessage($message, $type = 'INFO')
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
}

// 创建 Apple Pay 服务实例
$applePay = new ApplePayService();

// 创建异步处理器
$config = require __DIR__ . '/../../config/config.php';
$asyncProcessor = new AsyncProcessor($config, 'applepay', $applePay);

// 获取回调数据
$method = $_SERVER['REQUEST_METHOD'];
$callbackData = [];

if ($method === 'POST') {
    // 获取POST数据
    $input = file_get_contents('php://input');
    $callbackData = json_decode($input, true) ?: $_POST;
} else {
    // 获取GET数据
    $callbackData = $_GET;
}

// 记录原始回调数据
logMessage("收到 Apple Pay 回调，方法: $method");
logMessage("原始数据: " . json_encode($callbackData, JSON_UNESCAPED_UNICODE));

// 验证回调数据
if (empty($callbackData)) {
    logMessage("错误: 回调数据为空", 'ERROR');
    echo json_encode(['status' => 'FAIL', 'message' => '回调数据为空']);
    exit;
}

// 验证支付数据
if (isset($callbackData['payment_data'])) {
    $validationResult = $applePay->validatePayment($callbackData);
    
    if ($validationResult['code'] !== 0) {
        logMessage("错误: 支付数据验证失败 - " . $validationResult['message'], 'ERROR');
        echo json_encode(['status' => 'FAIL', 'message' => $validationResult['message']]);
        exit;
    }
    
    logMessage("支付数据验证成功", 'SUCCESS');
    
    // 提取关键信息
    $transactionId = $validationResult['data']['transaction_id'] ?? '';
    $paymentMethod = $validationResult['data']['payment_method'] ?? [];
    $amount = $validationResult['data']['amount'] ?? 0;
    $outTradeNo = $callbackData['order_data']['out_trade_no'] ?? '';
    
    logMessage("交易ID: $transactionId, 订单号: $outTradeNo, 金额: $amount");
    
    // 准备异步处理数据
    $asyncData = array_merge($callbackData, [
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'validation_result' => $validationResult
    ]);
    
    // 异步处理通知
    $asyncResult = $asyncProcessor->processAsyncNotify($asyncData);
    
    if ($asyncResult['success']) {
        echo json_encode(['status' => 'SUCCESS', 'message' => '已加入异步处理队列']);
    } else {
        logMessage("异步处理失败: " . $asyncResult['message'], 'ERROR');
        echo json_encode(['status' => 'FAIL', 'message' => $asyncResult['message']]);
    }
    
} else {
    logMessage("错误: 缺少支付数据", 'ERROR');
    echo json_encode(['status' => 'FAIL', 'message' => '缺少支付数据']);
}