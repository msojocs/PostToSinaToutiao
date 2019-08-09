<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 当发布文章时,能够将文章的标题,链接和内容以头条文章的方式同步至你的微博。<a href="https://github.com/jiyeme/PostToSinaToutiao">配置方法</a>。使用前请确定你的机器支持curl。
 * 
 * @package PostToSinaToutiao
 * @author 祭夜
 * @version 1.0.6
 * @link https://www.jysafe.cn
 */

if (basename(dirname(__FILE__)) != 'PostToSinaToutiao') {
    ?>
    <script src="https://api.hitokoto.jysafe.cn/?cat=&charset=utf-8&length=50&encode=js&fun=sync&user_id="></script>
    <script>
        hitokoto();
    </script><br />
    <?php
    echo '插件目录名称错误，请将目录名称由< ' . basename(dirname(__FILE__)) . ' >修改为< PostToSinaToutiao >！！！';
    exit;
}

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
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('PostToSinaToutiao_Plugin', 'Traum_Toutiao');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('PostToSinaToutiao_Plugin', 'Traum_Toutiao');
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
    public static function deactivate()
    { }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

        $debug = new Typecho_Widget_Helper_Form_Element_Radio(
            'debug',
            array(
                '1' => _t('启用'),
                '0' => _t('关闭'),
            ),
            '1',
            _t('日志设置'),
            _t('日志设置')
        );
        $form->addInput($debug);
        $defaultimg = new Typecho_Widget_Helper_Form_Element_Text('defaultimg', null, 'https://www.jysafe.cn/assets/images/LOGO.png', _t('头条文章默认封面'), '文章无图时显示的封面');
        $form->addInput($defaultimg);
        $appkey = new Typecho_Widget_Helper_Form_Element_Text('appkey', null, '', _t('App Key'), '<a href="http://open.weibo.com" >微博开放平台</a>获取');
        $form->addInput($appkey);
        $sinaaccount = new Typecho_Widget_Helper_Form_Element_Text('sinaaccount', null, '', _t('新浪微博账号'), '新浪微博账号');
        $form->addInput($sinaaccount);
        $sinapsw = new Typecho_Widget_Helper_Form_Element_Password('sinapsw', null, '', _t('新浪微博密码'), '<h2>日志：</h2>' . Traum_ReadLog() . '<br />');
        $form->addInput($sinapsw);
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    { }

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function Traum_Toutiao($contents, $class)
    {
        //如果文章属性为隐藏或滞后发布改良版,加入创建时间和修改时间不一致则返回不执行
        $modified = isset($contents['modified']) ? $contents['modified'] : null;
        if ('publish' != $contents['visibility'] || $contents['created'] > $modified) {
            return;
        }

        //必填项如果没填的话直接停止
        if (is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->appkey) || is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->sinaaccount) || is_null(Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->sinapsw)) {
            return;
        }

        //发布文章
        post_to_sina_weibo_toutiao($contents, $class);
    }
}

/*****************************************/
function post_to_sina_weibo_toutiao($content, $classa)
{
    require 'EasyHttp.php';
    require 'EasyHttp/Error.php';
    require 'EasyHttp/Curl.php';
    require 'EasyHttp/Cookie.php';
    require 'EasyHttp/Encoding.php';
    require 'EasyHttp/Fsockopen.php';
    require 'EasyHttp/Proxy.php';
    require 'EasyHttp/Streams.php';

    //get setting info
    $request = new EasyHttp();
    $appkey = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->appkey;          //key
    $username = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->sinaaccount;        //用户名
    $userpassword = Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->sinapsw;    //密码

    $parser = new HyperDown;
    $post_content = traum_toutiao_handle_content($parser->makeHtml($content['text']));  //Parser Article content
    $get_post_title = $content['title'];  //Article Title

    /* 获取文章标签关键词*/
    $tags = '#' . str_replace(",", "##", $content['tags']) . '#';

    $status = '【' . strip_tags($get_post_title) . '】 ' . mb_strimwidth(strip_tags($post_content), 0, 132, ' ');
    $cover_url = img_postthumb($content['text']);

    $api_url = 'http://api.weibo.com/proxy/article/publish.json';
    $body = array(
        'title'   => strip_tags($get_post_title),         //Article title
        'content' => $post_content . ' <br>原文地址:<a href="' . $classa->permalink . '" >' . strip_tags($get_post_title) . '</a>',    //article
        'cover'   => $cover_url,                 //头条的封面
        'summary' => mb_strimwidth(strip_tags($post_content), 0, 110, '...'),      //article summary
        'text'    => mb_strimwidth(strip_tags($post_content), 0, 110, $status) . $tags . '原文地址:<a href="' . $classa->permalink . '" >' . strip_tags($get_post_title) . '</a>',   //sina content
        'source'  => $appkey
    );
    $headers = array('Authorization' => 'Basic ' . base64_encode("$username:$userpassword"));
    $result = $request->post($api_url, array('body' => $body, 'headers' => $headers));

    Traum_LogInfo(json_encode($result));

    return;
}

//处理content内容
function traum_toutiao_handle_content($content) {
    if (!strpos($content, "<h1>") && strpos($content, "<h2>") && (strpos($content, "<h3>") || strpos($content, "<h4>") || strpos($content, "<h5>") ||strpos($content, "<h6>"))) {
        $content = str_replace("<h2>", "<h1>", $content);
        $content = str_replace("</h2>", "</h1>", $content);
    }

    $content = preg_replace("/\[\/?[a-z]+_[a-z]+\]/","",$content);
    //$content = str_replace(array("<br>", "<br />"), "&lt;br&gt;", $content);
    $content = str_replace(array("\r\n", "\r", "\n"), "<br>", $content);
    return $content;
}

//获取第一张图片
function img_postthumb($content)
{
    preg_match_all("/\[1\]:(.*)\\r\\n/U", $content, $thumbUrl);  //通过正则式获取图片地址
    if (empty($thumbUrl[1][0]))
        return Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->defaultimg;  //没找到(默认情况下)
    $img_src = $thumbUrl[1][0];  //将赋值给img_src
    $img_counter = count($thumbUrl[0]);  //一个src地址的计数器
    switch ($img_counter > 0) {
        case $allPics = 1:
            return $img_src;  //当找到一个src地址的时候，输出缩略图
            break;
        default:
            return Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->defaultimg;  //没找到(默认情况下)
    }
}

//log
function Traum_LogInfo($msg)
{
    if (Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->debug == '1') {
        $logFile = dirname(__FILE__) . '/tmp/sync_weibo.log'; // 日志路径
        date_default_timezone_set('Asia/Shanghai');
        file_put_contents($logFile, date('[Y-m-d H:i:s]: ') . $msg . PHP_EOL, FILE_APPEND);
        return $msg;
    } else {
        return;
    }              // 日志开关：1表示打开，0表示关闭
}

//Traum_ReadLog
function Traum_ReadLog()
{
    $file = dirname(__FILE__) . "/tmp/sync_weibo.log";
    if (file_exists($file)) {
        $file = fopen($file, "r") or exit("Unable to open file!");
        //Output a line of the file until the end is reached
        //feof() check if file read end EOF
        $log = '';
        while (!feof($file)) {
            //fgets() Read row by row
            $log .= fgets($file) . "<br />";
        }
        fclose($file);
        return $log;
    }
    return;
}
