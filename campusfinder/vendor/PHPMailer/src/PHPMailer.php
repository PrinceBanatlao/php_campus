<?php
/**
 * PHPMailer - PHP email creation and transport class.
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

use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * PHPMailer - PHP email creation and transport class.
 * @package PHPMailer
 */
class PHPMailer
{
    const CHARSET_ASCII = 'us-ascii';
    const CHARSET_ISO88591 = 'iso-8859-1';
    const CHARSET_UTF8 = 'utf-8';

    const CONTENT_TYPE_PLAINTEXT = 'text/plain';
    const CONTENT_TYPE_TEXT_CALENDAR = 'text/calendar';
    const CONTENT_TYPE_TEXT_HTML = 'text/html';
    const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    const CONTENT_TYPE_MULTIPART_MIXED = 'multipart/mixed';
    const CONTENT_TYPE_MULTIPART_RELATED = 'multipart/related';

    const ENCODING_7BIT = '7bit';
    const ENCODING_8BIT = '8bit';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    public $Priority;

    public $CharSet = self::CHARSET_ISO88591;

    public $ContentType = self::CONTENT_TYPE_PLAINTEXT;

    public $Encoding = self::ENCODING_8BIT;

    public $ErrorInfo = '';

    public $From = '';

    public $FromName = '';

    public $Sender = '';

    public $Subject = '';

    public $Body = '';

    public $AltBody = '';

    public $Ical = '';

    protected $MIMEBody = '';

    protected $MIMEHeader = '';

    protected $mailHeader = '';

    public $WordWrap = 0;

    public $Mailer = 'mail';

    public $Sendmail = '/usr/sbin/sendmail';

    public $UseSendmailOptions = true;

    public $PluginDir = '';

    public $ConfirmReadingTo = '';

    public $Hostname = '';

    public $MessageID = '';

    public $MessageDate = '';

    public $Host = 'localhost';

    public $Port = 25;

    public $Helo = '';

    public $SMTPSecure = '';

    public $SMTPAuth = false;

    public $Username = '';

    public $Password = '';

    public $AuthType = '';

    public $OAuth;

    public $Timeout = 300;

    public $SMTPDebug = 0;

    public $Debugoutput = 'echo';

    public $SMTPKeepAlive = false;

    public $SingleTo = false;

    protected $SingleToArray = [];

    public $do_verp = false;

    public $AllowEmpty = false;

    public $DKIM_selector = '';

    public $DKIM_identity = '';

    public $DKIM_passphrase = '';

    public $DKIM_domain = '';

    public $DKIM_copyHeaderFields = true;

    public $DKIM_extraHeaders = [];

    public $DKIM_private = '';

    public $DKIM_private_string;

    public $action_function = '';

    public $XMailer = '';

    public static $validator = 'php';

    protected $smtp;

    protected $to = [];

    protected $cc = [];

    protected $bcc = [];

    protected $ReplyTo = [];

    protected $all_recipients = [];

    protected $RecipientsQueue = [];

    protected $ReplyToQueue = [];

    protected $attachment = [];

    protected $CustomHeader = [];

    protected $lastMessageID = '';

    protected $message_type = '';

    protected $boundary = [];

    protected $language = [];

    protected $error_count = 0;

    protected $sign_cert_file = '';

    protected $sign_key_file = '';

    protected $sign_extracerts_file = '';

    protected $sign_key_pass = '';

    protected $exceptions = false;

    protected $uniqueid = '';

    const STOP_MESSAGE = 0;
    const STOP_CONTINUE = 1;
    const STOP_CRITICAL = 2;

    const MAX_LINE_LENGTH = 998;

    public function __construct($exceptions = null)
    {
        if (null !== $exceptions) {
            $this->exceptions = (bool) $exceptions;
        }
    }

    public function __destruct()
    {
        $this->smtpClose();
    }

    private function mailPassthru($to, $subject, $body, $header, $params)
    {
        if (ini_get('safe_mode') || !($this->UseSendmailOptions)) {
            $rt = @mail($to, $this->encodeHeader($this->secureHeader($subject)), $body, $header);
        } else {
            $rt = @mail($to, $this->encodeHeader($this->secureHeader($subject)), $body, $header, $params);
        }
        return $rt;
    }

    private function edebug($str)
    {
        if ($this->SMTPDebug <= 0) {
            return;
        }
        if (is_callable($this->Debugoutput)) {
            call_user_func($this->Debugoutput, $str);
            return;
        }
        if ($this->Debugoutput === 'error_log') {
            error_log($str);
        } elseif (!in_array($this->Debugoutput, ['error_log', 'html', 'echo']) && is_callable($this->Debugoutput)) {
            call_user_func($this->Debugoutput, $str);
        } else {
            if ($this->Debugoutput === 'html') {
                echo htmlentities(
                    preg_replace('/[\r\n]+/', '', $str),
                    ENT_QUOTES,
                    'UTF-8'
                ), "<br>\n";
            } else {
                echo $str;
            }
        }
    }

    public function isHTML($isHtml = true)
    {
        if ($isHtml) {
            $this->ContentType = static::CONTENT_TYPE_TEXT_HTML;
        } else {
            $this->ContentType = static::CONTENT_TYPE_PLAINTEXT;
        }
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function isMail()
    {
        $this->Mailer = 'mail';
    }

    public function isSendmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');

        if (false === stripos($ini_sendmail_path, 'sendmail')) {
            $this->Sendmail = '/usr/sbin/sendmail';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'sendmail';
    }

    public function isQmail()
    {
        $ini_sendmail_path = ini_get('sendmail_path');

        if (false === stripos($ini_sendmail_path, 'qmail')) {
            $this->Sendmail = '/var/qmail/bin/qmail-inject';
        } else {
            $this->Sendmail = $ini_sendmail_path;
        }
        $this->Mailer = 'qmail';
    }

    public function addAddress($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('to', $address, $name);
    }

    public function addCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('cc', $address, $name);
    }

    public function addBCC($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('bcc', $address, $name);
    }

    public function addReplyTo($address, $name = '')
    {
        return $this->addOrEnqueueAnAddress('Reply-To', $address, $name);
    }

    protected function addOrEnqueueAnAddress($kind, $address, $name)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));

        if (!$this->validateAddress($address)) {
            $this->setError($this->lang('invalid_address') . ": $address");
            $this->edebug($this->lang('invalid_address') . ": $address");
            if ($this->exceptions) {
                throw new Exception($this->lang('invalid_address') . ": $address");
            }
            return false;
        }

        $params = [$kind, $address, $name];

        if ($this->hasMultiBytes($address)) {
            $formatted = $this->addrFormat([$address, $name]);
            $this->RecipientsQueue[md5($formatted)] = $params;
        } else {
            if ($kind === 'Reply-To') {
                if (!array_key_exists($address, $this->ReplyTo)) {
                    $this->ReplyTo[$address] = [$address, $name];
                }
            } else {
                if (!array_key_exists($address, $this->$kind)) {
                    $this->$kind[$address] = [$address, $name];
                    $this->all_recipients[strtolower($address)] = true;
                }
            }
        }
        return true;
    }

    public function addAnAttachment($path, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment')
    {
        try {
            if (!@is_file($path)) {
                throw new Exception($this->lang('file_access') . $path, self::STOP_CONTINUE);
            }

            $filename = @basename($path);
            if ('' === $name) {
                $name = $filename;
            }

            $this->attachment[] = [
                0 => $path,
                1 => $filename,
                2 => $name,
                3 => $encoding,
                4 => $type,
                5 => false,
                6 => $disposition,
                7 => $name,
            ];
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            $this->edebug($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return true;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $address = trim($address);
        $name = trim(preg_replace('/[\r\n]+/', '', $name));

        if (!$this->validateAddress($address)) {
            $this->setError($this->lang('invalid_address') . ": $address");
            $this->edebug($this->lang('invalid_address') . ": $address");
            if ($this->exceptions) {
                throw new Exception($this->lang('invalid_address') . ": $address");
            }
            return false;
        }

        $this->From = $address;
        $this->FromName = $name;
        if ($auto && empty($this->Sender)) {
            $this->Sender = $address;
        }
        return true;
    }

    public function getLastMessageID()
    {
        return $this->lastMessageID;
    }

    public static function validateAddress($address, $patternselect = null)
    {
        if (null === $patternselect) {
            $patternselect = self::$validator;
        }

        if (is_callable($patternselect)) {
            return call_user_func($patternselect, $address);
        }

        if (false === stripos($patternselect, 'pcre')) {
            $patternselect = 'php';
        } else {
            $patternselect = strtolower($patternselect);
        }

        switch ($patternselect) {
            case 'pcre':
            case 'pcre8':
                return (bool) preg_match(
                    '/^(?!(?>(?1)"?(?>\\\[ -~]|[^"])"?)*$)' .
                    '(?:[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@' .
                    '(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])$/i',
                    $address
                );
            case 'html5':
                return (bool) preg_match(
                    '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/i',
                    $address
                );
            case 'php':
            default:
                return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
        }
    }

    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $e) {
            $this->mailHeader = '';
            $this->setError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

    public function preSend()
    {
        try {
            $this->error_count = 0;
            $this->mailHeader = '';

            if ((count($this->to) + count($this->cc) + count($this->bcc)) < 1) {
                throw new Exception($this->lang('provide_address'), self::STOP_CRITICAL);
            }

            if (!empty($this->AltBody) && empty($this->Body)) {
                throw new Exception($this->lang('empty_altbody'), self::STOP_CRITICAL);
            }

            foreach (['From', 'Sender', 'ConfirmReadingTo', 'ReturnPath'] as $address_kind) {
                if (!empty($this->$address_kind)) {
                    if (!$this->validateAddress($this->$address_kind)) {
                        $error_message = sprintf(
                            '%s (%s): %s',
                            $this->lang('invalid_address'),
                            $address_kind,
                            $this->$address_kind
                        );
                        $this->setError($error_message);
                        $this->edebug($error_message);
                        if ($this->exceptions) {
                            throw new Exception($error_message);
                        }
                        return false;
                    }
                }
            }

            if ($this->idnSupported() && $this->hasMultiBytes($this->From)) {
                $this->From = $this->punyencodeAddress($this->From);
            }
            if (empty($this->Sender) && !empty($this->From)) {
                $this->Sender = $this->From;
            }

            $this->MIMEHeader = '';
            $this->MIMEBody = '';

            $this->setMessageType();

            $this->MIMEHeader = $this->createHeader();
            $this->MIMEBody = $this->createBody();

            if ($this->Mailer === 'mail' && !empty($this->action_function) && is_callable($this->action_function)) {
                $this->mailHeader .= $this->headerLine('X-Mailer-Action', call_user_func($this->action_function));
            }

            if (!empty($this->XMailer)) {
                $this->mailHeader .= $this->headerLine('X-Mailer', trim($this->XMailer));
            }

            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

    protected function createHeader()
    {
        $result = '';

        if ($this->MessageDate == '') {
            $this->MessageDate = static::getDateTimeNow();
        }
        $result .= $this->headerLine('Date', $this->MessageDate);

        $this->lastMessageID = $this->generateId();
        $result .= $this->headerLine('Message-ID', $this->lastMessageID);

        if (!empty($this->Sender)) {
            $result .= $this->headerLine('Sender', $this->addrFormat([$this->Sender, '']));
        }

        if (!empty($this->From)) {
            $result .= $this->headerLine('From', $this->addrFormat([$this->From, $this->FromName]));
        }

        foreach ($this->to as $address) {
            $this->all_recipients[strtolower($address[0])] = true;
            $result .= $this->addrAppend('To', [$address]);
        }

        foreach ($this->cc as $address) {
            $this->all_recipients[strtolower($address[0])] = true;
            $result .= $this->addrAppend('Cc', [$address]);
        }

        foreach ($this->bcc as $address) {
            $this->all_recipients[strtolower($address[0])] = true;
        }

        foreach ($this->ReplyTo as $address) {
            $result .= $this->addrAppend('Reply-To', [$address]);
        }

        if (!empty($this->ConfirmReadingTo)) {
            $result .= $this->headerLine('Disposition-Notification-To', $this->addrFormat([$this->ConfirmReadingTo, '']));
        }

        foreach ($this->CustomHeader as $header) {
            $result .= $this->headerLine(
                trim($header[0]),
                $this->encodeHeader(trim($header[1]))
            );
        }

        if (!$this->sign_key_file) {
            $result .= $this->headerLine('MIME-Version', '1.0');
            $result .= $this->getMailMIME();
        }

        return $result;
    }

    protected function createBody()
    {
        $body = '';
        $this->uniqueid = $this->generateId();
        $this->boundary[1] = 'b1_' . $this->uniqueid;
        $this->boundary[2] = 'b2_' . $this->uniqueid;
        $this->boundary[3] = 'b3_' . $this->uniqueid;

        if ($this->sign_key_file) {
            $body .= $this->getMailMIME() . $this->LE;
        }

        $this->setWordWrap();

        $bodyEncoding = $this->Encoding;
        $bodyCharSet = $this->CharSet;

        if ($bodyEncoding === self::ENCODING_8BIT && !$this->hasMultiBytes($this->Body)) {
            $bodyEncoding = self::ENCODING_7BIT;
        }

        if ($this->hasMultiBytes($this->Body)) {
            $bodyCharSet = self::CHARSET_UTF8;
        }

        $altBodyEncoding = $this->Encoding;
        $altBodyCharSet = $this->CharSet;

        if (!empty($this->AltBody)) {
            if ($altBodyEncoding === self::ENCODING_8BIT && !$this->hasMultiBytes($this->AltBody)) {
                $altBodyEncoding = self::ENCODING_7BIT;
            }
            if ($this->hasMultiBytes($this->AltBody)) {
                $altBodyCharSet = self::CHARSET_UTF8;
            }
        }

        switch ($this->message_type) {
            case 'inline':
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, self::CONTENT_TYPE_MULTIPART_RELATED, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->attachAll('inline', $this->boundary[1]);
                break;
            case 'attach':
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, self::CONTENT_TYPE_MULTIPART_MIXED, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'inline_attach':
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' charset="' . $bodyCharSet . '"');
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, self::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->attachAll('inline', $this->boundary[2]);
                $body .= $this->LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'alt':
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, self::CONTENT_TYPE_MULTIPART_ALTERNATIVE, $bodyEncoding);
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, self::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[1] . '--');
                break;
            case 'alt_inline':
                $body .= $this->getBoundary($this->boundary[1], $bodyCharSet, self::CONTENT_TYPE_MULTIPART_ALTERNATIVE, $bodyEncoding);
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' charset="' . $bodyCharSet . '"');
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, self::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->attachAll('inline', $this->boundary[2]);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[1] . '--');
                break;
            case 'alt_attach':
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' charset="' . $bodyCharSet . '"');
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[2], $altBodyCharSet, self::CONTENT_TYPE_PLAINTEXT, $altBodyEncoding);
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[2], $bodyCharSet, self::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[2] . '--');
                $body .= $this->LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            case 'alt_inline_attach':
                $body .= $this->textLine('--' . $this->boundary[1]);
                $body .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[2] . '";');
                $body .= $this->textLine(' charset="' . $bodyCharSet . '"');
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[2], $altBodyCharSet, self::CONTENT_TYPE_PLAINTEXT, $altBodyEncoding);
                $body .= $this->encodeString($this->AltBody, $altBodyEncoding);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[2]);
                $body .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $body .= $this->textLine(' boundary="' . $this->boundary[3] . '";');
                $body .= $this->textLine(' charset="' . $bodyCharSet . '"');
                $body .= $this->LE;
                $body .= $this->getBoundary($this->boundary[3], $bodyCharSet, self::CONTENT_TYPE_TEXT_HTML, $bodyEncoding);
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                $body .= $this->LE;
                $body .= $this->attachAll('inline', $this->boundary[3]);
                $body .= $this->LE;
                $body .= $this->textLine('--' . $this->boundary[2] . '--');
                $body .= $this->LE;
                $body .= $this->attachAll('attachment', $this->boundary[1]);
                break;
            default:
                $body .= $this->encodeString($this->Body, $bodyEncoding);
                break;
        }

        if ($this->sign_key_file) {
            $file_to_sign = @tempnam(sys_get_temp_dir(), 'mail');
            if (false === @file_put_contents($file_to_sign, $body)) {
                throw new Exception($this->lang('signing') . ' Could not write temp file');
            }

            $signed = @tempnam(sys_get_temp_dir(), 'signed');
            if (false === $signed) {
                throw new Exception($this->lang('signing') . ' Could not create temp file');
            }

            $signOptions = [
                'private_key_bits' => OPENSSL_KEYTYPE_RSA,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'encrypt_key' => false,
            ];

            if (@openssl_pkcs7_sign(
                $file_to_sign,
                $signed,
                'file://' . realpath($this->sign_cert_file),
                ['file://' . realpath($this->sign_key_file), $this->sign_key_pass],
                [],
                PKCS7_DETACHED,
                $this->sign_extracerts_file
            )) {
                @unlink($file_to_sign);
                $body = @file_get_contents($signed);
                @unlink($signed);
                $body = str_replace("\n.\n", "\n..\n", $body);
            } else {
                @unlink($file_to_sign);
                @unlink($signed);
                throw new Exception($this->lang('signing') . openssl_error_string());
            }
        }

        return $body;
    }

    protected function getBoundary($boundary, $charSet, $contentType, $encoding)
    {
        $result = '';
        $result .= $this->textLine('--' . $boundary);
        $result .= $this->headerLine('Content-Type', $contentType . '; charset="' . $charSet . '"');
        $result .= $this->LE;
        $result .= $this->headerLine('Content-Transfer-Encoding', $encoding);
        $result .= $this->LE;

        return $result;
    }

    protected function attachAll($disposition_type, $boundary)
    {
        $mime = [];
        $cidUniq = [];
        $incl = [];

        foreach ($this->attachment as $attachment) {
            if ($attachment[6] === $disposition_type) {
                $string = '';
                $path = $attachment[0];
                $inclhash = hash('sha256', serialize($attachment));
                if (in_array($inclhash, $incl)) {
                    continue;
                }
                $incl[] = $inclhash;
                $name = $attachment[2];
                $encoding = $attachment[3];
                $type = $attachment[4];
                $disposition = $attachment[6];
                $cid = $attachment[7];
                if ('inline' === $disposition && array_key_exists($cid, $cidUniq)) {
                    continue;
                }
                $cidUniq[$cid] = true;

                $mime[] = sprintf('--%s%s', $boundary, $this->LE);

                if (!empty($name)) {
                    $mime[] = sprintf(
                        'Content-Type: %s; name="%s"%s',
                        $type,
                        $this->encodeHeader($this->secureHeader($name)),
                        $this->LE
                    );
                } else {
                    $mime[] = sprintf(
                        'Content-Type: %s%s',
                        $type,
                        $this->LE
                    );
                }

                $mime[] = sprintf('Content-Transfer-Encoding: %s%s', $encoding, $this->LE);

                if ('inline' === $disposition) {
                    $mime[] = sprintf('Content-ID: <%s>%s', $cid, $this->LE);
                }

                $mime[] = sprintf(
                    'Content-Disposition: %s; filename="%s"%s',
                    $disposition,
                    $this->encodeHeader($this->secureHeader($name)),
                    $this->LE . $this->LE
                );

                if ($attachment[5]) {
                    $string .= $attachment[0];
                } else {
                    $data = @file_get_contents($path);
                    if (false === $data) {
                        $this->setError($this->lang('file_open') . $path);
                        return '';
                    }
                    $string .= $data;
                }

                switch ($encoding) {
                    case self::ENCODING_BASE64:
                        $string = chunk_split(base64_encode($string), 76, $this->LE);
                        break;
                    case self::ENCODING_QUOTED_PRINTABLE:
                        $string = $this->encodeQP($string);
                        break;
                    default:
                        $string = $this->encodeString($string, $encoding);
                        break;
                }

                $mime[] = $string;
                $mime[] = $this->LE;
            }
        }

        $mime[] = sprintf('--%s--%s', $boundary, $this->LE);

        return implode('', $mime);
    }

    protected function postSend()
    {
        try {
            switch ($this->Mailer) {
                case 'sendmail':
                case 'qmail':
                    return $this->sendmailSend($this->MIMEHeader, $this->MIMEBody);
                case 'smtp':
                    return $this->smtpSend($this->MIMEHeader, $this->MIMEBody);
                case 'mail':
                    return $this->mailSend($this->MIMEHeader, $this->MIMEBody);
                default:
                    $sendMethod = $this->Mailer . 'Send';
                    if (method_exists($this, $sendMethod)) {
                        return $this->$sendMethod($this->MIMEHeader, $this->MIMEBody);
                    }

                    throw new Exception($this->lang('mailer_not_supported') . ' ' . $this->Mailer);
            }
        } catch (Exception $e) {
            $this->mailHeader = '';
            $this->setError($e->getMessage());
            $this->edebug($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

    protected function sendmailSend($header, $body)
    {
        $header = rtrim($header, "\r\n") . $this->LE;

        if (!empty($this->Sender)) {
            if ($this->Mailer === 'qmail') {
                $sendmail = sprintf('%s -f%s', escapeshellcmd($this->Sendmail), escapeshellarg($this->Sender));
            } else {
                $sendmail = sprintf('%s -oi -f%s -t', escapeshellcmd($this->Sendmail), escapeshellarg($this->Sender));
            }
        } else {
            if ($this->Mailer === 'qmail') {
                $sendmail = sprintf('%s', escapeshellcmd($this->Sendmail));
            } else {
                $sendmail = sprintf('%s -oi -t', escapeshellcmd($this->Sendmail));
            }
        }

        if ($this->SingleTo) {
            foreach ($this->SingleToArray as $toAddr) {
                $mail = @popen($sendmail, 'w');
                if (!$mail) {
                    throw new Exception($this->lang('execute') . $this->Sendmail);
                }
                fwrite($mail, 'To: ' . $toAddr . "\n");
                fwrite($mail, $header);
                fwrite($mail, $body);
                $result = pclose($mail);
                $this->doCallback(
                    ($result === 0),
                    [$toAddr],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
                if (0 !== $result) {
                    throw new Exception($this->lang('execute') . $this->Sendmail);
                }
            }
        } else {
            $mail = @popen($sendmail, 'w');
            if (!$mail) {
                throw new Exception($this->lang('execute') . $this->Sendmail);
            }
            fwrite($mail, $header);
            fwrite($mail, $body);
            $result = pclose($mail);
            $this->doCallback(
                ($result === 0),
                $this->to,
                $this->cc,
                $this->bcc,
                $this->Subject,
                $body,
                $this->From,
                []
            );
            if (0 !== $result) {
                throw new Exception($this->lang('execute') . $this->Sendmail);
            }
        }
        return true;
    }

    protected function mailSend($header, $body)
    {
        $header = rtrim($header, "\r\n") . $this->LE;
        $toArr = [];
        foreach ($this->to as $to) {
            $toArr[] = $this->addrFormat($to);
        }
        $to = implode(', ', $toArr);

        $params = null;

        if (!empty($this->Sender) && $this->validateAddress($this->Sender)) {
            if ($this->Sender !== $this->From) {
                $params = sprintf('-f%s', $this->Sender);
            }
        }

        if ($this->SingleTo) {
            foreach ($this->SingleToArray as $toAddr) {
                $rt = $this->mailPassthru($toAddr, $this->Subject, $body, $header, $params);
                $this->doCallback(
                    $rt,
                    [$toAddr],
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
            }
        } else {
            $rt = $this->mailPassthru($to, $this->Subject, $body, $header, $params);
            $this->doCallback(
                $rt,
                $this->to,
                $this->cc,
                $this->bcc,
                $this->Subject,
                $body,
                $this->From,
                []
            );
        }

        return $rt;
    }

    protected function smtpSend($header, $body)
    {
        $header = rtrim($header, "\r\n") . $this->LE;
        $bad_rcpt = [];

        if (!$this->smtpConnect()) {
            throw new Exception($this->lang('smtp_connect_failed'));
        }

        if (!empty($this->Sender) && $this->validateAddress($this->Sender)) {
            $smtp_from = $this->Sender;
        } else {
            $smtp_from = $this->From;
        }

        if (!$this->smtp->mail($smtp_from)) {
            throw new Exception($this->lang('from_failed') . $smtp_from);
        }

        $callbacks = [];
        foreach ([$this->to, $this->cc, $this->bcc] as $togroup) {
            foreach ($togroup as $to) {
                if (!$this->smtp->recipient($to[0])) {
                    $bad_rcpt[] = $to[0];
                } else {
                    $callbacks[] = $to[0];
                }
            }
        }

        if (count($this->all_recipients) > count($bad_rcpt)) {
            if (!$this->smtp->data($header . $body)) {
                throw new Exception($this->lang('data_not_accepted'));
            }

            if ($this->SMTPKeepAlive) {
                $this->smtp->reset();
            } else {
                $this->smtp->quit();
                $this->smtp->close();
            }

            if (count($bad_rcpt) > 0) {
                $this->setError($this->lang('recipients_failed') . implode(', ', $bad_rcpt));
                $this->doCallback(
                    false,
                    $callbacks,
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    $bad_rcpt
                );
            } else {
                $this->doCallback(
                    true,
                    $callbacks,
                    $this->cc,
                    $this->bcc,
                    $this->Subject,
                    $body,
                    $this->From,
                    []
                );
            }
        } else {
            throw new Exception($this->lang('recipients_failed') . implode(', ', $bad_rcpt));
        }

        return true;
    }

    public function smtpConnect($options = null)
    {
        if (null === $this->smtp) {
            $this->smtp = $this->getSMTPInstance();
        }

        if ($this->smtp->connected()) {
            return true;
        }

        $this->smtp->setTimeout($this->Timeout);
        $this->smtp->setDebugLevel($this->SMTPDebug);
        $this->smtp->setDebugOutput($this->Debugoutput);
        $this->smtp->setVerp($this->do_verp);

        if ($this->Host === '') {
            $this->Host = 'localhost';
        }

        $hosts = explode(';', $this->Host);
        $ports = $this->Port ? explode(';', $this->Port) : [];
        $index = 0;

        while ($index < count($hosts)) {
            $host = trim($hosts[$index]);
            $port = isset($ports[$index]) ? (int)$ports[$index] : $this->Port;

            $tls = ($this->SMTPSecure === 'tls');
            $ssl = ($this->SMTPSecure === 'ssl');

            if ($this->smtp->connect($host, $port, $this->Timeout, $options)) {
                try {
                    if ($this->Helo) {
                        $hello = $this->Helo;
                    } else {
                        $hello = $this->serverHostname();
                    }

                    $this->smtp->hello($hello);

                    if ($tls) {
                        if (!$this->smtp->startTLS()) {
                            throw new Exception($this->lang('connect_host'));
                        }
                        $this->smtp->hello($hello);
                    } elseif ($ssl) {
                        $this->smtp->hello($hello);
                    }

                    if ($this->SMTPAuth && !$this->smtp->authenticate(
                        $this->Username,
                        $this->Password,
                        $this->AuthType,
                        $this->OAuth
                    )) {
                        throw new Exception($this->lang('authenticate'));
                    }

                    return true;
                } catch (Exception $e) {
                    $this->smtp->reset();
                    $this->setError($e->getMessage());
                    $this->edebug($e->getMessage());
                    if ($this->exceptions) {
                        throw $e;
                    }
                }
            }
            $this->smtp->close();
            ++$index;
        }

        $this->setError($this->lang('connect_host'));
        $this->edebug($this->lang('connect_host'));
        if ($this->exceptions) {
            throw new Exception($this->lang('connect_host'));
        }

        return false;
    }

    public function smtpClose()
    {
        if ((null !== $this->smtp) && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
    }

    public function setLanguage($langcode = 'en', $lang_path = '')
    {
        $langcode = strtolower($langcode);
        $valid_codes = [
            'br', 'ca', 'cz', 'de', 'dk', 'en', 'es', 'fr', 'hu', 'it',
            'ja', 'nl', 'no', 'pl', 'pt', 'ru', 'se', 'tr', 'zh', 'zh_cn',
        ];

        if (!in_array($langcode, $valid_codes)) {
            $this->language = [];
            return false;
        }

        if ('' === $lang_path) {
            $lang_path = __DIR__ . '/language/';
        }

        $lang_file = $lang_path . 'phpmailer.lang-' . $langcode . '.php';

        if (!@is_file($lang_file)) {
            $this->language = [];
            return false;
        }

        $this->language = include $lang_file;

        return true;
    }

    public function lang($key)
    {
        $lang_keys = [
            'authenticate' => 'SMTP Error: Could not authenticate.',
            'connect_host' => 'SMTP Error: Could not connect to SMTP host.',
            'data_not_accepted' => 'SMTP Error: data not accepted.',
            'empty_altbody' => 'Alternative body cannot be empty if main body is empty.',
            'encoding' => 'Unknown encoding: ',
            'execute' => 'Could not execute: ',
            'file_access' => 'Could not access file: ',
            'file_open' => 'File Error: Could not open file: ',
            'from_failed' => 'The following From address failed: ',
            'instantiate' => 'Could not instantiate mail function.',
            'invalid_address' => 'Invalid address: ',
            'mailer_not_supported' => ' mailer is not supported.',
            'provide_address' => 'You must provide at least one recipient email address.',
            'recipients_failed' => 'SMTP Error: The following recipients failed: ',
            'signing' => 'Signing Error: ',
            'smtp_connect_failed' => 'SMTP connect() failed.',
            'smtp_error' => 'SMTP server error: ',
            'variable_set' => 'Cannot set or reset variable: ',
            'extension_missing' => 'Extension missing: ',
        ];

        return array_key_exists($key, $this->language) ? $this->language[$key] : (array_key_exists($key, $lang_keys) ? $lang_keys[$key] : $key);
    }

    public function setError($msg)
    {
        ++$this->error_count;
        $this->ErrorInfo = $msg;
    }

    public function getError()
    {
        return ['error' => $this->ErrorInfo, 'error_count' => $this->error_count];
    }

    public function getSMTPInstance()
    {
        if (!is_object($this->smtp)) {
            $this->smtp = new SMTP();
        }
        return $this->smtp;
    }

    public function serverHostname()
    {
        if (!empty($this->Hostname)) {
            $result = $this->Hostname;
        } elseif (isset($_SERVER) && array_key_exists('SERVER_NAME', $_SERVER)) {
            $result = $_SERVER['SERVER_NAME'];
        } elseif (function_exists('gethostname') && gethostname() !== false) {
            $result = gethostname();
        } elseif (php_uname('n') !== false) {
            $result = php_uname('n');
        } else {
            $result = 'localhost.localdomain';
        }
        return $result;
    }

    protected function encodeString($str, $encoding = self::ENCODING_BASE64)
    {
        switch (strtolower($encoding)) {
            case self::ENCODING_BASE64:
                return chunk_split(
                    base64_encode($str),
                    self::MAX_LINE_LENGTH,
                    $this->LE
                );
            case self::ENCODING_7BIT:
            case self::ENCODING_8BIT:
                return $this->fixEOL($str);
            case self::ENCODING_QUOTED_PRINTABLE:
                return $this->encodeQP($str);
            case self::ENCODING_BINARY:
                return $str;
            default:
                return $str;
        }
    }

    protected function encodeHeader($str, $position = 'text')
    {
        $matchcount = 0;
        switch (strtolower($position)) {
            case 'phrase':
                if (!preg_match('/[\200-\377]/', $str)) {
                    $encoded = addcslashes($str, "\0..\37\177\\\"");
                    return ($encoded === $str) ? $str : sprintf('"%s"', $encoded);
                }
                $matchcount = preg_match_all('/[^\040\041\043-\133\135-\176]/', $str, $matches);
                break;
            case 'comment':
                $matchcount = preg_match_all('/[()"]/', $str, $matches);
            case 'text':
            default:
                $matchcount += preg_match_all('/[\000-\010\013\014\016-\037\177-\377]/', $str, $matches);
                break;
        }

        if ($this->hasMultiBytes($str)) {
            $b = true;
        } else {
            $b = false;
            if ($matchcount > 0) {
                foreach ($matches[0] as $m) {
                    if (ord($m) > 127) {
                        $b = true;
                        break;
                    }
                }
            }
        }

        if ($b) {
            $encoded = $this->encodeQ($str, $position);
            return '=?UTF-8?Q?' . $encoded . '?=';
        }

        if ($matchcount > 0) {
            return preg_replace_callback(
                '/[\000-\010\013\014\016-\037\177-\377]/',
                function ($matches) {
                    return sprintf('=%02X', ord($matches[0]));
                },
                $str
            );
        }

        return $str;
    }

    protected function encodeQ($str, $position = 'text')
    {
        $encoded = preg_replace('/[\r\n]*/', '', $str);
        switch (strtolower($position)) {
            case 'phrase':
                $encoded = preg_replace_callback(
                    '/([ !"#-(*+-\/<-[\]-~])/',
                    function ($m) {
                        return sprintf('=%02X', ord($m[1]));
                    },
                    $encoded
                );
                break;
            case 'comment':
                $encoded = preg_replace_callback(
                    '/([ !(-*+-\/<-[\]-~])/',
                    function ($m) {
                        return sprintf('=%02X', ord($m[1]));
                    },
                    $encoded
                );
                break;
            case 'text':
            default:
                $encoded = preg_replace_callback(
                    '/([ !#-(*+-\/<-[\]-~])/',
                    function ($m) {
                        return sprintf('=%02X', ord($m[1]));
                    },
                    $encoded
                );
                break;
        }
        $encoded = str_replace(' ', '_', $encoded);
        return $encoded;
    }

    protected function encodeQP($string)
    {
        return $this->encodeQPphp($string);
    }

    protected function encodeQPphp($string)
    {
        $string = str_replace(
            ['%20', "\r\n", "\n"],
            [' ', $this->LE, $this->LE],
            rawurlencode($string)
        );

        return preg_replace_callback(
            '/([\000-\010\013\014\016-\037\075\177-\377])/',
            function ($m) {
                return sprintf('=%02X', ord($m[1]));
            },
            $string
        );
    }

    protected function setWordWrap()
    {
        if ($this->WordWrap < 1) {
            return;
        }

        switch ($this->message_type) {
            case 'alt':
            case 'alt_inline':
            case 'alt_attach':
            case 'alt_inline_attach':
                $this->AltBody = $this->wrapText($this->AltBody, $this->WordWrap);
                break;
            default:
                $this->Body = $this->wrapText($this->Body, $this->WordWrap);
                break;
        }
    }

    protected function wrapText($message, $length, $qp_mode = false)
    {
        $soft_break = ($qp_mode) ? sprintf(' =%s', $this->LE) : $this->LE;
        $message = $this->fixEOL($message);
        if (substr($message, -1) === $this->LE) {
            $message = substr($message, 0, -1);
        }

        $line = explode($this->LE, $message);
        $message = '';
        foreach ($line as $linecount => $linetext) {
            $line[$linecount] = $this->normalizeBreaks($linetext);
            if ($this->hasMultiBytes($linetext)) {
                $words = $this->splitMultiByteString($linetext);
            } else {
                $words = explode(' ', $linetext);
            }

            $linetext = '';
            $linepart = '';
            foreach ($words as $word) {
                if (strlen($linepart) + strlen($word) + 1 > $length) {
                    if (!empty($linepart)) {
                        $message .= $linepart . $soft_break;
                    }
                    if (strlen($word) > $length) {
                        $subwords = str_split($word, $length - 1);
                        $i = 0;
                        $len = count($subwords);
                        while ($i < $len) {
                            if ($i + 1 < $len) {
                                $message .= $subwords[$i] . $soft_break;
                            } else {
                                $linepart = $subwords[$i];
                            }
                            ++$i;
                        }
                    } else {
                        $linepart = $word;
                    }
                } else {
                    $linepart .= (empty($linepart) ? '' : ' ') . $word;
                }
            }
            $message .= $linepart;
            if ($linecount + 1 < count($line)) {
                $message .= $soft_break;
            }
        }

        return $message;
    }

    protected function splitMultiByteString($str)
    {
        $result = [];
        $len = mb_strlen($str, 'UTF-8');
        for ($i = 0; $i < $len; ++$i) {
            $result[] = mb_substr($str, $i, 1, 'UTF-8');
        }
        return $result;
    }

    protected function headerLine($name, $value)
    {
        return sprintf('%s: %s%s', $name, $value, $this->LE);
    }

    protected function textLine($value)
    {
        return $value . $this->LE;
    }

    protected function addrAppend($type, $addr)
    {
        $addresses = [];
        foreach ($addr as $address) {
            $addresses[] = $this->addrFormat($address);
        }
        return $this->headerLine($type, implode(', ', $addresses));
    }

    protected function addrFormat($addr)
    {
        if (empty($addr[1])) {
            return $this->secureHeader($addr[0]);
        }
        return sprintf(
            '%s <%s>',
            $this->encodeHeader($this->secureHeader($addr[1]), 'phrase'),
            $this->secureHeader($addr[0])
        );
    }

    protected function generateId()
    {
        $mid = sprintf(
            '<%s@%s>',
            md5(uniqid('', true)),
            $this->serverHostname()
        );
        return $mid;
    }

    protected function secureHeader($str)
    {
        return trim(
            str_replace(
                ["\r", "\n"],
                '',
                $str
            )
        );
    }

    protected function getMailMIME()
    {
        $result = '';
        switch ($this->message_type) {
            case 'inline':
                $result .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_RELATED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'attach':
            case 'inline_attach':
            case 'alt_attach':
            case 'alt_inline_attach':
                $result .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_MIXED . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            case 'alt':
            case 'alt_inline':
                $result .= $this->headerLine('Content-Type', self::CONTENT_TYPE_MULTIPART_ALTERNATIVE . ';');
                $result .= $this->textLine(' boundary="' . $this->boundary[1] . '"');
                break;
            default:
                $result .= $this->headerLine('Content-Type', $this->ContentType . '; charset=' . $this->CharSet);
                break;
        }

        if ($this->Encoding !== self::ENCODING_7BIT) {
            $result .= $this->headerLine('Content-Transfer-Encoding', $this->Encoding);
        }

        return $result;
    }

    protected function setMessageType()
    {
        $this->message_type = 'plain';

        if (!empty($this->AltBody)) {
            $this->message_type = 'alt';
        }
        if ($this->hasInlineImage()) {
            $this->message_type .= '_inline';
        }
        if ($this->hasAttachment()) {
            $this->message_type .= '_attach';
        }
    }

    protected function hasInlineImage()
    {
        foreach ($this->attachment as $attachment) {
            if ($attachment[6] === 'inline') {
                return true;
            }
        }
        return false;
    }

    protected function hasAttachment()
    {
        foreach ($this->attachment as $attachment) {
            if ($attachment[6] === 'attachment') {
                return true;
            }
        }
        return false;
    }

    protected function fixEOL($str)
    {
        $nstr = str_replace(["\r\n", "\r"], "\n", $str);
        if ($this->LE !== "\n") {
            $nstr = str_replace("\n", $this->LE, $nstr);
        }
        return $nstr;
    }

    protected function normalizeBreaks($text, $breaktype = null)
    {
        if (null === $breaktype) {
            $breaktype = $this->LE;
        }
        $text = str_replace(["\r\n", "\r", "\n"], $breaktype, $text);
        return preg_replace('/(' . preg_quote($breaktype, '/') . ')+/', $breaktype, $text);
    }

    protected function hasMultiBytes($str)
    {
        if (function_exists('mb_strlen')) {
            return (strlen($str) > mb_strlen($str, 'UTF-8'));
        }
        return false;
    }

    protected function idnSupported()
    {
        return function_exists('idn_to_ascii') && function_exists('idn_to_utf8');
    }

    protected function punyencodeAddress($address)
    {
        if ($this->idnSupported()) {
            if ('@' !== substr($address, -1)) {
                $address .= '@';
            }
            list($local, $domain) = explode('@', $address, 2);
            if ($this->hasMultiBytes($domain)) {
                $domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            }
            return $local . '@' . $domain;
        }
        return $address;
    }

    protected static function getDateTimeNow()
    {
        return date('r');
    }

    public function clearAddresses()
    {
        foreach (['to', 'cc', 'bcc'] as $kind) {
            $this->$kind = [];
        }
        $this->all_recipients = [];
        $this->RecipientsQueue = [];
    }

    public function clearCCs()
    {
        $this->cc = [];
        $this->rebuildAllRecipients();
    }

    public function clearBCCs()
    {
        $this->bcc = [];
        $this->rebuildAllRecipients();
    }

    public function clearReplyTos()
    {
        $this->ReplyTo = [];
        $this->ReplyToQueue = [];
    }

    public function clearAllRecipients()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->all_recipients = [];
        $this->RecipientsQueue = [];
    }

    public function clearAttachments()
    {
        $this->attachment = [];
    }

    public function clearCustomHeaders()
    {
        $this->CustomHeader = [];
    }

    protected function rebuildAllRecipients()
    {
        $this->all_recipients = [];
        foreach (['to', 'cc', 'bcc'] as $kind) {
            foreach ($this->$kind as $address) {
                $this->all_recipients[strtolower($address[0])] = true;
            }
        }
    }

    public function reset()
    {
        $this->clearAllRecipients();
        $this->clearReplyTos();
        $this->clearAttachments();
        $this->clearCustomHeaders();
        $this->Subject = '';
        $this->Body = '';
        $this->AltBody = '';
        $this->Ical = '';
        $this->MIMEBody = '';
        $this->MIMEHeader = '';
        $this->mailHeader = '';
        $this->ErrorInfo = '';
        $this->error_count = 0;
    }

    public function addCustomHeader($name, $value = null)
    {
        if (null === $value && strpos($name, ':') !== false) {
            list($name, $value) = explode(':', $name, 2);
        }
        $this->CustomHeader[] = [trim($name), trim($value)];
    }

    public function getCustomHeaders()
    {
        return $this->CustomHeader;
    }

    public function set($name, $value = '')
    {
        $name = ucfirst($name);
        if (isset($this->$name)) {
            $this->$name = $value;
            return true;
        }
        $this->setError($this->lang('variable_set') . $name);
        if ($this->exceptions) {
            throw new Exception($this->lang('variable_set') . $name);
        }
        return false;
    }

    public function get($name)
    {
        $name = ucfirst($name);
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }

    public function secureText($str)
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function doCallback($isSent, $to, $cc, $bcc, $subject, $body, $from, $bad_rcpt = [])
    {
        if (!empty($this->action_function) && is_callable($this->action_function)) {
            call_user_func(
                $this->action_function,
                $isSent,
                $to,
                $cc,
                $bcc,
                $subject,
                $body,
                $from,
                $bad_rcpt
            );
        }
    }

    public function getAttachments()
    {
        return $this->attachment;
    }

    public function getTranslations()
    {
        return $this->language;
    }

    public function getToAddresses()
    {
        return $this->to;
    }

    public function getCcAddresses()
    {
        return $this->cc;
    }

    public function getBccAddresses()
    {
        return $this->bcc;
    }

    public function getReplyToAddresses()
    {
        return $this->ReplyTo;
    }

    public function getAllRecipientAddresses()
    {
        return $this->all_recipients;
    }

    public static function isValidHost($host)
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return true;
        }
        return false;
    }

    protected function setBoundary($boundary)
    {
        $this->uniqueid = $boundary;
        $this->boundary[1] = 'b1_' . $boundary;
        $this->boundary[2] = 'b2_' . $boundary;
        $this->boundary[3] = 'b3_' . $boundary;
    }

    public static function normalizeBreaksStatic($text, $breaktype = null)
    {
        if (null === $breaktype) {
            $breaktype = "\r\n";
        }
        $text = str_replace(["\r\n", "\r", "\n"], $breaktype, $text);
        return preg_replace('/(' . preg_quote($breaktype, '/') . ')+/', $breaktype, $text);
    }

    public static function parseAddresses($addrstr)
    {
        $result = [];
        $addrstr = trim($addrstr);
        if (empty($addrstr)) {
            return $result;
        }

        $addresses = [];
        $current_addr = '';
        $current_name = '';
        $in_quotes = false;
        $in_angle = false;
        $in_name = false;

        for ($i = 0; $i < strlen($addrstr); ++$i) {
            $char = $addrstr[$i];

            if ($char === '"') {
                $in_quotes = !$in_quotes;
                continue;
            }

            if ($char === '<' && !$in_quotes) {
                $in_angle = true;
                if (!empty($current_name)) {
                    $current_name = trim($current_name);
                }
                continue;
            }

            if ($char === '>' && !$in_quotes) {
                $in_angle = false;
                continue;
            }

            if ($char === ',' && !$in_quotes && !$in_angle) {
                if (!empty($current_addr)) {
                    $addresses[] = [
                        'address' => trim($current_addr),
                        'name' => $current_name,
                    ];
                    $current_addr = '';
                    $current_name = '';
                    $in_name = false;
                }
                continue;
            }

            if ($in_angle) {
                $current_addr .= $char;
            } elseif ($in_quotes || ($char !== ' ' && $char !== "\t")) {
                $current_name .= $char;
                $in_name = true;
            } elseif ($in_name && ($char === ' ' || $char === "\t")) {
                $in_name = false;
            }
        }

        if (!empty($current_addr) || !empty($current_name)) {
            $addresses[] = [
                'address' => trim($current_addr),
                'name' => trim($current_name),
            ];
        }

        foreach ($addresses as $addr) {
            if (static::validateAddress($addr['address'])) {
                $result[] = $addr;
            }
        }

        return $result;
    }

    public static function getLE()
    {
        return "\r\n";
    }

    protected $LE = "\r\n";
}
?>