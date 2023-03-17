<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章页面增加三个功能：按下键盘pageup查看上一篇；按下键盘pagedown查看下一篇；按下回车键使用微软tts语音朗读文章标题及内容
 *
 * @package ArticleTools
 * @version 1.0.0
 * @link https://www.bing.com/
 */
class ArticleTools_Plugin implements Typecho_Plugin_Interface
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
        // 注册插件点Widget_Archive::footer，在文章页面底部添加JavaScript代码
        Typecho_Plugin::factory('Widget_Archive')->footer = array('ArticleTools_Plugin', 'render');
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
    {
        
    }

    /**
     * 获取插件配置面板 
     *
     * @access public 
     * @param Typecho_Widget_Helper_Form $form 配置面板 
     * @return void 
     */ 
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // 添加订阅密钥表单元素 
        $subscriptionKey = new Typecho_Widget_Helper_Form_Element_Text('subscriptionKey', NULL, '', _t('订阅密钥'), _t('请输入您从微软获取的订阅密钥')); 
        $form->addInput($subscriptionKey); 

        // 添加区域码表单元素 
        $regionCode = new Typecho_Widget_Helper_Form_Element_Text('regionCode', NULL, '', _t('区域码'), _t('请输入您从微软获取的区域码')); 
        $form->addInput($regionCode);
    /**
     * 个人用户的配置面板 
     *
     * @access public 
     * @param Typecho_Widget_Helper_Form $form 
     * @return void 
     */ 
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // 留空即可 
    }

    /**
     * 插件点Widget_Archive::footer的回调函数，在文章页面底部添加JavaScript代码
     *
     * @access public
     * @param string $footer 文章页面底部内容
     * @param Widget_Archive $archive 当前页面对象
     * @return void
     */
    public static function render($footer, Widget_Archive $archive)
    {
        // 判断当前页面是否是文章页面 
        if ($archive->is('single')) {
            // 获取插件配置信息 
            $options = Typecho_Widget::widget('Widget_Options')->plugin('ArticleTools'); 

            // 获取订阅密钥和区域码 
            $subscriptionKey = $options->subscriptionKey; 
            $regionCode = $options->regionCode; 

            // 获取当前文章对象 
            $post = Typecho_Widget::widget('Widget_Archive'); 

            // 获取当前文章标题和内容（去除HTML标签） 
            $title = strip_tags($post->title); 
            $content = strip_tags($post->content); 

            // 获取当前文章上一篇和下一篇的链接地址（如果存在） 
            if ($post->thePrev) { // 如果存在上一篇文章，则获取其链接地址并赋值给prevUrl变量；否则将prevUrl变量置为空字符串  
                ob_start(); // 开启输出缓冲区  
                $post->thePrev('%s', '', array('title' => '')); // 输出上一篇文章链接地址  
                $prevUrl = ob_get_contents(); // 将输出缓冲区内容赋值给prevUrl变量  
                ob_end_clean(); // 清空并关闭输出缓冲区  
            } else {  
                $prevUrl = '';  
            }
 if ($post->theNext) { // 如果存在下一篇文章，则获取其链接地址并赋值给nextUrl变量；否则将nextUrl变量置为空字符串  
                ob_start(); // 开启输出缓冲区  
                $post->theNext('%s', '', array('title' => '')); // 输出下一篇文章链接地址  
                $nextUrl = ob_get_contents(); // 将输出缓冲区内容赋值给nextUrl变量  
                ob_end_clean(); // 清空并关闭输出缓冲区  
            } else {  
                $nextUrl = '';  
            }

            // 输出JavaScript代码 
            echo <<<EOF 
<script> 
    // 定义订阅密钥和区域码 
    var subscriptionKey = '$subscriptionKey'; 
    var regionCode = '$regionCode'; 

    // 定义上一篇和下一篇文章链接地址 
    var prevUrl = '$prevUrl'; 
    var nextUrl = '$nextUrl'; 

    // 定义当前文章标题和内容（转义单引号） 
    var title = '$title'.replace(/'/g, "\\'"); 
    var content = '$content'.replace(/'/g, "\\'"); 

    // 定义一个全局变量audio，用于存储音频对象 
    var audio; 

    // 绑定键盘事件监听器 
    document.addEventListener('keydown', function(event) { 
        switch (event.code) { 
            case 'PageUp': // 如果按下pageup键，则跳转到上一篇文章（如果存在） 
                if (prevUrl) { 
                    window.location.href = prevUrl; 
                } else { 
                    alert('没有上一篇文章了'); 
                }   
                break;   
            case 'PageDown': // 如果按下pagedown键，则跳转到下一篇文章（如果存在）   
                if (nextUrl) {   
                    window.location.href = nextUrl;   
                } else {   
                    alert('没有下一篇文章了');   
                }     
                break;     
            case 'Enter': // 如果按下回车键，则调用微软tts API，并播放返回的音频数据
 // 如果audio对象已经存在，则停止播放并释放资源 
                if (audio) { 
                    audio.pause(); 
                    audio = null; 
                } 

                // 根据当前文章标题和内容生成一个合成语音请求，并发送给微软tts API 
                var request = new XMLHttpRequest(); // 创建一个XMLHttpRequest对象 
                request.open('POST', 'https://' + regionCode + '.tts.speech.microsoft.com/cognitiveservices/v1'); // 设置请求方法和地址 
                request.setRequestHeader('Ocp-Apim-Subscription-Key', subscriptionKey); // 设置订阅密钥头部 
                request.setRequestHeader('Content-Type', 'application/ssml+xml'); // 设置内容类型头部 
                request.setRequestHeader('X-Microsoft-OutputFormat', 'riff-24khz-16bit-mono-pcm'); // 设置输出格式头部 

                // 定义SSML文本，用于指定语音合成的参数（如语言、声音、速度等）和文本内容（标题和正文）  
                var ssml = '<speak version="1.0" xmlns="http://www.w3.org/2001/10/synthesis" xml:lang="zh-CN">';  
                ssml += '<voice name="zh-CN-XiaoxiaoNeural">';  
                ssml += '<prosody rate="-20%">';  
                ssml += '<break time="500ms"/>';  
                ssml += title; // 添加文章标题  
                ssml += '<break time="1000ms"/>';  
                ssml += content; // 添加文章正文
                ssml += '</prosody>';  
                ssml += '</voice>';  
                ssml += '</speak>';  

                // 将SSML文本作为请求体发送给微软tts API 
                request.send(ssml); 

                // 处理API返回的结果，并使用HTML5 Audio API来播放音频数据 
                request.addEventListener('load', function() { // 当请求完成时触发load事件 
                    if (request.status == 200) { // 如果状态码为200，则表示请求成功 
                        var audioData = request.response; // 获取响应体中的音频数据 
                        var blob = new Blob([audioData]); // 将音频数据转换为一个Blob对象 
                        var url = URL.createObjectURL(blob); // 将Blob对象转换为一个URL字符串 
                        audio = new Audio(); // 创建一个HTML5 Audio对象 
                        audio.src = url; // 设置Audio对象的src属性为URL字符串 
                        audio.play(); // 调用Audio对象的play方法，开始播放音频数据  
                    } else { // 如果状态码不为200，则表示请求失败  
                        alert('语音合成失败：' + request.status); // 弹出提示框显示错误信息  
                    }   
                });   
                break;   
            default:   
                break;   
        }     
    });     
</script>     
EOF;
    }
}