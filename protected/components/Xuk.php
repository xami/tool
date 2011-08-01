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

        $src=self::XUK_BASE_URL.'/'.$id.'.html';
        $st=Curl::get($src, self::EXPIRE_G);
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

        if(!isset($links[1]) || empty($links[1])){
            Yii::log('empty $links[1]',$level='warning',$category='getGallery');
            return false;
        }
        if(!isset($links[2]) || empty($links[2])){
            Yii::log('empty $links[2]',$level='warning',$category='getGallery');
            return false;
        }
        if(!isset($links[3]) || empty($links[3])){
            Yii::log('empty $links[3]',$level='warning',$category='getGallery');
            return false;
        }
        if(!isset($names[1]) || empty($names[1])){
            Yii::log('empty $names[1]',$level='warning',$category='getGallery');
            return false;
        }
        if(count($links[1])!=count($links[2]) || count($links[2])!=count($links[3]) || count($links[3])!=count($names[1])){
            Yii::log('count $links[1] $links[2] $links[3] $names[1] ',$level='warning',$category='getGallery');
            return false;
        }

        $i=0;
        $_gallery=new WpNggGallery;
        foreach($names[1] as $name){

            $gallery=clone $_gallery;
            $gallery->path='wp-content/gallery/xuk/'.trim($links[2][$i]).'/'.trim($links[3][$i]);
            $gallery->slug=trim($links[2][$i]).'/'.trim($links[3][$i]);
            $data=$gallery->search()->getData();
//                pd($data);

            if(empty($data)){
                $name_utf8=iconv($http_code, 'utf-8', $name);
                $gallery->name=$name_utf8;
                $gallery->title=trim($links[3][$i]);
                $gallery->galdesc=trim($links[2][$i]);
                $gallery->pageid=0;
                $gallery->previewpic=1;
                $gallery->author=0;         //if updated gallery ,then set to 1
                if(!$gallery->save()){
                    Yii::log('$gallery->save()::'.serialize($gallery->getData()),$level='warning',$category='list');
                }
            }

            $i++;
//                echo "\r\n";
        }
        
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