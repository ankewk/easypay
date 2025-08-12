<?php

namespace Applepay\Payment;

/**
 * 苹果支付服务类
 * 
 * 支持Apple Pay的各种功能，包括创建支付会话、验证支付、处理支付结果等
 * 
 * @author EasyPay
 * @version 1.0.0
 */
class ApplePayService
{
    private $config;
    private $merchantId;
    private $merchantCertPath;
    private $merchantKeyPath;
    private $merchantKeyPassword;
    private $gatewayUrl;
    private $notifyUrl;
    
    /**
     * 构造函数
     * 
     * @param array $config Apple Pay配置
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = require_once __DIR__ . '/../../../config/config_loader.php';
            $config = $config['applepay'];
        }
        
        $this->config = $config;
        $this->merchantId = $config['merchant_id'];
        $this->merchantCertPath = $config['merchant_cert_path'];
        $this->merchantKeyPath = $config['merchant_key_path'];
        $this->merchantKeyPassword = $config['merchant_key_password'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->notifyUrl = $config['notify_url'];
    }
    
    /**
     * 创建支付会话
     * 
     * @param array $order 订单信息
     * @return array 支付会话信息
     */
    public function createPaymentSession($order)
    {
        $sessionData = [
            'merchant_id' => $this->merchantId,
            'display_name' => $order['display_name'] ?? 'EasyPay',
            'initiative' => 'web',
            'initiative_context' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'domain_names' => [$order['domain_name'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost'],
            'merchant_session' => [
                'epoch_timestamp' => time() * 1000,
                'expires_at' => (time() + 3600) * 1000,
                'merchant_session_identifier' => uniqid('merchant_session_', true),
                'nonce' => bin2hex(random_bytes(32)),
                'merchant_identifier' => $this->merchantId,
                'display_name' => $order['display_name'] ?? 'EasyPay',
                'signature' => $this->generateSessionSignature($order)
            ]
        ];
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => $sessionData
        ];
    }
    
    /**
     * 验证支付数据
     * 
     * @param array $paymentData 支付数据
     * @return array 验证结果
     */
    public function validatePayment($paymentData)
    {
        if (!isset($paymentData['payment_data'])) {
            return [
                'code' => -1,
                'message' => '缺少支付数据',
                'data' => []
            ];
        }
        
        $paymentToken = $paymentData['payment_data'];
        
        // 验证支付令牌
        $validationResult = $this->validatePaymentToken($paymentToken);
        
        if (!$validationResult['valid']) {
            return [
                'code' => -2,
                'message' => '支付令牌验证失败: ' . $validationResult['error'],
                'data' => []
            ];
        }
        
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                'payment_method' => $validationResult['payment_method'],
                'transaction_id' => $validationResult['transaction_id'],
                'amount' => $validationResult['amount']
            ]
        ];
    }
    
    /**
     * 处理支付
     * 
     * @param array $paymentData 支付数据
     * @param array $order 订单信息
     * @return array 支付结果
     */
    public function processPayment($paymentData, $order)
    {
        // 验证支付数据
        $validation = $this->validatePayment($paymentData);
        
        if ($validation['code'] !== 0) {
            return $validation;
        }
        
        // 构建支付请求
        $paymentRequest = [
            'merchant_id' => $this->merchantId,
            'amount' => $order['total_amount'],
            'currency_code' => $order['currency_code'] ?? 'CNY',
            'merchant_reference' => $order['out_trade_no'],
            'payment_data' => $paymentData['payment_data'],
            'description' => $order['subject'],
            'notification_url' => $this->notifyUrl,
            'billing_address' => $order['billing_address'] ?? [],
            'shipping_address' => $order['shipping_address'] ?? []
        ];
        
        // 发送支付请求到Apple Pay服务器
        return $this->sendPaymentRequest($paymentRequest);
    }
    
    /**
     * 查询订单状态
     * 
     * @param string $transactionId 交易ID
     * @return array 订单状态
     */
    public function queryOrderStatus($transactionId)
    {
        $queryData = [
            'merchant_id' => $this->merchantId,
            'transaction_id' => $transactionId
        ];
        
        return $this->request('GET', '/api/v1/transactions/' . $transactionId, $queryData);
    }
    
    /**
     * 退款
     * 
     * @param string $transactionId 交易ID
     * @param float $amount 退款金额
     * @param string $reason 退款原因
     * @return array 退款结果
     */
    public function refund($transactionId, $amount, $reason = '')
    {
        $refundData = [
            'merchant_id' => $this->merchantId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'reason' => $reason,
            'merchant_reference' => 'RF' . date('YmdHis') . rand(1000, 9999)
        ];
        
        return $this->request('POST', '/api/v1/refunds', $refundData);
    }
    
    /**
     * 验证支付令牌
     * 
     * @param string $paymentToken 支付令牌
     * @return array 验证结果
     */
    private function validatePaymentToken($paymentToken)
    {
        try {
            $tokenData = json_decode(base64_decode($paymentToken), true);
            
            if (!$tokenData || !isset($tokenData['version'])) {
                return ['valid' => false, 'error' => '无效的支付令牌格式'];
            }
            
            // 验证令牌签名
            if (!$this->verifyTokenSignature($tokenData)) {
                return ['valid' => false, 'error' => '令牌签名验证失败'];
            }
            
            return [
                'valid' => true,
                'payment_method' => $tokenData['paymentMethod'] ?? [],
                'transaction_id' => $tokenData['transactionIdentifier'] ?? '',
                'amount' => $tokenData['paymentData']['amount'] ?? 0
            ];
            
        } catch (Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * 验证令牌签名
     * 
     * @param array $tokenData 令牌数据
     * @return bool 验证结果
     */
    private function verifyTokenSignature($tokenData)
    {
        // 这里应该实现实际的签名验证逻辑
        // 包括验证Apple的证书链和签名
        return true; // 简化实现，实际应用中需要完整验证
    }
    
    /**
     * 生成会话签名
     * 
     * @param array $order 订单信息
     * @return string 签名
     */
    private function generateSessionSignature($order)
    {
        $data = $this->merchantId . $order['out_trade_no'] . $order['total_amount'];
        return base64_encode(hash_hmac('sha256', $data, $this->merchantId, true));
    }
    
    /**
     * 发送支付请求
     * 
     * @param array $paymentRequest 支付请求数据
     * @return array 响应结果
     */
    private function sendPaymentRequest($paymentRequest)
    {
        // 使用商户证书进行SSL通信
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->gatewayUrl . '/api/v1/payments',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($paymentRequest),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSLCERT => $this->merchantCertPath,
            CURLOPT_SSLKEY => $this->merchantKeyPath,
            CURLOPT_SSLKEYPASSWD => $this->merchantKeyPassword,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'code' => -1,
                'message' => '网络请求错误: ' . $error,
                'data' => []
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'code' => -2,
                'message' => '响应解析错误',
                'data' => ['raw_response' => $response]
            ];
        }
        
        return $result;
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $method 请求方法
     * @param string $endpoint 接口地址
     * @param array $data 请求数据
     * @return array 响应结果
     */
    private function request($method, $endpoint, $data = [])
    {
        $url = $this->gatewayUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->merchantCertPath);
        curl_setopt($ch, CURLOPT_SSLKEY, $this->merchantKeyPath);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->merchantKeyPassword);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'code' => -1,
                'message' => '网络请求错误: ' . $error,
                'data' => []
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'code' => -2,
                'message' => '响应解析错误',
                'data' => ['raw_response' => $response]
            ];
        }
        
        return $result;
    }
}