<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Huaweipay\Payment\HuaweiPayService;

// 加载配置
$config = [
    'merchant_id' => 'your_merchant_id', // 华为商户号
    'app_id' => 'your_app_id', // 华为应用ID
    'private_key' => 'your_private_key', // 商户私钥
    'public_key' => 'your_public_key', // 华为公钥
    'gateway_url' => 'https://pay-api.huawei.com/gateway', // 华为支付网关
    'notify_url' => 'https://your-domain.com/callbacks/huaweipay/huawei_notify.php', // 回调地址
];

echo "=== 华为支付集成示例 ===\n\n";

// 创建支付服务实例
$payService = new HuaweiPayService($config);

// 1. 统一下单示例
function testUnifiedOrder($payService) {
    echo "1. 统一下单示例\n";
    
    $order = [
        'out_trade_no' => 'HW' . date('YmdHis') . rand(1000, 9999), // 商户订单号
        'total_amount' => 0.01, // 订单金额（元）
        'subject' => '测试商品', // 订单标题
        'body' => '华为支付测试商品描述', // 订单描述
        'currency' => 'CNY', // 货币类型
        'timeout_express' => '30m', // 订单过期时间
        'return_url' => 'https://your-domain.com/return.php', // 同步跳转地址
        'attach' => '自定义数据', // 附加数据
        'product_id' => 'P001', // 商品ID
        'scene' => 'WEB', // 支付场景
    ];
    
    echo "订单信息:\n";
    echo "订单号: " . $order['out_trade_no'] . "\n";
    echo "金额: " . $order['total_amount'] . "元\n";
    echo "商品: " . $order['subject'] . "\n\n";
    
    $result = $payService->generatePayParams($order);
    
    if ($result['success']) {
        echo "✅ 下单成功！\n";
        echo "预支付ID: " . $result['data']['prepay_id'] . "\n";
        echo "支付URL: " . ($result['data']['pay_url'] ?? 'N/A') . "\n";
        echo "二维码: " . ($result['data']['qr_code'] ?? 'N/A') . "\n";
        echo "过期时间: " . ($result['data']['expire_time'] ?? 'N/A') . "\n\n";
        
        return $order['out_trade_no'];
    } else {
        echo "❌ 下单失败: " . $result['error'] . "\n\n";
        return null;
    }
}

// 2. 订单查询示例
function testQueryOrder($payService, $outTradeNo) {
    echo "2. 订单查询示例\n";
    
    if (!$outTradeNo) {
        echo "跳过：没有订单号\n\n";
        return;
    }
    
    echo "查询订单: " . $outTradeNo . "\n";
    
    $result = $payService->queryOrder($outTradeNo);
    
    if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
        echo "✅ 查询成功！\n";
        echo "订单状态: " . $result['trade_state'] . "\n";
        echo "交易号: " . ($result['transaction_id'] ?? 'N/A') . "\n";
        echo "金额: " . ($result['total_amount'] ?? 0) / 100 . "元\n";
        echo "支付时间: " . ($result['time_end'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ 查询失败: " . ($result['err_code_des'] ?? $result['return_msg']) . "\n\n";
    }
}

// 3. 关闭订单示例
function testCloseOrder($payService, $outTradeNo) {
    echo "3. 关闭订单示例\n";
    
    if (!$outTradeNo) {
        echo "跳过：没有订单号\n\n";
        return;
    }
    
    echo "关闭订单: " . $outTradeNo . "\n";
    
    $result = $payService->closeOrder($outTradeNo);
    
    if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
        echo "✅ 关闭成功！\n\n";
    } else {
        echo "❌ 关闭失败: " . ($result['err_code_des'] ?? $result['return_msg']) . "\n\n";
    }
}

// 4. 申请退款示例
function testRefund($payService, $outTradeNo) {
    echo "4. 申请退款示例\n";
    
    if (!$outTradeNo) {
        echo "跳过：没有订单号\n\n";
        return;
    }
    
    $outRefundNo = 'RF' . date('YmdHis') . rand(1000, 9999); // 退款单号
    $refundAmount = 0.01; // 退款金额
    $reason = '测试退款'; // 退款原因
    
    echo "申请退款:\n";
    echo "订单号: " . $outTradeNo . "\n";
    echo "退款单号: " . $outRefundNo . "\n";
    echo "退款金额: " . $refundAmount . "元\n";
    echo "退款原因: " . $reason . "\n\n";
    
    $result = $payService->refund($outTradeNo, $refundAmount, $outRefundNo, $reason);
    
    if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
        echo "✅ 退款申请成功！\n";
        echo "退款单号: " . $result['out_refund_no'] . "\n";
        echo "退款金额: " . ($result['refund_amount'] ?? 0) / 100 . "元\n";
        echo "退款状态: " . $result['refund_status'] . "\n\n";
        
        return $outRefundNo;
    } else {
        echo "❌ 退款失败: " . ($result['err_code_des'] ?? $result['return_msg']) . "\n\n";
        return null;
    }
}

// 5. 退款查询示例
function testQueryRefund($payService, $outRefundNo) {
    echo "5. 退款查询示例\n";
    
    if (!$outRefundNo) {
        echo "跳过：没有退款单号\n\n";
        return;
    }
    
    echo "查询退款: " . $outRefundNo . "\n";
    
    $result = $payService->queryRefund($outRefundNo);
    
    if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
        echo "✅ 查询成功！\n";
        echo "退款状态: " . $result['refund_status'] . "\n";
        echo "退款金额: " . ($result['refund_amount'] ?? 0) / 100 . "元\n";
        echo "退款时间: " . ($result['refund_time'] ?? 'N/A') . "\n\n";
    } else {
        echo "❌ 查询失败: " . ($result['err_code_des'] ?? $result['return_msg']) . "\n\n";
    }
}

// 6. 签名验证示例
function testSignVerification($payService) {
    echo "6. 签名验证示例\n";
    
    $testData = [
        'merchantId' => 'test_merchant',
        'appId' => 'test_app',
        'outTradeNo' => 'test_order_001',
        'totalAmount' => 100,
        'timestamp' => date('YmdHis')
    ];
    
    // 生成签名
    $signStr = '';
    ksort($testData);
    foreach ($testData as $key => $value) {
        if ($key !== 'sign' && $value !== '' && $value !== null) {
            $signStr .= $key . '=' . $value . '&';
        }
    }
    $signStr = rtrim($signStr, '&');
    
    // 模拟签名（实际应用中需要真实私钥）
    $sign = base64_encode(hash('sha256', $signStr, true));
    
    echo "测试数据:\n";
    print_r($testData);
    echo "签名字符串: " . $signStr . "\n";
    echo "模拟签名: " . $sign . "\n\n";
    
    echo "注意：实际应用中需要使用真实私钥进行签名\n\n";
}

// 执行测试
$outTradeNo = testUnifiedOrder($payService);
testQueryOrder($payService, $outTradeNo);
testCloseOrder($payService, $outTradeNo);
$outRefundNo = testRefund($payService, $outTradeNo);
testQueryRefund($payService, $outRefundNo);
testSignVerification($payService);

echo "=== 华为支付集成示例完成 ===\n\n";

// 使用说明
echo "使用说明：\n";
echo "1. 请将配置中的参数替换为您的真实华为支付参数\n";
echo "2. 确保回调地址可以正常访问\n";
echo "3. 配置正确的商户证书和密钥\n";
echo "4. 测试前请确认华为支付账户已开通相关权限\n";
echo "5. 生产环境请使用HTTPS协议\n\n";

// 示例代码片段
echo "快速开始代码片段：\n";
echo "```php\n";
echo "use Huaweipay\Payment\HuaweiPayService;\n\n";
echo "\$config = [\n";
echo "    'merchant_id' => 'your_merchant_id',\n";
echo "    'app_id' => 'your_app_id',\n";
echo "    'private_key' => 'your_private_key',\n";
echo "    'public_key' => 'your_public_key',\n";
echo "    'gateway_url' => 'https://pay-api.huawei.com/gateway',\n";
echo "    'notify_url' => 'https://your-domain.com/callback.php',\n";
echo "];\n\n";
echo "\$payService = new HuaweiPayService(\$config);\n\n";
echo "\$order = [\n";
echo "    'out_trade_no' => 'ORDER_' . time(),\n";
echo "    'total_amount' => 0.01,\n";
echo "    'subject' => '测试订单',\n";
echo "];\n\n";
echo "\$result = \$payService->generatePayParams(\$order);\n";
echo "```\n";
?>