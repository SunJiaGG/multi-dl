<?php
namespace type;
abstract class url_provider {
	
	const TEMP_DIR='temp';
	const DOWNLOAD_DIR='downloads';
	public $PLUGIN_NAME='default';
	
    abstract public function get_urls($params);

    abstract public function get_curlopts($params);

    public function concat($temp_dir,$filename) {
		\tools\merge::mp4($temp_dir,$filename);
    }
    
    public function get_temp_dir($name){
		$temp_dir= self::DOWNLOAD_DIR.'/'.self::TEMP_DIR.'/'.$this->PLUGIN_NAME.'/'.$name;
		if(!file_exists($temp_dir)){
			mkdir($temp_dir,0777,true);
		}
		return $temp_dir;
	}
	public function get_filename($name){
		$file=self::DOWNLOAD_DIR.'/'.$this->PLUGIN_NAME.'/'.$name.'.mp4';
        $subdir=pathinfo($file,PATHINFO_DIRNAME);
        if(!file_exists($subdir)){
			mkdir($subdir,0777,true);
		}
		return $file;
	}
};
