<?php
/**
 * SMTP - Simple Mail Transfer Protocol class.
 * Simplified version for credential testing purposes.
 */

class SMTP
{
    private $smtp_conn;
    private $timeout = 30;
    private $debug = false;
    
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
    
    public function connect($host, $port = 25, $timeout = 30)
    {
        $this->timeout = $timeout;
        
        // Create socket connection
        $this->smtp_conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if (!$this->smtp_conn) {
            return false;
        }
        
        // Set timeout for socket operations
        stream_set_timeout($this->smtp_conn, $timeout);
        
        // Read initial response
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '220') {
            return false;
        }
        
        // Send EHLO command
        $this->send_command('EHLO localhost');
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '250') {
            // Try HELO if EHLO fails
            $this->send_command('HELO localhost');
            $response = $this->get_lines();
            
            if (substr($response, 0, 3) != '250') {
                return false;
            }
        }
        
        return true;
    }
    
    public function startTLS()
    {
        $this->send_command('STARTTLS');
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '220') {
            return false;
        }
        
        // Enable crypto
        if (!stream_socket_enable_crypto($this->smtp_conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return false;
        }
        
        // Send EHLO again after TLS
        $this->send_command('EHLO localhost');
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '250');
    }
    
    public function authenticate($username, $password)
    {
        // Send AUTH LOGIN command
        $this->send_command('AUTH LOGIN');
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send username
        $this->send_command(base64_encode($username));
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send password
        $this->send_command(base64_encode($password));
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '235');
    }
    
    public function mail($from)
    {
        $this->send_command('MAIL FROM:<' . $from . '>');
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '250');
    }
    
    public function recipient($to)
    {
        $this->send_command('RCPT TO:<' . $to . '>');
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '250');
    }
    
    public function data($message)
    {
        $this->send_command('DATA');
        $response = $this->get_lines();
        
        if (substr($response, 0, 3) != '354') {
            return false;
        }
        
        // Send message
        $this->send_command($message . "\r\n.");
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '250');
    }
    
    public function quit()
    {
        $this->send_command('QUIT');
        $response = $this->get_lines();
        
        return (substr($response, 0, 3) == '221');
    }
    
    public function close()
    {
        if ($this->smtp_conn) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }
    
    private function send_command($command)
    {
        if ($this->debug) {
            echo "CLIENT: " . $command . "\n";
        }
        
        return fwrite($this->smtp_conn, $command . "\r\n");
    }
    
    private function get_lines()
    {
        $response = '';
        
        while (($line = fgets($this->smtp_conn, 515)) !== false) {
            $response .= $line;
            
            if ($this->debug) {
                echo "SERVER: " . $line;
            }
            
            // Check if this is the last line (fourth character is space, not dash)
            if (strlen($line) >= 4 && $line[3] == ' ') {
                break;
            }
        }
        
        return $response;
    }
}
?>