<?php
   session_start();

   include_once( 'config.php' );
   include_once( 'saetv2.ex.class.php' );
   $params = array(
      'title' => "头条文章发布测试",
      'content' => rawurlencode("今天是2016年5月31日周二"),
      'cover' => "http://ww1.sinaimg.cn/crop.0.0.320.179.1000.562/baaa02f7jw1f455n706p3j208w0e6ta0.jpg",
      'summary' => "5.31",
      'text' => "hi~",
      'access_token' => $_SESSION['token']['access_token'],
     );
    
    $ch = curl_init ();
    $url = "https://api.weibo.com/proxy/article/publish.json";
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params);
    $ret = curl_exec ( $ch );
    curl_close ( $ch );
    print_r($ret);


