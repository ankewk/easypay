<?php
// 自动加载依赖
require_once __DIR__ . '/../../vendor/autoload.php';

use Alipay\Payment\AlipayService;

// 创建支付服务实例（会自动加载配置）
$payService = new AlipayService();

// 订单信息
$order = [
    'out_trade_no' => 'TEST' . date('YmdHis'),
    'total_amount' => 0.01, // 单位：元
    'subject' => '测试商品',
    'body' => '测试商品描述',
    'timeout_express' => '1h', // 订单超时时间
    'return_url' => config('alipay.return_url'), // 同步回调URL
];

// 电脑网站支付
$result = $payService->pagePay($order);

if ($result['success']) {
    // 支付页面重定向
    echo $result['data'];
} else {
    // 支付失败
    echo "错误：" . $result['error'];
}