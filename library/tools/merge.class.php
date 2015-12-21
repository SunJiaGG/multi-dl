<?php
namespace tools;
//~ include_once '../config.php';//for debug

class merge{
	
	static public function mp4($dir,$file){
		$sh='"'.CLASS_DIR.'/sh/merge_mp4.bash" "'.$dir.'" "'.$file.'"';
		shell_exec($sh);
		exit;
	}

}
