<?php

/**
 * 评论通知推送至 IFTTT Webhooks
 *
 * @package Comment2IFTTT
 * @author Tsuk1ko
 * @version 1.4.0
 * @link https://github.com/vndroid/Comment2IFTTT
 */
class Comment2IFTTT_Plugin implements Typecho_Plugin_Interface
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
        if (!function_exists('curl_init')) {
            throw new Typecho_Plugin_Exception(_t('检测到当前 PHP 环境没有 cURL 组件, 无法正常使用此插件！'));
        }
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('Comment2IFTTT_Plugin', 'msgPush');
        return _t('请进入插件配置 IFTTT Webhooks key 以正常工作');
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
        $filterOwner = new Typecho_Widget_Helper_Form_Element_Radio('filterOwner',
            array(
                '1' => '是',
                '0' => '否'
            ), '1', _t('当评论者为博主时不推送'), _t('启用后，若评论者为博主，则不会推送至 IFTTT Webhooks'));
        $form->addInput($filterOwner);

        $filterCNChars = new Typecho_Widget_Helper_Form_Element_Radio('filterCNChars',
            array(
                '1' => '是',
                '0' => '否'
            ), '1', _t('过滤非中文评论'), _t('启用后，若评论中不含中文字符，则不会推送至 IFTTT Webhooks'));
        $form->addInput($filterCNChars);

        $key = new Typecho_Widget_Helper_Form_Element_Text('whKey', NULL, NULL, _t('Webhooks Key'), _t('想要获取 Webhooks key 则需要启用 <a href="https://ifttt.com/maker_webhooks" target="_blank">IFTTT 的 Webhooks 服务</a>，然后点击页面中的“Documentation”来查看'));
        $form->addInput($key->addRule('required', _t('清填写 IFTTT 的 Webhooks key')));

        $event = new Typecho_Widget_Helper_Form_Element_Text('evName', NULL, NULL, _t('Event Name'), _t('Webhooks 事件名'));
        $form->addInput($event->addRule('required', _t('清填写 IFTTT 的 Event Name')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 推送至 IFTTT Webhooks
     *
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return array $comment
     */
    public static function msgPush($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options')->plugin('Comment2IFTTT');
        // 获取用户配置
        $whKey = $options->whKey;
        $evName = $options->evName;
        $filterOwner = $options->filterOwner;
        $filterCNChars = $options->filterCNChars;

        //var_dump($comment);die();
        if ($comment->authorId == 1 && $filterOwner == '1') {
            return $comment;
        }

        if ($filterCNChars == '1') {
            $chkResult = preg_match('/[\x{4e00}-\x{9fa5}]/u', $comment->text);
            if (!$chkResult == 1){
                return $comment;
            }
        }

        $headers = array(
            "Content-type: application/json"
        );
        $url = 'https://maker.ifttt.com/trigger/' . $evName . '/with/key/' . $whKey;
        $data = array(
            'value1' => $comment->title,
            'value2' => $comment->author,
            'value3' => $comment->text
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);
        curl_close($ch);

        return $comment;
    }
}
