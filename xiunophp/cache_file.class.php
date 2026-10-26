<?php

/**
 * File-based cache driver for XiunoPHP
 * 
 * 文件缓存驱动，利用本地文件系统存储缓存数据。
 * 优点：无需额外扩展，适用于没有 Redis/Memcached 的环境。
 * 支持预置缓存目录（可配置为 /dev/shm 利用内存加速）。
 * 
 * @package XiunoPHP
 * @version 2.0
 */

class cache_file {
	
	public $conf = array();
	public $cachepre = '';
	public $errno = 0;
	public $errstr = '';
	public $cache_dir = '';
	
	public function __construct($conf = array()) {
		$this->conf = $conf;
		$this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre_';
		$this->cache_dir = isset($conf['path']) ? $conf['path'] : APP_PATH . 'tmp/cache/';
		$this->init_dir();
	}
	
	/**
	 * Initialize cache directory
	 */
	public function init_dir() {
		if(!is_dir($this->cache_dir)) {
			$r = @mkdir($this->cache_dir, 0777, TRUE);
			if(!$r) {
				$this->error(-1, '无法创建缓存目录: ' . $this->cache_dir);
				return FALSE;
			}
		}
		return is_writable($this->cache_dir);
	}
	
	/**
	 * Get file path for cache key
	 */
	public function file_path($k) {
		// 使用子目录避免单目录文件过多
		$hash = md5($k);
		$subdir = substr($hash, 0, 2);
		$path = $this->cache_dir . $subdir . '/';
		if(!is_dir($path)) {
			@mkdir($path, 0777, TRUE);
		}
		return $path . $hash . '.php';
	}
	
	public function connect() {
		return $this->init_dir();
	}
	
	public function set($k, $v, $life = 0) {
		$file = $this->file_path($k);
		$expiry = $life ? time() + $life : 0;
		$data = array(
			'k' => $k,
			'v' => $v,
			'expiry' => $expiry,
		);
		$s = "<?php exit;?>\t" . xn_json_encode($data);
		// 防止并发读取到写了一半的缓存文件：
		// 类 Unix 用“临时文件 + 原子 rename”，读取端无需加锁；
		// Windows 的 rename 不能覆盖已被打开的文件，改用共享文件锁串行化读写。
		if(DIRECTORY_SEPARATOR === '\\') {
			return $this->write_locked($file, $s);
		}
		$tmp = $file . '.' . getmypid() . '.' . mt_rand(100000, 999999) . '.tmp';
		$r = @file_put_contents($tmp, $s, LOCK_EX);
		if($r === FALSE || $r !== strlen($s)) {
			@unlink($tmp);
			$this->error(-1, '写入缓存文件失败: ' . $file);
			return FALSE;
		}
		if(!@rename($tmp, $file)) {
			@unlink($tmp);
			$this->error(-1, '重命名缓存文件失败: ' . $file);
			return FALSE;
		}
		@clearstatcache(TRUE, $file);
		return TRUE;
	}
	
	// Windows 专用：加排他锁后原地覆盖写
	private function write_locked($file, $s) {
		$fp = @fopen($file, 'cb');
		if(!$fp) {
			$this->error(-1, '打开缓存文件失败: ' . $file);
			return FALSE;
		}
		if(!@flock($fp, LOCK_EX)) {
			@fclose($fp);
			$this->error(-1, '锁定缓存文件失败: ' . $file);
			return FALSE;
		}
		@ftruncate($fp, 0);
		@rewind($fp);
		$len = strlen($s);
		$written = 0;
		while($written < $len) {
			$n = @fwrite($fp, substr($s, $written));
			if($n === FALSE || $n === 0) break;
			$written += $n;
		}
		@fflush($fp);
		@flock($fp, LOCK_UN);
		@fclose($fp);
		if($written !== $len) {
			$this->error(-1, '写入缓存文件失败: ' . $file);
			return FALSE;
		}
		@clearstatcache(TRUE, $file);
		return TRUE;
	}
	
	// Windows 专用：加共享锁读取，等待写操作完成
	private function read_locked($file) {
		$fp = @fopen($file, 'rb');
		if(!$fp) return FALSE;
		@flock($fp, LOCK_SH);
		$s = @stream_get_contents($fp);
		@flock($fp, LOCK_UN);
		@fclose($fp);
		return $s;
	}
	
	public function get($k) {
		$file = $this->file_path($k);
		if(!is_file($file)) {
			return NULL;
		}
		$s = DIRECTORY_SEPARATOR === '\\' ? $this->read_locked($file) : @file_get_contents($file);
		if($s === FALSE) {
			return NULL;
		}
		// 空文件说明写入中断（或磁盘异常），清理后让上层重建
		if($s === '') {
			@unlink($file);
			return NULL;
		}
		// 跳过 PHP exit 头
		$pos = strpos($s, "\t");
		if($pos === FALSE) {
			@unlink($file);
			return NULL;
		}
		$json = substr($s, $pos + 1);
		$data = xn_json_decode($json);
		if(empty($data)) {
			@unlink($file);
			return NULL;
		}
		// 检查过期
		if($data['expiry'] && time() > $data['expiry']) {
			@unlink($file);
			return NULL;
		}
		return $data['v'];
	}
	
	public function delete($k) {
		$file = $this->file_path($k);
		if(is_file($file)) {
			return @unlink($file);
		}
		return TRUE; // 不存在的 key 视为删除成功
	}
	
	public function truncate() {
		$r = $this->rmdir_recursive($this->cache_dir);
		$this->init_dir();
		return $r;
	}
	
	/**
	 * 递归删除目录内容（保留目录本身）
	 */
	public function rmdir_recursive($dir) {
		if(!is_dir($dir)) return FALSE;
		$files = scandir($dir);
		foreach($files as $file) {
			if($file == '.' || $file == '..') continue;
			$path = $dir . '/' . $file;
			if(is_dir($path)) {
				$this->rmdir_recursive($path);
				@rmdir($path);
			} else {
				@unlink($path);
			}
		}
		return TRUE;
	}
	
	// 统计缓存数量和大小
	public function stats() {
		$count = 0;
		$size = 0;
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->cache_dir, RecursiveDirectoryIterator::SKIP_DOTS)
		);
		foreach($iterator as $file) {
			if($file->isFile() && $file->getExtension() == 'php') {
				$count++;
				$size += $file->getSize();
			}
		}
		return array('count' => $count, 'size' => $size, 'dir' => $this->cache_dir);
	}
	
	public function error($errno = 0, $errstr = '') {
		$this->errno = $errno;
		$this->errstr = $errstr;
		DEBUG AND trigger_error('Cache Error: ' . $this->errstr);
	}
	
	public function __destruct() {
		// noop
	}
}

?>