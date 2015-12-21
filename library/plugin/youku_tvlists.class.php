<?php
namespace plugin;
//~ include_once '../config.php';//for debug

class youku_tvlists extends \type\config_provider {


	public $PLUGIN_NAME='youku';

	public function get_config($params){
		$url='http://v.youku.com/v_show/id_'.$params['vid'].'.html';
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,false);
        $data=curl_exec($ch);
        curl_close($ch);

		$matches=array();
		$pattern='#<meta name="title" content="([^"]*)">#';
		preg_match($pattern,$data,$matches);
		if(!isset($matches[1]) or empty($matches[1])){
			throw new \Exception('Unable to get the tv lists title.');
		}
		$title=$matches[1];

		$matches=array();
		$pattern='#<div class="item" name="tvlist" flag="\\d*" seq="\\d*" id="item_([^"]+)" title="([^"]+)">#';
		preg_match_all($pattern,$data,$matches,PREG_SET_ORDER);
		if(empty($matches)){
			throw new \Exception('Unable to get the tv lists.');
		}
		
		$config=array();
		foreach($matches as $k=>$v){
			$config[$k]['vid']=$v[1];
			$config[$k]['name']=$title.'/'.$v[2];
			$config[$k]['plugin']='youku';
		}
		
		return $config;
	}
}

?>
