<?php
// 自动加载依赖
require_once __DIR__ . '/../../vendor/autoload.php';

use Wxpay\Payment\WxPayService;

// 创建支付服务实例（会自动加载配置）
$payService = new WxPayService();

// 订单信息
$order = [
    'out_trade_no' => 'TEST' . date('YmdHis'),
    'total_fee' => 1, // 单位：分
    'body' => '测试商品',
    'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
    'trade_type' => 'JSAPI',
    'openid' => 'user-openid', // 用户openid
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

    // 这里可以将支付参数返回给前端，用于调起微信支付
} else {
    // 支付参数生成失败
    echo "错误：" . $result['error'];
}