<?php

namespace Huaweipay\Payment;

use Exception;

/**
 * 华为支付异步任务处理器
 * 
 * 处理华为支付的异步回调和订单状态更新
 */
class AsyncProcessor
{
    private $config;
    private $payService;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->payService = new HuaweiPayService($config);
    }
    
    /**
     * 处理异步通知
     * 
     * @param array $notifyData 通知数据
     * @return array 处理结果
     */
    public function processAsyncNotify($notifyData)
    {
        try {
            // 验证签名
            if (!$this->validateNotify($notifyData)) {
                return [
                    'success' => false,
                    'message' => '签名验证失败',
                    'code' => 'SIGN_ERROR'
                ];
            }
            
            // 解析通知数据
            $parsedData = $this->parseNotifyData($notifyData);
            
            // 异步处理订单状态
            $result = $this->asyncProcessOrder($parsedData);
            
            return $result;
            
        } catch (Exception $e) {
            $this->logError('Async process error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PROCESS_ERROR'
            ];
        }
    }
    
    /**
     * 验证通知签名
     * 
     * @param array $notifyData 通知数据
     * @return bool 验证结果
     */
    private function validateNotify($notifyData)
    {
        if (!isset($notifyData['sign'])) {
            return false;
        }
        
        $sign = $notifyData['sign'];
        unset($notifyData['sign']);
        
        return $this->payService->verifySign($notifyData, $sign);
    }
    
    /**
     * 解析通知数据
     * 
     * @param array $notifyData 通知数据
     * @return array 解析后的数据
     */
    private function parseNotifyData($notifyData)
    {
        return [
            'out_trade_no' => $notifyData['out_trade_no'] ?? '',
            'transaction_id' => $notifyData['transaction_id'] ?? '',
            'trade_state' => $notifyData['trade_state'] ?? '',
            'total_amount' => $notifyData['total_amount'] ?? 0,
            'refund_amount' => $notifyData['refund_amount'] ?? 0,
            'success_time' => $notifyData['success_time'] ?? '',
            'err_code' => $notifyData['err_code'] ?? '',
            'err_code_des' => $notifyData['err_code_des'] ?? '',
            'raw_data' => $notifyData
        ];
    }
    
    /**
     * 异步处理订单状态
     * 
     * @param array $data 解析后的通知数据
     * @return array 处理结果
     */
    private function asyncProcessOrder($data)
    {
        $outTradeNo = $data['out_trade_no'];
        $tradeStatus = $data['trade_state'];
        
        // 使用异步任务队列处理
        $taskData = [
            'type' => 'huawei_pay_notify',
            'order_no' => $outTradeNo,
            'transaction_id' => $data['transaction_id'],
            'status' => $tradeStatus,
            'amount' => $data['total_amount'],
            'timestamp' => time(),
            'retry_count' => 0,
            'max_retries' => 3
        ];
        
        // 添加到异步任务队列
        $this->addToTaskQueue($taskData);
        
        return [
            'success' => true,
            'message' => '已加入异步处理队列',
            'task_id' => $this->generateTaskId($taskData)
        ];
    }
    
    /**
     * 添加到任务队列
     * 
     * @param array $taskData 任务数据
     */
    private function addToTaskQueue($taskData)
    {
        // 支持多种队列实现
        $queueType = $this->config['queue_type'] ?? 'file';
        
        switch ($queueType) {
            case 'redis':
                $this->addToRedisQueue($taskData);
                break;
            case 'database':
                $this->addToDatabaseQueue($taskData);
                break;
            case 'file':
            default:
                $this->addToFileQueue($taskData);
                break;
        }
    }
    
    /**
     * 添加到Redis队列
     * 
     * @param array $taskData 任务数据
     */
    private function addToRedisQueue($taskData)
    {
        try {
            $redis = new \Redis();
            $redis->connect($this->config['redis_host'] ?? '127.0.0.1', $this->config['redis_port'] ?? 6379);
            
            if (!empty($this->config['redis_password'])) {
                $redis->auth($this->config['redis_password']);
            }
            
            $redis->lPush('huawei_pay_tasks', json_encode($taskData));
            $redis->close();
            
            $this->logInfo("任务已添加到Redis队列: " . $taskData['order_no']);
        } catch (Exception $e) {
            $this->logError("Redis队列添加失败: " . $e->getMessage());
            // 降级到文件队列
            $this->addToFileQueue($taskData);
        }
    }
    
    /**
     * 添加到数据库队列
     * 
     * @param array $taskData 任务数据
     */
    private function addToDatabaseQueue($taskData)
    {
        try {
            $pdo = $this->getDatabaseConnection();
            
            $stmt = $pdo->prepare("INSERT INTO async_tasks (task_type, order_no, data, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([
                $taskData['type'],
                $taskData['order_no'],
                json_encode($taskData),
                'pending'
            ]);
            
            $this->logInfo("任务已添加到数据库队列: " . $taskData['order_no']);
        } catch (Exception $e) {
            $this->logError("数据库队列添加失败: " . $e->getMessage());
            // 降级到文件队列
            $this->addToFileQueue($taskData);
        }
    }
    
    /**
     * 添加到文件队列
     * 
     * @param array $taskData 任务数据
     */
    private function addToFileQueue($taskData)
    {
        $queueDir = __DIR__ . '/../../../logs/huaweipay/queue';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }
        
        $taskFile = $queueDir . '/' . date('Y-m-d') . '_tasks.json';
        $tasks = [];
        
        // 读取现有任务
        if (file_exists($taskFile)) {
            $content = file_get_contents($taskFile);
            $tasks = json_decode($content, true) ?: [];
        }
        
        // 添加新任务
        $tasks[] = $taskData;
        
        // 保存回文件
        file_put_contents($taskFile, json_encode($tasks, JSON_PRETTY_PRINT));
        
        $this->logInfo("任务已添加到文件队列: " . $taskData['order_no']);
    }
    
    /**
     * 处理任务队列
     * 
     * @param int $limit 处理任务数量限制
     * @return array 处理结果
     */
    public function processTaskQueue($limit = 10)
    {
        $results = [];
        $queueType = $this->config['queue_type'] ?? 'file';
        
        switch ($queueType) {
            case 'redis':
                $results = $this->processRedisQueue($limit);
                break;
            case 'database':
                $results = $this->processDatabaseQueue($limit);
                break;
            case 'file':
            default:
                $results = $this->processFileQueue($limit);
                break;
        }
        
        return $results;
    }
    
    /**
     * 处理Redis队列
     * 
     * @param int $limit 处理任务数量限制
     * @return array 处理结果
     */
    private function processRedisQueue($limit)
    {
        $results = [];
        
        try {
            $redis = new \Redis();
            $redis->connect($this->config['redis_host'] ?? '127.0.0.1', $this->config['redis_port'] ?? 6379);
            
            if (!empty($this->config['redis_password'])) {
                $redis->auth($this->config['redis_password']);
            }
            
            for ($i = 0; $i < $limit; $i++) {
                $taskJson = $redis->rPop('huawei_pay_tasks');
                if (!$taskJson) {
                    break;
                }
                
                $task = json_decode($taskJson, true);
                $result = $this->executeTask($task);
                $results[] = $result;
            }
            
            $redis->close();
        } catch (Exception $e) {
            $this->logError("Redis队列处理失败: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 处理数据库队列
     * 
     * @param int $limit 处理任务数量限制
     * @return array 处理结果
     */
    private function processDatabaseQueue($limit)
    {
        $results = [];
        
        try {
            $pdo = $this->getDatabaseConnection();
            
            $stmt = $pdo->prepare("SELECT * FROM async_tasks WHERE task_type = 'huawei_pay_notify' AND status = 'pending' ORDER BY created_at ASC LIMIT ?");
            $stmt->execute([$limit]);
            $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($tasks as $task) {
                $taskData = json_decode($task['data'], true);
                $result = $this->executeTask($taskData);
                
                // 更新任务状态
                $updateStmt = $pdo->prepare("UPDATE async_tasks SET status = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$result['success'] ? 'completed' : 'failed', $task['id']]);
                
                $results[] = $result;
            }
        } catch (Exception $e) {
            $this->logError("数据库队列处理失败: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * 处理文件队列
     * 
     * @param int $limit 处理任务数量限制
     * @return array 处理结果
     */
    private function processFileQueue($limit)
    {
        $results = [];
        $queueDir = __DIR__ . '/../../../logs/huaweipay/queue';
        $taskFile = $queueDir . '/' . date('Y-m-d') . '_tasks.json';
        
        if (!file_exists($taskFile)) {
            return $results;
        }
        
        $content = file_get_contents($taskFile);
        $tasks = json_decode($content, true) ?: [];
        
        $pendingTasks = array_filter($tasks, function($task) {
            return ($task['status'] ?? 'pending') === 'pending';
        });
        
        $processTasks = array_slice($pendingTasks, 0, $limit);
        
        foreach ($processTasks as $index => $task) {
            $result = $this->executeTask($task);
            $results[] = $result;
            
            // 更新任务状态
            $taskKey = array_keys($pendingTasks)[$index];
            $tasks[$taskKey]['status'] = $result['success'] ? 'completed' : 'failed';
            $tasks[$taskKey]['processed_at'] = date('Y-m-d H:i:s');
        }
        
        // 保存更新后的任务状态
        file_put_contents($taskFile, json_encode($tasks, JSON_PRETTY_PRINT));
        
        return $results;
    }
    
    /**
     * 执行具体任务
     * 
     * @param array $task 任务数据
     * @return array 执行结果
     */
    private function executeTask($task)
    {
        try {
            $orderNo = $task['order_no'];
            $status = $task['status'];
            
            // 根据状态处理订单
            switch ($status) {
                case 'SUCCESS':
                    $result = $this->handlePaymentSuccess($orderNo, $task);
                    break;
                case 'CLOSED':
                    $result = $this->handlePaymentClosed($orderNo, $task);
                    break;
                case 'REFUND':
                    $result = $this->handlePaymentRefund($orderNo, $task);
                    break;
                case 'PAY_ERROR':
                    $result = $this->handlePaymentError($orderNo, $task);
                    break;
                default:
                    $result = [
                        'success' => false,
                        'message' => '未知状态'
                    ];
            }
            
            return array_merge($result, ['task_id' => $task['task_id']]);
            
        } catch (Exception $e) {
            $this->logError("任务执行失败: " . $e->getMessage());
            
            // 重试逻辑
            if ($task['retry_count'] < $task['max_retries']) {
                $task['retry_count']++;
                $this->addToTaskQueue($task);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'retry_count' => $task['retry_count']
            ];
        }
    }
    
    /**
     * 处理支付成功
     * 
     * @param string $orderNo 订单号
     * @param array $task 任务数据
     * @return array 处理结果
     */
    private function handlePaymentSuccess($orderNo, $task)
    {
        // 这里实现支付成功的业务逻辑
        // 例如：更新订单状态、发送通知、更新库存等
        
        $this->logInfo("处理支付成功 - 订单: $orderNo");
        
        return [
            'success' => true,
            'message' => '支付成功处理完成',
            'order_no' => $orderNo,
            'transaction_id' => $task['transaction_id']
        ];
    }
    
    /**
     * 处理交易关闭
     * 
     * @param string $orderNo 订单号
     * @param array $task 任务数据
     * @return array 处理结果
     */
    private function handlePaymentClosed($orderNo, $task)
    {
        // 这里实现交易关闭的业务逻辑
        
        $this->logInfo("处理交易关闭 - 订单: $orderNo");
        
        return [
            'success' => true,
            'message' => '交易关闭处理完成',
            'order_no' => $orderNo
        ];
    }
    
    /**
     * 处理退款
     * 
     * @param string $orderNo 订单号
     * @param array $task 任务数据
     * @return array 处理结果
     */
    private function handlePaymentRefund($orderNo, $task)
    {
        // 这里实现退款的业务逻辑
        
        $this->logInfo("处理退款 - 订单: $orderNo");
        
        return [
            'success' => true,
            'message' => '退款处理完成',
            'order_no' => $orderNo
        ];
    }
    
    /**
     * 处理支付错误
     * 
     * @param string $orderNo 订单号
     * @param array $task 任务数据
     * @return array 处理结果
     */
    private function handlePaymentError($orderNo, $task)
    {
        // 这里实现支付错误的业务逻辑
        
        $this->logInfo("处理支付错误 - 订单: $orderNo");
        
        return [
            'success' => true,
            'message' => '支付错误处理完成',
            'order_no' => $orderNo
        ];
    }
    
    /**
     * 获取数据库连接
     * 
     * @return \PDO 数据库连接
     * @throws Exception 连接失败时抛出异常
     */
    private function getDatabaseConnection()
    {
        $host = $this->config['db_host'] ?? 'localhost';
        $port = $this->config['db_port'] ?? 3306;
        $dbname = $this->config['db_name'] ?? 'easypay';
        $username = $this->config['db_user'] ?? 'root';
        $password = $this->config['db_pass'] ?? '';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        
        return new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
    }
    
    /**
     * 生成任务ID
     * 
     * @param array $taskData 任务数据
     * @return string 任务ID
     */
    private function generateTaskId($taskData)
    {
        return 'hw_' . $taskData['order_no'] . '_' . time() . '_' . rand(1000, 9999);
    }
    
    /**
     * 记录信息日志
     * 
     * @param string $message 日志消息
     */
    private function logInfo($message)
    {
        $this->logMessage($message, 'INFO');
    }
    
    /**
     * 记录错误日志
     * 
     * @param string $message 日志消息
     */
    private function logError($message)
    {
        $this->logMessage($message, 'ERROR');
    }
    
    /**
     * 记录日志
     * 
     * @param string $message 日志消息
     * @param string $level 日志级别
     */
    private function logMessage($message, $level = 'INFO')
    {
        $logDir = __DIR__ . '/../../../logs/huaweipay';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/async_' . date('Y-m-d') . '.log';
        $time = date('Y-m-d H:i:s');
        $log = "[$time] [$level] $message\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    }
}