<?php
/**
 * 京东支付示例文件
 * 
 * 使用前请确保：
 * 1. 已在配置文件中配置好京东支付相关参数
 * 2. 已安装必要的依赖包
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// 加载配置
require_once __DIR__ . '/../../config/config_loader.php';

use Jdpay\Payment\JdPayService;

// 创建京东支付服务实例
$jdPay = new JdPayService();

// 示例订单信息
$order = [
    'out_trade_no' => 'JD' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '京东支付测试商品描述', // 订单描述
    'timeout_express' => '30m', // 订单过期时间
];

echo "京东支付示例\n";
echo "===================\n";

// 1. 生成支付参数
echo "1. 生成支付参数...\n";
$result = $jdPay->generatePayParams($order);

if ($result['success']) {
    echo "支付参数生成成功！\n";
    echo "支付参数: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
    
    // 这里可以返回给前端进行支付
    // 前端可以使用这些参数调起京东支付
} else {
    echo "支付参数生成失败: " . $result['error'] . "\n\n";
}

// 2. 查询订单
echo "2. 查询订单...\n";
$queryResult = $jdPay->queryOrder($order['out_trade_no']);

if ($queryResult['success']) {
    echo "订单查询成功！\n";
    echo "订单信息: " . json_encode($queryResult['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "订单查询失败: " . $queryResult['error'] . "\n\n";
}

// 3. 关闭订单（测试用）
echo "3. 关闭订单...\n";
$closeResult = $jdPay->closeOrder($order['out_trade_no']);

if ($closeResult['success']) {
    echo "订单关闭成功！\n";
} else {
    echo "订单关闭失败: " . $closeResult['error'] . "\n";
}

echo "\n示例完成！\n";
echo "===================\n";
echo "注意事项：\n";
echo "1. 请先在配置文件中配置京东支付参数\n";
echo "2. 确保notify_url可以被外网访问\n";
echo "3. 实际使用时请根据业务需求调整订单参数\n";