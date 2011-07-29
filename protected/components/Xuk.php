<?php
/**
 * Xuk
 *
 * @author: LJ (orzero@admin.com)
 * @data: 11-7-29
 * @time: 下午3:12
 * @version: 0.1
 */
 
class Xuk
{
    const XUK_BASE_URL='http://xuk.ru';
    const EXPIRE_G=600;
    const EXPIRE_I=300;
    const EXPIRE_F=100;
    
    //取得相册列表
    public function getGallery(){
        $id = intval(Yii::app()->request->getParam('id', '1'));
        while ($id < 1):
            $id=1;
        endwhile;

        $src=XUK_BASE_URL.'/'.$id.'.html';
        $st=Curl::get($src, EXPIRE_G);
        if($st===false){
            return false;
        }

        if(!isset(Curl::$c['Info']['http_code'])||(Curl::$c['Info']['http_code']!=200)){
            return false;
        }

        $html=Curl::$c['Result'];
        //get album name
        preg_match_all("'vid-2\.html\"\s+class=\"contentheading0\">([^<]*?)<\/a>'isx", $html, $names);
        //get gallery links
        preg_match_all("'<a\s+href=\"(http:\/\/xuk\.ru\/([^\/]*?)\/([^\/]*?)\/vid-1\.html)\">'isx", $html, $links);

        preg_match("'<meta\s+http-equiv=\"Content-Type\"\s+content=\"text/html;\s?charset=(.*?)\"\s?[\/]?>'isx", $html, $http_code);
        $http_code=isset($http_code[1]) ? $http_code[1] : 'utf-8';

        pr($links);
        pr($names);
    }

    //取得图片列表
    public function getImage(){
        
    }

    //下载图片
    public function getFile(){
        
    }

    //发表帖子,每次下载图片成功入库后直接调用，满足条件则发表
    public function putPost(){
        
    }
}