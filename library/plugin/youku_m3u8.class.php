<?php
namespace plugin;
//~ include_once __DIR__.'/../config.php';//for debug

class youku_m3u8 extends \type\url_provider {
    static private function yk_e($a, $c) {
        for ($f=0, $i, $e='', $h=0; 256 > $h; $h++) {
            $b[$h]=$h;
        }
        for ($h=0; 256 > $h; $h++) {
            $f=(($f + $b[$h]) +self::charCodeAt($a, $h % strlen($a))) % 256;
            $i=$b[$h];
            $b[$h]=$b[$f];
            $b[$f]=$i;
        }
        for ($q=($f=($h=0)); $q < strlen($c); $q++) {
            $h=($h + 1) % 256;
            $f=($f + $b[$h]) % 256;
            $i=$b[$h];
            $b[$h]=$b[$f];
            $b[$f]=$i;
            $e .= self::fromCharCode(self::charCodeAt($c, $q) ^ $b[($b[$h] + $b[$f]) % 256]);
        }
        return $e;
    }

    static private function fromCharCode($codes) {
        if (is_scalar($codes)) {
            $codes=func_get_args();
        }
        $str='';
        foreach ($codes as $code) {
            $str .= chr($code);
        }
        return $str;
    }

    static private function charCodeAt($str, $index) {
        $charCode=array();
        $key=md5($str);
        $index=$index + 1;
        if (isset($charCode[$key])) {
            return $charCode[$key][$index];
        }
        $charCode[$key]=unpack('C*', $str);
        return $charCode[$key][$index];
    }

    static private function charAt($str, $index=0) {
        return substr($str, $index, 1);
    }

    public $PLUGIN_NAME='youku_m3u8';


    /**
     *
     * @params,array('vid','name')
     *
     */
    public function get_m3u8($params) {
        if(empty($params['vid'])) {
            throw new \Exception('Please input vid.');
        }
        $gid=$this->PLUGIN_NAME.'_'.$params['vid'].'_m3u8';

        $data='';
        if($data=\tools\cache::memcached()->get($gid)) {
            return $data;
        }

        $API='http://play.youku.com/play/get.json?vid='.$params['vid'].'&ct=12';
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$API);
        curl_setopt($ch,CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch,CURLOPT_HEADER,1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,array('Referer: http://v.youku.com/v_show/'.$params['vid'].'.html?x',));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        $data=curl_exec($ch);
        $headerSize=curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $header=explode("\r\n",substr($data, 0, $headerSize));
        $retval=substr($data, $headerSize);

        foreach($header as $headeritem) {
            $headerdetail=explode(": ",$headeritem);
            if(!empty($headerdetail[1]))
                $headerarray[$headerdetail[0]]=$headerdetail[1];
            else
                $headerarray[$headerdetail[0]]='';
        }

        $cookies=explode('; ',$headerarray['Set-Cookie']);
        $ret['cookie']=$cookies[0];
        $r_key=substr($cookies[0],3,-1);

        if(!empty($retval)) {
            $rs=json_decode($retval, true);
            $ep=$rs['data']['security']['encrypt_string'];


            if (!empty($ep)) {
                $ip=$rs['data']['security']['ip'];
                $videoid=$rs['data']['id'];
                list($sid, $token)=explode('_',self::yk_e('becaf9be', base64_decode($ep)));
                $ep=urlencode(base64_encode(self::yk_e('bf7e5f01',$sid.'_'.$videoid.'_'.$token)));
                $ret['url']=$final_url='http://pl.youku.com/playlist/m3u8?ctype=12&ep='.$ep.'&ev=1&keyframe=1&oip='.$ip.'&sid='.$sid.'&token='.$token.'&vid='.$videoid.'&type=mp4';
            } else {
                throw new \Exception('Invalid vid.');
            }
        } else {
            throw new \Exception('An error occurred on fetch data.');
        }

        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$ret['url']);
        curl_setopt($ch,CURLOPT_COOKIE,$ret['cookie']);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,false);
        $data=curl_exec($ch);
        curl_close($ch);


        \tools\cache::memcached()->set($gid,$data,3600*4);
        return $data;
    }

    /**
     *
     * @params,array('vid','name')
     *
     */
    public function get_urls($params) {
        $data=$this->get_m3u8($params);

        $pattern='#(http://[^\\r\\n]*)[\\r\\n]+#';
        $matches=array();
        preg_match_all($pattern,$data,$matches);

        if(empty($matches[1])) {
            throw new \Exception('An error occurred on fetch m3u8 file.');
        }
        return $matches[1];
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
namespace plugin\youku_m3u8;

//for debug
function main() {
    //~ global $argv;
    $vid=$argv[1];
    $vid='XNzE1MTQ3Mjky';

    $ret=(new \plugin\youku_m3u8())->a(array('vid'=>$vid));
    var_dump($ret);
}
main();//for debug

*/

?>
