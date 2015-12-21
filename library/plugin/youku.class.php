<?php
namespace plugin;
//~ include_once __DIR__.'/../config.php';//for debug

class youku extends \type\url_provider {
	public $PLUGIN_NAME='youku';

    /**
     *
     * @params,array('vid','name')
     *
     */
    public function get_urls($params) {
        $data=(new \plugin\youku_m3u8())->get_m3u8($params);
        //~ file_put_contents("1.m3u8",$data);//for debug
        $pattern='#(http://[^\\r\\n]*\.mp4)[^\\r\\n]*[\\r\\n]+#';
        $matches=array();
        preg_match_all($pattern,$data,$matches);
        if(empty($matches[1])) {
            throw new \Exception('An error occurred on fetch m3u8 file.');
        }
        
        return array_unique($matches[1]);
    }

    /**
     *
     * @params,array('vid','name')
     *
     */
    public function get_curlopts($params) {
        $urls=self::get_urls($params);

        $return_data=array();
        foreach($urls as $k=>$v) {
            $filename=(10000+$k).'.mp4';
            $return_data[$filename][CURLOPT_URL]=$v;
            $return_data[$filename][CURLOPT_HEADER]=false;
            $return_data[$filename][CURLOPT_RETURNTRANSFER]=true;
            $return_data[$filename][CURLOPT_CONNECTTIMEOUT]=10;
        }
        return $return_data;
    }

}
/*
namespace plugin\youku;

//for debug
function main() {
    //~ global $argv;
    $vid=$argv[1];
    $vid='XNzE1MTQ3Mjky';
    
    $ret=(new \plugin\youku())->get_urls(array('vid'=>$vid));
    var_dump($ret);
}
main();
*/
?>
