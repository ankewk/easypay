<?php

/**
 * 配置加载器
 * 根据当前环境加载对应的配置文件
 */
class ConfigLoader {
    /**
     * 当前环境
     * @var string
     */
    private $env;

    /**
     * 配置数据
     * @var array
     */
    private $config;

    /**
     * ConfigLoader constructor.
     * @param string $env 环境名称: dev, uat, prod
     */
    public function __construct($env = 'dev') {
        $this->env = $env;
        $this->loadConfig();
    }

    /**
     * 加载配置文件
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/' . $this->env . '.php';

        if (!file_exists($configFile)) {
            throw new Exception("配置文件不存在: \$configFile");
        }

        $this->config = require $configFile;
    }

    /**
     * 获取配置项
     * @param string $key 配置键，支持点表示法，如 'alipay.app_id'
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($key, $default = null) {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 获取所有配置
     * @return array
     */
    public function getAll() {
        return $this->config;
    }

    /**
     * 设置环境
     * @param string $env
     */
    public function setEnv($env) {
        if ($this->env !== $env) {
            $this->env = $env;
            $this->loadConfig();
        }
    }

    /**
     * 获取当前环境
     * @return string
     */
    public function getEnv() {
        return $this->env;
    }
}

// 创建一个全局可用的配置实例
function config($key = null, $default = null) {
    static $configLoader = null;

    if ($configLoader === null) {
        // 从环境变量获取环境，默认为dev
        $env = getenv('APP_ENV') ?: 'dev';
        $configLoader = new ConfigLoader($env);
    }

    if ($key === null) {
        return $configLoader;
    }

    return $configLoader->get($key, $default);
}