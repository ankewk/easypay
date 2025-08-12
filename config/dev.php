<?php

return [
    // 全局配置
    'app_name' => 'EasyPay',
    'debug' => true,

    // 支付宝配置
    'alipay' => [
        'app_id' => 'your_dev_alipay_app_id',
        'merchant_private_key' => 'your_dev_merchant_private_key',
        'alipay_public_key' => 'your_dev_alipay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/alipay/alipay_notify.php',
        'return_url' => 'https://dev.your-domain.com/return.php',
        'sign_type' => 'RSA2',
        'gateway_url' => 'https://openapi.alipaydev.com/gateway.do', // 沙箱环境
    ],

    // 微信支付配置
    'wxpay' => [
        'app_id' => 'your_dev_wxpay_app_id',
        'mch_id' => 'your_dev_mch_id',
        'key' => 'your_dev_api_key',
        'cert_path' => __DIR__ . '/cert/wxpay/dev/apiclient_cert.pem',
        'key_path' => __DIR__ . '/cert/wxpay/dev/apiclient_key.pem',
        'notify_url' => 'https://dev.your-domain.com/callbacks/wxpay/wxpay_notify.php',
        'gateway_url' => 'https://api.mch.weixin.qq.com/pay/unifiedorder',
    ],

    // 云闪付配置
    'cmbpay' => [
        'app_id' => 'your_dev_cmbpay_app_id',
        'merchant_id' => 'your_dev_cmbpay_merchant_id',
        'private_key' => 'your_dev_cmbpay_private_key',
        'public_key' => 'your_dev_cmbpay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/cmbpay/cmbpay_notify.php',
        'gateway_url' => 'https://open.cmbchina.com/api/Payment/UnifiedOrder', // 示例网关，实际请替换
    ],

    // 抖音支付配置
    'douyinpay' => [
        'app_id' => 'your_dev_douyinpay_app_id',
        'merchant_id' => 'your_dev_douyinpay_merchant_id',
        'private_key' => 'your_dev_douyinpay_private_key',
        'public_key' => 'your_dev_douyinpay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/douyinpay/douyinpay_notify.php',
        'gateway_url' => 'https://developer.toutiao.com/api/apps/ecpay/v1/create_order', // 示例网关，实际请替换
    ],

    // 快手支付配置
    'kuaishoupay' => [
        'app_id' => 'your_dev_kuaishoupay_app_id',
        'merchant_id' => 'your_dev_kuaishoupay_merchant_id',
        'private_key' => 'your_dev_kuaishoupay_private_key',
        'public_key' => 'your_dev_kuaishoupay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/kuaishoupay/kuaishoupay_notify.php',
        'gateway_url' => 'https://open.kuaishou.com/api/pay/create_order', // 示例网关，实际请替换
    ],

    // 京东支付配置
    'jdpay' => [
        'merchant_no' => 'your_dev_jdpay_merchant_no',
        'des_key' => 'your_dev_jdpay_des_key',
        'private_key' => 'your_dev_jdpay_private_key',
        'public_key' => 'your_dev_jdpay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/jdpay/jdpay_notify.php',
        'gateway_url' => 'https://wepay.jd.com/jdpay/saveOrder', // 示例网关，实际请替换
    ],

    // 拼多多支付配置
    'pinduoduopay' => [
        'client_id' => 'your_dev_pinduoduopay_client_id',
        'client_secret' => 'your_dev_pinduoduopay_client_secret',
        'private_key' => 'your_dev_pinduoduopay_private_key',
        'public_key' => 'your_dev_pinduoduopay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/pinduoduopay/pinduoduopay_notify.php',
        'gateway_url' => 'https://open-api.pinduoduo.com/gateway', // 示例网关，实际请替换
    ],

    // 美团支付配置
    'meituanpay' => [
        'merchant_id' => 'your_dev_meituanpay_merchant_id',
        'app_key' => 'your_dev_meituanpay_app_key',
        'app_secret' => 'your_dev_meituanpay_app_secret',
        'private_key' => 'your_dev_meituanpay_private_key',
        'public_key' => 'your_dev_meituanpay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/meituanpay/meituanpay_notify.php',
        'gateway_url' => 'https://api.meituan.com/gateway', // 示例网关，实际请替换
    ],

    // 苹果支付配置
    'applepay' => [
        'merchant_id' => 'your_dev_applepay_merchant_id',
        'merchant_cert_path' => __DIR__ . '/cert/applepay/dev/merchant-cert.pem',
        'merchant_key_path' => __DIR__ . '/cert/applepay/dev/merchant-key.pem',
        'merchant_key_password' => 'your_dev_merchant_key_password',
        'notify_url' => 'https://dev.your-domain.com/callbacks/applepay/applepay_notify.php',
        'gateway_url' => 'https://apple-pay-gateway.apple.com/payments', // 苹果支付网关
    ],

    // 华为支付配置
    'huaweipay' => [
        'merchant_id' => 'your_dev_huaweipay_merchant_id',
        'app_id' => 'your_dev_huaweipay_app_id',
        'private_key' => 'your_dev_huaweipay_private_key',
        'public_key' => 'your_dev_huaweipay_public_key',
        'notify_url' => 'https://dev.your-domain.com/callbacks/huaweipay/huawei_notify.php',
        'gateway_url' => 'https://pay-api.huawei.com/gateway', // 华为支付网关
    ],
];