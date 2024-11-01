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
    die('These droids are not the droids you are looking for.');
}

class MstFactory
{
	private $instances = array();
	private $classMapping = array();
	private $prefix = 'Mst';
	private $basePath = 'mailster';
    private $scriptMapping = array();

	/** Private constructor -> no direct class instantiation */
	private function __construct() {}

	/**
	 * Gets a single instance of our Factory
	 */
	protected static function &getInstance() {
		static $factory;
		if(!is_object($factory)){
				$factory = new self();										
				if(empty($factory->classMapping))
				{
					$factory->classMapping = array(
						'Application'			=> 'app/Application',
						'Authorization'			=> 'app/Authorization',
						'CacheUtils'			=> 'app/CacheUtils',
						'Events'				=> 'app/Events',
						'Notify'				=> 'app/Notify',
						'NotifyUtils'			=> 'app/NotifyUtils',
						'PluginUtils'			=> 'app/PluginUtils',
						'Log'					=> 'app/Log',
                        'VersionMgmt'		   	=> 'app/VersionMgmt',
						'Configuration'			=> 'conf/Configuration',
						'Parameter'				=> 'conf/Parameter',
						'AttachmentsUtils'		=> 'mail/AttachmentsUtils',
						'DigestSender'			=> 'mail/DigestSender',
                        'Mailer'				=> 'mail/MstMailer',
						'MailingList'			=> 'mail/MailingList',
						'MailingListMailbox'	=> 'mail/MailingListMailbox',
						'MailingListUtils'		=> 'mail/MailingListUtils',
						'MailQueue'				=> 'mail/MailQueue',						
						'MailRetriever'			=> 'mail/MailRetriever',
						'MailSender'			=> 'mail/MailSender',
						'MailUtils'				=> 'mail/MailUtils',
                        'ModerationUtils'		=> 'mail/ModerationUtils',
						'Recipients'			=> 'mail/Recipients',
						'SendEvent'				=> 'mail/SendEvent',
						'ThreadUtils'			=> 'mail/ThreadUtils',
						'SubscriberPlugin'		=> 'subscr/SubscriberPlugin',
						'SubscribeUtils'		=> 'subscr/SubscribeUtils',
						'ConverterUtils'		=> 'utils/ConverterUtils',
						'DateUtils'				=> 'utils/DateUtils',
                        'DB'				    => 'utils/DB',
						'DBUtils'				=> 'utils/DBUtils',
						'Environment'			=> 'utils/Environment',
						'FileUtils'				=> 'utils/FileUtils',
						'HashUtils'				=> 'utils/HashUtils',
						'Utils'					=> 'utils/Utils'
					);
				}									
				if(count($factory->scriptMapping) < 1)
				{
					$factory->scriptMapping = array(
						'lib_mathcaptcha'	=> 'lib/mathcaptcha/MathCaptcha',
						'lib_html2text'		=> 'lib/html2text/Html2Text'
					);
				}
				
		}
		return $factory;
	}

	/**
	 * Gets/creates instances of classes
	 * @param string $className
	 * @return object
	 */
	protected static function &getClassInstance($className) {
		$self = self::getInstance();
        $inclFolder = plugin_dir_path( __FILE__ );
		if(!isset($self->objList[$className])){
			foreach($self->classMapping as $classNameKey => $path)
			{
				if($classNameKey === $className){
					require_once($inclFolder.$path . '.php');
					break;
				}
			}
			$className = $self->prefix.$className;
			$self->objList[$className] = new $className;
		}
		return $self->objList[$className];
	}
	
	/**
	 * Gets/includes scripts
	 * @param string $scriptId
	 * @return bool
	 */
	protected static function getScript($scriptId) {
		$self = self::getInstance();
        $inclFolder = plugin_dir_path( __FILE__ );
		foreach($self->scriptMapping as $scriptKey => $path)
		{
			if($scriptKey === $scriptId){
				require_once($inclFolder.$path . '.php');
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes class instances
	 * @param string $className
	 */
	protected static function &unsetClassInstance($className) {
		$self = self::getInstance();
		if(isset($self->objList[$className]))
		{
			$self->objList[$className] = null;
			unset($self->objList[$className]);
		}
	}
	
	/* ******************* PUBLIC METHO"/" ******************* */
	/**
	 * @return MstMailer
	 */
	public static function &getMailer(){
		return self::getClassInstance('Mailer');
	}
	/**
	 * @return MstApplication
	 */
	public static function &getApplication(){
		return self::getClassInstance('Application');
	}

	/**
	 * @return MstAuthorization
	 */
	public static function &getAuthorization(){
		return self::getClassInstance('Authorization');
	}

	/**
	 * @return MstConfiguration
	 */
	public static function &getConfig(){
		return self::getClassInstance('Configuration');
	}

	/**
	 * @return MstCacheUtils
	 */
	public static function &getCacheUtils(){
		return self::getClassInstance('CacheUtils');
	}

	/**
	 * @return MstDigestSender
	 */
	public static function &getDigestSender(){
		return self::getClassInstance('DigestSender');
	}

	/**
	 * @return MstFileUtils
	 */
	public static function &getFileUtils(){
		return self::getClassInstance('FileUtils');
	}
	
	/**
	 * @return MstLog
	 */
	public static function &getLogger(){
		return self::getClassInstance('Log');
	}
	
	/**
	 * @return MstMailUtils
	 */
	public static function &getMailUtils(){
		return self::getClassInstance('MailUtils');
	}

	/**
	 * @return MstMailRetriever
	 */
	public static function &getMailRetriever(){
		return self::getClassInstance('MailRetriever');
	}

	/**
	 * @return MstMailSender
	 */
	public static function &getMailSender(){
		return self::getClassInstance('MailSender');
	}

    /**
     * @return MstModerationUtils
     */
    public static function &getModerationUtils(){
        return self::getClassInstance('ModerationUtils');
    }
	
	/**
	 * @return MstRecipients
	 */
	public static function &getRecipients(){
		return self::getClassInstance('Recipients');
	}

	/**
	 * @return MstSendEvent
	 */
	public static function &getSendEvents(){
		return self::getClassInstance('SendEvent');
	}

	/**
	 * @return MstSubscriberPlugin
	 */
	public static function &getSubscriberPlugin(){
		return self::getClassInstance('SubscriberPlugin');
	}
	
	/**
	 * @return MstSubscribeUtils
	 */
	public static function &getSubscribeUtils(){
		return self::getClassInstance('SubscribeUtils');
	}

	/**
	 * @return MstThreadUtils
	 */
	public static function &getThreadUtils(){
		return self::getClassInstance('ThreadUtils');
	}

    /**
     * @return MstVersionMgmt
     */
    public static function &getV(){
        return self::getClassInstance('VersionMgmt');
    }

	/**
	 * @return MstUtils
	 */
	public static function &getUtils(){
		return self::getClassInstance('Utils');
	}
	
	/**
	 * @return MstPluginUtils
	 */
	public static function &getPluginUtils(){
		return self::getClassInstance('PluginUtils');
	}
	
	/**
	 * @return MstAttachmentsUtils
	 */
	public static function &getAttachmentsUtils(){
		return self::getClassInstance('AttachmentsUtils');
	}
	
	/**
	 * @return MstMailQueue
	 */
	public static function &getMailQueue(){
		return self::getClassInstance('MailQueue');
	}
	
	/**
	 * @return MstMailingListMailbox
	 */
	public static function &getMailingListMailbox(){
		return self::getClassInstance('MailingListMailbox');
	}

	/**
	 * @return MstConverterUtils
	 */
	public static function &getConverterUtils(){
		return self::getClassInstance('ConverterUtils');
	}
	
	/**
	 * @return MstDateUtils
	 */
	public static function &getDateUtils(){
		return self::getClassInstance('DateUtils');
	}

    /**
     * @return MstDB
     */
    public static function &getDB(){
        return self::getClassInstance('DB');
    }

    /**
     * @return MstDBUtils
     */
    public static function &getDBUtils(){
        return self::getClassInstance('DBUtils');
    }

	/**
	 * @return MstEnvironment
	 */
	public static function &getEnvironment(){
		return self::getClassInstance('Environment');
	}

	/**
	 * @return MstEvents
	 */
	public static function &getEvents(){
		return self::getClassInstance('Events');
	}

	/**
	 * @return MstHashUtils
	 */
	public static function &getHashUtils(){
		return self::getClassInstance('HashUtils');
	}
	
	/**
	 * @return MstMailingListUtils
	 */
	public static function &getMailingListUtils(){
		return self::getClassInstance('MailingListUtils');
	}
	
	/**
	 * @return MstMailingList
	 */
	public static function &getMailingList(){
		return self::getClassInstance('MailingList');
	}
	
	/**
	 * @return MstNotify
	 */
	public static function &getNotify(){
		return self::getClassInstance('Notify');
	}

	/**
	 * @return MstNotifyUtils
	 */
	public static function &getNotifyUtils(){
		return self::getClassInstance('NotifyUtils');
	}
	
	public static function getParameter($jParameter){
        /** @var MstParameter $tmp */
		$tmp = self::getClassInstance('Parameter');			
		return $tmp->getParameterFromJParameter($jParameter);
	}

	public static function loadLibrary($libName){
		return self::getScript('lib_' . strtolower($libName));
	}

	public static function &getModel($modelName){
		
		$model = null;
		$modelClassName = 'MailsterModel'.ucfirst($modelName);
		
		$model = self::getModelInstance( $modelName, 'MailsterModel' );
		
		return $model;
	}

	static function &getModelInstance( $type, $prefix = '', $config = array() ) {
		$type           = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
		$modelClass     = $prefix.ucfirst($type);
		$result         = false;

		if (!class_exists( $modelClass )) {
			$path = plugin_dir_path( __FILE__ )."../models/".$modelClass.".php";
			if ($path) {
				require_once $path;
				if (!class_exists( $modelClass )) {
					error_log('Model class ' . $modelClass . ' not found in file.' );
					return $result;
				}
			}
			else return $result;
		}

		$result = new $modelClass($config);
		return $result;
	}

	/**
	 * @return MailsterModelUser
	 */
	public static function &getUserModel() {
		return self::getModel("user");
	}
	/**
	 * @return MailsterModelDigest
	 */
	public static function &getDigestModel() {
		return self::getModel("digest");
	}
	/**
	 * @return MailsterModelServer
	 */
	public static function &getServerModel() {
		return self::getModel('server');
	}
	/**
	 * @return MailsterModelServer
	 */
	public static function &getServersModel() {
		return self::getModel('server');
	}
	/**
	 * @return MailsterModelMail
	 */
	public static function &getMailModel() {
		return self::getModel('mail');
	}
	/**
	 * @return MailsterModelMails
	 */
	public static function &getMailsModel() {
		return self::getModel('mails');
	}
	/**
	 * @return MailsterModelGroupusers
	 */
	public static function &getGroupUsersModel() {
		return self::getModel('groupusers');
	}
	/**
	 * @return MailsterModelUserGroups
	 */
	public static function &getUserGroupsModel() {
		return self::getModel('usergroups');
	}
	/**
	 * @return MailsterModelThread
	 */
	public static function &getThreadModel() {
		return self::getModel('thread');
	}
	/**
	 * @return MailsterModelThreads
	 */
	public static function &getThreadsModel() {
		return self::getModel('threads');
	}
	/**
	 * @return MailsterModelGroup
	 */
	public static function &getGroupModel() {
		return self::getModel('group');
	}
	/**
	 * @return MailsterModelListGroups
	 */
	public static function &getListgroupsModel() {
		return self::getModel('listGroups');
	}
	/**
	 * @return MailsterModelList
	 */
	public static function &getListModel() {
		return self::getModel('list');
	}
	/**
	 * @return MailsterModelLists
	 */
	public static function &getListsModel() {
		return self::getModel('lists');
	}
	/**
	 * @return MailsterModelQueue
	 */
	public static function &getQueueModel() {
		return self::getModel('queue');
	}

}