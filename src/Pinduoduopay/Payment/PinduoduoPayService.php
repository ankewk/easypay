<?php
/**
 * 拼多多支付服务类
 * 
 * 提供拼多多支付的集成服务，包括订单创建、查询、关闭等功能
 */

namespace Pinduoduopay\Payment;

class PinduoduoPayService
{
    private $config;
    private $clientId;
    private $clientSecret;
    private $gatewayUrl;
    private $notifyUrl;

    /**
     * 构造函数
     * 
     * @param array $config 配置数组
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = $this->loadConfig();
        }
        
        $this->config = $config;
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->notifyUrl = $config['notify_url'];
    }

    /**
     * 加载配置
     * 
     * @return array 配置数组
     */
    private function loadConfig()
    {
        if (function_exists('config')) {
            return config('pinduoduopay');
        }
        
        $env = getenv('APP_ENV') ?: 'dev';
        $configFile = __DIR__ . '/../../../config/' . $env . '.php';
        
        if (file_exists($configFile)) {
            $config = include $configFile;
            return $config['pinduoduopay'] ?? [];
        }
        
        return [];
    }

    /**
     * 生成支付参数
     * 
     * @param array $order 订单信息
     * @return array 支付参数结果
     */
    public function generatePayParams($order)
    {
        try {
            $params = [
                'client_id' => $this->clientId,
                'out_trade_no' => $order['out_trade_no'],
                'total_amount' => $order['total_amount'],
                'subject' => $order['subject'],
                'body' => $order['body'] ?? '',
                'timeout_express' => $order['timeout_express'] ?? '30m',
                'notify_url' => $this->notifyUrl,
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'sign_type' => 'RSA2'
            ];

            // 生成签名
            $params['sign'] = $this->generateSign($params);

            return [
                'success' => true,
                'data' => $params,
                'message' => '支付参数生成成功'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 统一下单
     * 
     * @param array $order 订单信息
     * @return array 下单结果
     */
    public function createOrder($order)
    {
        try {
            $params = [
                'client_id' => $this->clientId,
                'out_trade_no' => $order['out_trade_no'],
                'total_amount' => $order['total_amount'],
                'subject' => $order['subject'],
                'body' => $order['body'] ?? '',
                'timeout_express' => $order['timeout_express'] ?? '30m',
                'notify_url' => $this->notifyUrl,
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'sign_type' => 'RSA2'
            ];

            $params['sign'] = $this->generateSign($params);

            // 模拟API调用（实际使用时需要替换为真实API调用）
            $response = $this->httpPost($this->gatewayUrl, $params);

            if ($response['code'] == 200) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'message' => '订单创建成功'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? '订单创建失败'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 查询订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 查询结果
     */
    public function queryOrder($outTradeNo)
    {
        try {
            $params = [
                'client_id' => $this->clientId,
                'out_trade_no' => $outTradeNo,
                'method' => 'pdd.pay.query',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'sign_type' => 'RSA2'
            ];

            $params['sign'] = $this->generateSign($params);

            $response = $this->httpPost($this->gatewayUrl, $params);

            if ($response['code'] == 200) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'message' => '订单查询成功'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? '订单查询失败'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 关闭订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 关闭结果
     */
    public function closeOrder($outTradeNo)
    {
        try {
            $params = [
                'client_id' => $this->clientId,
                'out_trade_no' => $outTradeNo,
                'method' => 'pdd.pay.close',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'sign_type' => 'RSA2'
            ];

            $params['sign'] = $this->generateSign($params);

            $response = $this->httpPost($this->gatewayUrl, $params);

            if ($response['code'] == 200) {
                return [
                    'success' => true,
                    'data' => $response['data'],
                    'message' => '订单关闭成功'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? '订单关闭失败'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 验证回调签名
     * 
     * @param array $data 回调数据
     * @param string $sign 签名
     * @return bool 验证结果
     */
    public function verifyNotify($data, $sign)
    {
        if (empty($sign) || empty($data)) {
            return false;
        }

        $calculatedSign = $this->generateSign($data, false);
        return $calculatedSign === $sign;
    }

    /**
     * 生成签名
     * 
     * @param array $params 参数数组
     * @param bool $includeSignKey 是否包含签名密钥
     * @return string 签名结果
     */
    private function generateSign($params, $includeSignKey = true)
    {
        // 过滤空值和签名参数
        $filteredParams = [];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== '' && $value !== null) {
                $filteredParams[$key] = $value;
            }
        }

        // 按键名升序排序
        ksort($filteredParams);

        // 拼接字符串
        $queryString = '';
        foreach ($filteredParams as $key => $value) {
            $queryString .= $key . '=' . $value . '&';
        }
        $queryString = rtrim($queryString, '&');

        if ($includeSignKey) {
            $queryString .= $this->clientSecret;
        }

        // 使用RSA2签名（实际使用时需要替换为真实私钥签名）
        return hash('sha256', $queryString);
    }

    /**
     * HTTP POST请求
     * 
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @return array 响应结果
     */
    private function httpPost($url, $data)
    {
        // 模拟HTTP POST请求，实际使用时替换为真实API调用
        return [
            'code' => 200,
            'data' => [
                'trade_no' => 'PDD' . date('YmdHis') . rand(1000, 9999),
                'out_trade_no' => $data['out_trade_no'],
                'trade_status' => 'WAIT_BUYER_PAY',
                'total_amount' => $data['total_amount']
            ],
            'message' => 'success'
        ];
    }
}