<?php
namespace tools;

class downloader {
    static public function batch(\type\url_provider $hander,$params,$threads=3) {
        try {

            $curlopts=$hander->get_curlopts($params);
            $file=$hander->get_filename($params['name']);
            $dir=$hander->get_temp_dir($params['name']);
            
            debug("start download: {$file}");
            
            $i=0;
            for(;$i<10;$i++){
				$urls=self::breakpoint_dl($dir,$curlopts);
				//self::batch_dl($params['name'],$curlopts,$threads);
				if(empty($urls)){
					break;
				}
            }
            
            if(!empty($urls)){
				throw new \Exception('Max times try break point download. Unable to download all data form urls.');
			}

            if(!file_exists($file) || (0!=$i)){
				$hander->concat($dir,$file);
			}
			
            debug("download over: {$file}");
            
        } catch(\Exception $e) {
            echo 'Error when download '.$params['name'].': ';
            echo $e->getMessage()."\n";
        }
    }

    
    static protected function breakpoint_dl($dir,$urlparams,$threads=3){
        $urls=array();

        foreach($urlparams as $filename=>$curlopts){
			$file=$dir.'/'.$filename;
			if(file_exists($file) && (0!=($file_size=filesize($file)))){
				$content_length=self::get_content_length($curlopts);
				debug("file: {$file}, length: {$content_length}, downloaded: {$file_size}");
				if($file_size==$content_length){
					continue;
				}elseif($file_size>$content_length){
					//~ throw new \Exception("Error: filesize is greater than content_length.file::{$file}");
					debug("Error: filesize is greater than content_length.file: {$file}");
					debug("Delete file: {$file}");
					unlink($file);
				}else{
					$curlopts[CURLOPT_RANGE]=$file_size.'-';
				}
			}
			$urls[$filename]=$curlopts;
		}

		self::batch_dl($dir,$urls,$threads=3);
		return $urls;
	}

    static protected function batch_dl($dir,$urlparams,$threads=3) {
        for($i=0; $urls=array_slice($urlparams,$threads*$i,$threads); $i++) {
            $mh=curl_multi_init();
            $fp=array();
            $ch=array();

            foreach($urls as $filename => $curlopts) {
				$file=$dir.'/'.$filename;
				$dir=pathinfo($file,PATHINFO_DIRNAME);
				if(!file_exists($dir)){
					mkdir($dir,0777,true);
				}	
								
                debug("start download: {$file}...");
                if(file_exists($file) && isset($curlopts[CURLOPT_RANGE])){
					$fp[$filename]=fopen($file,'a+');
                }else{
					$fp[$filename]=fopen($file,'w+');
				}					
                $ch[$filename]=curl_init();
                $curlopts[CURLOPT_CONNECTTIMEOUT]=10;
                $curlopts[CURLOPT_FILE]=$fp[$filename];
                curl_setopt_array($ch[$filename],$curlopts);
                curl_multi_add_handle($mh,$ch[$filename]);
            }

            $active = null;
            do {
                $mrc = curl_multi_exec($mh, $active);
                //~ echo "in curl_multi_exec 1 \n";
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) == -1) {
                    //~ echo "in curl_multi_select 1 \n";
                    usleep(10);
                }

                do {
                    $mrc = curl_multi_exec($mh, $active);
                    //echo "in curl_multi_exec 2 \n";
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }


            foreach($urls as $filename => $curlopts) {
                debug("download over: {$dir}/{$filename}...");
                curl_multi_remove_handle($mh,$ch[$filename]);
                curl_close($ch[$filename]);
                fclose($fp[$filename]);
            }
            curl_multi_close($mh);
        }
    }
    
    
    static protected function get_content_length($curlopts){
		$gid='content_length_'.md5(serialize($curlopts));
		
		$data='';
		if($data=\tools\cache::memcached()->get($gid)){
			return $data;
		}

                $ch=curl_init();
                if(!isset($curlopts[CURLOPT_HTTPHEADER])){
					$curlopts[CURLOPT_HTTPHEADER]=array();
                }
                $curlopts[CURLOPT_HTTPHEADER]=array_merge($curlopts[CURLOPT_HTTPHEADER],array('Connection: close'));
                $curlopts[CURLOPT_CUSTOMREQUEST]='HEAD';
                $curlopts[CURLOPT_NOBODY]=true;
                $curlopts[CURLOPT_HEADER]=true;
                foreach($curlopts as $curlopt_k => $curlopt_v) {
                    curl_setopt($ch,$curlopt_k, $curlopt_v);
                }
               curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,10);
               //~ curl_setopt($ch,CURLOPT_TIMEOUT,3);
               $data=curl_exec($ch);
               
               curl_close($ch);
               	$matches=array();
               	preg_match('#Content-Length:[^0-9\r\n](\d+)#i',$data,$matches);
               if(empty($matches[1])){
				   throw new \Exception('Unable to get filesize!');
			   }
			   
			   	$data=''.$matches[1];
				\tools\cache::memcached()->set($gid,$data);
			   return $data;
	}
}
