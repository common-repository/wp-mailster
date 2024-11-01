<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
/**
 * Mailing List Threads Model
 *
 */
class MailsterModelThreads extends MailsterModel
{

	var $_id = 0;
	var $_data = null;
	var $_total = null;
	var $_pagination = null;
	var $_filterByFrontendMailArchiveACL = false;

	function __construct()
	{
		parent::__construct();
	}

	function getData($listId=false, $overrideLimits=false, $orderBy='rpost', $filterByFrontendMailArchiveACL=false, $page=null)
	{
		if (empty($this->_data) || $this->_id != $listId){
			$this->_id = $listId;
			$this->_filterByFrontendMailArchiveACL = $filterByFrontendMailArchiveACL;

			$query = $this->_buildQuery($listId, $orderBy, $page);
			$this->_data = $this->_getList($query, 0, 0);
		}
		return $this->_data;
	}

	function getTotal()
	{
		if ( empty( $this->_total ) ) {
			global $wpdb;
			$query = $this->_buildQuery( $this->_id );
			$results = $wpdb->get_results( $query );
			$this->_total =  $wpdb->num_rows;
		}
		return $this->_total;
	}

	function _buildQuery($listId, $orderBy='rpost', $page=null)
	{

		$where	= $this->_buildContentWhere($listId);
		$order	= $this->_buildContentOrderBy($orderBy);

		$outerWhereFilter = '';
		/*
		if($this->_filter2subscribed_lists_user_id){
			$recip = MstFactory::getRecipients();
			$subscribedListIdsOfUser = $recip->getListsUserIsMemberOf($this->_filter2subscribed_lists_user_id, 1);
			$outerWhereFilter = ' AND m.list_id IN ( '.implode(', ', $subscribedListIdsOfUser).' )';
		}*/

		if($this->_filterByFrontendMailArchiveACL){
			$listIdsAllowedToBeShown = array();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mailingLists = $mailingListUtils->getAllMailingLists();
			$recip = MstFactory::getRecipients();
			$user = wp_get_current_user();
			$log = MstFactory::getLogger();
			$userIsLoggedIn = false;
			if($user->ID > 0){
				$userIsLoggedIn = true;
			}
			foreach($mailingLists AS $mList){
				if($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_ALL_USERS){
					$listIdsAllowedToBeShown[] = $mList->id;
					$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as all users are allowed');
				}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_USERS && $userIsLoggedIn){
					$listIdsAllowedToBeShown[] = $mList->id;
					$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as all logged-in users are allowed');
				}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_SUBSCRIBERS_OF_MAILING_LIST && $userIsLoggedIn){
					$subscribedListIdsOfUser = $recip->getListsUserIsMemberOf($user->id, 1);
					for($i=0;$i<count($subscribedListIdsOfUser);$i++){
						if($subscribedListIdsOfUser[$i] == $mList->id){
							$listIdsAllowedToBeShown[] = $mList->id;
							$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as user ID '.$user->id.' is a subscriber as required');
							break;
						}
					}
				}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_NOBODY){
					continue; // nope, this list is not allowed...
				}
			}
			$log->debug('threads_model: listIdsAllowedToBeShown: '.print_r($listIdsAllowedToBeShown, true));
			if(count($listIdsAllowedToBeShown) > 0){
				$outerWhereFilter = ' AND m.list_id IN ( '.implode(', ', $listIdsAllowedToBeShown).' )';
			}else{
				$outerWhereFilter = ' AND m.list_id < 0'; // add impossible condition
			}
		}else{
			$outerWhereFilter = '';
		}

		global $wpdb;

		$query = 'SELECT t.first_mail_id, t.last_mail_id, t.ref_message_id, m.*, '
		         . ' m.receive_timestamp AS post_timestamp, '
		         . ' mai.receive_timestamp AS thread_timestamp, li.name AS list_name '
		         . ' FROM ' . $wpdb->prefix . 'mailster_threads AS t'
		         . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_mails AS m'
		         . ' ON (m.id = t.last_mail_id)'
		         . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_mails AS mai'
		         . ' ON (mai.id = t.first_mail_id)'
		         . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_lists AS li'
		         . ' ON (li.id = m.list_id)'
		         . ' WHERE t.id in ('
		         . 'SELECT DISTINCT ma.thread_id'
		         . ' FROM ' . $wpdb->prefix . 'mailster_mails AS ma'
		         . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_lists AS l'
		         . ' ON (ma.list_id = l.id)'
		         . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_attachments AS attach'
		         . ' ON (ma.id = attach.mail_id)'
		         . $where
		         . ')'
		         . $outerWhereFilter
		         . $order;
		if($page) {
			$per_page = 12;
			$query .= " LIMIT $per_page";
			$query .= ' OFFSET ' . ( $page - 1 ) * $per_page;
		}
		$log = MstFactory::getLogger();
		$log->debug('threads_model: '.$query);
		return $query;
	}

	function _buildContentOrderBy($orderBy='rpost')
	{
		switch($orderBy){
			case 'thread':
				$orderby 	= ' ORDER BY thread_timestamp ASC';
				break;
			case 'rthread':
				$orderby 	= ' ORDER BY thread_timestamp DESC';
				break;
			case 'post':
				$orderby 	= ' ORDER BY post_timestamp ASC';
				break;
			case 'rpost':
				$orderby 	= ' ORDER BY post_timestamp DESC';
				break;
			default:
				$orderby 	= ' ORDER BY post_timestamp DESC';
				break;
		}

		return $orderby;
	}

	function _buildContentWhere($listId)
	{
		global $wpdb;
		$dbUtils = MstFactory::getDBUtils();

		$where = array();
		if($listId > 0){
			$where[] = 'ma.list_id = \'' . $listId . '\'';
		}

		if( isset($_REQUEST['filter_search'])) {
			$filterSearch = sanitize_text_field($_REQUEST['filter_search']);
		} else {
			$filterSearch = "";
		}
		if(strlen(trim($filterSearch)) > 0){
			$where[] = '( '
			           . ' ma.subject LIKE \'%'.$filterSearch.'%\''
			           . ' OR ma.body LIKE \'%'.$filterSearch.'%\''
			           . ' OR ma.html LIKE \'%'.$filterSearch.'%\''
			           . ' OR ma.from_name LIKE \'%'.$filterSearch.'%\''
			           . ' OR ma.from_email LIKE \'%'.$filterSearch.'%\''
			           . ' OR EXISTS ('
			           . ' SELECT 1'
			           . ' FROM ' . $wpdb->prefix . 'mailster_attachments attach'
			           . ' WHERE attach.mail_id = ma.id'
			           . ' AND attach.filename LIKE \'%'.$filterSearch.'%\''
			           . ')'
			           . ')';
		}
		$where[] = ' ((ma.bounced_mail IS NULL AND ma.blocked_mail IS NULL) OR (ma.bounced_mail = \'0\' AND ma.blocked_mail = \'0\'))';
        $where[] = ' ma.moderation_status >= \'0\'';
		if( isset($_REQUEST['filter_start_date']) ) {
			$startDate = sanitize_text_field($_REQUEST['filter_start_date']);
		} else {
			$startDate = "";
		}
		if(strlen(trim($startDate)) > 0){
			$startDate = $dbUtils->getTimestampFromDate(strtotime($startDate));
			$where[] = 'ma.receive_timestamp >= \'' . $startDate . '\'';
		}
		if( isset($_REQUEST['filter_end_date']) ) {
			$endDate = sanitize_text_field($_REQUEST['filter_end_date']);
		} else {
			$endDate = "";
		}
		if(strlen(trim($endDate)) > 0){
			$endDate = strtotime($endDate);
			$endDate = mktime(23, 59, 59, date('m', $endDate), date('d', $endDate), date('Y', $endDate));
			$endDate = $dbUtils->getTimestampFromDate(strtotime($endDate));
			$where[] = 'ma.receive_timestamp <= \'' . $endDate . '\'';
		}

		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

		return $where;
	}


}//Class end