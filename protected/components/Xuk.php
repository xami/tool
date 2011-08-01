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
    const WP_ROOT_PATH='/data/htdocs/haoselang';
    const WP_DOMAIN='http://www.haoselang.com';
    const EXPIRE_G=600;
    const EXPIRE_I=300;
    const EXPIRE_F=100;
    
    //取得相册列表
    //gallery::author=0
    public function getGallery(){
        $id = intval(Yii::app()->request->getParam('id', '1'));
        while ($id < 1):
            $id=1;
        endwhile;

        $src=self::XUK_BASE_URL.'/'.$id.'.html';
        $st=Curl::get($src, self::EXPIRE_G);
        if($st===false){
            Yii::log('$st===false',$level='warning',$category='getGallery');
            return false;
        }

        if(!isset(Curl::$c['Info']['http_code'])||(Curl::$c['Info']['http_code']!=200)){
            Yii::log('(Curl::$c[\'Info\'][\'http_code\']!=200)',$level='warning',$category='getGallery');
            return false;
        }
        if(!isset(Curl::$c['Result']) || empty(Curl::$c['Result'])){
            Yii::log('empty(Curl::$c[\'Result\'])',$level='warning',$category='getGallery');
            return false;
        }else{
            $html=Curl::$c['Result'];
        }

        //get album name
        preg_match_all("'vid-2\.html\"\s+class=\"contentheading0\">([^<]*?)<\/a>'isx", $html, $names);
        //get gallery links
        preg_match_all("'<a\s+href=\"(http:\/\/xuk\.ru\/([^\/]*?)\/([^\/]*?)\/vid-1\.html)\">'isx", $html, $links);
        //get html code
        preg_match("'<meta\s+http-equiv=\"Content-Type\"\s+content=\"text/html;\s?charset=(.*?)\"\s?[\/]?>'isx", $html, $http_code);
        $http_code=isset($http_code[1]) ? $http_code[1] : 'utf-8';

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
        $r=true;
        $_gallery=new WpNggGallery;
        foreach($names[1] as $name){
            $name_utf8=iconv($http_code, 'utf-8', $name);
            
            $gallery=clone $_gallery;
            $gallery->path='wp-content/gallery/xuk/'.trim($links[2][$i]).'/'.trim($links[3][$i]);
            $gallery->slug=trim($links[2][$i]).'/'.trim($links[3][$i]);
            $data=$gallery->search()->getData();

            if(empty($data)){
                $gallery->name=$name_utf8;
                $gallery->title=trim($links[3][$i]);
                $gallery->galdesc=trim($links[2][$i]);
                $gallery->pageid=0;
                $gallery->previewpic=1;
                $gallery->author=0;         //if updated gallery ,then set to 1
                if(!$gallery->save()){
                    Yii::log('$gallery->save()::'.serialize($gallery->getData()),$level='warning',$category='getGallery');
                    $r=false;
                }
            }

            $i++;
        }

        return $r;
    }

    //取得图片列表
    //gallery::author=0 --> -1
    //src::status=0
    public function getImage(){
		$gallery=WpNggGallery::model()->find('author=0');
        if(empty($gallery->slug)){
            Yii::log('empty($gallery->slug)',$level='warning',$category='getImage');
            return false;
        }
        $src=self::XUK_BASE_URL.$gallery->slug.'/vid-1.html';

        $st=Curl::get($src, self::EXPIRE_I);
        if($st===false){
            Yii::log('$st===false',$level='warning',$category='getImage');
            return false;
        }
        if(!isset(Curl::$c['Info']['http_code'])||(Curl::$c['Info']['http_code']!=200)){
            Yii::log('(Curl::$c[\'Info\'][\'http_code\']!=200)',$level='warning',$category='getImage');
            return false;
        }
        if(!isset(Curl::$c['Result']) || empty(Curl::$c['Result'])){
            Yii::log('empty(Curl::$c[\'Result\'])',$level='warning',$category='getImage');
            return false;
        }else{
            $html=Curl::$c['Result'];
        }

        preg_match_all("'<a\s+class=\"xuk_gallery\"\s+href=\"(.*?)\">'isx", $html, $images);
        if(!isset($images[1]) || empty($images[1])){
            Yii::log('empty($images)',$level='warning',$category='getImage');
            return false;
        }

        $r=true;
        $pictures=new WpNggPictures;
        foreach($images[1] as $image){
            $key=md5($image);
            $suffix=substr($image,strrpos($image,'.'));
            $suffix_len=strlen($suffix);
            if($suffix_len<4 || $suffix_len>5 || (substr($suffix, 0, 1)!='.')){
                Yii::log('suffix error::'.$image,$level='warning',$category='getImage');
                continue;
            }

            if(strpos($gallery->slug,'/')!==false){
                $alt=explode('/',$gallery->slug);
            }else{
                $alt[0]='haose,selang';
                $alt[1]='haoselang,haoselang.com';
            }

            $_pictures=clone $pictures;
            $_pictures->image_slug=$key;
            $_pictures->galleryid=$gallery->gid;
            $data=$_pictures->search()->getData();

            if(empty($data)){
                $_pictures->post_id=0;
                $_pictures->filename=$key.$suffix;
                $_pictures->description='haoselang,'.$gallery->name;
                $_pictures->alttext=$gallery->name.','.$alt[0].','.$alt[1];
                $_pictures->imagedate=date('Y-m-d H:i:s');
                $_pictures->exclude=0;
                $_pictures->sortorder=0;
                $_pictures->meta_data='';
                if($_pictures->save()){
                    $src_obj=new WpNggSrc;
                    $src_obj->pid=$_pictures->pid;
                    $src_obj->gid=$gallery->gid;
                    $src_obj->src=$image;
                    $src_obj->name=$gallery->name;
                    $src_obj->path=$gallery->path;
                    $src_obj->filename=$_pictures->filename;
                    $src_obj->status=0;
                    $src_obj->mktime=date('Y-m-d H:i:s');
                    if(!$src_obj->save()){
                        Yii::log('$src_obj->save()::'.serialize($src_obj->getData()),$level='warning',$category='getImage');
                        $r=false;
                    }
                }else{
                    Yii::log('$_pictures->save()::'.serialize($_pictures->getData()),$level='warning',$category='getImage');
                    $r=false;
                }
            }
        }

        $gallery->author=-1;
        if(!$gallery->save()){
            Yii::log('$gallery->save()::'.serialize($gallery->getData()),$level='warning',$category='getImage');
            $r=false;
        }
        
        return $r;
    }

    //下载图片
    //gallery::author=-1 --> 1 if(get all gallery images)
    //src::status=0 --> -1
    public function getFile(){
        $src_obj=WpNggSrc::model()->recently_one()->find('status=0');
        if(!isset($src_obj->src) || empty($src_obj->src)){
            Yii::log('!isset($src_obj->src) || empty($src_obj->src)',$level='warning',$category='getFile');
            return false;
        }

        $st=Curl::get($src, self::EXPIRE_F);
        if($st===false){
            Yii::log('$st===false',$level='warning',$category='getFile');
            return false;
        }
        if(!isset(Curl::$c['Info']['http_code'])||(Curl::$c['Info']['http_code']!=200)){
            Yii::log('(Curl::$c[\'Info\'][\'http_code\']!=200)',$level='warning',$category='getFile');
            return false;
        }
        if(!isset(Curl::$c['Result']) || empty(Curl::$c['Result'])){
            Yii::log('empty(Curl::$c[\'Result\'])',$level='warning',$category='getFile');
            return false;
        }else{
            $html=Curl::$c['Result'];
        }

        $save_path=self::WP_ROOT_PATH.DIRECTORY_SEPARATOR.$src_obj->path;
        if(!is_dir($save_path)){
            if(!mkdir($save_path, 755, true)){
                Yii::log('mkdir($save_path, 755, true)',$level='warning',$category='getFile');
                return false;
            }
        }
        $thumbs_path=$save_path.DIRECTORY_SEPARATOR.'thumbs';
        if(!is_dir($thumbs_path)){
            if(!mkdir($thumbs_path, 755, true)){
                Yii::log('mkdir($thumbs_path, 755, true)',$level='warning',$category='getFile');
                return false;
            }
        }

        if(file_put_contents($save_path.DIRECTORY_SEPARATOR.$src_obj->filename,$html)){
            $src_obj->status=-1;
            if(!$src_obj->save()){
                Yii::log('!$src_obj->save()',$level='warning',$category='getFile');
                return false;
            }

            $gallery=WpNggGallery::model()->findByPk($src_obj->gid);
            $next_src=WpNggSrc::model()->find('gid=:gid AND status=0', array(':gid'=>$src_obj->gid));
            if(!isset($next_src->id)||empty($next_src->id)){
                $gallery->author=1;
            }
            $gallery->previewpic=$src_obj->pid;
            if(!$gallery->save()){
                Yii::log('!$gallery->save()',$level='warning',$category='getFile');
                return false;
            }
            return true;
        }

        Yii::log('file_put_contents($save_path.DIRECTORY_SEPARATOR.$src_obj->filename,$html)',$level='warning',$category='getFile');
        return false;
    }

    //发表帖子,每次下载图片成功入库后直接调用，满足条件则发表
    //post::author=0
    //post::post_status='private'
    public function putPost(){
        $gallery=WpNggGallery::model()->recently_one()->find('author=1');
        if(empty($gallery) ||
           (!isset($gallery->gid) || empty($gallery->gid)) ||
           (!isset($gallery->slug) || empty($gallery->slug)) ||
           (!isset($gallery->name) || empty($gallery->name))){
            Yii::log('empty($gallery->slug) || empty($gallery->name)',$level='warning',$category='putPost');
            return false;
        }
        $date=date('Y-m-d H:i:s');
/*
 *
wp_terms
term_id 	name 	slug 	term_group
3 	Teen 	teen 	0
4 	Celeb 	celeb 	0
5 	Pussy 	pussy 	0
6 	Bikini 	bikini 	0
7 	Asian 	asian 	0
8 	Lesbian 	lesbian 	0
9 	Tits 	tits 	0
10 	Other 	other 	0

wp_term_taxonomy
term_taxonomy_id 	term_id 	taxonomy 	description 	parent 	count
3 	3 	category 		0 	1
4 	4 	category 		0 	0
5 	5 	category 		0 	0
7 	7 	category 		0 	0
8 	8 	category 		0 	0
9 	9 	category 		0 	0
10 	10 	category 		0 	0

wp_term_relationships
object_id 	term_taxonomy_id 	term_order
*/
        $post=new WpPosts;
        $post->post_author=0;
        $post->post_date=$date;
        $post->post_date_gmt=$date;
        $post->post_content='[nggallery id='.$gallery->gid.']';
        $post->post_title=$gallery->name;
        $post->post_excerpt='';
        $post->post_status='private';
        $post->comment_status='open';
        $post->ping_status='open';
        $post->post_name=$gallery->title;
        $post->to_ping='';
        $post->pinged='';
        $post->post_modified=$date;
        $post->post_modified_gmt=$date;
        $post->post_content_filtered='';
        $post->post_parent=0;
        $post->guid='';
        $post->menu_order=0;
        $post->post_type='post';
        $post->comment_count=0;
        $st=true;
        if($post->save()){
            $post->guid=self::WP_DOMAIN.'/?p='.$post->ID;
            if(!$post->save()){
                Yii::log('$post->ID'.serialize($post->getData()),$level='warning',$category='putPost');
                $st=false;
            }

            $ngg_post=new WpNggPost();
            $ngg_post->gid=$gallery->gid;
            $ngg_post->post_id=$post->ID;
            if(empty($ngg_post->search()->getData())){
                $ngg_post->time=time();
                if(!$ngg_post->save()){
                    Yii::log('!$ngg_post->save()'.serialize($ngg_post->getData()),$level='warning',$category='putPost');
                    $st=false;
                }
            }
            
            switch(strtolower($gallery->galdesc)){
                case 'teen':
                    $cid=3;break;
                case 'celeb':
                    $cid=4;break;
                case 'pussy':
                    $cid=5;break;
                case 'bikini':
                    $cid=6;break;
                case 'asian':
                    $cid=7;break;
                case 'lesbian':
                    $cid=8;break;
                case 'tits':
                    $cid=9;break;
                case 'other':
                    $cid=10;break;
                default:
                    $cid=0;
            }

            if(!empty($cid)){
                $TR=new WpTermRelationships;
                $TR->object_id=$post->ID;
                $TR->term_taxonomy_id=$cid;
                $TR->term_order=0;
                if($TR->save()){
                    $TT=WpTermTaxonomy::model()->findByPk($cid);
                    $TT->count++;
                    if(!$TT->save()){
                        Yii::log('$TT->save()::'.serialize($TT->getData()),$level='warning',$category='putPost');
                        $st=false;
                    }
                }else{
                    Yii::log('$TR->save()::'.serialize($TR->getData()),$level='warning',$category='putPost');
                    $st=false;
                }
            }else{
                $st=false;
            }

        }else{
            Yii::log('$post->save()::'.serialize($post->getData()),$level='warning',$category='putPost');
            $st=false;
        }

        return $st;
    }

    //src::status=-1 --> 1
    //post::author=0 --> 1
    //post::post_status='private' --> 'publish'
    public static function createThumbnail()
    {
        $src_obj=WpNggSrc::model()->recently_one()->find('status=-1');
        $once=trim(strtolower(Yii::app()->request->getParam('once', '')));

        if(!isset($src_obj->pid) || empty($src_obj->pid)){
            Yii::log('empty($data)::',$level='warning',$category='createThumbnail');
            return false;
        }

        if (Yii::app()->request->isAjaxRequest) {
            $st=true;
            
            $pid=trim(strtolower(Yii::app()->request->getParam('pid', 0)));
            if($pid==$src_obj->pid){
                $src_obj->status=1;
                if(!$src_obj->save()){
                    Yii::log('!$src_obj->save()'.serialize($src_obj->getData()),$level='warning',$category='createThumbnail');
                    $st=false;
                }
                
                $src=WpNggSrc::model()->find('gid=:gid AND status<>1', array(':gid'=>$src_obj->gid));
                if(empty($src) && is_file(self::WP_ROOT_PATH.DIRECTORY_SEPARATOR.$src_obj->path.DIRECTORY_SEPARATOR.
                                          'thumbs'.DIRECTORY_SEPARATOR.'thumbs_'.$src_obj->filename)){
                    $ngg_post=WpNggPost::model()->find('gid=:gid',array(':gid'=>$src_obj->gid));
                    if(!empty($ngg_post)&&isset($ngg_post->post_id)){
                        $post=WpPosts::model()->findByPk($ngg_post->post_id);
                        if(empty($post)){
                            Yii::log('$post->ID'.serialize($post->getData()),$level='warning',$category='createThumbnail');
                            $st=false;
                        }else{
                            $post->author=1;
                            $post->post_status='publish';
                            if(!$post->save()){
                                Yii::log('$post->ID'.serialize($post->getData()),$level='warning',$category='createThumbnail');
                                $st=false;
                            }
                        }
                    }else{
                        Yii::log('!isset($ngg_post->post_id)'.serialize($ngg_post->getData()),$level='warning',$category='createThumbnail');
                        $st=false;
                    }
                }
            }else{
                Yii::log('$pid!=$src_obj->pid',$level='warning',$category='createThumbnail');
                $st=false;
            }
            
            return $st;
        }

        return array('pid'=>$src_obj->pid, 'dm'=>self::WP_DOMAIN, 'once'=>$once);
    }
}