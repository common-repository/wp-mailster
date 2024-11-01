<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
	$users = null;
    $inputVarOffset = 40;

    $importtask = ( isset( $_POST['importtask'] ) ) ? sanitize_text_field( $_POST['importtask'] ) : "";
    $targetgroup = ( isset( $_POST['targetgroup'] ) ) ? intval( $_POST['targetgroup'] ) : 0;
    $targetlist = ( isset( $_POST['targetlist'] ) ) ? intval( $_POST['targetlist'] ) : 0;
    $duplicateopt = ( isset( $_POST['duplicateopt'] ) ) ? intval( $_POST['duplicateopt'] ) : 0;
    $newgroupname = ( isset( $_POST['newgroupname'] ) ) ? sanitize_text_field( $_POST['newgroupname'] ) : "";
    $importtarget = ( isset( $_POST['importtarget'] ) ) ? sanitize_text_field( $_POST['importtarget'] ) : "";

	$listsModel = MstFactory::getListsModel();
	$groupsModel = MstFactory::getGroupModel();
	$groupUsersModel = MstFactory::getGroupUsersModel();
	$groups = $groupsModel->getAllGroups();
	$mailLists = $listsModel->getData();
	$mstUtils = MstFactory::getUtils();

	$messages = array();
	
	//** start step 1
	if(isset($_POST['task']) && $_POST['task'] == "startimport") {
		$log       = MstFactory::getLogger();
		$mailUtils = MstFactory::getMailUtils();

        $filePath = ( isset( $_POST['filepath'] ) ) ? sanitize_text_field( $_POST['filepath'] ) : "";
        $autoFormat = ( isset( $_POST['autoformat'] ) ) ? intval( $_POST['autoformat'] ) : 0;
		$autoFormat = ($autoFormat > 0 ? true : false);

        $delimiter = ( isset( $_POST['delimiter'] ) ) ? sanitize_text_field( $_POST['delimiter'] ) : "";
        $dataorder = ( isset( $_POST['dataorder'] ) ) ? sanitize_text_field( $_POST['dataorder'] ) : "";
        $importtask = ( isset( $_POST['importtask'] ) ) ? sanitize_text_field( $_POST['importtask'] ) : "";
        $targetgroup = ( isset( $_POST['targetgroup'] ) ) ? sanitize_text_field( $_POST['targetgroup'] ) : "";
        $duplicateopt = ( isset( $_POST['duplicateopt'] ) ) ? sanitize_text_field( $_POST['duplicateopt'] ) : "";
        $targetlist = ( isset( $_POST['targetlist'] ) ) ? sanitize_text_field( $_POST['targetlist'] ) : "";
        $newGroupName = ( isset( $_POST['newgroupname'] ) ) ? sanitize_text_field( $_POST['newgroupname'] ) : "";
        $dataSource = ( isset( $_POST['datasource'] ) ) ? sanitize_text_field( $_POST['datasource'] ) : "";

		$uploaded = false;
		$log->debug( 'Starting CSV import...' );
		$log->debug( 'Data Source: ' . $dataSource );
		if ( $dataSource == 'local_file' ) {
			$log->debug( 'Source: Local file, we need to upload' );
			// local file, so we have to upload it first
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			$result = wpmst_parse_file_errors( $_FILES['filepath_local'] );

			if ( $result['error'] ) {
				$log->error( 'ERROR when checking parse file errors: ' . $result['error'] );
				$messages[] = '<p>ERROR: ' . $result['error'] . '</p>';
			} else {
				//do the import
				$uploaded = true;
			}
			$uploadedfile = $_FILES['filepath_local'];
			$filePath = $_FILES["filepath_local"]["tmp_name"];
		} else {
			$log->debug( 'Source: Server file, NO need to upload' );
			// server file, should be uploaded
			$uploaded = true;
			$filePath = get_home_path() . "/" . $filePath; // source file for import
		}
		$log->debug( 'Filepath: ' . $filePath );
		$log->debug( 'Uploaded: ' . ($uploaded ? 'Yes' : 'No') );
		
		// Auto detect line endings, to deal with Mac line endings...
		$oldLineEndingSetting = ini_get( 'auto_detect_line_endings' );
		ini_set( 'auto_detect_line_endings', true );
				
        $autoFormatRecognized = false;
        $hasHeader = false;
		if($autoFormat){
			$maxLinesToCheck = 5;
			$filePointer = @fopen($filePath, "r");
            $lines = array();
			if($filePointer){
				$currLine = 0;
				while( ($line = fgets($filePointer, 500)) !== false && $currLine++ <= $maxLinesToCheck){
					$lines[] = $line;
				}
			}
			$formatObject = detectCSVformat($lines);
            if($formatObject){
                $delimiter = $formatObject->delimiter;
                $dataorder = $formatObject->dataorder;
                $hasHeader = $formatObject->hasHeader;
                $autoFormatRecognized = true;
            }else{
                $messages[] = __("CSV format not recognized", 'wp-mailster');
            }
		}
		
		if($uploaded == true && (!$autoFormat || ($autoFormat && $autoFormatRecognized))){
			$filePointer = @fopen( $filePath, "r" );
			if ( $filePointer ) {
				$i = 0;
                $users = array();
                $log->debug( 'Got file handle of file ' . $filePath );
				$log->debug( 'Working with delimiter: ' . $delimiter );
				while ( ( $data = fgetcsv( $filePointer, 500, $delimiter ) ) !== false ) {
                    if($hasHeader){
                        $log->debug('File format has header, thus skip first line');
                        if ( ( $data = fgetcsv( $filePointer, 500, $delimiter ) ) === false ) {
                            break; // leave when no more lines
                        }
                        $hasHeader = false; // set to false since we already skipped this line
                    }
					if ( ! is_null( $data ) ) {
						$log->debug( 'Data No ' . $i . ' #cols: ' . count( $data ) );
                        if(isset($data[0]) && (isset($data[1]) || (strtolower($dataorder) === 'email_only')))	{
                            $users[$i] = array();

                            $firstEnc  = mb_detect_encoding( $data[0] . ' ', 'UTF-8, ISO-8859-1, ISO-8859-15' );
							if ( $firstEnc == 'UTF-8' ) {
								$first = utf8_decode( $data[0] );
								if ( substr( $first, 0, 1 ) == '?' ) {
									$first = substr( $first, 1 );
								}
							} else {
								$first = mb_convert_encoding( $data[0], 'UTF-8', $firstEnc );
							}

                            if(isset($data[1])){
                                $secondEnc = mb_detect_encoding( $data[1] . ' ', 'UTF-8, ISO-8859-1, ISO-8859-15' );
                                if ( $secondEnc == 'UTF-8' ) {
                                    $second = utf8_decode( $data[1] );
                                } else {
                                    $second = mb_convert_encoding( $data[1], 'UTF-8', $secondEnc );
                                }
                            }else{
                                $second = ''; // we don't have a second entry
                            }

							$first  = htmlentities( $first );
							$second = htmlentities( $second );
                            if(strtolower($dataorder) === 'email_only'){
                                $email = $first;
                                $name = $second;
                            }elseif ( strtolower( $dataorder ) == 'name_del_email' ) {
								$name  = $first;
								$email = $second;
							} else {
                                $email = $first;
								$name  = $second;
							}
							if ( is_email( $email ) && $mailUtils->isValidEmail( $email ) ) {
								$users[ $i ]['name']  = $name;
								$users[ $i ]['email'] = $email;
								$i ++;
							} else {
								$log->debug( 'CSV file contains for user "' . $name . '" invalid email: ' . $email );
								$messages[] = 'CSV file contains for user "' . $name . '" invalid email: ' . $email. '<br>';
							}
						} else {
							$log->debug( 'Skipping incomplete line: ' . print_r( $data, true ) );
						}
					} else {
						$log->debug( 'Skipping invalid line' );
					}
				}
				$log->debug( 'Close file handle' );
				fclose( $filePointer );

                $userCount = count( $users );
                $maxInputVars = ini_get('max_input_vars');
                if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                    $maxInputVars = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                    $maxInputVars = floor($maxInputVars/2); // we need two (name + email) inputs per user entry
                }
                if($maxInputVars < $userCount){
                    $log->warning(sprintf(__('Your server only supports importing a maximum of %d entries at once!', 'wp-mailster'), $maxInputVars ));
                    $messages[] = sprintf(__('Your server only supports importing a maximum of %d entries at once!', 'wp-mailster'), $maxInputVars ).'<br>';
                }

				$log->debug( 'File imported successfully, #data sets loaded: ' . $userCount.' (max input: '.$maxInputVars.')' );
				$messages[] = __("File Loaded Successfully", 'wp-mailster');

				reviewimports();
			} else {
				$messages[] = __("File not found", 'wp-mailster');
				$log->debug( 'Could NOT get file handle for file ' . $filePath );
				import();
			}
		} else {
            if(!$uploaded){
                $messages[] = __("File not found", 'wp-mailster');
                $log->debug( 'Could NOT upload file ' . $filePath );
            }
			import();
		}
		ini_set( 'auto_detect_line_endings', $oldLineEndingSetting );
	} else if(isset($_POST['task']) && $_POST['task'] == "saveimport") {
		$messages = saveimport();
	}

	$titleTxt = __( 'CSV Import', 'wp-mailster' );
	if($users){
		$titleTxt = $titleTxt . ' (' . __( 'step', 'wp-mailster' ) . ' 2/2)';
	}else{
		$titleTxt = $titleTxt . ' (' . __( 'step', 'wp-mailster' ) . ' 1/2)';
	}
	//** end step 1
	
	function detectCSVformat($sampleLinesfromCSV)
	{
        $log = MstFactory::getLogger();
		$mailUtils = MstFactory::getMailUtils();
		$mailer = MstFactory::getMailer();
		
		if(count($sampleLinesfromCSV)<=0){
            $log->debug('detectCSVformat No lines, no detection possible');
			return false; // no detection possible
		}
		
		$foundFormat = false;
		$foundDelimiter = '';
		$foundDataorder = '';
		
		$delimiterCandidates = array();
		$delimiterCandidates[] = ',';
		$delimiterCandidates[] = ';';
		$delimiterCandidates[] = '|';
		$delimiterCandidates[] = "\t";
		
		$nameHeaderCandidates = array();
		$nameHeaderCandidates[] = 'name';
		$nameHeaderCandidates[] = 'full name';
        $nameHeaderCandidates[] = 'sure name';
		
		$emailHeaderCandidates = array();
        $emailHeaderCandidates[] = 'mail';
		$emailHeaderCandidates[] = 'email';
		$emailHeaderCandidates[] = 'e-mail';
        $emailHeaderCandidates[] = 'email address';
        $emailHeaderCandidates[] = 'e-mail address';

        $log->debug('detectCSVformat check sample line: '.$sampleLinesfromCSV[0]);
		
		$foundHeaderLine = false;
		$firstLine = trim(strtolower($sampleLinesfromCSV[0]));
		foreach($nameHeaderCandidates AS $nameHeader){
			foreach($emailHeaderCandidates AS $emailHeader){
				foreach($delimiterCandidates AS $delimiter){
					$matchCombinations = array();
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'name_del_email';
					$matchCombination->line = trim(strtolower($nameHeader)).$delimiter.trim(strtolower($emailHeader));					
					$matchCombinations[] = $matchCombination;
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'name_del_email';
					$matchCombination->line = trim(strtolower($nameHeader)).$delimiter.' '.trim(strtolower($emailHeader));					
					$matchCombinations[] = $matchCombination;					
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'name_del_email';
					$matchCombination->line = trim(strtolower($nameHeader)).' '.$delimiter.trim(strtolower($emailHeader));					
					$matchCombinations[] = $matchCombination;	
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'name_del_email';
					$matchCombination->line = trim(strtolower($nameHeader)).' '.$delimiter.' '.trim(strtolower($emailHeader));					
					$matchCombinations[] = $matchCombination;
					
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'email_del_name';
					$matchCombination->line = trim(strtolower($emailHeader)).$delimiter.trim(strtolower($nameHeader));					
					$matchCombinations[] = $matchCombination;
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'email_del_name';
					$matchCombination->line = trim(strtolower($emailHeader)).$delimiter.' '.trim(strtolower($nameHeader));					
					$matchCombinations[] = $matchCombination;					
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'email_del_name';
					$matchCombination->line = trim(strtolower($emailHeader)).' '.$delimiter.trim(strtolower($nameHeader));					
					$matchCombinations[] = $matchCombination;	
					
					$matchCombination = new stdClass();
					$matchCombination->delimiter = $delimiter;
					$matchCombination->dataorder = 'email_del_name';
					$matchCombination->line = trim(strtolower($emailHeader)).' '.$delimiter.' '.trim(strtolower($nameHeader));					
					$matchCombinations[] = $matchCombination;
					
					foreach($matchCombinations AS $matchCombination){
						if($firstLine === $matchCombination->line){
							$foundHeaderLine = true;
							$foundDelimiter = $matchCombination->delimiter;
							$foundDataorder = $matchCombination->dataorder;
                            $log->debug('detectCSVformat Found Header line, order: '.$foundDataorder.', delimiter: '.$foundDelimiter);
							break;
						}					
					}
					if($foundHeaderLine){
						break;
					}
					
				}
				if($foundHeaderLine){
					break;
				}
			}
			if($foundHeaderLine){
				break;
			}
		}
		
		if($foundHeaderLine){
			$foundFormat = true;
		}
		
		if(!$foundFormat){ // have not found fitting header, thus we will search based on content
            $log->debug('detectCSVformat Did not find fitting header');
			$mostFindings = 0;
			$bestDelimiter = ',';
			foreach($delimiterCandidates AS $delimiterCandidate){
				$foundColumns = 0;
				foreach($sampleLinesfromCSV AS $line){
					$lineParts = explode($delimiterCandidate, $line);
					$foundColumns += count($lineParts);
				}
				if($foundColumns > $mostFindings){
					$mostFindings = $foundColumns;
					$bestDelimiter = $delimiterCandidate;
				}
			}
			
			if($mostFindings > 0){
				$foundDelimiter = $bestDelimiter;
				$testLine = $sampleLinesfromCSV[0];
                $log->debug('detectCSVformat Most Findings with delimiter: '.$foundDelimiter);
				if(count($sampleLinesfromCSV) > 1 && strlen($sampleLinesfromCSV[1]) > 3){
					$testLine = $sampleLinesfromCSV[1]; // look at second line if possible (better in the chance there is a header that was not recognized)
				}
				$lineParts = explode($foundDelimiter, $testLine);
                $firstEmailCandidate = $lineParts[0];
                $log->debug('detectCSVformat firstEmailCandidate: '.$firstEmailCandidate);
                $firstCmsCheck = $mailer->validateAddress($firstEmailCandidate);
                $firstMstCheck = $mailUtils->isValidEmail($firstEmailCandidate);

                if(count($lineParts) > 1){
                    $secondEmailCandidate = $lineParts[1];
                    $log->debug('detectCSVformat secondEmailCandidate: '.$secondEmailCandidate);
                    $secondCmsCheck = $mailer->validateAddress($secondEmailCandidate);
                    $secondMstCheck = $mailUtils->isValidEmail($secondEmailCandidate);
                }else{
                    $secondEmailCandidate = null;
                    $log->debug('detectCSVformat secondEmailCandidate NONE, i.e. no second column');
                    $secondCmsCheck = $secondMstCheck = false;
                }

                $log->debug('detectCSVformat result 1st CMS: '.$firstCmsCheck);
                $log->debug('detectCSVformat result 1st Mst: '.$firstMstCheck);
                $log->debug('detectCSVformat result 2nd CMS: '.$secondCmsCheck);
                $log->debug('detectCSVformat result 2nd Mst: '.$secondMstCheck);

                if($secondEmailCandidate){
                    if($firstCmsCheck || $firstMstCheck){
                        $foundDataorder = 'email_del_name';
                        $foundFormat = true;
                        $log->debug('detectCSVformat Found format with ordering: '.$foundDataorder);
                    }elseif($secondCmsCheck || $secondMstCheck){
                        $foundDataorder = 'name_del_email';
                        $foundFormat = true;
                        $log->debug('detectCSVformat Found format with ordering: '.$foundDataorder);
                    }else{
                        $log->debug('detectCSVformat Was not able to find data ordering with line: '.$testLine);
                    }
                }else{
                    if($firstCmsCheck || $firstMstCheck){
                        $foundDataorder = 'email_only';
                        $foundFormat = true;
                        $log->debug('detectCSVformat Found format with ordering: '.$foundDataorder);
                    }else{
                        $log->debug('detectCSVformat Was not able to find data ordering with line: '.$testLine);
                    }
                }
			}
		
		}
				
		if($foundFormat){
			$res = new stdClass();
            $res->hasHeader = $foundHeaderLine;
			$res->delimiter = $foundDelimiter;
			$res->dataorder = $foundDataorder;
            $log->debug('detectCSVformat Result: '.print_r($res, true));
			return $res;
		}else{
            $log->debug('detectCSVformat Failed to auto detect CSV format');
			return false;
		}
	}

	function import()
	{
        $importTarget = (isset($_POST['importtarget'])) ? sanitize_text_field($_POST['importtarget']) : "";
        set_transient('wpmst_trnsnt_import_target', $importTarget, HOUR_IN_SECONDS);
		$groupModel = MstFactory::getGroupModel();
		$listsModel = MstFactory::getListsModel();
		$groupUsersModel = MstFactory::getGroupUsersModel();
	}

	function saveimport()
	{
	    $log = MstFactory::getLogger();
        $importtask  = ( isset($_POST['importtask']) ) ? sanitize_text_field($_POST['importtask']) : "";
        $targetgroup  = ( isset($_POST['targetgroup']) ) ? intval($_POST['targetgroup']) : 0;
        $targetlist  = ( isset($_POST['targetlist']) ) ? intval($_POST['targetlist']) : 0;
        $userCr  = ( isset($_POST['usercount']) ) ? intval($_POST['usercount']) : 0;
        $newGroupName  = ( isset($_POST['newgroupname']) ) ? sanitize_text_field($_POST['newgroupname']) : "";
        $duplicateopt  = ( isset($_POST['duplicateopt']) ) ? sanitize_text_field($_POST['duplicateopt']) : "";

		$addedToList = false;
		$addedToGroup = false;

		$importedCr = 0;
		$messages = array();
        $log->debug('saveimport userCr: '.$userCr);
        $log->debug('saveimport duplicateopt: '.$duplicateopt);
        $log->debug('saveimport importtask: '.$importtask);
		for($i = 0; $i < $userCr; $i++){
			$name 	= sanitize_text_field($_POST['name'. $i]);
			$email	= sanitize_text_field($_POST['email'. $i]);

			if($email != ''){
				if( $name == "" ) {
					$messages[] = sprintf( __( "User with email %s, does not have a name.", 'wp-mailster' ), $email );
				}

                $userId = 0;
                $isCoreUser = 0;
				$duplicate = true;
				$model	= MstFactory::getUserModel();
				if($duplicateopt == 'merge'){
					$user	= $model->isDuplicateEntry($email, true);
					if(!$user){
					    $log->debug('saveimport email '.$email. ' is no duplicate');
						$duplicate = false;
					}else{
						// we have a duplicate and load the existent user identifiers
						$userId = $user->id;
						$isCoreUser = $user->is_core_user;
                        $log->debug('saveimport Duplicate recognized for email '.$email.': is user ID '.$userId.' / core: '.$isCoreUser);
					}
				}
				if(($duplicateopt == 'ignore') || ($duplicate == false)) {
                    $user_options = array();
					$user_options['name'] = sanitize_text_field($name);
					$user_options['email'] = sanitize_email($email);
					$success = $model->saveData($user_options);
					$userId = $model->getId();
                    $isCoreUser = 0;
                    $log->debug('saveimport create new user '.print_r($user_options, true).', new userId: '.$userId);
				}

				if($importtask == 'add2group'){
                    $log->debug('saveimport add2group to group '.$targetgroup);
					if($targetgroup == 0){
						// Create a new Group
						$model = MstFactory::getGroupModel();
                        $group_options = array();
						$group_options[ 'name' ] = sanitize_text_field($newGroupName);
						$model->saveData( $group_options );
						$targetgroup = $model->getId();
                        $log->debug('saveimport create new group '.print_r($group_options, true).', new targetgroup: '.$targetgroup);
					}
					// Insert User in Group
					$model = MstFactory::getGroupUsersModel();
					$groupUser = new stdClass();
					$groupUser->user_id			= $userId;
					$groupUser->group_id		= $targetgroup;
					$groupUser->is_core_user	= $isCoreUser;
					$success = $model->store($groupUser);
					if($success){
                        $addedToGroup = true;
                        $log->debug('saveimport User added to group: '.print_r($groupUser, true));
                    }else{
					    $log->error('saveimport Failed to add to group: '.print_r($groupUser, true).', POST was: '.print_r($_POST, true));
                    }

				}elseif($importtask == 'add2list'){
                    $log->debug('saveimport add2list to list '.$targetlist);
					// Insert User in Mailing List
					$list = new MailsterModelList($targetlist);
					$success = $list->addUserById( intval($userId), intval($isCoreUser) );
                    if($success){
                        $addedToList = true;
                        $log->debug('saveimport Added user: '.$userId.', isCoreUser: '.$isCoreUser.' to list: '.$targetlist);
                    }else{
                        $log->error('saveimport Failed to add user: '.$userId.', isCoreUser: '.$isCoreUser.' to list: '.$targetlist.', POST was: '.print_r($_POST, true));
                    }
				}
				$importedCr++;
			}
		}
		$messages[] = sprintf(__("Successfully imported %d users.", 'wp-mailster'), $importedCr);
		if($addedToList){
			$list = new MailsterModelList($targetlist);
			$mstRecipients = MstFactory::getRecipients();
			$mstRecipients->recipientsUpdated($targetlist); // update cache state
			$messages[] = __("New users were added to the list", 'wp-mailster') . " <a href='?page=mst_mailing_lists&subpage=recipients&lid=" . $targetlist . "'>" . $list->_data[0]->name . "</a>";
		}
		if($addedToGroup){
			$mstRecipients 		= MstFactory::getRecipients();
			$groupUsersModel	= MstFactory::getGroupUsersModel();
			$listsToUpdRecips 	= $groupUsersModel->getListsWithGroup($targetgroup);

			for($k=0; $k < count($listsToUpdRecips); $k++)
			{
				$currList = &$listsToUpdRecips[$k];
				$mstRecipients->recipientsUpdated($currList->id); // update cache state
			}
			$group = new MailsterModelGroup($targetgroup);
			$messages[] = __("New users were added to the group", 'wp-mailster') . " <a href='?page=mst_groups&subpage=edit&sid=" . $targetgroup . "'>" . $group->_data[0]->name . "</a>";
		}
		return $messages;
	}

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $titleTxt; ?></h2>
        <?php if(isset($messages) && count($messages) && count($messages) <= 5){
			foreach($messages AS $key => $message){ ?>
				<div class="notice notice-info is-dismissible">
					<p><strong><?php echo $message; ?></strong></p>
				</div><?php 
			}
		}
		if(isset($messages) && count($messages) && count($messages) > 5){ ?>		
			<div class="notice notice-info is-dismissible"><?php
				foreach($messages AS $key => $message){ ?>
					<p><strong><?php echo $message; ?></strong></p><?php 
				} ?>
			</div><?php 
		} ?>
		<div id="mst_list_members" class="mst_listing mst_list_members">
			<div class="wptl_container">
				<div class="wrap">

					<form enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
						<table class="adminform">
						<?php
							$task = 'saveimport';
							if(!$users){
								$task = 'startimport';
								?>
								<tr><th colspan="3" style="text-align:left;"><?php _e( 'Import Source' ); ?></th></tr>
								<tr>
									<td width="15px" style="text-align:right;">
										<input type="radio" name="datasource" value="local_file" id="local_file" checked="checked" />
									</td>
									<td width="100px" style="text-align:left;">
										<label for="filepath_local">
											<?php _e( 'Upload file (local)' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input class="input_box" id="filepath_local" name="filepath_local" type="file" size="57" />
									</td>
								</tr>
								<tr>
									<td width="15px" style="text-align:right;">
										<input type="radio" name="datasource" value="server_file" id="server_file" />
									</td>
									<td width="100px" style="text-align:left;">
										<label for="filepath" title="<?php _e("Path relative to WordPress's base directory (e.g. /wp-content/uploads/importme.csv means that the file importme.csv is in WordPress's uploads folder)", 'wp-mailster'); ?>">
											<?php _e( 'File Path (server)', 'wp-mailster' ); ?>:
										</label>
									</td>
									<td width="200px" style="text-align:left;">
										<input class="inputbox" name="filepath" value="/wp-content/uploads/users.csv" size="30" maxlength="255" id="filepath" title="<?php _e("Path relative to WordPress's base directory (e.g. /wp-content/uploads/importme.csv means that the file importme.csv is in WordPress's uploads folder)", 'wp-mailster'); ?>"/>
									</td>
								</tr>
												
								<tr>
									<td width="100px" style="text-align:right;" colspan="2">
										<label for="autoformat" title="<?php esc_attr_e( 'Automatically detect CSV file format', 'wp-mailster' ); ?>">
											<?php esc_html_e( 'Automatically detect CSV file format', 'wp-mailster' ); ?>
										</label>
									</td>
									<td width="200px">
										<input type="radio" name="autoformat" value="1" id="auto_detect_on" checked="checked" />
										<?php echo __('Yes'); ?>
									</td>
								</tr>				
								<tr>
									<td width="100px" colspan="2">&nbsp;</td>
									<td width="200px">
										<input type="radio" name="autoformat" value="0" id="auto_detect_off" />
										<?php echo _('No'); ?>
									</td>
								</tr>
								
								
								<tr style="display:none;" class="manualformat">
									<td width="100px" style="text-align:right;" colspan="2">
										<label for="delimiter" title="<?php _e('Character/letter separating name and email column', 'wp-mailster' );?>">
											<?php _e( 'Delimiter', 'wp-mailster' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input class="inputbox" name="delimiter" value=";" size="3" maxlength="5" id="delimiter" title="<?php _e('Character/letter separating name and email column', 'wp-mailster' );?>"/>
									</td>
								</tr>
								<tr style="display:none;" class="manualformat">
									<td width="100px" style="text-align:right;" colspan="2">
										<label for="dataorder" title="<?php _e( 'Order of the columns (name, email) in the CSV file', 'wp-mailster' ); ?>">
											<?php _e( 'Data order in CSV file', 'wp-mailster' ); ?>:
										</label>
									</td>
									<td width="200px">
										<input type="radio" name="dataorder" value="name_del_email" id="name_del_email" checked="checked" />
										<label for="name_del_email"><?php _e( 'Name [delimiter] Email', 'wp-mailster' ); ?></label>
									</td>
								</tr>
								<tr style="display:none;" class="manualformat">
									<td width="100px" colspan="2">&nbsp;</td>
									<td width="200px">
										<input type="radio" name="dataorder" value="email_del_name" id="email_del_name" />
										<label for="email_del_name"><?php _e( 'Email [delimiter] Name', 'wp-mailster' ); ?></label>
									</td>
								</tr>
                                <tr style="display:none;" class="manualformat">
                                    <td width="100px" colspan="2">&nbsp;</td>
                                    <td width="200px">
                                        <input type="radio" name="dataorder" value="email_only" id="email_only" />
                                        <label for="email_only"><?php _e( 'Email (without name)', 'wp-mailster' ); ?></label>
                                    </td>
                                </tr>
								<?php
							} else {
								?>
								<tr><th><?php _e( 'Import Result' ); ?></th></tr>
								<tr>
									<td><?php
                                        $userCount = count( $users );
                                        $maxInputVars = ini_get('max_input_vars');
                                        if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                                            $maxInputVars = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                                            $maxInputVars = floor($maxInputVars/2); // we need two (name + email) inputs per user entry
                                        }
                                        if($userCount > $maxInputVars){
                                            $userCount = $maxInputVars;
                                        }
                                        echo sprintf(__( '%d user data sets found', 'wp-mailster' ), $userCount); ?></td>
								</tr>
								<?php
							}
						?>
						</table>
						<table>
							<tr>
								<th colspan="5" style="text-align:left;"><?php _e( 'Import Options', 'wp-mailster' ); ?></th>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="duplicateopt"  title="<?php _e( 'Decide how duplicate user data records are treated if they occur', 'wp-mailster' );?>">
										<?php _e( 'Options for duplicates', 'wp-mailster' ); ?>:
									</label>
								</td>
								<td width="200px" colspan="2">
									<input type="radio" name="duplicateopt" value="merge" id="merge" checked="checked" />
									<label for="merge"><?php _e( 'Merge user data (no duplicates)', 'wp-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">&nbsp;</td>
								<td width="200px" colspan="2">
									<input type="radio" name="duplicateopt" value="ignore" id="ignore" />
									<label for="ignore"><?php _e( 'Ignore (don\'t avoid duplicates)', 'wp-mailster' ); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" style="text-align:right;" colspan="2">
									<label for="importtask"  title="<?php _e( 'Specify if the users should be directly added to a group or a mailing list (optional)', 'wp-mailster' );?>">
										<?php _e( 'Import users and...', 'wp-mailster' ); ?>:
									</label>
								</td>
								<td width="200px">
									<input type="radio" name="importtask" value="importonly" id="importonly" checked="checked" />
									<label for="importonly"><?php _e( ' Nothing else (import only)', 'wp-mailster'); ?></label>
								</td>
								<td>&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td>
									<input type="radio" name="importtask" value="add2group" id="add2group" />
									<label for="add2group"><?php _e( 'Add to group', 'wp-mailster' ); ?></label>
								</td>
								<td>
									<label for="targetgroup"><?php _e( 'Choose Group', 'wp-mailster' ); ?></label><br/>
									<select id="targetgroup" name="targetgroup" style="width:180px" disabled="disabled">
										<option value="0" selected="selected"><?php echo '< ' . __( 'New Group', 'wp-mailster' ) . ' >'; ?></option>
										<?php
											for($i=0, $n=count( $groups ); $i < $n; $i++) {
												$group = &$groups[$i];
												?>
												<option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td><?php _e( 'New Group Name', 'wp-mailster' ); ?><br/><input type="text" name="newgroupname" value="<?php echo ($users ? $newgroupname : __( 'New Group', 'wp-mailster' )); ?>" id="newgroupname" disabled="disabled" /></td>
							</tr>
							<tr>
								<td width="100px" colspan="2">&nbsp;</td>
								<td width="200px">
									<input type="radio" name="importtask" value="add2list" id="add2list" />
									<?php _e( 'Add as recipients', 'wp-mailster' ); ?>
								</td>
								<td width="200px" rowspan="3">
									<label for="targetlist"><?php _e( 'Choose a mailing list', 'wp-mailster' ); ?></label><br/>
									<select id="targetlist" name="targetlist" style="width:180px" disabled="disabled">
										<?php
											for($i=0, $n=count( $mailLists ); $i < $n; $i++) {
												$mailingList = &$mailLists[$i];
												$selected = ($i==0 ? 'selected="selected"' : '');
												?>
												<option value="<?php echo $mailingList->id; ?>" <?php echo $selected; ?>><?php echo $mailingList->name; ?></option>
												<?php
											}
										?>
									</select>
								</td>
								<td>&nbsp;</td>
							</tr>
							<?php
							if(!$users){
								?>
							<tr>
								<td colspan="3">&nbsp;</td>
								<td width="100px" style="text-align:right;">
									<input type="submit" name="submitbutton" value="<?php _e( 'Import Now', 'wp-mailster' ); ?>" id="submitbutton" class="submitButton" />
								</td>
								<td>&nbsp;</td>
							</tr>
							<?php } else { ?>
							<tr><td colspan="5">&nbsp;</td></tr>
							<tr><td colspan="3">&nbsp;</td><td><input type="submit" value="<?php _e( 'Save New Users', 'wp-mailster' ); ?>" class="submitButton" style="background-color:green;" /></td><td>&nbsp;</td></tr>
							<tr><th colspan="3"><?php _e( 'Preview Users to Import', 'wp-mailster' ); ?></th><td>&nbsp;</td></tr>
							<tr><th>&nbsp;</th><th><?php _e( 'Name', 'wp-mailster' ); ?></th><th><?php _e( 'Email', 'wp-mailster' ); ?></th><th>&nbsp;</th></tr>
							<?php
								$n=count( $users );
                                $maxInputVars = ini_get('max_input_vars');
                                if($maxInputVars > 0 && $maxInputVars > $inputVarOffset){
                                    $maxInputVars = $maxInputVars - $inputVarOffset; // we need some variables for the control handling...
                                    $maxInputVars = floor($maxInputVars/2); // we need two (name + email) inputs per user entry
                                }else{
                                    $maxInputVars = ini_get('max_input_vars');
                                }
								if($n > 1) {
									for ( $i = 0; $i < $n; $i ++ ) {
										$user = $users[ $i ];
										?>
										<tr>
											<td style="text-align:right;"><?php echo( $i + 1 ); ?></td>
											<td><input name="name<?php echo $i; ?>" value="<?php echo $user['name'] ?>"
													   size="50" maxlength="255"/></td>
											<td><input name="email<?php echo $i; ?>"
													   value="<?php echo $user['email'] ?>" size="50" maxlength="255"/>
											</td>
											<td>&nbsp;</td>
										</tr>
										<?php
                                        if(($i+1) >= $maxInputVars){
                                            break; // do not continue further if too much input variables would be present
                                        }
									}
								}
							}
							?>
						</table>
						<input type="hidden" name="task" value="<?php echo $task; ?>" />
						<input type="hidden" name="usercount" value="<?php echo ($users ? count( $users ) : 0); ?>" />
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
	<script type="text/javascript">
		var $j = jQuery.noConflict();
		$j(document).ready(
			function () {
				var usersImported = '<?php echo ($users ? 'true' : 'false'); ?>';
				if(usersImported == 'true'){
					var importTask = '<?php echo $importtask; ?>';
					var duplicateopt = '<?php echo $duplicateopt; ?>';
					var selectCase = false;
					if(importTask == 'add2group'){
						$j('#add2group').attr('checked', 'checked');
						var targetValue = '<?php echo $targetgroup; ?>';
						var targetElement = '#targetgroup';
						selectCase = true;
					}else if(importTask == 'add2list'){
						$j('#add2list').attr('checked', 'checked');
						var targetValue = '<?php echo $targetlist; ?>';
						var targetElement = '#targetlist';
						selectCase = true;
					}
					if(selectCase == true){
						$j(targetElement + ' option').each(function(){
							var value = $j(this).attr("value");
							if(value == targetValue)
							{
								$j(this).attr("selected", "selected");
							}
						});
					}
					if(duplicateopt == 'ignore'){
						$j('#ignore').attr('checked', 'checked');
					}else{
						$j('#merge').attr('checked', 'checked');
					}
				}else{
					var importTarget = '<?php echo ($importtarget ? $importtarget : ''); ?>';
					if(importTarget != ''){
						if(importTarget == 'add2group'){
							$j('#add2group').attr('checked', 'checked');
						}else if(importTarget == 'add2list'){
							$j('#add2list').attr('checked', 'checked');
						}else{
							$j('#importonly').attr('checked', 'checked');
						}
						toggleImportTarget(importTarget);
					}
				}
				toggleImportTarget(importTask);
				toggleImportSource('local_file');

				$j('#importonly').click(function () {
					if(this.checked == true){
						toggleImportTarget('importonly');
					}
				});

				$j('#add2group').click(function () {
					if(this.checked == true){
						toggleImportTarget('add2group');
					}
				});

				$j('#add2list').click(function () {
					if(this.checked == true){
						toggleImportTarget('add2list');
					}
				});

				$j('#targetgroup').click(function () {
					if(this.value == 0){
						$j('#newgroupname').removeAttr('disabled');
					}else{
						$j('#newgroupname').attr('disabled',  'disabled');
					}
				});

				$j('#local_file').click(function () {
					if(this.checked == true){
						toggleImportSource('local_file');
					}
				});

				$j('#server_file').click(function () {
					if(this.checked == true){
						toggleImportSource('server_file');
					}
				});				
				
				$j("input:radio[name ='autoformat']").click(function () {
					toggleAutoDetectFormat();
				});

			});

		function toggleImportTarget(importTarget){
			if(importTarget == 'importonly'){
				$j('#newgroupname').attr('disabled',  'disabled');
				$j('#targetgroup').attr('disabled',  'disabled');
				$j('#targetlist').attr('disabled',  'disabled');
			}else if(importTarget == 'add2group'){
				if($j('#targetgroup').val() == 0){
					$j('#newgroupname').removeAttr('disabled');
				}else{
					$j('#newgroupname').attr('disabled',  'disabled');
				}
				$j('#targetgroup').removeAttr('disabled');
				$j('#targetlist').attr('disabled',  'disabled');
			}else if(importTarget == 'add2list'){
				$j('#newgroupname').attr('disabled',  'disabled');
				$j('#targetgroup').attr('disabled',  'disabled');
				$j('#targetlist').removeAttr('disabled');
			}
		}

		function toggleImportSource(importSource){
			if(importSource == 'local_file'){
				$j('#filepath_local').removeAttr('disabled');
				$j('#filepath').attr('disabled',  'disabled');
			}else if(importSource == 'server_file'){
				$j('#filepath_local').attr('disabled',  'disabled');
				$j('#filepath').removeAttr('disabled');
			}
		}
		
		function toggleAutoDetectFormat(){
			if(parseInt($j("input:radio[name ='autoformat']:checked").val()) > 0){
				$j('.manualformat').hide();
			}else{
				$j('.manualformat').show();
			}
		}

		function submitbutton(task)
		{
			var form = document.adminForm;
			if (task == 'cancel') // check we aren't cancelling
			{	// no need to validate, we are cancelling
				submitform( task );
				return;
			}else{
				submitform( task );
			}
		}
	</script>

<?php
	function wpmst_parse_file_errors($file = ''){
		$result = array();
		$result['error'] = 0;

		if($file['error']){
			$result['error'] = "No file uploaded or there was an upload error!";
			return $result;
		}
		if(($file['size'] > wp_max_upload_size())){
			$result['error'] = 'Your file was ' . $file['size'] . ' bytes! It must not exceed ' . wp_max_upload_size() . ' bytes.';
		}
		return $result;
	}

	/**
	 * logic for cancel an action
	 */
	function cancel(){
		// TODO get Params to determine where to return to (e.g. users, groups, list....)
		//$this->setRedirect( 'index.php?option=com_mailster&view=groupusers' );
	}

	function reviewimports(){
		import();
	}
