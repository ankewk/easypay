<?php

namespace Common\Payment;

use Exception;

/**
 * 通用支付异步任务处理器
 * 
 * 支持所有支付方式的异步回调处理
 * 支持Redis、数据库、文件三种队列存储方式
 */
class AsyncProcessor
{
    private $config;
    private $paymentType;
    private $service;
    private $queue;
    private $logger;
    
    public function __construct($config, $paymentType, $service)
    {
        $this->config = $config;
        $this->paymentType = $paymentType;
        $this->service = $service;
        $this->logger = new AsyncLogger($config['log_file'] ?? null);
        
        $this->initQueue();
    }
    
    /**
     * 初始化队列
     */
    private function initQueue()
    {
        $queueType = $this->config['queue_type'] ?? 'file';
        
        switch ($queueType) {
            case 'redis':
                $this->queue = new RedisQueue($this->config);
                break;
            case 'database':
                $this->queue = new DatabaseQueue($this->config);
                break;
            case 'file':
            default:
                $this->queue = new FileQueue($this->config);
                break;
        }
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
            
            // 创建任务并添加到队列
            $task = $this->createTask($parsedData);
            $result = $this->addTask($task);
            
            return [
                'success' => true,
                'message' => '已加入异步处理队列',
                'task_id' => $task['id']
            ];
            
        } catch (Exception $e) {
            $this->logger->error('Async process error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PROCESS_ERROR'
            ];
        }
    }
    
    /**
     * 创建任务
     */
    private function createTask($data)
    {
        return [
            'id' => uniqid('task_'),
            'payment_type' => $this->paymentType,
            'order_no' => $data['out_trade_no'] ?? '',
            'transaction_id' => $data['transaction_id'] ?? '',
            'status' => $this->getStatusFromData($data),
            'amount' => $data['amount'] ?? 0,
            'raw_data' => $data['raw_data'],
            'created_at' => date('Y-m-d H:i:s'),
            'retry_count' => 0,
            'max_retries' => $this->config['max_retries'] ?? 3
        ];
    }
    
    /**
     * 添加任务到队列
     */
    public function addTask($taskData)
    {
        $task = [
            'id' => $taskData['id'] ?? uniqid('task_'),
            'payment_type' => $this->paymentType,
            'data' => $taskData,
            'created_at' => date('Y-m-d H:i:s'),
            'retry_count' => 0,
            'status' => 'pending'
        ];
        
        return $this->queue->push($task);
    }
    
    /**
     * 处理任务队列
     */
    public function processTaskQueue($limit = null)
    {
        $results = [];
        $processed = 0;
        $limit = $limit ?? $this->config['batch_size'] ?? 50;
        
        while ($task = $this->queue->pop($this->paymentType)) {
            if ($processed >= $limit) {
                break;
            }
            
            $result = $this->processTask($task);
            $results[] = $result;
            $processed++;
            
            if ($result['success']) {
                $this->queue->ack($task['id']);
            } else {
                $this->handleTaskFailure($task, $result['error']);
            }
        }
        
        return $results;
    }
    
    /**
     * 处理单个任务
     */
    private function processTask($task)
    {
        try {
            $this->logger->info("开始处理任务", [
                'task_id' => $task['id'],
                'payment_type' => $task['payment_type'],
                'order_no' => $task['data']['order_no'] ?? ''
            ]);
            
            // 调用支付服务处理
            $result = $this->service->processPaymentNotify($task['data']['raw_data']);
            
            if ($result['success']) {
                $this->logger->info("任务处理成功", [
                    'task_id' => $task['id'],
                    'order_no' => $task['data']['order_no'] ?? ''
                ]);
                
                return [
                    'success' => true,
                    'order_no' => $task['data']['order_no'] ?? '',
                    'task_id' => $task['id']
                ];
            } else {
                throw new Exception($result['message'] ?? '处理失败');
            }
            
        } catch (Exception $e) {
            $this->logger->error("任务处理失败", [
                'task_id' => $task['id'],
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'order_no' => $task['data']['order_no'] ?? '',
                'task_id' => $task['id'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 处理任务失败
     */
    private function handleTaskFailure($task, $error)
    {
        $maxRetries = $this->config['max_retries'] ?? 3;
        $retryDelay = $this->config['retry_delay'] ?? 5;
        
        if ($task['retry_count'] < $maxRetries) {
            // 重新入队
            $task['retry_count']++;
            $task['status'] = 'retry';
            $task['next_retry'] = date('Y-m-d H:i:s', time() + $retryDelay);
            
            $this->queue->push($task);
            
            $this->logger->warning("任务重试", [
                'task_id' => $task['id'],
                'retry_count' => $task['retry_count'],
                'max_retries' => $maxRetries
            ]);
        } else {
            // 标记为失败
            $task['status'] = 'failed';
            $task['failed_at'] = date('Y-m-d H:i:s');
            
            $this->queue->markFailed($task);
            
            $this->logger->error("任务最终失败", [
                'task_id' => $task['id'],
                'error' => $error
            ]);
        }
    }
    
    /**
     * 验证通知签名
     */
    private function validateNotify($notifyData)
    {
        $method = 'verifySign';
        if (method_exists($this->service, $method)) {
            if (!isset($notifyData['sign'])) {
                return false;
            }
            
            $sign = $notifyData['sign'];
            unset($notifyData['sign']);
            
            return $this->service->$method($notifyData, $sign);
        }
        
        return true;
    }
    
    /**
     * 解析通知数据
     */
    private function parseNotifyData($notifyData)
    {
        $data = [
            'payment_type' => $this->paymentType,
            'raw_data' => $notifyData,
            'timestamp' => time()
        ];
        
        switch ($this->paymentType) {
            case 'alipay':
                $data['out_trade_no'] = $notifyData['out_trade_no'] ?? '';
                $data['trade_no'] = $notifyData['trade_no'] ?? '';
                $data['trade_status'] = $notifyData['trade_status'] ?? '';
                $data['amount'] = $notifyData['total_amount'] ?? 0;
                break;
                
            case 'wxpay':
                $data['out_trade_no'] = $notifyData['out_trade_no'] ?? '';
                $data['transaction_id'] = $notifyData['transaction_id'] ?? '';
                $data['result_code'] = $notifyData['result_code'] ?? '';
                $data['amount'] = ($notifyData['total_fee'] ?? 0) / 100;
                break;
                
            case 'huaweipay':
                $data['out_trade_no'] = $notifyData['out_trade_no'] ?? '';
                $data['transaction_id'] = $notifyData['transaction_id'] ?? '';
                $data['trade_state'] = $notifyData['trade_state'] ?? '';
                $data['amount'] = $notifyData['total_amount'] ?? 0;
                break;
                
            case 'applepay':
                $data['out_trade_no'] = $notifyData['out_trade_no'] ?? '';
                $data['transaction_id'] = $notifyData['transaction_id'] ?? '';
                $data['status'] = $notifyData['status'] ?? '';
                $data['amount'] = $notifyData['amount'] ?? 0;
                break;
                
            default:
                $data['out_trade_no'] = $notifyData['out_trade_no'] ?? '';
                $data['transaction_id'] = $notifyData['transaction_id'] ?? '';
                $data['status'] = $notifyData['status'] ?? '';
                $data['amount'] = $notifyData['amount'] ?? 0;
        }
        
        return $data;
    }
    
    /**
     * 从数据中提取状态
     */
    private function getStatusFromData($data)
    {
        switch ($this->paymentType) {
            case 'alipay':
                return $data['trade_status'] ?? '';
            case 'wxpay':
                return $data['result_code'] ?? '';
            case 'huaweipay':
                return $data['trade_state'] ?? '';
            case 'applepay':
                return $data['status'] ?? '';
            default:
                return $data['status'] ?? '';
        }
    }
    
    /**
     * 获取队列状态
     */
    public function getQueueStatus()
    {
        return $this->queue->getStatus($this->paymentType);
    }
    
    /**
     * 清空队列
     */
    public function clearQueue()
    {
        return $this->queue->clear($this->paymentType);
    }
    
    /**
     * 添加任务到队列（兼容旧接口）
     */
    private function addToTaskQueue($taskData)
    {
        $task = [
            'id' => uniqid('task_'),
            'payment_type' => $this->paymentType,
            'data' => $taskData,
            'created_at' => date('Y-m-d H:i:s'),
            'retry_count' => 0,
            'status' => 'pending'
        ];
        
        return $this->queue->push($task);
    }
    
    /**
     * 生成任务ID（兼容旧接口）
     */
    private function generateTaskId($taskData)
    {
        return uniqid('task_');
    }
    
    /**
     * 记录错误（兼容旧接口）
     */
    private function logError($message)
    {
        $this->logger->error($message);
    }
}
            default:
                return $data['status'] ?? '';
        }
    }
    
    /**
     * 添加到任务队列
     * 
     * @param array $taskData 任务数据
     */
    private function addToTaskQueue($taskData)
    {
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
            
            $queueName = 'easypay_' . $this->paymentType . '_tasks';
            $redis->lPush($queueName, json_encode($taskData));
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
            
            $stmt = $pdo->prepare("INSERT INTO easypay_async_tasks (task_type, order_no, data, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
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
        $queueDir = __DIR__ . '/../../logs/async_queue';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }
        
        $queueFile = $queueDir . '/' . date('Y-m-d') . '_' . $this->paymentType . '_tasks.json';
        $tasks = [];
        
        if (file_exists($queueFile)) {
            $content = file_get_contents($queueFile);
            $tasks = json_decode($content, true) ?: [];
        }
        
        $tasks[] = $taskData;
        file_put_contents($queueFile, json_encode($tasks, JSON_PRETTY_PRINT));
        
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
            
            $queueName = 'easypay_' . $this->paymentType . '_tasks';
            
            for ($i = 0; $i < $limit; $i++) {
                $taskJson = $redis->rPop($queueName);
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
            
            $taskType = $this->paymentType . '_notify';
            $stmt = $pdo->prepare("SELECT * FROM easypay_async_tasks WHERE task_type = ? AND status = 'pending' ORDER BY created_at ASC LIMIT ?");
            $stmt->execute([$taskType, $limit]);
            $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            foreach ($tasks as $task) {
                $taskData = json_decode($task['data'], true);
                $result = $this->executeTask($taskData);
                
                // 更新任务状态
                $updateStmt = $pdo->prepare("UPDATE easypay_async_tasks SET status = ?, updated_at = NOW() WHERE id = ?");
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
        $queueDir = __DIR__ . '/../../logs/async_queue';
        $queueFile = $queueDir . '/' . date('Y-m-d') . '_' . $this->paymentType . '_tasks.json';
        
        if (!file_exists($queueFile)) {
            return $results;
        }
        
        $content = file_get_contents($queueFile);
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
        file_put_contents($queueFile, json_encode($tasks, JSON_PRETTY_PRINT));
        
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
            $result = $this->handlePaymentEvent($orderNo, $status, $task);
            
            return array_merge($result, [
                'task_id' => $task['task_id'] ?? '',
                'payment_type' => $this->paymentType
            ]);
            
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
     * 处理支付事件
     * 
     * @param string $orderNo 订单号
     * @param string $status 状态
     * @param array $task 任务数据
     * @return array 处理结果
     */
    private function handlePaymentEvent($orderNo, $status, $task)
    {
        $this->logInfo("处理{$this->paymentType}支付事件 - 订单: $orderNo, 状态: $status");
        
        // 这里实现具体的业务逻辑
        // 可以调用具体的支付服务方法
        
        return [
            'success' => true,
            'message' => '支付事件处理完成',
            'order_no' => $orderNo,
            'payment_type' => $this->paymentType,
            'status' => $status
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
        return $this->paymentType . '_' . $taskData['order_no'] . '_' . time() . '_' . rand(1000, 9999);
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
        $logDir = __DIR__ . '/../../logs/async_queue';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/' . date('Y-m-d') . '_async.log';
        $time = date('Y-m-d H:i:s');
        $log = "[$time] [$level] [$this->paymentType] $message\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    }
}

/**
 * 文件队列实现
 */
class FileQueue
{
    private $config;
    private $queueDir;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->queueDir = __DIR__ . '/../../../queue';
        
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
    }
    
    public function push($task)
    {
        $file = $this->queueDir . '/' . $task['payment_type'] . '_tasks.json';
        
        $tasks = [];
        if (file_exists($file)) {
            $tasks = json_decode(file_get_contents($file), true) ?: [];
        }
        
        $tasks[] = $task;
        
        return file_put_contents($file, json_encode($tasks)) !== false;
    }
    
    public function pop($paymentType)
    {
        $file = $this->queueDir . '/' . $paymentType . '_tasks.json';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $tasks = json_decode(file_get_contents($file), true) ?: [];
        
        foreach ($tasks as $index => $task) {
            if (in_array($task['status'], ['pending', 'retry'])) {
                unset($tasks[$index]);
                
                // 重新保存剩余任务
                file_put_contents($file, json_encode(array_values($tasks)));
                
                return $task;
            }
        }
        
        return null;
    }
    
    public function ack($taskId)
    {
        // 文件队列中ack操作已在pop中完成
        return true;
    }
    
    public function markFailed($task)
    {
        $failedFile = $this->queueDir . '/failed_tasks.json';
        
        $failed = [];
        if (file_exists($failedFile)) {
            $failed = json_decode(file_get_contents($failedFile), true) ?: [];
        }
        
        $failed[] = $task;
        
        return file_put_contents($failedFile, json_encode($failed)) !== false;
    }
    
    public function getStatus($paymentType)
    {
        $file = $this->queueDir . '/' . $paymentType . '_tasks.json';
        
        if (!file_exists($file)) {
            return ['pending' => 0, 'retry' => 0, 'failed' => 0];
        }
        
        $tasks = json_decode(file_get_contents($file), true) ?: [];
        
        $status = ['pending' => 0, 'retry' => 0, 'failed' => 0];
        
        foreach ($tasks as $task) {
            $status[$task['status']]++;
        }
        
        return $status;
    }
    
    public function clear($paymentType)
    {
        $file = $this->queueDir . '/' . $paymentType . '_tasks.json';
        
        if (file_exists($file)) {
            unlink($file);
        }
        
        return true;
    }
}

/**
 * Redis队列实现
 */
class RedisQueue
{
    private $config;
    private $redis;
    
    public function __construct($config)
    {
        $this->config = $config;
        
        if (!class_exists('Redis')) {
            throw new Exception("Redis扩展未安装");
        }
        
        $this->redis = new \Redis();
        $this->redis->connect(
            $config['redis_host'] ?? '127.0.0.1',
            $config['redis_port'] ?? 6379
        );
        
        if (!empty($config['redis_password'])) {
            $this->redis->auth($config['redis_password']);
        }
        
        if (!empty($config['redis_database'])) {
            $this->redis->select($config['redis_database']);
        }
    }
    
    public function push($task)
    {
        $key = 'easypay_' . $task['payment_type'] . '_tasks';
        return $this->redis->lPush($key, json_encode($task));
    }
    
    public function pop($paymentType)
    {
        $key = 'easypay_' . $paymentType . '_tasks';
        $task = $this->redis->rPop($key);
        
        return $task ? json_decode($task, true) : null;
    }
    
    public function ack($taskId)
    {
        // Redis队列中ack操作已在pop中完成
        return true;
    }
    
    public function markFailed($task)
    {
        $key = 'easypay_failed_tasks';
        return $this->redis->lPush($key, json_encode($task));
    }
    
    public function getStatus($paymentType)
    {
        $key = 'easypay_' . $paymentType . '_tasks';
        $failedKey = 'easypay_failed_tasks';
        
        return [
            'pending' => $this->redis->lLen($key),
            'retry' => 0,
            'failed' => $this->redis->lLen($failedKey)
        ];
    }
    
    public function clear($paymentType)
    {
        $key = 'easypay_' . $paymentType . '_tasks';
        return $this->redis->del($key);
    }
}

/**
 * 数据库队列实现
 */
class DatabaseQueue
{
    private $config;
    private $pdo;
    
    public function __construct($config)
    {
        $this->config = $config;
        
        try {
            $this->pdo = new \PDO(
                "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']}",
                $config['db_user'],
                $config['db_pass'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $this->createTable();
            
        } catch (Exception $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    private function createTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS easypay_async_tasks (
            id VARCHAR(50) PRIMARY KEY,
            payment_type VARCHAR(20) NOT NULL,
            data JSON NOT NULL,
            status ENUM('pending', 'processing', 'retry', 'failed', 'completed') DEFAULT 'pending',
            retry_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            next_retry DATETIME,
            failed_at DATETIME,
            INDEX idx_payment_type (payment_type),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        )";
        
        $this->pdo->exec($sql);
    }
    
    public function push($task)
    {
        $sql = "INSERT INTO easypay_async_tasks (id, payment_type, data, status, retry_count, next_retry)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $task['id'],
            $task['payment_type'],
            json_encode($task['data']),
            $task['status'],
            $task['retry_count'],
            $task['next_retry'] ?? null
        ]);
    }
    
    public function pop($paymentType)
    {
        $this->pdo->beginTransaction();
        
        try {
            $sql = "SELECT * FROM easypay_async_tasks 
                    WHERE payment_type = ? AND status IN ('pending', 'retry') 
                    ORDER BY created_at ASC LIMIT 1 FOR UPDATE";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$paymentType]);
            $task = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($task) {
                // 标记为处理中
                $update = "UPDATE easypay_async_tasks SET status = 'processing' WHERE id = ?";
                $stmt = $this->pdo->prepare($update);
                $stmt->execute([$task['id']]);
            }
            
            $this->pdo->commit();
            
            if ($task) {
                $task['data'] = json_decode($task['data'], true);
            }
            
            return $task;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function ack($taskId)
    {
        $sql = "UPDATE easypay_async_tasks SET status = 'completed' WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$taskId]);
    }
    
    public function markFailed($task)
    {
        $sql = "UPDATE easypay_async_tasks SET status = 'failed', failed_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$task['id']]);
    }
    
    public function getStatus($paymentType)
    {
        $sql = "SELECT status, COUNT(*) as count FROM easypay_async_tasks 
                WHERE payment_type = ? GROUP BY status";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$paymentType]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $status = ['pending' => 0, 'retry' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $status[$row['status']] = (int)$row['count'];
        }
        
        return $status;
    }
    
    public function clear($paymentType)
    {
        $sql = "DELETE FROM easypay_async_tasks WHERE payment_type = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$paymentType]);
    }
}

/**
 * 异步日志类
 */
class AsyncLogger
{
    private $logFile;
    
    public function __construct($logFile = null)
    {
        $this->logFile = $logFile ?? __DIR__ . '/../../../logs/async_queue/async.log';
        
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }
    
    public function warning($message, $context = [])
    {
        $this->log('WARNING', $message, $context);
    }
    
    private function log($level, $message, $context = [])
    {
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
        
        $logLine = json_encode($log) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}