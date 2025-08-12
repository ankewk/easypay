<?php

/**
 * Apple Pay 支付示例
 * 
 * 本示例演示如何使用 Apple Pay 支付服务
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Applepay\Payment\ApplePayService;

// 设置响应头
header('Content-Type: application/json; charset=utf-8');

// 创建 Apple Pay 服务实例
$applePay = new ApplePayService();

// 演示1：创建支付会话
echo "=== Apple Pay 支付会话创建示例 ===\n";

$sessionData = [
    'display_name' => 'EasyPay Store',
    'domain_name' => $_SERVER['HTTP_HOST'] ?? 'localhost',
    'out_trade_no' => 'AP' . date('YmdHis') . rand(1000, 9999),
    'total_amount' => 0.01,
    'currency_code' => 'CNY',
    'subject' => '测试商品',
    'body' => 'Apple Pay 测试商品'
];

try {
    $sessionResult = $applePay->createPaymentSession($sessionData);
    
    if ($sessionResult['code'] === 0) {
        echo "✅ 支付会话创建成功！\n";
        echo "商户会话标识: " . $sessionResult['data']['merchant_session']['merchant_session_identifier'] . "\n";
        echo "会话过期时间: " . date('Y-m-d H:i:s', $sessionResult['data']['merchant_session']['expires_at'] / 1000) . "\n";
        echo "\n";
    } else {
        echo "❌ 支付会话创建失败: " . $sessionResult['message'] . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ 支付会话创建异常: " . $e->getMessage() . "\n\n";
}

// 演示2：模拟支付处理
echo "=== Apple Pay 支付处理示例 ===\n";

// 模拟从客户端获取的支付数据
$mockPaymentData = [
    'payment_data' => base64_encode(json_encode([
        'version' => 'EC_v1',
        'data' => 'mock_encrypted_payment_data',
        'signature' => 'mock_signature',
        'header' => [
            'ephemeralPublicKey' => 'mock_ephemeral_public_key',
            'publicKeyHash' => 'mock_public_key_hash',
            'transactionId' => 'mock_transaction_id'
        ]
    ]))
];

// 模拟订单数据
$orderData = [
    'out_trade_no' => 'AP' . date('YmdHis') . rand(1000, 9999),
    'total_amount' => 0.01,
    'currency_code' => 'CNY',
    'subject' => '测试商品',
    'body' => 'Apple Pay 测试商品',
    'billing_address' => [
        'givenName' => '张',
        'familyName' => '三',
        'addressLines' => ['北京市朝阳区测试路123号'],
        'locality' => '北京',
        'administrativeArea' => '北京市',
        'postalCode' => '100000',
        'countryCode' => 'CN'
    ],
    'shipping_address' => [
        'givenName' => '张',
        'familyName' => '三',
        'addressLines' => ['北京市朝阳区测试路123号'],
        'locality' => '北京',
        'administrativeArea' => '北京市',
        'postalCode' => '100000',
        'countryCode' => 'CN'
    ]
];

try {
    $paymentResult = $applePay->processPayment($mockPaymentData, $orderData);
    
    if ($paymentResult['code'] === 0) {
        echo "✅ 支付处理成功！\n";
        echo "订单号: " . $orderData['out_trade_no'] . "\n";
        echo "交易ID: " . ($paymentResult['data']['transaction_id'] ?? 'N/A') . "\n";
        echo "支付金额: ¥" . $orderData['total_amount'] . "\n";
        echo "\n";
    } else {
        echo "❌ 支付处理失败: " . $paymentResult['message'] . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ 支付处理异常: " . $e->getMessage() . "\n\n";
}

// 演示3：查询订单状态
if (isset($paymentResult['data']['transaction_id'])) {
    echo "=== Apple Pay 订单状态查询示例 ===\n";
    
    try {
        $transactionId = $paymentResult['data']['transaction_id'];
        $queryResult = $applePay->queryOrderStatus($transactionId);
        
        if ($queryResult['code'] === 0) {
            echo "✅ 订单状态查询成功！\n";
            echo "交易状态: " . ($queryResult['data']['status'] ?? '未知') . "\n";
            echo "交易金额: ¥" . ($queryResult['data']['amount'] ?? 0) . "\n";
            echo "创建时间: " . ($queryResult['data']['created_at'] ?? 'N/A') . "\n";
        } else {
            echo "❌ 订单状态查询失败: " . $queryResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ 订单状态查询异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 演示4：申请退款
if (isset($paymentResult['data']['transaction_id'])) {
    echo "=== Apple Pay 退款示例 ===\n";
    
    $transactionId = $paymentResult['data']['transaction_id'];
    $refundAmount = 0.01;
    $reason = '测试退款 - 用户取消订单';
    
    try {
        $refundResult = $applePay->refund($transactionId, $refundAmount, $reason);
        
        if ($refundResult['code'] === 0) {
            echo "✅ 退款申请成功！\n";
            echo "交易ID: $transactionId\n";
            echo "退款金额: ¥$refundAmount\n";
            echo "退款原因: $reason\n";
        } else {
            echo "❌ 退款申请失败: " . $refundResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ 退款申请异常: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 演示5：前端集成示例代码
echo "=== Apple Pay 前端集成示例 ===\n";
echo "HTML/JavaScript 示例:\n";
echo <<<HTML
<script src="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js"></script>
<script>
// 检查 Apple Pay 是否可用
if (window.ApplePaySession && ApplePaySession.canMakePayments()) {
    console.log('Apple Pay 可用');
    
    // 创建 Apple Pay 会话
    const paymentRequest = {
        countryCode: 'CN',
        currencyCode: 'CNY',
        supportedNetworks: ['visa', 'masterCard', 'chinaUnionPay'],
        merchantCapabilities: ['supports3DS'],
        total: {
            label: 'EasyPay Store',
            amount: '0.01'
        }
    };
    
    const session = new ApplePaySession(3, paymentRequest);
    
    // 处理商户验证
    session.onvalidatemerchant = function(event) {
        const validationURL = event.validationURL;
        
        // 从服务器获取商户会话
        fetch('/examples/applepay/session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                validationURL: validationURL,
                displayName: 'EasyPay Store'
            })
        })
        .then(response => response.json())
        .then(data => {
            session.completeMerchantValidation(data);
        })
        .catch(error => {
            console.error('商户验证失败:', error);
            session.abort();
        });
    };
    
    // 处理支付授权
    session.onpaymentauthorized = function(event) {
        const payment = event.payment;
        
        // 发送支付数据到服务器
        fetch('/examples/applepay/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                payment_data: payment.token.paymentData,
                order_data: {
                    out_trade_no: 'AP' + Date.now(),
                    total_amount: 0.01,
                    currency_code: 'CNY',
                    subject: '测试商品'
                }
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
            } else {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
            }
        })
        .catch(error => {
            console.error('支付处理失败:', error);
            session.completePayment(ApplePaySession.STATUS_FAILURE);
        });
    };
    
    // 开始 Apple Pay 会话
    session.begin();
} else {
    console.log('Apple Pay 不可用');
}
</script>
HTML;

// 演示6：获取配置信息
echo "=== Apple Pay 配置信息 ===\n";
echo "商户ID: your_applepay_merchant_id\n";
echo "网关地址: https://apple-pay-gateway.apple.com/payments\n";
echo "通知地址: http://localhost:8000/callbacks/applepay/applepay_notify.php\n";
echo "\n";

// 返回结果示例
$response = [
    'status' => 'success',
    'message' => 'Apple Pay 演示完成',
    'data' => [
        'session_created' => isset($sessionResult) && $sessionResult['code'] === 0,
        'payment_processed' => isset($paymentResult) && $paymentResult['code'] === 0,
        'examples' => [
            'session_demo' => '/examples/applepay/applepay_demo.php',
            'web_integration' => '/examples/applepay/web_integration.html',
            'mobile_integration' => '/examples/applepay/mobile_integration.html'
        ]
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);