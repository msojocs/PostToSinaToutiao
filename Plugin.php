<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 当发布文章时,能够将文章的标题,链接和内容以头条文章的方式同步至你的微博。<a href="https://github.com/ShangJixin/Typecho-Plugin-PostToSinaToutiao">配置方法</a>。使用前请确定你的机器支持curl。
 * 
 * @package PostToSinaToutiao
 * @author 祭夜
 * @version 1.0.3
 * @link https://www.jysafe.cn
 */
 
class PostToSinaToutiao_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('PostToSinaToutiao_Plugin', 'justdoit');
		Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('PostToSinaToutiao_Plugin', 'justdoit');
		return _t('欢迎使用！！第一次使用请查看<a href="https://www.jysafe.cn/3226.air">食用方法</a>');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        
		$debug = new Typecho_Widget_Helper_Form_Element_Text('debug', null, '', _t('<h2><a href="https://www.jysafe.cn/3226.air">----->食用方法</a>||||<a href="https://github.com/jiyeme/PostToSinaToutiao">查看Github</a></h2><br /><script type="text/javascript" src="//api.jysafe.cn/yy/?encode=js&charset=utf-8"></script>
<div id="cqchitokoto"><script defer>cqchitokoto()</script></div><br />是否启用日志'), '0或空不开，其它开');
		$form->addInput($debug);
		$defaultimg = new Typecho_Widget_Helper_Form_Element_Text('defaultimg', null, 'https://www.jysafe.cn/assets/images/LOGO.png', _t('头条文章默认封面'), '文章无图时显示的封面');
		$form->addInput($defaultimg);
		$AppKey = new Typecho_Widget_Helper_Form_Element_Text('AppKey', null, '', _t('App Key'), '<a href="http://open.weibo.com" >微博开放平台</a>获取');
		$form->addInput($AppKey);
		$SinaAccount = new Typecho_Widget_Helper_Form_Element_Text('SinaAccount', null, '', _t('新浪微博账号'), '新浪微博账号');
		$form->addInput($SinaAccount);
		$SinaPsw = new Typecho_Widget_Helper_Form_Element_Text('SinaPsw', null, '', _t('新浪微博密码'), '日志：<br />'.readlog());
		$form->addInput($SinaPsw);
    }
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
	public static function justdoit($contents, $class)
    {
        //logInfo($contents['text']);
        //logInfo(content($contents['text']));
        
		//如果文章属性为隐藏或滞后发布改良版,加入创建时间和修改时间不一致则返回不执行
		if( 'publish' != $contents['visibility'] || $contents['created'] > $contents['modified']){
            return;
        }
		//必填项如果没填的话直接停止
		if( is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->AppKey) || is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->SinaAccount) || is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->SinaPsw)){
            return;
        }
		
		//发布文章
		post_to_sina_weibo_toutiao($contents,$class);
		
    } 
}
/*****************************************/
function post_to_sina_weibo_toutiao($content,$classa) {
    
    require 'EasyHttp.php';
    require 'EasyHttp/Curl.php';
    require 'EasyHttp/Cookie.php';
    require 'EasyHttp/Encoding.php';
    require 'EasyHttp/Fsockopen.php';
    require 'EasyHttp/Proxy.php';
    require 'EasyHttp/Streams.php';
    
    $request = new EasyHttp();
    $appkey = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->AppKey;          //key
    $username = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->SinaAccount;        //用户名
    $userpassword = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->SinaPsw;    //密码
    
    
    $get_post_centent = content($content['text']);  //文章内容
    $get_post_title = $content['title'];  //文章标题
    
    
    /* 获取文章标签关键词*/
    $tags = '#'.str_replace(",","##",$content['tags']).'#';
    
    $status = '【' . strip_tags($get_post_title) . '】 ' . mb_strimwidth(strip_tags($get_post_centent) , 0, 132, ' '); 
    $tupianurl = img_postthumb($content['text']);
    
    
    $api_url = 'https://api.weibo.com/proxy/article/publish.json';  
    $body = array(
        'title'   => strip_tags($get_post_title),         //头条的标题
        'content' => $get_post_centent.' <br>原文地址:' . $classa->permalink,    //头条的正文
        'cover'   => $tupianurl,                 //头条的封面
        'summary' => mb_strimwidth(strip_tags($get_post_centent) , 0, 110, '...'),      //头条的导语
        'text'    => mb_strimwidth(strip_tags($get_post_centent) , 0, 110, $status).$tags.'原文地址:' . $classa->permalink,    //微博的内容
        'source'  => $appkey
    ); 
    $headers = array('Authorization' => 'Basic ' . base64_encode("$username:$userpassword"));  
    $result = $request->post($api_url, array('body' => $body,'headers' => $headers)); 
    logInfo($result['body']);
}
//获取第一张图片
function img_postthumb($content) {
 
preg_match_all ("/\[1\]:(.*)\\r\\n/U", $content, $thumbUrl);  //通过正则式获取图片地址
$img_src = $thumbUrl[1][0];  //将赋值给img_src
$img_counter = count($thumbUrl[0]);  //一个src地址的计数器
switch ($img_counter > 0) {
case $allPics = 1:
return $img_src;  //当找到一个src地址的时候，输出缩略图
break;
default:
return Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->defaultimg;  //没找到(默认情况下)，不输出任何内容
}
}
//记录日志
function logInfo($msg)
{
    //日志记录是否启用
    if(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->debug) 
    {
        $logSwitch = 1;
        
    }else{
        $logSwitch  = 0;
        
    }              // 日志开关：1表示打开，0表示关闭
    $logFile    = '/tmp/sync_weibo.log'; // 日志路径           
    if ($logSwitch == 0 ) return;
    date_default_timezone_set('Asia/Shanghai');
    file_put_contents($logFile, date('[Y-m-d H:i:s]: ') . $msg . PHP_EOL, FILE_APPEND);
    return $msg;
}
//读取日志
function readlog(){
    $file = "/tmp/sync_weibo.log";
    if(file_exists($file) && Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->debug){
        $file = fopen($file, "r") or exit("Unable to open file!");
        //Output a line of the file until the end is reached
        //feof() check if file read end EOF
        while(!feof($file))
        {
         //fgets() Read row by row
         return fgets($file). "<br />";
        }
        fclose($file);
    }
        
}
function content($content){
    $content = '##############">'.preg_replace("/!\[(.*)\]\[\d\]/U",'<img src="**************##############">',$content).'<img src="**************';//正则替换
    $str = preg_replace("/\s+\r/is", "\n", $content);//回车符是\r  
    $str = preg_replace("/\s+\r\n/is", "\n", $str);//回车符是\r\n  
    $str = preg_replace("/\s+\n/is", "\n", $str);//回车符是\n  
    $content = str_replace("\n",'<br />',$str);  
    preg_match_all ('/##############(.*)(\*\*\*\*\*\*\*\*\*\*\*\*\*\*)/U', $content, $content1); //切割内容
    $content1_counter = count($content1[0]); //内容计数器
    
    //切割图片链接
    preg_match_all ("/\[\d\]:[ ](.*)(\/uploads)/U", $content, $thumbUrl1);  //通过正则式获取图片前地址
    preg_match_all ("/\/usr(.*)(g)/U", $content, $thumbUrl2);  //通过正则式获取图片后地址
    $img_counter = count($thumbUrl2[0]);  //一个图片地址地址的计数器
    
    //合成前后链接
    for($i = 0;$i < $img_counter; $i++){
        $thumbUrl[1][$i] = $thumbUrl1[1][$i].$thumbUrl2[1][$i].'g';
    }
    //切割结束
    
    //将内容与链接合成
    $content = '';
    for($i = 0;$i < $content1_counter; $i++){
        if ($i == $content1_counter-1){
            $content = $content.$content1[1][$i];
        }else{
            $content = $content.$content1[1][$i].$thumbUrl[1][$i];
        }
        
    }
    
    preg_match_all ('/">(.*)(\[1\])/i', $content, $content);
    return $content[1][0];
}