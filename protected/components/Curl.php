<?php
/**
 * Curl 采集类
 *
 * @author: LJ (orzero@admin.com)
 * @data: 11-7-29
 * @time: 下午2:46
 * @version: 0.1
 */
 
class Curl
{
    public static $c;
    
	public static function get($src, $expire=60)
	{
		$expire = intval($expire)>30 ? intval($expire) : 30;
		$src = trim($src);
		if(empty($src)) return false;
        if(!self::is_url($src)) return false;

		self::$c = null;
		$key = md5($src);
		$cache = Yii::app()->cache;
		self::$c=$cache->get($key);

		if(empty(self::$c)){
			//Run curl
            if(!isset(Yii::app()->CURL)||empty(Yii::app()->CURL)){
                return false;
            }
			$curl = Yii::app()->CURL;
			$curl->run(array(CURLOPT_REFERER => $src));
			$curl->setUrl($src);
			$curl->exec();

			if(Yii::app()->CURL->isError()) {
				// Error
                self::$c=array(
					'ErrNo'=>$curl->getErrNo(),
					'Error'=>$curl->getError(),
					'Header'=>$curl->getHeader(),
					'Info'=>$curl->getInfo(),
					'Result'=>$curl->getResult(),
				);
                return false;
			}else{
				// More info about the transfer
				self::$c=array(
					'ErrNo'=>$curl->getErrNo(),
					'Error'=>$curl->getError(),
					'Header'=>$curl->getHeader(),
					'Info'=>$curl->getInfo(),
					'Result'=>$curl->getResult(),
				);
                $cache->set($key, self::$c, $expire);
                return true;
			}

		}

		return true;
	}

	public static function is_url($url){
		$validate=new CUrlValidator();
		if(empty($url)){
			return false;
		}
		if($validate->validateValue($url)===false){
			return false;
		}
	    return true;
	}

}