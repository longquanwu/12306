<?php
/**
 * Email.php
 * User: wlq314@qq.com
 * Date: 16/8/15 Time: 11:10
 */

require_once("PHPMailer/PHPMailerAutoload.php");

class Email{
    
    private $mail;
    private $debug = 0;  //是否开启debug, 0 不提示, 1 提示
    private $charSet = 'UTF-8';
    private $SMTPSecure = 'ssl';
    private $port = '465';
    private $isHTML = TRUE;
    private $host;
    private $name;
    private $from;
    private $userName;
    private $passWord;

    /**
     * 读取配置文件并赋值
     * Email constructor.
     * @param $conf
     */
    public function __construct($conf){
        $this->mail = new PHPMailer();
        $this->host = $conf['host'];
        $this->name = $conf['name'];
        $this->from = $conf['from'];
        $this->userName = $conf['username'];
        $this->passWord = $conf['password'];
    }

    /**
     * @param string $to  发送到邮箱地址
     * @param string $subject  主题
     * @param string $message  内容
     * @param string $attachment  附件
     * @param string $attName  附件名称
     * @return bool
     * @throws phpmailerException
     */
    public function send($to, $subject, $message, $attachment = '', $attName = ''){
        $this->mail->SMTPDebug = $this->debug;  //是否启用smtp的debug进行调试 开发环境建议开启 默认关闭debug调试模式
        $this->mail->isSMTP();  //使用smtp发送邮件
        $this->mail->SMTPAuth=true;  //smtp需要鉴权 这个必须是true
        $this->mail->Host = $this->host;  //服务器地址
        $this->mail->SMTPSecure = $this->SMTPSecure;  //设置使用ssl加密方式登录鉴权
        $this->mail->Port = $this->port;  //设置ssl连接smtp服务器的远程服务器端口号 可选465或587
        $this->mail->CharSet = $this->charSet;  //设置编码
        $this->mail->FromName = $this->from;  //设置发件人姓名（昵称） 任意内容，显示在收件人邮件的发件人邮箱地址前的发件人姓名
        $this->mail->Username = $this->userName;  //smtp登录的账号
        $this->mail->Password= $this->passWord;  //smtp登录账号的密码
        $this->mail->From= $this->from;  //设置发件人邮箱地址
        $this->mail->isHTML($this->isHTML);  //邮件正文是否为html编码
        $this->mail->addAddress($to);  //设置收件人邮箱地址
        $this->mail->Subject = $subject;  //邮件主题
        $this->mail->Body = $message;  //邮件正文 上方将isHTML设置成了true，则可以是完整的html字符串 如：使用file_get_contents函数读取本地的html文件
        if ($attachment !== '')
            $this->mail->addAttachment($attachment, $attName);  //为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） 第二参数为在邮件附件中该附件的名称
        return $this->mail->send();  //发送命令 返回布尔值. 要是收件人不存在，若不出现错误依然返回true 也就是说在发送之前 自己需要些方法实现检测该邮箱是否真实有效
    }

    /**
     * 获得错误信息 
     * @return string
     */
    public function errorInfo(){
        return $this->mail->ErrorInfo;
    }

}
