<?php
// 自动加载依赖
require_once __DIR__ . '/../../vendor/autoload.php';

use Jdpay\Payment\JdPayService;
use Common\Payment\AsyncProcessor;

// 创建支付服务实例（会自动加载配置）
$payService = new JdPayService();

// 创建异步处理器
$config = require __DIR__ . '/../../config/config.php';
$asyncProcessor = new AsyncProcessor($config, 'jdpay', $payService);

// 获取回调数据
$data = $_POST;

// 异步处理通知
$result = $asyncProcessor->processAsyncNotify($data);

if ($result['success']) {
    echo 'success'; // 通知京东支付已成功接收
} else {
    echo 'fail';    // 通知京东支付需要重试
    file_put_contents('jdpay_async_error.log', date('Y-m-d H:i:s') . ' - ' . $result['message'] . '\n', FILE_APPEND);
}