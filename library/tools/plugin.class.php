<?php
namespace tools;
//~ include_once '../config.php';//for debug

class plugin {

    static public function dl($condig_name) {
        $data=\tools\parser::downloader()->read($condig_name);
        if(empty($data)){
			throw new \Exception("file config/{$condig_name}.downloader didn\'t exists");
		}
        self::downloader($data);
    }

    static public function downloader($data) {
        foreach($data as $k=>$v) {
            try {
                $plugin='\\plugin\\'.$v['plugin'];
                
                //check if the class is exists.
                if(!class_exists($plugin)) {
                    throw new \Exception("class {$plugin} didn\'t exists");
                }
                
                //switch plugin type
                if(is_a($plugin,'\\type\\'.'url_provider',true)) {
                    \tools\downloader::batch(new $plugin(),$v,10);
                }elseif(is_a($plugin,'\\type\\config_provider',true)){
                    $config_name=$v['name'].'.tvlists';
                    if(!($config_data=\tools\parser::downloader()->read($config_name))){
                    $config_data=(new $plugin)->get_config($v);
                        \tools\parser::downloader()->write($config_name,$config_data);
                    }
                    self::downloader($config_data);
                }
            } catch(\Exception $e) {
                echo 'Error when parser config data: ';
                echo $e->getMessage()."\n";
                echo 'config data:'."\n";
                var_dump($v);
            }

        }
    }
}
