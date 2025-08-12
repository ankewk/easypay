# easypay
集成支付宝、微信、云闪付、抖音、快手、京东、拼多多、美团、苹果、华为支付的Composer包

## 环境配置

本项目支持多环境配置，通过配置文件和环境变量来管理不同环境的支付参数。

## 环境要求
- PHP >= 5.6.0
- Composer
- Docker（可选，用于容器化部署）

## Docker环境（推荐）

### 服务组成
本项目的Docker环境包含以下服务：
1. **php-fpm**: PHP 5.6运行环境
2. **nginx**: Web服务器
3. **mysql**: MySQL 5.7数据库服务

### 快速开始

#### 1. 构建镜像
在项目根目录执行以下命令构建Docker镜像：
```bash
docker-compose build
```

#### 2. 启动服务
```bash
docker-compose up -d
```

#### 3. 停止服务
```bash
docker-compose down
```

#### 4. 查看服务状态
```bash
docker-compose ps
```

#### 5. 查看日志
```bash
docker-compose logs -f
```

### 访问项目
服务启动后，可以通过以下方式访问项目：
- 项目首页: http://localhost:8000
- 支付宝示例: http://localhost:8000/examples/alipay/alipay_demo.php
- 微信支付示例: http://localhost:8000/examples/wxpay/wxpay_demo.php
- 云闪付示例: http://localhost:8000/examples/cmbpay/cmbpay_demo.php
- 抖音支付示例: http://localhost:8000/examples/douyinpay/douyinpay_demo.php
- 快手支付示例: http://localhost:8000/examples/kuaishoupay/kuaishoupay_demo.php
- 京东支付示例: http://localhost:8000/examples/jdpay/jdpay_demo.php
- 拼多多支付示例: http://localhost:8000/examples/pinduoduopay/pinduoduopay_demo.php
- 美团支付示例: http://localhost:8000/examples/meituanpay/meituanpay_demo.php
- 苹果支付示例: http://localhost:8000/examples/applepay/applepay_demo.php
- 华为支付示例: http://localhost:8000/examples/huaweipay/huawei_demo.php

### 数据库配置
MySQL服务默认配置：
- 端口: 3306
- 数据库名: easypay
- 用户名: easypay
- 密码: easypay
- 根密码: root

### 注意事项
1. 首次启动服务时，会自动安装项目依赖。
2. 如需要修改PHP版本或安装其他扩展，请修改Dockerfile。
3. 如需要修改Nginx配置，请修改nginx/conf.d/default.conf文件。
4. 代码修改后会自动同步到容器中，但如果修改了composer.json，需要重新构建镜像或进入容器执行composer install。

## 🚀 异步支付处理系统

本系统为所有支付方式提供了完整的异步处理支持，确保支付回调能够快速响应并可靠处理。

### 📋 支持的支付方式
| 支付方式 | 异步支持 | 配置示例 |
|---------|----------|----------|
| 支付宝 (alipay) | ✅ | `start alipay` |
| 微信支付 (wxpay) | ✅ | `start wxpay` |
| 华为支付 (huaweipay) | ✅ | `start huaweipay` |
| 苹果支付 (applepay) | ✅ | `start applepay` |
| 抖音支付 (douyinpay) | ✅ | `start douyinpay` |
| 快手支付 (kuaishoupay) | ✅ | `start kuaishoupay` |
| 京东支付 (jdpay) | ✅ | `start jdpay` |
| 拼多多支付 (pinduoduopay) | ✅ | `start pinduoduopay` |
| 美团支付 (meituanpay) | ✅ | `start meituanpay` |
| 云闪付 (cmbpay) | ✅ | `start cmbpay` |

### 🎯 快速开始（异步处理）

#### 🐳 方式1：Docker启动（推荐）

**前提条件**
- 已安装Docker和Docker Compose

**启动步骤**

1. **启动完整环境**
   ```bash
   ./docker-start.sh
   ```

2. **查看状态**
   ```bash
   ./docker-status.sh
   ```

3. **查看日志**
   ```bash
   ./docker-logs.sh
   ```

4. **停止服务**
   ```bash
   ./docker-stop.sh
   ```

**访问地址**
- Web服务: http://localhost:8000
- MySQL: localhost:3306 (root/root)
- Redis: localhost:6379

#### 🔧 方式2：手动安装PHP环境

**macOS安装PHP**
```bash
# 使用Homebrew安装PHP
brew install php

# 验证安装
php -v
```

**启动步骤**
1. **安装依赖**
   ```bash
   composer install
   ```

2. **启动异步处理**
   ```bash
   ./start_async.sh install
   ./start_async.sh start all
   ```

3. **监控状态**
   ```bash
   ./start_async.sh monitor
   ```

### 🔧 异步配置说明

编辑 `config/async.php` 配置文件：

#### 队列存储方式
```php
// 文件队列 (默认)
'queue_type' => 'file',
'file_queue' => [
    'directory' => __DIR__ . '/../queue',
    'max_size' => 100 * 1024 * 1024, // 100MB
],

// Redis队列
'queue_type' => 'redis',
'redis' => [
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => '',
    'database' => 0,
],

// 数据库队列
'queue_type' => 'database',
'database' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'easypay',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
],
```

#### 重试策略
```php
'retry' => [
    'max_retries' => 3,
    'delay_base' => 1,
    'delay_multiplier' => 2,
    'max_delay' => 300,
],
```

### 📊 监控和管理

#### 实时监控
```bash
# 查看所有队列状态
php monitor_async.php

# 查看特定支付方式状态
php monitor_async.php status wxpay

# 清理失败任务
php monitor_async.php cleanup
```

#### 日志查看
```bash
# 查看异步处理日志
tail -f logs/async_queue/async.log

# 查看特定支付方式日志
tail -f logs/alipay_async_error.log
```

### 🧪 测试
```bash
# 测试所有支付方式
php tests/test_async_processor.php

# 测试特定支付方式
php tests/test_async_processor.php alipay
```

### 📁 异步系统文件结构
```
easypay/
├── callbacks/              # 支付回调处理器（已更新为异步）
├── src/Common/Payment/     # 异步处理器核心
│   └── AsyncProcessor.php  # 通用异步处理器
├── config/async.php        # 异步处理配置
├── queue/                  # 文件队列存储
├── logs/async_queue/       # 异步处理日志
├── async_processor.php     # 异步处理主脚本
├── monitor_async.php       # 监控脚本
├── install_async.php       # 安装脚本
├── start_async.sh          # 启动管理脚本
└── tests/                  # 测试脚本
```

## 传统安装（非Docker）

### 配置文件结构
项目根目录下的`config`目录包含以下文件：

```
config/
├── config_loader.php   # 配置加载器
├── dev.php            # 开发环境配置
├── uat.php            # 测试环境配置
└── prod.php           # 生产环境配置
```

### 配置加载器
`config_loader.php`提供了一个简单的配置加载机制，可以根据当前环境自动加载对应的配置文件。

#### 使用方法

```php
// 引入配置加载器
require_once __DIR__ . '/config/config_loader.php';

// 获取配置项
$appId = config('alipay.app_id');
$notifyUrl = config('wxpay.notify_url');

// 获取所有配置
$allConfig = config()->getAll();

// 切换环境
config()->setEnv('prod');
```

### 环境切换
默认情况下，配置加载器会使用`dev`环境的配置。你可以通过以下方式切换环境：

1. 设置环境变量`APP_ENV`：
```bash
export APP_ENV=prod
```

2. 直接在代码中设置：
```php
config()->setEnv('uat');
```

### 配置参数说明
配置文件中包含以下类型的参数：

#### 全局配置
- `app_name`: 应用名称
- `debug`: 是否开启调试模式

#### 支付宝配置
- `app_id`: 支付宝APPID
- `merchant_private_key`: 商户私钥
- `alipay_public_key`: 支付宝公钥
- `notify_url`: 异步回调URL
- `return_url`: 同步回调URL
- `sign_type`: 签名类型
- `gateway_url`: 支付宝网关URL

#### 微信支付配置
- `app_id`: 微信公众号APPID
- `mch_id`: 商户号
- `key`: API密钥
- `cert_path`: 证书路径
- `key_path`: 密钥路径
- `notify_url`: 支付回调URL
- `gateway_url`: 微信支付网关URL

## 微信支付集成

## 微信支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
微信支付需要以下配置参数：

```php
$config = [
    'app_id' => 'your-app-id', // 微信公众号APPID
    'mch_id' => 'your-mch-id', // 商户号
    'key' => 'your-api-key', // API密钥
    'notify_url' => 'https://your-domain.com/callbacks/wxpay/wxpay_notify.php', // 支付回调URL
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Wxpay\Payment\WxPayService;

$payService = new WxPayService($config);
```

#### 2. 生成支付参数

```php
$order = [
    'out_trade_no' => 'TEST' . date('YmdHis'), // 商户订单号
    'total_fee' => 1, // 订单金额，单位：分
    'body' => '测试商品', // 商品描述
    'spbill_create_ip' => $_SERVER['REMOTE_ADDR'], // 客户端IP
    'trade_type' => 'JSAPI', // 支付类型
    'openid' => 'user-openid', // 用户openid
];

$result = $payService->generatePayParams($order);

if ($result['success']) {
    $payParams = $result['data']; // 支付参数，用于前端调起微信支付
} else {
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data']; // 订单信息
} else {
    echo "错误：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "错误：" . $result['error'];
}
```

### 回调处理
创建 `callbacks/wxpay/wxpay_notify.php` 文件处理微信支付回调：

```php
use Wxpay\Payment\WxPayService;

$payService = new WxPayService($config);
$result = $payService->handleNotify();

if ($result['success']) {
    $response = $result['data'];
    $response->send(); // 向微信支付平台发送成功响应
} else {
    // 处理失败
    file_put_contents('pay_notify_error.log', '错误：' . $result['error'] . '\n', FILE_APPEND);
}
```

### 异步处理支持
微信支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动微信支付异步处理
./start_async.sh start wxpay

# 查看微信支付状态
./start_async.sh status wxpay
```

### 测试
`examples/wxpay/wxpay_demo.php` 文件提供了微信支付的使用示例，你可以修改配置参数后直接运行测试。

## 支付宝支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
支付宝支付需要以下配置参数：

```php
$config = [
    'app_id' => 'your-app-id', // 支付宝APPID
    'private_key' => 'your-private-key', // 商户私钥
    'alipay_public_key' => 'alipay-public-key', // 支付宝公钥
    'notify_url' => 'https://your-domain.com/callbacks/alipay/alipay_notify.php', // 异步回调URL
    'mode' => 'sandbox', // 可选，沙箱环境，正式环境请删除此参数
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Alipay\Payment\AlipayService;

$payService = new AlipayService($config);
```

#### 2. 电脑网站支付

```php
$order = [
    'out_trade_no' => 'TEST' . date('YmdHis'), // 商户订单号
    'total_amount' => 0.01, // 订单金额，单位：元
    'subject' => '测试商品', // 商品标题
    'body' => '测试商品描述', // 商品描述
    'timeout_express' => '1h', // 订单超时时间
    'return_url' => 'https://your-domain.com/return.php', // 同步回调URL
];

$result = $payService->pagePay($order);

if ($result['success']) {
    // 支付页面重定向
    echo $result['data'];
} else {
    // 支付失败
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data']; // 订单信息
} else {
    echo "错误：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "错误：" . $result['error'];
}
```

### 回调处理
创建 `callbacks/alipay/alipay_notify.php` 文件处理支付宝支付回调：

```php
use Alipay\Payment\AlipayService;

$config = [
    'app_id' => 'your-app-id', // 支付宝APPID
    'private_key' => 'your-private-key', // 商户私钥
    'alipay_public_key' => 'alipay-public-key', // 支付宝公钥
    'notify_url' => 'https://your-domain.com/callbacks/alipay/alipay_notify.php', // 异步回调URL
];

$payService = new AlipayService($config);

// 获取回调数据
$data = $_POST;
$sign = $data['sign'];
unset($data['sign'], $data['sign_type']);

// 验证签名
$verifyResult = $payService->verifyNotify($data, $sign);

if ($verifyResult) {
    // 签名验证成功
    if ($data['trade_status'] == 'TRADE_SUCCESS') {
        // 交易成功，处理订单
        echo 'success';
    } else {
        echo 'success';
    }
} else {
    // 签名验证失败
    file_put_contents('alipay_notify_error.log', '签名验证失败: ' . json_encode($data) . '\n', FILE_APPEND);
    echo 'fail';
}
```

### 异步处理支持
支付宝支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动支付宝支付异步处理
./start_async.sh start alipay

# 查看支付宝支付状态
./start_async.sh status alipay
```

### 测试
`examples/alipay/alipay_demo.php` 文件提供了支付宝支付的使用示例，你可以修改配置参数后直接运行测试。

## 抖音支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
抖音支付需要以下配置参数：

```php
$config = [
    'app_id' => 'your-app-id', // 抖音应用APPID
    'merchant_id' => 'your-merchant-id', // 商户号
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 抖音公钥
    'notify_url' => 'https://your-domain.com/callbacks/douyinpay/douyin_notify.php', // 支付回调URL
    'gateway_url' => 'https://pay-api.douyin.com', // 抖音支付网关URL
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Douyinpay\Payment\DouyinPayService;

$payService = new DouyinPayService($config);
```

#### 2. 统一下单

```php
$order = [
    'out_trade_no' => 'DY' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '抖音支付测试商品描述', // 订单描述
    'time_expire' => date('Y-m-d H:i:s', strtotime('+30 minutes')), // 订单过期时间
    'product_id' => '123456', // 商品ID
];

$result = $payService->unifiedOrder($order);

if ($result['code'] === 0) {
    echo "下单成功";
    $qrCode = $result['data']['qr_code']; // 二维码链接
    $prepayId = $result['data']['prepay_id']; // 预支付交易会话标识
} else {
    echo "下单失败：" . $result['message'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['code'] === 0) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_state'];
} else {
    echo "查询失败：" . $result['message'];
}
```

#### 4. 关闭订单

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->closeOrder($outTradeNo);

if ($result['code'] === 0) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['message'];
}
```

### 回调处理
抖音支付的异步通知处理请参考：`callbacks/douyinpay/douyin_notify.php`

### 异步处理支持
抖音支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动抖音支付异步处理
./start_async.sh start douyinpay

# 查看抖音支付状态
./start_async.sh status douyinpay
```

### 测试
`examples/douyinpay/douyinpay_demo.php` 文件提供了抖音支付的使用示例，你可以修改配置参数后直接运行测试。

## 快手支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
快手支付需要以下配置参数：

```php
$config = [
    'app_id' => 'your-app-id', // 快手应用APPID
    'merchant_id' => 'your-merchant-id', // 商户号
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 快手公钥
    'notify_url' => 'https://your-domain.com/callbacks/kuaishoupay/kuaishou_notify.php', // 支付回调URL
    'gateway_url' => 'https://pay-api.kuaishou.com', // 快手支付网关URL
];
```

### 异步处理支持
快手支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 异步回调处理
快手支付的异步通知处理已集成异步处理系统，请查看：`callbacks/kuaishoupay/kuaishou_notify.php`

#### 启动异步处理
```bash
# 启动快手支付异步处理
./start_async.sh start kuaishoupay

# 查看快手支付状态
./start_async.sh status kuaishoupay
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Kuaishoupay\Payment\KuaishouPayService;

$payService = new KuaishouPayService($config);
```

#### 2. 生成支付参数

```php
$order = [
    'out_trade_no' => 'KS' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '快手支付测试商品描述', // 订单描述
    'timeout_express' => '30m', // 订单过期时间
];

$result = $payService->generatePayParams($order);

if ($result['success']) {
    $payParams = $result['data']; // 支付参数，用于前端调起快手支付
} else {
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_status'];
} else {
    echo "查询失败：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['error'];
}
```

### 回调处理

快手支付的异步通知处理请参考：`callbacks/kuaishoupay/kuaishoupay_notify.php`

### 测试
`examples/kuaishoupay/kuaishoupay_demo.php` 文件提供了快手支付的使用示例，你可以修改配置参数后直接运行测试。

## 京东支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
京东支付需要以下配置参数：

```php
$config = [
    'merchant_no' => 'your-merchant-no', // 商户号
    'des_key' => 'your-des-key', // DES密钥
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 京东公钥
    'notify_url' => 'https://your-domain.com/callbacks/jdpay/jdpay_notify.php', // 支付回调URL
    'gateway_url' => 'https://wepay.jd.com/jdpay/saveOrder', // 京东支付网关URL
];
```

### 异步处理支持
京东支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动京东支付异步处理
./start_async.sh start jdpay

# 查看京东支付状态
./start_async.sh status jdpay
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Jdpay\Payment\JdPayService;

$payService = new JdPayService($config);
```

#### 2. 生成支付参数

```php
$order = [
    'out_trade_no' => 'JD' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '京东支付测试商品描述', // 订单描述
    'timeout_express' => '30m', // 订单过期时间
];

$result = $payService->generatePayParams($order);

if ($result['success']) {
    $payParams = $result['data']; // 支付参数，用于前端调起京东支付
} else {
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_status'];
} else {
    echo "查询失败：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['error'];
}
```

### 回调处理

京东支付的异步通知处理请参考：`callbacks/jdpay/jdpay_notify.php`

### 测试
`examples/jdpay/jdpay_demo.php` 文件提供了京东支付的使用示例，你可以修改配置参数后直接运行测试。

## 拼多多支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
拼多多支付需要以下配置参数：

```php
$config = [
    'client_id' => 'your-client-id', // 拼多多应用Client ID
    'client_secret' => 'your-client-secret', // 拼多多应用Client Secret
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 拼多多公钥
    'notify_url' => 'https://your-domain.com/callbacks/pinduoduopay/pinduoduopay_notify.php', // 支付回调URL
    'gateway_url' => 'https://open-api.pinduoduo.com/gateway', // 拼多多支付网关URL
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Pinduoduopay\Payment\PinduoduoPayService;

$payService = new PinduoduoPayService($config);
```

#### 2. 生成支付参数

```php
$order = [
    'out_trade_no' => 'PDD' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '拼多多支付测试商品描述', // 订单描述
    'timeout_express' => '30m', // 订单过期时间
];

$result = $payService->generatePayParams($order);

if ($result['success']) {
    $payParams = $result['data']; // 支付参数，用于前端调起拼多多支付
} else {
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_status'];
} else {
    echo "查询失败：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['error'];
}
```

### 回调处理

拼多多支付的异步通知处理请参考：`callbacks/pinduoduopay/pinduoduopay_notify.php`

### 异步处理支持
拼多多支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动拼多多支付异步处理
./start_async.sh start pinduoduopay

# 查看拼多多支付状态
./start_async.sh status pinduoduopay
```

### 测试
`examples/pinduoduopay/pinduoduopay_demo.php` 文件提供了拼多多支付的使用示例，你可以修改配置参数后直接运行测试。

## 美团支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
美团支付需要以下配置参数：

```php
$config = [
    'merchant_id' => 'your-merchant-id', // 美团商户号
    'app_key' => 'your-app-key', // 美团应用App Key
    'app_secret' => 'your-app-secret', // 美团应用App Secret
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 美团公钥
    'notify_url' => 'https://your-domain.com/callbacks/meituanpay/meituanpay_notify.php', // 支付回调URL
    'gateway_url' => 'https://api.meituan.com/gateway', // 美团支付网关URL
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Meituanpay\Payment\MeituanPayService;

$payService = new MeituanPayService($config);
```

#### 2. 生成支付参数

```php
$order = [
    'out_trade_no' => 'MT' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '美团支付测试商品描述', // 订单描述
    'timeout_express' => '30m', // 订单过期时间
];

$result = $payService->generatePayParams($order);

if ($result['success']) {
    $payParams = $result['data']; // 支付参数，用于前端调起美团支付
} else {
    echo "错误：" . $result['error'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['success']) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_status'];
} else {
    echo "查询失败：" . $result['error'];
}
```

#### 4. 关闭订单

```php
$result = $payService->closeOrder($outTradeNo);

if ($result['success']) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['error'];
}
```

#### 5. 申请退款

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$refundAmount = 0.01; // 退款金额
$outRefundNo = 'RF' . date('YmdHis') . rand(1000, 9999); // 商户退款单号
$reason = '测试退款'; // 退款原因

$result = $payService->refund($outTradeNo, $refundAmount, $outRefundNo, $reason);

if ($result['success']) {
    echo "退款申请成功";
} else {
    echo "退款失败：" . $result['error'];
}
```

### 回调处理

美团支付的异步通知处理请参考：`callbacks/meituanpay/meituanpay_notify.php`

### 异步处理支持
美团支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动美团支付异步处理
./start_async.sh start meituanpay

# 查看美团支付状态
./start_async.sh status meituanpay
```

### 测试
`examples/meituanpay/meituanpay_demo.php` 文件提供了美团支付的使用示例，你可以修改配置参数后直接运行测试。

## 苹果支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
苹果支付需要以下配置参数：

```php
$config = [
    'merchant_id' => 'your-merchant-id', // Apple Pay商户ID
    'merchant_cert_path' => '/path/to/merchant-cert.pem', // 商户证书路径
    'merchant_key_path' => '/path/to/merchant-key.pem', // 商户私钥路径
    'merchant_key_password' => 'your-key-password', // 私钥密码
    'notify_url' => 'https://your-domain.com/callbacks/applepay/applepay_notify.php', // 支付回调URL
    'gateway_url' => 'https://apple-pay-gateway.apple.com/payments', // Apple Pay网关URL
    'display_name' => 'EasyPay', // 显示名称
];
```

### 异步处理支持
苹果支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 异步回调处理
苹果支付的异步通知处理已集成异步处理系统，请查看：`callbacks/applepay/applepay_notify.php`

#### 启动异步处理
```bash
# 启动苹果支付异步处理
./start_async.sh start applepay

# 查看苹果支付状态
./start_async.sh status applepay
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Applepay\Payment\ApplePayService;

$payService = new ApplePayService($config);
```

#### 2. 创建支付会话

```php
$order = [
    'out_trade_no' => 'AP' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'currency_code' => 'CNY', // 货币代码
    'subject' => '测试商品', // 订单标题
    'country_code' => 'CN', // 国家代码
    'domain_name' => $_SERVER['HTTP_HOST'], // 域名
];

$sessionData = $payService->createPaymentSession($order);
```

#### 3. 验证商户

```php
$validationUrl = 'https://apple-pay-gateway.apple.com/paymentservices/startSession';
$result = $payService->validateMerchant($validationUrl);

if ($result['code'] === 0) {
    $merchantSession = $result['data'];
    // 用于前端Apple Pay会话
} else {
    echo "验证失败：" . $result['message'];
}
```

#### 4. 处理支付完成

```php
$paymentData = [
    'payment' => [
        'token' => [
            'paymentData' => $paymentToken, // 从前端获取的支付令牌
        ]
    ]
];

$result = $payService->processPayment($paymentData, $order);

if ($result['code'] === 0) {
    echo "支付成功";
    $transactionId = $result['data']['transaction_id'];
} else {
    echo "支付失败：" . $result['message'];
}
```

#### 5. 查询订单状态

```php
$transactionId = 'your-transaction-id';
$result = $payService->queryOrderStatus($transactionId);

if ($result['code'] === 0) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['status'];
} else {
    echo "查询失败：" . $result['message'];
}
```

#### 6. 申请退款

```php
$transactionId = 'your-transaction-id'; // Apple Pay交易ID
$refundAmount = 0.01; // 退款金额
$reason = '测试退款'; // 退款原因

$result = $payService->refund($transactionId, $refundAmount, $reason);

if ($result['code'] === 0) {
    echo "退款申请成功";
    $refundId = $result['data']['refund_id'];
} else {
    echo "退款失败：" . $result['message'];
}
```

### 前端集成示例

```html
<!DOCTYPE html>
<html>
<head>
    <title>Apple Pay 支付示例</title>
    <script src="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js"></script>
</head>
<body>
    <apple-pay-button
        buttonstyle="black"
        type="plain"
        locale="zh-CN">
    </apple-pay-button>

    <script>
        const session = new ApplePaySession(3, {
            countryCode: 'CN',
            currencyCode: 'CNY',
            supportedNetworks: ['visa', 'masterCard', 'chinaUnionPay'],
            merchantCapabilities: ['supports3DS', 'supportsCredit', 'supportsDebit'],
            total: {
                label: '测试商品',
                amount: '0.01'
            }
        });

        session.onvalidatemerchant = async (event) => {
            try {
                const response = await fetch('/validate-merchant.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        validationURL: event.validationURL
                    })
                });
                
                const merchantSession = await response.json();
                session.completeMerchantValidation(merchantSession);
            } catch (error) {
                console.error('商户验证失败:', error);
                session.abort();
            }
        };

        session.onpaymentauthorized = (event) => {
            fetch('/process-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment: event.payment
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    session.completePayment(ApplePaySession.STATUS_SUCCESS);
                } else {
                    session.completePayment(ApplePaySession.STATUS_FAILURE);
                }
            });
        };

        document.querySelector('apple-pay-button').addEventListener('click', () => {
            session.begin();
        });
    </script>
</body>
</html>
```

### 回调处理

苹果支付的异步通知处理请参考：`callbacks/applepay/applepay_notify.php`

### 异步处理支持
苹果支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动苹果支付异步处理
./start_async.sh start applepay

# 查看苹果支付状态
./start_async.sh status applepay
```

### 测试
`examples/applepay/applepay_demo.php` 文件提供了苹果支付的使用示例，你可以修改配置参数后直接运行测试。

### 证书配置
苹果支付需要配置商户证书，请按照以下步骤操作：

1. 登录 [Apple Developer Portal](https://developer.apple.com)
2. 创建 Apple Pay 商户ID
3. 生成并下载商户证书
4. 将证书和私钥放置在配置指定的路径
5. 确保服务器可以访问证书文件

### 注意事项
- 苹果支付仅支持HTTPS环境
- 需要在苹果开发者后台配置正确的域名
- 证书有效期为1年，需要定期更新
- 测试环境使用Apple Pay沙箱环境

## 云闪付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
云闪付需要以下配置参数：

```php
$config = [
    'merchant_id' => 'your-merchant-id', // 云闪付商户号
    'app_key' => 'your-app-key', // 云闪付应用App Key
    'app_secret' => 'your-app-secret', // 云闪付应用App Secret
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 云闪付公钥
    'notify_url' => 'https://your-domain.com/callbacks/cmbpay/cmbpay_notify.php', // 支付回调URL
    'gateway_url' => 'https://api.95516.com/gateway', // 云闪付支付网关URL
];
```

### 使用方法

#### 1. 创建支付服务实例

```php
use Cmbpay\Payment\CmbPayService;

$payService = new CmbPayService($config);
```

#### 2. 统一下单

```php
$order = [
    'out_trade_no' => 'CMB' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '云闪付测试商品描述', // 订单描述
    'time_expire' => date('Y-m-d H:i:s', strtotime('+30 minutes')), // 订单过期时间
    'product_id' => '123456', // 商品ID
];

$result = $payService->unifiedOrder($order);

if ($result['code'] === 0) {
    echo "下单成功";
    $qrCode = $result['data']['qr_code']; // 二维码链接
    $prepayId = $result['data']['prepay_id']; // 预支付交易会话标识
} else {
    echo "下单失败：" . $result['message'];
}
```

#### 3. 订单查询

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['code'] === 0) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_state'];
} else {
    echo "查询失败：" . $result['message'];
}
```

#### 4. 关闭订单

```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->closeOrder($outTradeNo);

if ($result['code'] === 0) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['message'];
}
```

### 回调处理
云闪付的异步通知处理请参考：`callbacks/cmbpay/cmbpay_notify.php`

### 异步处理支持
云闪付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动云闪付异步处理
./start_async.sh start cmbpay

# 查看云闪付状态
./start_async.sh status cmbpay
```

### 测试
`examples/cmbpay/cmbpay_demo.php` 文件提供了云闪付的使用示例，你可以修改配置参数后直接运行测试。

## 华为支付集成

### 安装
使用 Composer 安装：

```bash
composer require ankewk/easypay
```

### 配置
华为支付需要以下配置参数：

```php
$config = [
    'app_id' => 'your-app-id', // 华为应用APPID
    'merchant_id' => 'your-merchant-id', // 商户号
    'private_key' => 'your-private-key', // 商户私钥
    'public_key' => 'your-public-key', // 华为公钥
    'notify_url' => 'https://your-domain.com/callbacks/huaweipay/huawei_notify.php', // 支付回调URL
    'gateway_url' => 'https://pay-api.cloud.huawei.com', // 华为支付网关URL
];
```

### 异步处理支持
华为支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 异步回调处理
华为支付的异步通知处理已集成异步处理系统，请查看：`callbacks/huaweipay/huawei_notify.php`

#### 启动异步处理
```bash
# 启动华为支付异步处理
./start_async.sh start huaweipay

# 查看华为支付状态
./start_async.sh status huaweipay
```

### 使用方法

#### 1. 创建支付服务实例
```php
use Huaweipay\Payment\HuaweiPayService;

$payService = new HuaweiPayService($config);
```

#### 2. 统一下单
```php
$order = [
    'out_trade_no' => 'HW' . date('YmdHis') . rand(1000, 9999), // 商户订单号
    'total_amount' => 0.01, // 订单金额（元）
    'subject' => '测试商品', // 订单标题
    'body' => '测试商品描述', // 订单描述
    'time_expire' => date('Y-m-d H:i:s', strtotime('+30 minutes')), // 订单过期时间
    'product_id' => '123456', // 商品ID
];

$result = $payService->unifiedOrder($order);

if ($result['code'] === 0) {
    echo "下单成功";
    $qrCode = $result['data']['qr_code']; // 二维码链接
    $prepayId = $result['data']['prepay_id']; // 预支付交易会话标识
} else {
    echo "下单失败：" . $result['message'];
}
```

#### 3. 订单查询
```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->queryOrder($outTradeNo);

if ($result['code'] === 0) {
    $orderInfo = $result['data'];
    echo "订单状态：" . $orderInfo['trade_state'];
} else {
    echo "查询失败：" . $result['message'];
}
```

#### 4. 关闭订单
```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$result = $payService->closeOrder($outTradeNo);

if ($result['code'] === 0) {
    echo "订单关闭成功";
} else {
    echo "关闭失败：" . $result['message'];
}
```

#### 5. 申请退款
```php
$outTradeNo = 'your-out-trade-no'; // 商户订单号
$refundAmount = 0.01; // 退款金额
$reason = '测试退款'; // 退款原因
$outRefundNo = 'RF' . date('YmdHis') . rand(1000, 9999); // 商户退款单号

$result = $payService->refund($outTradeNo, $refundAmount, $reason, $outRefundNo);

if ($result['code'] === 0) {
    echo "退款申请成功";
    $refundId = $result['data']['refund_id'];
} else {
    echo "退款失败：" . $result['message'];
}
```

#### 6. 退款查询
```php
$outRefundNo = 'your-out-refund-no'; // 商户退款单号
$result = $payService->queryRefund($outRefundNo);

if ($result['code'] === 0) {
    $refundInfo = $result['data'];
    echo "退款状态：" . $refundInfo['refund_state'];
} else {
    echo "查询失败：" . $result['message'];
}
```

#### 7. 生成支付参数
```php
$prepayId = 'your-prepay-id'; // 预支付交易会话标识
$params = $payService->generatePayParams($prepayId);

// 返回给前端的支付参数
// {
//     "appId": "应用ID",
//     "partnerId": "商户号",
//     "prepayId": "预支付会话标识",
//     "package": "Sign=WXPay",
//     "nonceStr": "随机字符串",
//     "timeStamp": "时间戳",
//     "sign": "签名"
// }
```

#### 8. 验证签名
```php
$data = $_POST; // 回调数据
$isValid = $payService->verifySign($data);

if ($isValid) {
    echo "签名验证成功";
    // 处理业务逻辑
} else {
    echo "签名验证失败";
}
```

### 回调处理
华为支付的异步通知处理请参考：`callbacks/huaweipay/huawei_notify.php`

### 测试
`examples/huaweipay/huawei_demo.php` 文件提供了华为支付的使用示例，你可以修改配置参数后直接运行测试。

### 密钥配置
华为支付需要配置商户密钥，请按照以下步骤操作：

1. 登录 [华为开发者联盟](https://developer.huawei.com)
2. 进入"开发者中心" > "支付服务"
3. 创建应用并获取App ID和商户ID
4. 生成RSA密钥对（推荐使用2048位）
5. 将公钥上传到华为开发者后台
6. 将私钥配置到项目中

### 注意事项
- 华为支付支持多种支付方式：华为应用内支付、华为钱包支付等
- 需要在华为开发者后台配置正确的包名和签名
- 密钥需要妥善保管，不要泄露给第三方
- 测试环境使用华为沙箱环境
- 确保服务器时间准确，否则可能导致签名验证失败

### 异步处理
华为支付支持异步处理订单状态更新，适用于高并发场景。

#### 1. 配置异步处理
在配置文件中添加异步处理相关配置：
```php
'huaweipay' => [
    // ...其他配置...
    
    // 异步处理配置
    'queue_type' => 'file', // 可选：file, redis, database
    
    // Redis配置（当queue_type=redis时）
    'redis_host' => '127.0.0.1',
    'redis_port' => 6379,
    'redis_password' => '',
    
    // 数据库配置（当queue_type=database时）
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'easypay',
    'db_user' => 'root',
    'db_pass' => '',
    
    // 重试配置
    'max_retries' => 3,
    'retry_delay' => 5, // 秒
],
```

#### 2. 使用异步处理器
```php
use Huaweipay\Payment\AsyncProcessor;

// 创建异步处理器
$asyncProcessor = new AsyncProcessor($config);

// 处理异步通知
$result = $asyncProcessor->processAsyncNotify($notifyData);

// 处理任务队列（定时任务）
$results = $asyncProcessor->processTaskQueue(10);
```

#### 3. 设置定时任务
```bash
# 每5分钟处理一次任务队列
*/5 * * * * /usr/bin/php /path/to/easypay/callbacks/huaweipay/async_processor.php --action=process --limit=10
```

### 异步处理支持
华为支付已完全支持异步处理，回调处理器已更新为异步模式，确保快速响应和高可靠性。

#### 启动异步处理
```bash
# 启动华为支付异步处理
./start_async.sh start huaweipay

# 查看华为支付状态
./start_async.sh status huaweipay
```

完整示例请参考：`examples/huaweipay/async_config.php`
