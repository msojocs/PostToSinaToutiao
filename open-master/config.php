<?php
header('Content-Type: text/html; charset=UTF-8');
require('../../../../config.inc.php');
define( "WB_AKEY" , Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->AppKey );
define( "WB_SKEY" , Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->AppSecret );
define( "WB_CALLBACK_URL" , Typecho_Widget::widget('Widget_Options')->plugin('PostToSinaToutiao')->domain.'/usr/plugins/PostToSinaToutiao/open-master/callback.php' );
