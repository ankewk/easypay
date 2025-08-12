<?php
// 自动加载依赖
require_once __DIR__ . '/../../vendor/autoload.php';

use Cmbpay\Payment\CmbPayService;

// 创建支付服务实例（会自动加载配置）
$payService = new CmbPayService();

// 订单信息
$order = [
    'out_trade_no' => 'TEST' . date('YmdHis'),
    'total_amount' => 0.01, // 单位：元
    'subject' => '测试商品',
    'body' => '测试商品描述',
    'timeout_express' => '1h', // 订单超时时间
    'return_url' => config('cmbpay.return_url'), // 同步回调URL
];

// 生成支付参数
$result = $payService->generatePayParams($order);

if ($result['success']) {
    // 支付参数生成成功
    $payParams = $result['data'];
    echo "<pre>";
    echo "支付参数：\n";
    print_r($payParams);
    echo "</pre>";

    // 这里可以将支付参数返回给前端，用于调起云闪付
} else {
    // 支付参数生成失败
    echo "错误：" . $result['error'];
}