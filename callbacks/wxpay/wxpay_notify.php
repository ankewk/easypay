<?php
// 自动加载依赖
require_once __DIR__ . '/../../vendor/autoload.php';

use Wxpay\Payment\WxPayService;
use Common\Payment\AsyncProcessor;

// 创建支付服务实例（会自动加载配置）
$payService = new WxPayService();

// 创建异步处理器
$config = require __DIR__ . '/../../config/config.php';
$asyncProcessor = new AsyncProcessor($config, 'wxpay', $payService);

// 处理支付回调
$result = $payService->handleNotify();

if ($result['success']) {
    // 异步处理通知
    $asyncResult = $asyncProcessor->processAsyncNotify($result['data']);
    
    if ($asyncResult['success']) {
        $response = $result['data'];
        $response->send(); // 向微信支付平台发送成功响应
    } else {
        file_put_contents('wxpay_async_error.log', '异步处理失败: ' . $asyncResult['message'] . '\n', FILE_APPEND);
    }
} else {
    // 记录错误日志
    file_put_contents('wxpay_notify_error.log', '错误：' . $result['error'] . '\n', FILE_APPEND);
}