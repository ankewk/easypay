<?php
namespace Alipay\Payment;

use Alipay\EasySDK\Kernel\Factory;
use Alipay\EasySDK\Kernel\Config;

class AlipayService {
    /**
     * 支付实例
     * @var \Alipay\EasySDK\Payment\Client
     */
    private $payment;

    /**
     * AlipayService constructor.
     */
    public function __construct() {
        // 初始化配置
        $alipayConfig = new Config();
        $alipayConfig->appId = config('alipay.app_id');
        $alipayConfig->privateKey = config('alipay.merchant_private_key');
        $alipayConfig->alipayPublicKey = config('alipay.alipay_public_key');
        $alipayConfig->notifyUrl = config('alipay.notify_url');

        // 设置网关（沙箱或生产）
        $alipayConfig->gatewayHost = parse_url(config('alipay.gateway_url'), PHP_URL_HOST);

        // 初始化支付宝客户端
        Factory::setOptions($alipayConfig);
        $this->payment = Factory::payment();
    }

    /**
     * 统一收单交易创建接口
     * @param array $order 订单信息
     * @return array
     */
    public function createOrder(array $order) {
        try {
            // 构建订单参数
            $builder = $this->payment->common()->create(
                $order['out_trade_no'], // 商户订单号
                $order['total_amount'], // 订单总金额
                $order['subject'], // 订单标题
                $order['body'] ?? '' // 订单描述（可选）
            );

            // 设置可选参数
            if (isset($order['timeout_express'])) {
                $builder->setTimeoutExpress($order['timeout_express']);
            }

            if (isset($order['product_code'])) {
                $builder->setProductCode($order['product_code']);
            }

            // 执行请求
            $result = $builder->getResult();

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 统一收单交易支付接口（电脑网站支付）
     * @param array $order 订单信息
     * @return array
     */
    public function pagePay(array $order) {
        try {
            $result = $this->payment->page()->pay(
                $order['out_trade_no'], // 商户订单号
                $order['total_amount'], // 订单总金额
                $order['subject'], // 订单标题
                $order['return_url'] // 同步回调URL
            );

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 统一收单交易查询接口
     * @param string $outTradeNo 商户订单号
     * @return array
     */
    public function queryOrder($outTradeNo) {
        try {
            $result = $this->payment->common()->query($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 统一收单交易关闭接口
     * @param string $outTradeNo 商户订单号
     * @return array
     */
    public function closeOrder($outTradeNo) {
        try {
            $result = $this->payment->common()->close($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 处理支付回调
     * @param array $data 回调数据
     * @param string $sign 签名
     * @return bool
     */
    public function verifyNotify($data, $sign) {
        try {
            return Factory::verify($data, $sign);
        } catch (\Exception $e) {
            return false;
        }
    }
}