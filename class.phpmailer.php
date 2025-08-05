<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified version for credential testing purposes.
 */

class PHPMailer
{
    public $isSMTP = false;
    public $Host = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 25;
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $isHTML = false;
    public $SMTPDebug = 0;
    
    private $to = array();
    private $smtp = null;
    
    public function __construct($exceptions = null)
    {
        // Constructor
    }
    
    public function isSMTP()
    {
        $this->isSMTP = true;
    }
    
    public function addAddress($address, $name = '')
    {
        $this->to[] = array('address' => $address, 'name' => $name);
    }
    
    public function isHTML($isHtml = true)
    {
        $this->isHTML = $isHtml;
    }
    
    public function smtpConnect($options = array())
    {
        if (!$this->isSMTP) {
            return false;
        }
        
        $this->smtp = new SMTP();
        $this->smtp->setTimeout(30);
        
        // Connect to SMTP server
        if (!$this->smtp->connect($this->Host, $this->Port, 30)) {
            return false;
        }
        
        // Start TLS if required
        if ($this->SMTPSecure == 'tls') {
            if (!$this->smtp->startTLS()) {
                return false;
            }
        }
        
        // Authenticate if required
        if ($this->SMTPAuth) {
            if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function smtpClose()
    {
        if ($this->smtp !== null) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }
    
    public function send()
    {
        if (!$this->isSMTP) {
            return $this->mailSend();
        }
        
        return $this->smtpSend();
    }
    
    private function smtpSend()
    {
        if ($this->smtp === null) {
            if (!$this->smtpConnect()) {
                return false;
            }
        }
        
        // Send email via SMTP
        if (!$this->smtp->mail($this->From)) {
            return false;
        }
        
        foreach ($this->to as $recipient) {
            if (!$this->smtp->recipient($recipient['address'])) {
                return false;
            }
        }
        
        if (!$this->smtp->data($this->getMailMIME())) {
            return false;
        }
        
        return true;
    }
    
    private function mailSend()
    {
        // Fallback to PHP mail() function
        $to = '';
        foreach ($this->to as $recipient) {
            if ($to != '') $to .= ', ';
            $to .= $recipient['address'];
        }
        
        $headers = "From: " . $this->From . "\r\n";
        if ($this->isHTML) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return mail($to, $this->Subject, $this->Body, $headers);
    }
    
    private function getMailMIME()
    {
        $result = '';
        $result .= "From: " . $this->From . "\r\n";
        $result .= "Subject: " . $this->Subject . "\r\n";
        
        if ($this->isHTML) {
            $result .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $result .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        
        $result .= "\r\n";
        $result .= $this->Body;
        
        return $result;
    }
}
?>