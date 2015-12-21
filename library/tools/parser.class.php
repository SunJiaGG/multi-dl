<?php
namespace tools;
//~ include_once '../config.php';//for debug

/**
 * parse config file
 * 
 */
class parser implements \type\parser {
    const CONFIG_DIR='config';
    static protected $types=array(
		'downloader'
    );
    protected $type;
    protected function __construct($type) {
		if(!file_exists(self::CONFIG_DIR)){
			mkdir(self::CONFIG_DIR,0777,true);
		}
        $this->type=strtolower($type);
    }
    static protected function factory($type) {
        static $handers=array();

			if(!in_array($type,self::$types)){
				throw new \Exception("Error: load {$type} parser.No such parser exist.");
			} 

        if(empty($handers[$type])) {
        $handers[$type]=new parser($type);
        }
        return $handers[$type];
    }


    protected function __get_path($filename) {
        if('file://'==substr($filename,0,7)) {
            $path=substr($filename,7);
        } else {
            $path=self::CONFIG_DIR.'/'.$filename.'.'.$this->type;
        }
        return $path;
    }

	static protected function __explode_text($data,$callback=NULL){
		$data=str_replace("\r",'',$data);
		$lines=explode("\n",$data);
		
		$ret=array();
		foreach($lines as $n=>$line){
			if(empty($line)){
				continue;
			}
			$ret[$n]=explode(' ',trim($line));
			
			if(NULL!==$callback){
				$ret[$n]=$callback($ret[$n]);
			}
			//replace whitespace
			array_walk($ret[$n],function(&$item,$key){
				$item=str_replace('&nbsp;',' ',$item);
			});
		}
		return $ret;
	}


	static protected function __implode_text($data,$callback=NULL){
		$ret=array();
		
		foreach($data as $n=>$v){
			if(NULL!==$callback){
				$v=$callback($v);
			}
			//replace whitespace
			array_walk($v,function(&$item,$key){
				$item=str_replace(' ','&nbsp;',$item);
			});
			$ret[$n]=implode(' ',$v);
		}
		return implode("\n",$ret);
	}
	
	public function read($filename) {
        $path=$this->__get_path($filename);

        $data='';
        if(!file_exists($path)){
			return ;
        }
        $data=file_get_contents($path);
        
        $method='__read_'.$this->type;
        return $this->$method($data);
    }
    public function write($filename,$data) {
        $path=$this->__get_path($filename);

        $method='__write_'.$this->type;
        $data=$this->$method($data);
        return file_put_contents($path,$data);
    }


    static function downloader() {
        return self::factory('downloader');
    }
    
    
    
	private function __read_downloader($data){
		return self::__explode_text($data,function($data){
				$ret['plugin']=$data[0];
				$ret['name']=$data[1];
				for($i=1;$item=array_slice($data,2*$i,2);$i++){
					if(isset($item[1])){
						$ret[$item[0]]=$item[1];
					}
				}
				return $ret;
		});
	}
	private function __write_downloader($data){
		return self::__implode_text($data,function($data){
			$ret[0]=$data['plugin'];
			$ret[1]=$data['name'];
			unset($data['plugin']);
			unset($data['name']);
			
			$n=1;
			foreach($data as $k=>$v){
				$ret[2*$n]=$k;
				$ret[2*$n+1]=$v;
				$n++;
			}
			return $ret;
		});
	}
}
