<?php
/**
 * PHPMailer SMTP class.
 * @package PHPMailer
 * @author Marcus Bointon (Synchro/coolie) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer SMTP class.
 * @package PHPMailer
 */
class SMTP
{
    const VERSION = '6.9.2';

    const LE = "\r\n";

    const DEFAULT_PORT = 25;

    public $SMTP_PORT = self::DEFAULT_PORT;

    public $CRAM_MD5 = 'CRAM-MD5';

    public $PLAIN = 'PLAIN';

    public $LOGIN = 'LOGIN';

    public $XOAUTH2 = 'XOAUTH2';

    protected $smtp_conn;

    protected $error = [
        'error' => '',
        'detail' => '',
        'smtp_code' => '',
        'smtp_code_ex' => '',
    ];

    protected $helo_rply;

    protected $server_caps;

    protected $last_reply = '';

    protected $debug_level = 0;

    protected $debug_output = 'echo';

    protected $do_verp = false;

    protected $timeout = 300;

    protected $timelimit = 600;

    public function __construct()
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        $this->error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];

        if (null === $this->smtp_conn) {
            $this->smtp_conn = $this->getConnection($host, $port, $timeout, $options);
        }

        if ($this->smtp_conn) {
            stream_set_timeout($this->smtp_conn, $this->timeout);
            $this->setLastReply($this->get_lines());
            return true;
        }

        return false;
    }

    protected function getConnection($host, $port = null, $timeout = 30, $options = [])
    {
        if (null === $port) {
            $port = $this->SMTP_PORT;
        }

        $errno = 0;
        $errstr = '';

        $socket_context = stream_context_create($options);
        $connection = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $socket_context
        );

        if ($connection === false) {
            $this->setError(
                'Connect failed',
                $errstr,
                (string) $errno
            );
            return false;
        }

        return $connection;
    }

    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }

        $crypto_ok = stream_socket_enable_crypto(
            $this->smtp_conn,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if (false === $crypto_ok) {
            $this->setError('STARTTLS not successful');
            return false;
        }

        return true;
    }

    public function authenticate($username, $password, $authtype = null, $OAuth = null)
    {
        if (!$this->server_caps) {
            $this->setError('Authentication is not allowed before HELO/EHLO');
            return false;
        }

        if (array_key_exists('EHLO', $this->server_caps)) {
            if (!array_key_exists('AUTH', $this->server_caps)) {
                $this->setError('Authentication is not allowed at this stage');
                return false;
            }

            $authtype = $this->getAuthType($authtype, $this->server_caps['AUTH']);

            if ($authtype === $this->XOAUTH2 && null !== $OAuth) {
                return $this->authXOAUTH2($OAuth->getOauth64());
            }

            if (!in_array($authtype, $this->server_caps['AUTH'], true)) {
                $this->setError('The requested authentication mechanism is not supported');
                return false;
            }
        }

        switch ($authtype) {
            case $this->PLAIN:
                if (!$this->sendCommand('AUTH', 'AUTH PLAIN', 334)) {
                    return false;
                }
                if (!$this->sendCommand(
                    'User & Password',
                    base64_encode("\0" . $username . "\0" . $password),
                    235
                )) {
                    return false;
                }
                break;
            case $this->LOGIN:
                if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) {
                    return false;
                }
                if (!$this->sendCommand('Username', base64_encode($username), 334)) {
                    return false;
                }
                if (!$this->sendCommand('Password', base64_encode($password), 235)) {
                    return false;
                }
                break;
            case $this->CRAM_MD5:
                if (!$this->sendCommand('AUTH CRAM-MD5', 'AUTH CRAM-MD5', 334)) {
                    return false;
                }
                $challenge = base64_decode(substr($this->last_reply, 4));
                $response = $username . ' ' . $this->hmac($challenge, $password);
                return $this->sendCommand('CRAM-MD5 Response', base64_encode($response), 235);
            default:
                $this->setError("Authentication method \"$authtype\" is not supported");
                return false;
        }
        return true;
    }

    protected function authXOAUTH2($xoauth2_token)
    {
        if (!$this->sendCommand('AUTH', 'AUTH XOAUTH2 ' . $xoauth2_token, 235)) {
            return false;
        }
        return true;
    }

    protected function hmac($data, $key)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }

        $key = str_pad($key, 64, chr(0x00));
        if (strlen($key) > 64) {
            $key = pack('H*', md5($key));
            $key = str_pad($key, 64, chr(0x00));
        }

        $ipad = str_repeat(chr(0x36), 64);
        $opad = str_repeat(chr(0x5c), 64);

        $inner = pack('H*', md5(($key ^ $ipad) . $data));
        return md5(($key ^ $opad) . $inner);
    }

    public function connected()
    {
        return (bool) $this->smtp_conn;
    }

    public function close()
    {
        $this->error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];
        $this->helo_rply = null;
        $this->server_caps = null;
        $this->last_reply = '';

        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    public function data($msg_data)
    {
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }

        $msg_data = str_replace("\r\n.", "\r\n..", rtrim($msg_data, "\r\n") . "\r\n.\r\n");

        if (!$this->sendData($msg_data)) {
            return false;
        }

        if (!$this->sendCommand('Data End', '.', 250)) {
            return false;
        }

        return true;
    }

    public function hello($host = '')
    {
        return $this->sendHello('EHLO', $host) || $this->sendHello('HELO', $host);
    }

    protected function sendHello($type, $host)
    {
        $noerror = $this->sendCommand($type, $type . ' ' . $host, [250]);
        $this->helo_rply = $this->last_reply;

        if ($noerror && $this->parseHelloFields($type)) {
            return true;
        }

        return false;
    }

    protected function parseHelloFields($type)
    {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $fields = explode(' ', $line);
            if (!empty($fields)) {
                if ($type === 'HELO') {
                    $this->server_caps['HELO'] = $fields[0];
                } else {
                    $this->server_caps[$fields[0]] = array_slice($fields, 1);
                    if ($fields[0] === 'AUTH') {
                        $this->server_caps['AUTH'] = array_slice($fields, 1);
                    }
                }
            }
        }

        return true;
    }

    public function mail($from)
    {
        $useVerp = ($this->do_verp ? ' XVERP' : '');
        return $this->sendCommand(
            'MAIL FROM',
            'MAIL FROM:<' . $from . '>' . $useVerp,
            [250]
        );
    }

    public function quit()
    {
        return $this->sendCommand('QUIT', 'QUIT', 221);
    }

    public function recipient($address)
    {
        return $this->sendCommand(
            'RCPT TO',
            'RCPT TO:<' . $address . '>',
            [250, 251]
        );
    }

    public function reset()
    {
        return $this->sendCommand('RSET', 'RSET', 250);
    }

    protected function sendCommand($command, $commandstring, $expect)
    {
        if (!$this->connected()) {
            $this->setError("Called $command without being connected");
            return false;
        }

        if (is_array($expect)) {
            $expect = array_map('strval', $expect);
        } else {
            $expect = [(string) $expect];
        }

        if (false === $this->sendData($commandstring . self::LE)) {
            $this->setError("Failed to send $command");
            return false;
        }

        $reply = $this->get_lines();
        $this->setLastReply($reply);

        $code = $this->getCodeFromReply($reply);
        $detail = $this->getDetailFromReply($reply);

        if (!in_array($code, $expect, true)) {
            $this->setError(
                "$command command failed",
                $detail,
                $code,
                $this->getExtendedCode($reply)
            );
            return false;
        }

        $this->error = [
            'error' => '',
            'detail' => '',
            'smtp_code' => '',
            'smtp_code_ex' => '',
        ];

        return true;
    }

    protected function sendData($data)
    {
        if ($this->connected()) {
            return (bool) fwrite($this->smtp_conn, $data);
        }
        return false;
    }

    protected function get_lines()
    {
        if (!$this->connected()) {
            return '';
        }

        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->timeout);

        if ($this->timelimit > 0) {
            $endtime = time() + $this->timelimit;
        }

        $selR = [$this->smtp_conn];
        $selW = null;

        while (true) {
            if ($endtime && time() > $endtime) {
                $this->setError('Timeout reached while reading server response');
                break;
            }

            if (false === stream_select($selR, $selW, $selW, $this->timelimit)) {
                $this->setError('Stream select failed');
                break;
            }

            $str = @fgets($this->smtp_conn, 512);
            if ($str === false) {
                $this->setError('Server closed connection unexpectedly');
                break;
            }

            $data .= $str;

            if (substr($str, 3, 1) === ' ' && substr($str, 0, 3) !== '421') {
                break;
            }

            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                $this->setError('Timeout occurred while reading server response');
                break;
            }
        }

        $this->edebug($data, 2);
        return $data;
    }

    public function setDebugOutput($debug_output)
    {
        $this->debug_output = $debug_output;
        return $this;
    }

    public function getDebugOutput()
    {
        return $this->debug_output;
    }

    public function setDebugLevel($level = 0)
    {
        $this->debug_level = $level;
        return $this;
    }

    public function getDebugLevel()
    {
        return $this->debug_level;
    }

    public function setTimeout($timeout = 0)
    {
        $this->timeout = $timeout > 0 ? $timeout : 300;
        if ($this->connected()) {
            stream_set_timeout($this->smtp_conn, $this->timeout);
        }
        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getServerExtList()
    {
        return $this->server_caps;
    }

    public function setVerp($enabled = false)
    {
        $this->do_verp = $enabled;
        return $this;
    }

    public function getVerp()
    {
        return $this->do_verp;
    }

    protected function setError($error, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = [
            'error' => $error,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex,
        ];
        $this->edebug($this->formatError(), 1);
    }

    protected function formatError()
    {
        $error = $this->error['error'];
        $detail = $this->error['detail'];
        $smtp_code = $this->error['smtp_code'];
        $smtp_code_ex = $this->error['smtp_code_ex'];

        $output = "SMTP Error: $error";
        if (!empty($detail)) {
            $output .= " - $detail";
        }
        if (!empty($smtp_code)) {
            $output .= " (Code: $smtp_code";
            if (!empty($smtp_code_ex)) {
                $output .= ", Extended: $smtp_code_ex";
            }
            $output .= ")";
        }
        return $output;
    }

    protected function edebug($str, $level = 0)
    {
        if ($this->debug_level < $level) {
            return;
        }

        if (is_callable($this->debug_output)) {
            call_user_func($this->debug_output, $str);
            return;
        }

        if ($this->debug_output === 'error_log') {
            error_log($str);
        } elseif ($this->debug_output === 'html') {
            echo htmlentities(
                preg_replace('/[\r\n]+/', '', $str),
                ENT_QUOTES,
                'UTF-8'
            ), "<br>\n";
        } else {
            echo $str;
        }
    }

    protected function getCodeFromReply($reply)
    {
        $lines = explode("\n", $reply);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && preg_match('/^(\d{3})/', $line, $matches)) {
                return $matches[1];
            }
        }
        return '';
    }

    protected function getDetailFromReply($reply)
    {
        $lines = explode("\n", $reply);
        $detail = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $detail .= $line . "\n";
            }
        }
        return rtrim($detail);
    }

    protected function getExtendedCode($reply)
    {
        $lines = explode("\n", $reply);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^\d{3}\s+(\d+\.\d+\.\d+)/', $line, $matches)) {
                return $matches[1];
            }
        }
        return '';
    }

    protected function setLastReply($reply)
    {
        $this->last_reply = $reply;
        $this->edebug($reply, 2);
    }

    protected function getAuthType($authtype, $auth_methods)
    {
        if (null === $authtype || !is_string($authtype)) {
            if (in_array($this->LOGIN, $auth_methods, true)) {
                return $this->LOGIN;
            }
            if (in_array($this->PLAIN, $auth_methods, true)) {
                return $this->PLAIN;
            }
            if (in_array($this->CRAM_MD5, $auth_methods, true)) {
                return $this->CRAM_MD5;
            }
            if (in_array($this->XOAUTH2, $auth_methods, true)) {
                return $this->XOAUTH2;
            }
            return '';
        }
        return strtoupper($authtype);
    }
}
?>