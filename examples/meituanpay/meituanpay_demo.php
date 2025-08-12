<?php

/**
 * 美团支付示例
 * 
 * 本示例演示如何使用美团支付服务
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Meituanpay\Payment\MeituanPayService;

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 创建美团支付服务实例
$meituanPay = new MeituanPayService();

// 演示1：创建支付订单
echo "=== 美团支付订单创建示例 ===\n";

$orderData = [
    'out_trade_no' => 'MT' . date('YmdHis') . rand(1000, 9999),
    'total_amount' => 0.01,
    'subject' => '测试商品',
    'body' => '美团支付测试商品',
    'return_url' => 'http://localhost:8000/examples/meituanpay/return.php',
    'timeout_express' => '30m',
];

try {
    $payResult = $meituanPay->generatePayParams($orderData);
    
    if ($payResult['code'] === 0) {
        echo "✅ 订单创建成功！\n";
        echo "订单号: " . $orderData['out_trade_no'] . "\n";
        echo "支付金额: ¥" . $orderData['total_amount'] . "\n";
        echo "支付链接: " . $payResult['data']['pay_url'] . "\n";
        
        if (!empty($payResult['data']['qr_code'])) {
            echo "支付二维码: " . $payResult['data']['qr_code'] . "\n";
        }
        
        echo "\n";
    } else {
        echo "❌ 订单创建失败: " . $payResult['message'] . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ 订单创建异常: " . $e->getMessage() . "\n\n";
}

// 演示2：查询订单
if (isset($orderData['out_trade_no'])) {
    echo "=== 美团支付订单查询示例 ===\n";
    
    try {
        $queryResult = $meituanPay->queryOrder($orderData['out_trade_no']);
        
        if ($queryResult['code'] === 0) {
            echo "✅ 订单查询成功！\n";
            echo "订单状态: " . $queryResult['data']['trade_status'] . "\n";
            echo "订单金额: ¥" . $queryResult['data']['total_amount'] . "\n";
            echo "创建时间: " . $queryResult['data']['gmt_create'] . "\n";
            
            if (isset($queryResult['data']['gmt_payment'])) {
                echo "支付时间: " . $queryResult['data']['gmt_payment'] . "\n";
            }
        } else {
            echo "❌ 订单查询失败: " . $queryResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ 订单查询异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 演示3：关闭订单
if (isset($orderData['out_trade_no'])) {
    echo "=== 美团支付关闭订单示例 ===\n";
    
    try {
        $closeResult = $meituanPay->closeOrder($orderData['out_trade_no']);
        
        if ($closeResult['code'] === 0) {
            echo "✅ 订单关闭成功！\n";
            echo "订单号: " . $orderData['out_trade_no'] . "\n";
        } else {
            echo "❌ 订单关闭失败: " . $closeResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ 订单关闭异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 演示4：申请退款
if (isset($orderData['out_trade_no'])) {
    echo "=== 美团支付退款示例 ===\n";
    
    $refundNo = 'RF' . date('YmdHis') . rand(1000, 9999);
    $refundData = [
        'out_trade_no' => $orderData['out_trade_no'],
        'refund_amount' => $orderData['total_amount'],
        'out_refund_no' => $refundNo,
        'reason' => '测试退款',
    ];
    
    try {
        $refundResult = $meituanPay->refund(
            $refundData['out_trade_no'],
            $refundData['refund_amount'],
            $refundData['out_refund_no'],
            $refundData['reason']
        );
        
        if ($refundResult['code'] === 0) {
            echo "✅ 退款申请成功！\n";
            echo "退款单号: " . $refundNo . "\n";
            echo "退款金额: ¥" . $refundData['refund_amount'] . "\n";
        } else {
            echo "❌ 退款申请失败: " . $refundResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ 退款申请异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 演示5：获取配置信息
echo "=== 美团支付配置信息 ===\n";
echo "网关地址: https://api.meituan.com\n";
echo "通知地址: http://localhost:8000/callbacks/meituanpay/meituanpay_notify.php\n";
echo "\n";

// 返回结果示例
$response = [
    'status' => 'success',
    'message' => '美团支付演示完成',
    'data' => [
        'order_created' => isset($payResult) && $payResult['code'] === 0,
        'order_no' => $orderData['out_trade_no'] ?? null,
        'amount' => $orderData['total_amount'] ?? 0,
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);