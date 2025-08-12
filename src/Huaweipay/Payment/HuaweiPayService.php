<?php

namespace Huaweipay\Payment;

/**
 * 华为支付服务类
 * 
 * 支持华为支付的各种功能，包括统一下单、订单查询、关闭订单、退款等
 * 
 * @author EasyPay
 * @version 1.0.0
 */
class HuaweiPayService
{
    private $config;
    private $merchantId;
    private $appId;
    private $privateKey;
    private $publicKey;
    private $gatewayUrl;
    private $notifyUrl;
    
    /**
     * 构造函数
     * 
     * @param array $config 华为支付配置
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = require_once __DIR__ . '/../../../config/config_loader.php';
            $config = $config['huaweipay'];
        }
        
        $this->config = $config;
        $this->merchantId = $config['merchant_id'];
        $this->appId = $config['app_id'];
        $this->privateKey = $config['private_key'];
        $this->publicKey = $config['public_key'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->notifyUrl = $config['notify_url'];
    }
    
    /**
     * 统一下单
     * 
     * @param array $order 订单信息
     * @return array 下单结果
     */
    public function unifiedOrder($order)
    {
        $params = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'outTradeNo' => $order['out_trade_no'],
            'totalAmount' => $this->formatAmount($order['total_amount']),
            'currency' => $order['currency'] ?? 'CNY',
            'subject' => $order['subject'],
            'body' => $order['body'] ?? $order['subject'],
            'timeExpire' => $this->getExpireTime($order['timeout_express'] ?? '30m'),
            'notifyUrl' => $this->notifyUrl,
            'returnUrl' => $order['return_url'] ?? '',
            'attach' => $order['attach'] ?? '',
            'productId' => $order['product_id'] ?? '',
            'deviceInfo' => $order['device_info'] ?? '',
            'scene' => $order['scene'] ?? 'WEB',
            'version' => '1.0.0',
            'signType' => 'RSA2',
            'timestamp' => date('YmdHis'),
            'nonceStr' => $this->generateNonceStr()
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/unifiedorder', $params);
    }
    
    /**
     * 订单查询
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 查询结果
     */
    public function queryOrder($outTradeNo)
    {
        $params = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'outTradeNo' => $outTradeNo,
            'version' => '1.0.0',
            'signType' => 'RSA2',
            'timestamp' => date('YmdHis'),
            'nonceStr' => $this->generateNonceStr()
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/queryorder', $params);
    }
    
    /**
     * 关闭订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 关闭结果
     */
    public function closeOrder($outTradeNo)
    {
        $params = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'outTradeNo' => $outTradeNo,
            'version' => '1.0.0',
            'signType' => 'RSA2',
            'timestamp' => date('YmdHis'),
            'nonceStr' => $this->generateNonceStr()
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/closeorder', $params);
    }
    
    /**
     * 申请退款
     * 
     * @param string $outTradeNo 商户订单号
     * @param float $refundAmount 退款金额
     * @param string $outRefundNo 商户退款单号
     * @param string $reason 退款原因
     * @return array 退款结果
     */
    public function refund($outTradeNo, $refundAmount, $outRefundNo, $reason = '')
    {
        $params = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'outTradeNo' => $outTradeNo,
            'outRefundNo' => $outRefundNo,
            'refundAmount' => $this->formatAmount($refundAmount),
            'reason' => $reason,
            'version' => '1.0.0',
            'signType' => 'RSA2',
            'timestamp' => date('YmdHis'),
            'nonceStr' => $this->generateNonceStr()
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/refund', $params);
    }
    
    /**
     * 退款查询
     * 
     * @param string $outRefundNo 商户退款单号
     * @return array 查询结果
     */
    public function queryRefund($outRefundNo)
    {
        $params = [
            'merchantId' => $this->merchantId,
            'appId' => $this->appId,
            'outRefundNo' => $outRefundNo,
            'version' => '1.0.0',
            'signType' => 'RSA2',
            'timestamp' => date('YmdHis'),
            'nonceStr' => $this->generateNonceStr()
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/queryrefund', $params);
    }
    
    /**
     * 生成支付参数
     * 
     * @param array $order 订单信息
     * @return array 支付参数
     */
    public function generatePayParams($order)
    {
        $result = $this->unifiedOrder($order);
        
        if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
            return [
                'success' => true,
                'data' => [
                    'prepay_id' => $result['prepay_id'],
                    'pay_url' => $result['pay_url'] ?? '',
                    'qr_code' => $result['qr_code'] ?? '',
                    'expire_time' => $result['expire_time'] ?? ''
                ]
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['err_code_des'] ?? $result['return_msg'] ?? '未知错误'
            ];
        }
    }
    
    /**
     * 验证签名
     * 
     * @param array $data 待验证数据
     * @param string $sign 签名
     * @return bool 验证结果
     */
    public function verifySign($data, $sign)
    {
        $signStr = $this->buildSignString($data);
        return $this->rsaVerify($signStr, $sign, $this->publicKey);
    }
    
    /**
     * 生成签名
     * 
     * @param array $params 参数数组
     * @return string 签名
     */
    private function generateSign($params)
    {
        $signStr = $this->buildSignString($params);
        return $this->rsaSign($signStr, $this->privateKey);
    }
    
    /**
     * 构建签名字符串
     * 
     * @param array $params 参数数组
     * @return string 签名字符串
     */
    private function buildSignString($params)
    {
        ksort($params);
        $signStr = '';
        
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== '' && $value !== null) {
                $signStr .= $key . '=' . $value . '&';
            }
        }
        
        return rtrim($signStr, '&');
    }
    
    /**
     * RSA签名
     * 
     * @param string $data 待签名数据
     * @param string $privateKey 私钥
     * @return string 签名
     */
    private function rsaSign($data, $privateKey)
    {
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . 
                     wordwrap($privateKey, 64, "\n", true) . 
                     "\n-----END PRIVATE KEY-----";
        
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }
    
    /**
     * RSA验签
     * 
     * @param string $data 待验签数据
     * @param string $sign 签名
     * @param string $publicKey 公钥
     * @return bool 验签结果
     */
    private function rsaVerify($data, $sign, $publicKey)
    {
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" . 
                      wordwrap($publicKey, 64, "\n", true) . 
                      "\n-----END PUBLIC KEY-----";
        
        $signature = base64_decode($sign);
        return openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
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
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'return_code' => 'FAIL',
                'return_msg' => '网络请求错误: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'return_code' => 'FAIL',
                'return_msg' => '响应解析错误',
                'raw_response' => $response
            ];
        }
        
        return $result;
    }
    
    /**
     * 格式化金额（元转分）
     * 
     * @param float $amount 金额（元）
     * @return int 金额（分）
     */
    private function formatAmount($amount)
    {
        return intval(round($amount * 100));
    }
    
    /**
     * 生成随机字符串
     * 
     * @param int $length 长度
     * @return string 随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 获取过期时间
     * 
     * @param string $timeoutExpress 超时时间表达式（如30m）
     * @return string 过期时间（YmdHis格式）
     */
    private function getExpireTime($timeoutExpress)
    {
        $expire = strtotime('+30 minutes');
        
        if (preg_match('/^(\d+)([mhd])$/', $timeoutExpress, $matches)) {
            $amount = intval($matches[1]);
            $unit = $matches[2];
            
            switch ($unit) {
                case 'm':
                    $expire = strtotime("+{$amount} minutes");
                    break;
                case 'h':
                    $expire = strtotime("+{$amount} hours");
                    break;
                case 'd':
                    $expire = strtotime("+{$amount} days");
                    break;
            }
        }
        
        return date('YmdHis', $expire);
    }
}