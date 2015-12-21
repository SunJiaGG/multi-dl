<?php
namespace tools;
//~ include_once '../config.php';//for debug

class cache implements \type\cache {
	
	const FILE_CACHE_DIR='cache';
	const SERIALIZE_HEADER='|__SERIALIZED__|';//'|__THIS_FILE_IS_SERIALIZED__|';


    static protected $types=array(
		'file','memcached'
    );
    protected $type;

	static protected $memcached_hander;

    protected function __construct($type) {
        $this->type=strtolower($type);
        
        switch($type){
			case 'memcached':
				if(empty(self::$memcached_hander)){
						self::$memcached_hander= new \Memcached();
						self::$memcached_hander->addServer(MEMCACHED_HOST,MEMCACHED_PORT);
				}
				break;
		}
        
    }
    static protected function factory($type) {
        static $handers=array();
			if(!in_array($type,self::$types)){
				throw new \Exception("Error: load {$type} parser.No such cache db exist.");
			} 

        if(empty($handers[$type])) {
        $handers[$type]=new cache($type);
        }
        return $handers[$type];
    }


	

	function set($k,$v,$e=0){
		if(!is_string($v)){
			$v=self::SERIALIZE_HEADER.serialize($v);
		}
		$method='__set_'.$this->type;
        return $this->$method($k,$v,$e);
	}
	
	public function get($k){
		$method='__get_'.$this->type;
		$v=$this->$method($k);
		if(self::SERIALIZE_HEADER==substr($v,0,strlen(self::SERIALIZE_HEADER))){
			$v=unserialize(substr($v,strlen(self::SERIALIZE_HEADER)));
		}
		return $v;
	}
	
	
    static function file() {
        return self::factory('file');
    }

	public function __set_file($k,$v,$e=0){
		$filename=self::__file_filename($k);
		return file_put_contents($filename,gzencode($v));
	}
	
	public function __get_file($k){
		$filename=self::__file_filename($k);
		if(!file_exists($filename)){
			return '';
		}
		$v=file_get_contents($filename);
		return self::__gzdecode($v);
	}
	static protected function __file_filename($k){
		if(!file_exists(self::FILE_CACHE_DIR)){
			mkdir(self::FILE_CACHE_DIR,0777,true);
		}
		return self::FILE_CACHE_DIR.'/'.$k.'.gz';
	}
	
	
	static function memcached() {
		if(MEMCACHED){
			return self::factory('memcached');
		}else{
			return self::file();
		}
    }

	public function __set_memcached($k,$v,$e=0){
		return self::$memcached_hander->set($k,gzencode($v),$e);
	}
	
	public function __get_memcached($k){
		$v=self::$memcached_hander->get($k);
		
		return self::__gzdecode($v);
	}
	
	static protected function __gzdecode($data){
		if(strlen($data)>=2){
			$m=unpack('C2',$data);
			if((0x1f==$m[1]) && (0x8b==$m[2])){
				return gzdecode($data);
			}
		}
		return $data;
	}


}
