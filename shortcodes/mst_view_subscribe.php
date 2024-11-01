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


if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
    die( 'These are not the droids you are looking for.' );
}

/* Subscribe / Unsubscribe Shortcodes [mst_subscribe] and [mst_unsubscribe] */
class mst_frontend_subscribe extends wpmst_mailster {

    public static function mst_subscribe($atts = array(), $content = null) {
        if ( is_admin()){
            return;
        }
        ob_start();
        self::generateForm('subscribe', $atts, $content);
        return ob_get_clean();
    }

    public static function mst_unsubscribe($atts = array(), $content = null) {
        if ( is_admin()){
            return;
        }
        ob_start();
        self::generateForm('unsubscribe', $atts, $content);
        return ob_get_clean();
    }

    protected static function generateForm($formType, $atts, $content){
        if(!$atts || $atts == ''){
            $atts = array();
        }
        $log = MstFactory::getLogger();
        $subscriberPlugin = MstFactory::getSubscriberPlugin();
        $scConfig = self::loadShortcodeSettings($formType, $atts, $content);
        $log->debug('generateForm atts: '.print_r($atts, true));
        $log->debug('generateForm scConfig: '.print_r($scConfig, true));
        $formIdentifier = sha1('sc_'.strtolower($formType).rand(0, 999999));

        $formSessionInfo = new stdClass();
        $formSessionInfo->id = $formIdentifier;
        $formSessionInfo->type = $formType;
        $formSessionInfo->origin = 'shortcode';
        $formSessionInfo->submitTxt = $scConfig->submitTxt;
        $formSessionInfo->submitConfirmTxt = $scConfig->submitConfirmTxt;
        $formSessionInfo->errorTxt = $scConfig->errorTxt;
        $formSessionInfo->captcha = $scConfig->captcha;
        $formSessionInfo->subscribeAdd2Group = $scConfig->subscribeAdd2Group;
        $formSessionInfo->unsubscribeRemoveFromGroup = $scConfig->unsubscribeRemoveFromGroup;

        $transientId = 'wpmst_subscribe_forms'.'_'.$formIdentifier;
        $log->debug('generateForm Store to transient '.$transientId.': '.print_r($formSessionInfo, true));
        set_transient($transientId, $formSessionInfo,HOUR_IN_SECONDS);

        $settings = (array)$scConfig;
        if($formType === 'subscribe'){
            $subscriberPlugin->getSubscriberHtml($settings, $formIdentifier);
        }elseif($formType === 'unsubscribe'){
            $subscriberPlugin->getUnsubscriberHtml($settings, $formIdentifier);
        }else{
            $log->warning('mst_view_subscribe generateForm invalid formType: '.$formType);
        }

    }

    public static function loadShortcodeSettings($formType, $atts, $content){
        $buttonTxtDefault = ($formType == 'subscribe') ? __('Subscribe', 'wp-mailster') : __('Unsubscribe', 'wp-mailster');
        $headerTxtDefault = ($formType == 'subscribe') ? __('WP Mailster Mailing List Subscription', 'wp-mailster') : __('WP Mailster Mailing List Unsubscription', 'wp-mailster');
        $submitTxtDefault = ($formType == 'subscribe') ? __('Thank you for subscribing', 'wp-mailster') : __('Successfully unsubscribed', 'wp-mailster');
        $errorTxtDefault = ($formType == 'subscribe') ? __('Subscription error occurred. Please try again.', 'wp-mailster') : __('Unsubscription error occurred. Please try again.', 'wp-mailster');
        $submitConfirmTxtDefault = ($formType == 'subscribe') ? __('Thank you for your subscription. An email was sent to confirm your subscription. Please follow the instructions in the email.', 'wp-mailster') : __('An email was sent to you in order to confirm that you wanted to unsubscribe. Please follow the instructions in the email.', 'wp-mailster');

        $scConfig = new stdClass();
        $scConfig->allLists = !array_key_exists('id', $atts) || intval($atts['id']) == 0;
        $scConfig->listIdSpecified = !$scConfig->allLists;
        $scConfig->listNameSpecified = false; // do not offer that way of selection (anymore)
        $scConfig->designChoice = ''; // do not offer selection (anymore)
        $scConfig->listId = self::shortCodeParamInt($atts, 'id', 0);
        $scConfig->subscribeAdd2Group = self::shortCodeParamInt($atts, 'add2Group', self::shortCodeParamInt($atts, 'add2group', 0)); // allow both add2Group and add2group
        $scConfig->unsubscribeRemoveFromGroup = self::shortCodeParamInt($atts, 'removeFromGroup', self::shortCodeParamInt($atts, 'removefromgroup', 0)); // allow both removeFromGroup and removefromgroup
        $scConfig->hideListName = self::shortCodeParamBool($atts, 'hideList');
        $scConfig->hideNameField = self::shortCodeParamBool($atts, 'hideName');
        $scConfig->smartHide = self::shortCodeParamBool($atts, 'smartHide');
        $scConfig->suggestUserData = self::shortCodeParamBool($atts, 'suggestUserData');
        $scConfig->enforceUserData = self::shortCodeParamBool($atts, 'enforceUserData');
        $scConfig->reqTosApproval = self::shortCodeParamBool($atts, 'requireTerms');
        $scConfig->digestChoice = self::shortCodeParamBool($atts, 'digestChoice');
        $scConfig->digestChoiceLabel = self::shortCodeParamString($atts, 'digestChoiceLabel', __('Digest', 'wp-mailster'));
        $scConfig->buttonTxt = self::shortCodeParamString($atts, 'buttonTxt', $buttonTxtDefault);
        $scConfig->headerTxt = self::shortCodeParamString($atts, 'headerTxt', $headerTxtDefault);
        // accept both thankTxt and submitTxt
        $scConfig->submitTxt = self::shortCodeParamString($atts, 'thankTxt', self::shortCodeParamString($atts, 'submitTxt', $submitTxtDefault));
        $scConfig->errorTxt = self::shortCodeParamString($atts, 'errorTxt', $errorTxtDefault);
        $scConfig->submitConfirmTxt = self::shortCodeParamString($atts, 'submitConfirmTxt', $submitConfirmTxtDefault);
        $scConfig->captcha = self::shortCodeParamString($atts, 'captcha', null);
        $scConfig->cssPrefix = self::shortCodeParamString($atts, 'css', null);
        $scConfig->listLabel = self::shortCodeParamString($atts, 'listLabel', __('Mailing list', 'wp-mailster'));
        $scConfig->emailLabel = self::shortCodeParamString($atts, 'emailLabel', __('Email', 'wp-mailster'));
        $scConfig->nameLabel = self::shortCodeParamString($atts, 'nameLabel', __('Name', 'wp-mailster'));
        $scConfig->tosLabelText = self::shortCodeParamString($atts, 'termsLabel', __('Yes, I have read and accept the terms of service', 'wp-mailster'));
        $scConfig->tosLinkLabel = self::shortCodeParamString($atts, 'termsLinkLabel', __('Our Terms', 'wp-mailster'));
        $scConfig->tosLinkUrl = array_key_exists(strtolower('termsLinkUrl'), $atts) ? esc_url($atts[strtolower('termsLinkUrl')]) :  __('Our Terms', 'wp-mailster');
        $scConfig->tosConfirmError = self::shortCodeParamString($atts, 'termsConfirmError', __('Please accept our terms of service first', 'wp-mailster'));

        return $scConfig;
    }

    /**
     * @param array $atts
     * @param string $val
     * @return bool
     */
    protected static function shortCodeParamBool($atts, $val){
        return (array_key_exists(strtolower($val), $atts) || in_array($val, $atts));
    }

    /**
     * @param array $atts
     * @param string $val
     * @param string $default
     * @return string
     */
    protected static function shortCodeParamString($atts, $val, $default){
        return array_key_exists(strtolower($val), $atts) ? sanitize_text_field($atts[strtolower($val)]) : $default;
    }

    /**
     * @param array $atts
     * @param string $val
     * @param int $default
     * @return int
     */
    protected static function shortCodeParamInt($atts, $val, $default){
        return array_key_exists(strtolower($val), $atts) ? intval($atts[strtolower($val)]) : $default;
    }
}