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

class MstCaptcha
{
    public $html;
    public $cType;
    public $error;
    public $twoCols;
    public $firstCol;

    public function __construct($cType=MstConsts::CAPTCHA_ID_MATH) {
        $this->error = false;
        $this->twoCols = false;
        $this->firstCol = '';
        $this->cType = $cType;
    }

    function MstCaptcha($cType=MstConsts::CAPTCHA_ID_MATH)
    {
        self::__construct($cType);
    }
    
    function htmlOk(){
    	return !$this->error; 
    }
    
    function getHtml($cError=null, $cssPrefix='', $targetHtmlId = null){
    	$mstUtils = MstFactory::getUtils();
    	$mstConfig = MstFactory::getConfig();
    	
        switch($this->cType){

            case MstConsts::CAPTCHA_ID_RECAPTCHA: // v1 no longer, v2 is default
            case MstConsts::CAPTCHA_ID_RECAPTCHA_V2:
                $keys = $mstConfig->getRecaptchaV2Keys();
                $pubK = $keys['public'];
                $priK = $keys['private'];
                $theme = $mstConfig->getRecaptchaTheme();
                if((!is_null($pubK)) && (strlen($pubK)>5)
                    && (!is_null($priK)) && (strlen($priK)>5)){

                    $onloadScript = "var onLoadInitCaptchas = function() {
                    var recaptchaTargets = jQuery('.g-recaptcha-target').map(function() { return this.id; }).get();
                        jQuery.each(recaptchaTargets, function(index, item) {
                                grecaptcha.render(item, {'sitekey' : '".$pubK."', 'theme' : '".$theme."'});
                        });
                    };";

                    // Add inline code for reCaptcha callback function
                    wp_register_script( 'wpmst-dummy-js-footer', '', array("jquery"), '', true );
                    wp_enqueue_script( 'wpmst-dummy-js-footer'  );
                    wp_add_inline_script( 'wpmst-dummy-js-footer', $onloadScript);

                    // Add reCaptcha script
                    wp_enqueue_script( 'wpmst-recaptcha-v2' );

                    $this->html = '<div id="'.$targetHtmlId.'" class="g-recaptcha-target g-recaptcha-small"></div>';
                }else{
                    $this->html = __( 'Please provide reCAPTCHA API keys in the configuration settings of WP Mailster', 'wp-mailster' );
                    $this->error = true;
                }
                break;

        	case MstConsts::CAPTCHA_ID_MATH:
        		$this->twoCols = true;
        		$ok = MstFactory::loadLibrary(MstConsts::CAPTCHA_ID_MATH);
        		if($ok){
        			$this->html = MstMathCaptcha::getHTML($cssPrefix);
        			$this->firstCol = MstMathCaptcha::getQuestion();
        		}else{
        			$this->error = true; 
        		}
        		break;

        	default:
        		// don't know that one...
        		$this->error = true;
        		$this->html = __( 'Unkown CAPTCHA', 'wp-mailster' ) . ': ' . $this->cType;
        	break;
        }
    	return $this->html;	
    }
    
    function isValid(){
        $log = MstFactory::getLogger();
    	$mstUtils = MstFactory::getUtils();
    	$mstConfig = MstFactory::getConfig();
    	
    	switch($this->cType){

            case MstConsts::CAPTCHA_ID_RECAPTCHA: // v1 no longer, v2 is default
            case MstConsts::CAPTCHA_ID_RECAPTCHA_V2:
                $log->debug('Captcha->isValid CAPTCHA_ID_RECAPTCHA_V2');
                $keys = $mstConfig->getRecaptchaV2Keys();
                $pubK = $keys['public'];
                $priK = $keys['private'];
                if((!is_null($pubK)) && (strlen($pubK)>5)
                    && (!is_null($priK)) && (strlen($priK)>5)){
                    $captchaResponse = array_key_exists('g-recaptcha-response', $_POST) ? $_POST['g-recaptcha-response'] : null;
                    if($captchaResponse){
                        try {
                            $url = 'https://www.google.com/recaptcha/api/siteverify';

                            $data = array(
                                'secret'   => $priK,
                                'response' => $captchaResponse,
                                'remoteip' => $_SERVER['REMOTE_ADDR']);

                            $options = array(
                                'http' => array(
                                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                                    'method'  => 'POST',
                                    'content' => http_build_query($data)
                                )
                            );

                            $context  = stream_context_create($options);
                            $result = file_get_contents($url, false, $context);
                            $result = json_decode($result);
                            if($result->success){
                                $log->debug('Captcha->isValid Captcha '.$this->cType.' answered correctly');
                                return true;
                            }else{
                                $log->debug('Captcha->isValid Captcha '.$this->cType.' answered incorrectly');
                                $log->debug('Captcha->isValid Returned object: '.print_r($result, true));
                            }
                        }
                        catch (Exception $e) {
                            $exceptionMsg = 'Exception checking captcha '.$this->cType.': '.$e->getMessage().'(Code: '.$e->getCode().')';
                            $log->error($exceptionMsg);
                            $this->html = $exceptionMsg;
                            $this->error = true;
                        }
                    }else{
                        $log->error('Captcha->isValid No captcha response in POST');
                        $this->html = __( 'No Captcha response provided', 'wp-mailster' );
                        $this->error = true;
                    }
                }else{
                    $log->error('Captcha->isValid Please provide reCAPTCHA API keys in the configuration settings of WP Mailster');
                    $this->html = __( 'Please provide reCAPTCHA API keys in the configuration settings of WP Mailster', 'wp-mailster' );
                    $this->error = true;
                }
                break;

        	case MstConsts::CAPTCHA_ID_MATH:
                $log->debug('Captcha->isValid CAPTCHA_ID_MATH');
        		$ok = MstFactory::loadLibrary(MstConsts::CAPTCHA_ID_MATH);
        		if($ok){
        			if (MstMathCaptcha::answerCorrect()){
				        return true;
        			}
        		}else{
        			$this->error = true; 
        		}
        	break;
        	default:
                $log->error('Captcha->isValid Unknown captcha type: '.$this->cType);
        		// don't know that one...
        		$this->error = true;
        	break;
        }
        return false;
    }
    
}
