<?php
	/**
	 * @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 * 
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}

global $wp_version;
if(version_compare($wp_version,'5.5', '>=') ){
    // starting with WP 5.5.
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    if(!class_exists('PHPMailer', false)) {
        class_alias(PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer');
    }
    if(!class_exists('phpmailerException', false)) {
        class_alias(PHPMailer\PHPMailer\Exception::class, 'phpmailerException');
    }
}else{
    // older WP versions
    require_once ABSPATH . WPINC . '/class-phpmailer.php';
}

$idnaConvertFolder = plugin_dir_path( __FILE__ ) . '../lib/idna-convert/';
require_once $idnaConvertFolder . 'EncodingHelper.php';
require_once $idnaConvertFolder . 'NamePrepDataInterface.php';
require_once $idnaConvertFolder . 'NamePrepData.php';
require_once $idnaConvertFolder . 'NamePrepData2003.php';
require_once $idnaConvertFolder . 'UnicodeTranscoderInterface.php';
require_once $idnaConvertFolder . 'UnicodeTranscoder.php';
require_once $idnaConvertFolder . 'PunycodeInterface.php';
require_once $idnaConvertFolder . 'Punycode.php';
require_once $idnaConvertFolder . 'IdnaConvert.php';

class MstMailer extends PHPMailer
{

	/**
	 * @var    array  JMail instances container.
	 * @since  11.3
	 */
	protected static $instances = array();

	/**
	 * @var    string  Charset of the message.
	 * @since  11.1
	 */
	public $CharSet = 'utf-8';

	/**
	 * Constructor
	 *
	 * @since   11.1
	 */
	public function __construct()
	{
		// PHPMailer has an issue using the relative path for its language files
		$this->setLanguage();
	}
	
	/**
	 * Returns the global email object, only creating it
	 * if it doesn't already exist.
	 *
	 * NOTE: If you need an instance to use that does not have the global configuration
	 * values, use an id string that is not 'WordPress'.
	 *
	 * @param   string  $id  The id string for the JMail instance [optional]
	 *
	 * @return  MstMailer  The global JMail object
	 *
	 * @since   11.1
	 */
	public static function getInstance($id = 'WordPress')
	{
		if (empty(self::$instances[$id]))
		{
			self::$instances[$id] = new MstMailer;
		}

		return self::$instances[$id];
	}

	/**
	 * Send the mail
	 *
	 * @return  mixed  True if successful; JError if using legacy tree (no exception thrown in that case).
	 *
	 * @since   11.1
	 * @throws  RuntimeException
	 */
	public function Send()
	{
        $result = false;
	    try {
            $result = parent::send();
            if ($result == false){
                $log = MstFactory::getLogger();
                $errorInfo = $this->ErrorInfo;
                if($log){
                    $log->error('MstMailer->send() '.$errorInfo);
                }
                throw new RuntimeException(sprintf('%s::Send failed: "%s".', get_class($this), $errorInfo));
            }
        }catch(Exception $e){
	        throw new RuntimeException(sprintf('%s::Send failed: "%s".', get_class($this), $e->getMessage()));
        }

		return $result;
	}

	/**
	 * Cleans single line inputs.
	 *
	 * @param   string  $value  String to be cleaned.
	 *
	 * @return  string  Cleaned string.
	 *
	 * @since   11.1
	 */
	public static function cleanLine($value)
	{
		$value = self::emailToPunycode($value);

		return trim(preg_replace('/(%0A|%0D|\n+|\r+)/i', '', $value));
	}

	/**
	 * Cleans multi-line inputs.
	 *
	 * @param   string  $value  Multi-line string to be cleaned.
	 *
	 * @return  string  Cleaned multi-line string.
	 *
	 * @since   11.1
	 */
	public static function cleanText($value)
	{
		return trim(preg_replace('/(%0A|%0D|\n+|\r+)(content-type:|to:|cc:|bcc:)/i', '', $value));
	}

	/**
	 * Transforms a UTF-8 e-mail to a Punycode e-mail
	 * This assumes a valid email address
	 *
	 * @param   string  $email  The UTF-8 e-mail to transform
	 *
	 * @return  string  The punycode e-mail
	 *
	 * @since   3.1.2
	 */
	public static function emailToPunycode($email)
	{
		$explodedAddress = explode('@', $email);

		// Not addressing UTF-8 user names
		$newEmail = $explodedAddress[0];

		if (!empty($explodedAddress[1]))
		{
			$domainExploded = explode('.', $explodedAddress[1]);
			$newdomain = '';

			foreach ($domainExploded as $domainex)
			{
				$domainex = static::toPunycode($domainex);
				$newdomain .= $domainex . '.';
			}

			$newdomain = substr($newdomain, 0, -1);
			$newEmail = $newEmail . '@' . $newdomain;
		}

		return $newEmail;
	}
	
	/**
	 * Transforms a UTF-8 string to a Punycode string
	 *
	 * @param   string  $utfString  The UTF-8 string to transform
	 *
	 * @return  string  The punycode string
	 *
	 * @since   3.1.2
	 */
	public static function toPunycode($utfString)
	{
        $punycode = $utfString; // fall-back
		$idn = new Algo26\IdnaConvert\IdnaConvert();
        try{
            $punycode = $idn->encode($utfString);
        }catch(Exception $e){
            $log = MstFactory::getLogger();
            $log->warning('toPunycode (1st try) Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage(). ', string: ' . $utfString);
            try{
                $utf8Version = \Algo26\IdnaConvert\EncodingHelper::toUtf8($utfString);
                $punycode = $idn->encode($utf8Version);
            }catch(Exception $e){
                $log->error('toPunycode (2nd try) Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage(). ', orig string: ' . $utfString . ', utf8 string: ' . $utf8Version);
            }
        }
        return $punycode;
	}

	/**
	 * Use SMTP for sending the email
	 *
	 * @param   string   $auth    SMTP Authentication [optional]
	 * @param   string   $host    SMTP Host [optional]
	 * @param   string   $user    SMTP Username [optional]
	 * @param   string   $pass    SMTP Password [optional]
	 * @param   string   $secure  Use secure methods
	 * @param   integer  $port    The SMTP port
	 *
	 * @return  boolean  True on success
	 *
	 * @since   11.1
	 */
	public function useSmtp($auth = null, $host = null, $user = null, $pass = null, $secure = null, $port = 25)
	{
		$this->SMTPAuth = $auth;
		$this->Host = $host;
		$this->Username = $user;
		$this->Password = $pass;
		$this->Port = $port;

		if ($secure == 'ssl' || $secure == 'tls')
		{
			$this->SMTPSecure = $secure;
		}

		if (($this->SMTPAuth !== null && $this->Host !== null && $this->Username !== null && $this->Password !== null)
			|| ($this->SMTPAuth === null && $this->Host !== null))
		{
			$this->isSMTP();

			return true;
		}
		else
		{
			$this->isMail();

			return false;
		}
	}

	/**
	 * Set the email sender
	 *
	 * @param   mixed  $from  email address and Name of sender
	 *                        <code>array([0] => email Address, [1] => Name)</code>
	 *                        or as a string
	 *
	 * @return  MstMailer  Returns this object for chaining.
	 *
	 * @since   11.1
	 * @throws  UnexpectedValueException
	 */
	public function setSender($from)
	{
	    try {
            if (is_array($from)) {
                // If $from is an array we assume it has an address and a name
                if (isset($from[2])) {
                    // If it is an array with entries, use them
                    $this->setFrom($this->cleanLine($from[0]), $this->cleanLine($from[1]), (bool)$from[2]);
                } else {
                    $this->setFrom($this->cleanLine($from[0]), $this->cleanLine($from[1]));
                }
            } elseif (is_string($from)) {
                // If it is a string we assume it is just the address
                $this->setFrom($this->cleanLine($from));
            } else {
                // If it is neither, we log a message and throw an exception
                $exceptionMsg = sprintf('Invalid email Sender: %s, Sender(%s)', $from);
                $log = MstFactory::getLogger();
                if ($log) {
                    $log->error('MstMailer->setSender() ' . $exceptionMsg);
                }
                throw new UnexpectedValueException($exceptionMsg);
            }
        }catch(Exception $e){
            throw new UnexpectedValueException($e->getMessage());
        }

		return $this;
	}

	/**
	 * Set the email subject
	 *
	 * @param   string  $subject  Subject of the email
	 *
	 * @return  MstMailer  Returns this object for chaining.
	 *
	 * @since   11.1
	 */
	public function setSubject($subject)
	{
		$this->Subject = $this->cleanLine($subject);

		return $this;
	}

	/**
	 * Set the email body
	 *
	 * @param   string  $content  Body of the email
	 *
	 * @return  MstMailer  Returns this object for chaining.
	 *
	 * @since   11.1
	 */
	public function setBody($content)
	{
		/*
		 * Filter the Body
		 * TODO: Check for XSS
		 */
		$this->Body = $this->cleanText($content);

		return $this;
	}
}		