<?php

/**
 * Redis cache driver for XiunoPHP (Enhanced)
 * 
 * 增强版 Redis 缓存驱动，支持：
 * - 密码认证 (Redis >= 6.0 支持 ACL)
 * - 连接超时设置
 * - 数据库选择 (select)
 * - 序列化前缀
 * 
 * @package XiunoPHP
 */

class cache_redis {
	
	public $conf = array();
	public $link = NULL;
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	
	public function __construct($conf = array()) {
		if(!extension_loaded('Redis')) {
			return $this->error(-1, ' Redis 扩展没有加载');
		}
		$this->conf = $conf;
		$this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre_';
	}
	
	public function connect() {
		if($this->link) return $this->link;
		
		$redis = new Redis;
		
		$host = isset($this->conf['host']) ? $this->conf['host'] : '127.0.0.1';
		$port = isset($this->conf['port']) ? intval($this->conf['port']) : 6379;
		$timeout = isset($this->conf['timeout']) ? floatval($this->conf['timeout']) : 1.0;
		$persistent = isset($this->conf['pconnect']) ? !!$this->conf['pconnect'] : FALSE;
		$password = isset($this->conf['password']) ? $this->conf['password'] : '';
		$database = isset($this->conf['database']) ? intval($this->conf['database']) : 0;
		
		try {
			if($persistent) {
				$r = $redis->pconnect($host, $port, $timeout);
			} else {
				$r = $redis->connect($host, $port, $timeout);
			}
		} catch (Throwable $e) {
			return $this->error(-1, '连接 Redis 服务器失败: ' . $e->getMessage());
		}
		
		if(!$r) {
			return $this->error(-1, '连接 Redis 服务器失败。');
		}
		
		// 密码认证
		if(!empty($password)) {
			try {
				if(!$redis->auth($password)) {
					return $this->error(-1, 'Redis 认证失败，密码错误');
				}
			} catch (Throwable $e) {
				return $this->error(-1, 'Redis 认证失败: ' . $e->getMessage());
			}
		}
		
		// 选择数据库
		if($database > 0) {
			try {
				$redis->select($database);
			} catch (Throwable $e) {
				// ignore
			}
		}
		
		$this->link = $redis;
		return $this->link;
	}
	
	public function set($k, $v, $life = 0) {
		if(!$this->link && !$this->connect()) return FALSE;
		$v = xn_json_encode($v);
		$r = $this->link->set($k, $v);
		if($life > 0 && $r) {
			$this->link->expire($k, $life);
		}
		return $r;
	}
	
	public function get($k) {
		if(!$this->link && !$this->connect()) return FALSE;
		$r = $this->link->get($k);
		return $r === FALSE ? NULL : xn_json_decode($r);
	}
	
	public function delete($k) {
		if(!$this->link && !$this->connect()) return FALSE;
		return $this->link->del($k) ? TRUE : FALSE;
	}
	
	public function truncate() {
		if(!$this->link && !$this->connect()) return FALSE;
		return $this->link->flushdb(); // flushall
	}
	
	public function error($errno = 0, $errstr = '') {
		$this->errno = $errno;
		$this->errstr = $errstr;
		DEBUG AND trigger_error('Cache Error:'.$this->errstr);
	}
	
	public function __destruct() {
		// noop
	}
}

?>