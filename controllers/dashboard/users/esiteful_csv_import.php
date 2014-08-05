 <?php  

class DashboardUsersEsitefulCsvImportController extends Controller {
	
	public function upload(){
		
		if ($this->isPost()) {
			
			ini_set('max_execution_time', 600);
			
			$importHelper = Loader::helper('esiteful_user_import', 'esiteful_user_import');
			$valc = Loader::helper('concrete/validation');
			
			$error = array();
			$message = array();
			
			if (is_uploaded_file($_FILES['importFile']['tmp_name'])) {
				
				$file_handle = fopen($_FILES['importFile']['tmp_name'], "r");
				
				$i = 0;
				while (!feof($file_handle) ) {
					$line = fgetcsv($file_handle, 0, ',', '"', '\\');
					
					if($i == 0){
						//Get headers
						$columns = $line;
						if($columns[0]!='uName'||$columns[1]!='uPassword'||$columns[2]!='uEmail'){
							$error[] = t('Header does not contain uName, uPassword, and uEmail.');
							break;
						}
						
					}else if($line){
						//Import	
						$data = array_combine($columns, $line);		
						
						if(trim(implode('',$data)) == '') continue; //skip empty lines				
						
						//Generate a username for them
						if(!$data['uName'] && !UserInfo::getByEmail($data['uEmail'])){
							$data['uName'] = preg_replace('/^(.+)(@.+)$/', '$1', $data['uEmail']);
							$data['uName'] = preg_replace('/[^a-zA-Z0-9\-\_]/', '', $data['uName']);
							$uName = $data['uName'];
							$uNameInc = 1;
							while(!$valc->isUniqueUsername($data['uName'])){
								$data['uName'] = $uName.$uNameInc;
								$uNameInc++;
							}
						}
						
						$usernameUser = UserInfo::getByUserName($data['uName']);
						if(is_object($usernameUser) && $usernameUser->isSuperUser()){
							continue; //don't allow updates to the super user
						}
						
						$update = FALSE;
						if(is_array($_REQUEST['update_existing']) && count($_REQUEST['update_existing'])){
							$update = implode(',', $_REQUEST['update_existing']);
						}
						$result = $importHelper->importUserData($data, $update, $_REQUEST['password_format'] == 'raw');
						
						if($result['userInfo'] && !count($result['error'])){
							$subject = $result['userInfo'];
							
							if(is_array($_POST['uGroups'])){
								foreach($_POST['uGroups'] as $gID){	
									$group = Group::getByID($gID);
									$subjectUser = $subject->getUserObject();
									if(!$subjectUser->inGroup($group)){
										$subjectUser->enterGroup($group);
									}
								}
							}
							
							//$message[]= ($result['isExisting'] ? t('Updated: ') : t('Created: ')) . t('%s : %s : %s', '<a href="'.View::getInstance()->url('/dashboard/users/search?uID='.$subject->getUserID()).'">'.$subject->getUserID().'</a>', $subject->getUserName(), $subject->getUserEmail());
							$message[]= ($result['isExisting'] ? t('Updated: ') : t('Created: ')) . t('%s : %s : %s', $subject->getUserID(), $subject->getUserName(), $subject->getUserEmail());	
						
							//Trigger event
							Events::fire('on_user_update', $subject);
						}
					}
					$i++;
				}
				
				fclose($file_handle);
			}
			
			$this->set('error', $error);
			$this->set('message', implode("\n", $message));
		}
	}
	
	public function upload2() {
		if ($this->isPost()) {
			
			ini_set('max_execution_time', 600);
		
			$u = new User();
			$uh = Loader::helper('concrete/user');
			$txt = Loader::helper('text');
			$vals = Loader::helper('validation/strings');
			$valt = Loader::helper('validation/token');
			$valc = Loader::helper('concrete/validation');
			$dtt = Loader::helper('form/date_time');
			$form = Loader::helper('form');
			$ih = Loader::helper('concrete/interface');
			$av = Loader::helper('concrete/avatar');
			$pkg = Package::getByHandle('hsec_user_tools');
			Loader::model('attribute/categories/user');

			
			$error = array();
			
			if (is_uploaded_file($_FILES['importFile']['tmp_name'])) {
				
				$file_handle = fopen($_FILES['importFile']['tmp_name'], "r");
				
				$i = 0;
				while (!feof($file_handle) ) {
					$line = fgetcsv($file_handle, 0);
					
					//check for required attributes
					if($i==0){//first row
						if($line[0]!='uName'||$line[1]!='uPassword'||$line[2]!='uEmail'){
							$error[] = 'Header row not formatted correctly';
						}
						$importHandles = array();
						foreach ($line as $key => $value) {//loop through headers to check to see if handles are valid
							if($key > 2 && !in_array('uIsActive','uIsValidated', 'uIsFullRecord')){
								
								$attribs = UserAttributeKey::getList();
								foreach($attribs as $ak) {
									$handleArray[] = $ak->getAttributeKeyHandle();
								}
								if(!in_array($value, $handleArray)){
									$error[] = 'Not a valid attribute: ' . strtoupper($value);
								} else {
									//$importHandles[] = $value;
								}
								$dataIndex = 'data_'+$key;
								$importHandles[$dataIndex] = $value;
							}
							
						}//end foreach
					}else{
						break;	
					}
				$i++;
				}//end reading of the csv
				
				fclose($file_handle);


			} else {
				$error[] = "Select a CSV file to upload.";			
			}

			
			if ($error) {
				$this->set('error', $error);
			} else { //DO THE IMPORT
				
				$file_handle = fopen($_FILES['importFile']['tmp_name'], "r");
				
				$i = 0;
				$importCount = 0;
				while (!feof($file_handle) ) {
					$line = fgetcsv($file_handle, 0);
					$import = 1;
					
					//check for required attributes
					if($i!=0){//ignore first row for import
						
						$username = trim($line[0]);//clean the username
						$username = preg_replace("/\s+/", " ", $username);//clean the username
						
						$password = $line[1];
						
						$email = $line[2];
						
						//BEGIN VALIDATE USERNAME
						if (strlen($username) < USER_USERNAME_MINIMUM) {
							$uname = strtoupper($username);
							if(strlen($username) > 1) {
								$success_message[] = t('User %s not imported. Reason: %s',$uname,'Username too short');								
							}
							$import = 0;
						}
					
						if (strlen($username) > USER_USERNAME_MAXIMUM) {
							$uname = strtoupper($username);
							$success_message[] = t('User %s not imported. REASON: %s',$uname,'Username too long');
							$import = 0;
						}
					
						/* if (strlen($username) >= USER_USERNAME_MINIMUM && !$valc->username($username)) {
							if(USER_USERNAME_ALLOW_SPACES) {
								$uname = strtoupper($username);
								$success_message[] = t('User %s not imported. REASON: %s',$uname,'A username may only contain letters, numbers and spaces');
								$import = 0;
							} else {
								$uname = strtoupper($username);
								$success_message[] = t('User %s not imported. REASON: %s',$uname,'A username may only contain letters or numbers.');
								$import = 0;
							}
						} */
						
						if (!$valc->isUniqueUsername($username)) {
							if($_POST['update_duplicate_username'] == '1'){
								$success_message[] = t('Updated: %s',$username);
								$import = 2;
							}else{
								$success_message[] = t('Ignored: %s',$username);
								$import = 0;
							}							
						}		
					
						if ($username == USER_SUPER) {
							$uname = strtoupper($username);
							$success_message[] = t('User %s not imported. REASON: %s',$uname,'Invalid Username');
							$import = 0;
						}
						//END VALIDATE USER NAME
						
						
						//BEGIN VALIDATE EMAIL
						if (!$vals->email($email)) {
							$uname = strtoupper($username);
							if(strlen($email) > 0) {
								$success_message[] = t('User %s not imported. REASON: %s',$uname,'Invalid email address provided.');
							}
							$import = 0;
						} else if (!$valc->isUniqueEmail($email) && $import != 2) {
							$uname = strtoupper($username);
							//$success_message[] = t('User %s not imported. REASON: %s',$uname,'The email address is already in use.');
							if($_POST['update_duplicate_email'] == '1'){
								$success_message[] = t('Updated: %s', $email);
								$import = 3;
							}else{								
								$success_message[] = t('Ignored: %s', $email);
								$import = 0;
							}
						}
						//END VALIDATE EMAIL
						
						//BEGIN VALIDATE PASSWORD
						if($_POST['password_format'] == 'raw'){
							if ((strlen($password) < USER_PASSWORD_MINIMUM) || (strlen($password) > USER_PASSWORD_MAXIMUM)) {
								$uname = strtoupper($username);
								if(strlen($password) > 0) {
									$success_message[] = t('User %s not imported. REASON: %s',$uname,'The password is not the right length');
								}
								$import = 0;
							}
								
							if (strlen($password) >= USER_PASSWORD_MINIMUM && !$valc->password($password)) {
								$uname = strtoupper($username);
								$success_message[] = t('User %s not imported. REASON: %s',$uname,'A password may not contain ", \', >, <, or any spaces.');
								$import = 0;
							}
						}
						//END VALIDATE PASSWORD
						
						//REGISTER THE USER
						if ($import!=0) {
							$data = array('uName' => $username, 'uPassword' => $password, 'uEmail' => $email);
							if($import == 1){
								// do the registration								
								$uo = UserInfo::add($data);
							}else if($import == 2){
								$uo = UserInfo::getByUserName($username);
								if($email){
									$uo->update(array('uEmail' => $email)); //Update the user's email
								}
							}else if($import == 3){
								$uo = UserInfo::getByEmail($email);
								if($username){
									$uo->update(array('uName' => $username)); //Update the username
								}
							}
							
							
							if($POST['password_format'] == 'raw' && $password){
								
								$uo->changePassword($password);
								
							}else if($_POST['password_format'] == 'encrypted'){
								$db = Loader::db();
								//if ($this->uID) {
								$v = array($password, $this->uID);
								$q = "update Users set uPassword = ? where uID = ?";
								$r = $db->prepare($q);
								$res = $db->execute($r, $v);
					
									//Events::fire('on_user_change_password', $this, $newPassword);
								//}	
							}
							
							$importCount++; 
							
							if(is_object($uo)){//enter attributes
								$in = 0;
								foreach ($line as $k => $v) {//loop through headers to check to see if handles are valid
									if($in > 2){
										//$success_message[] = $importHandles[$in];
										
										//if this is an address field, then we need to make this an array
										$uat = UserAttributeKey::getByHandle($importHandles[$in]);
										
										$uatype = $uat->getAttributeType();
										$typeName = $uatype->getAttributeTypeName();
										if($typeName=='Address'){
											//$success_message[] = 'Added Address';
											$addressArray = explode('|',$v);
											$addArray['address1'] = $addressArray[0];
											$addArray['address2'] = $addressArray[1];
											$addArray['city'] = $addressArray[2];
											$addArray['state_province'] = $addressArray[3];
											$addArray['country'] = $addressArray[4];
											$addArray['postal_code'] = $addressArray[5];
											$uo->setAttribute($importHandles[$in], $addArray);
										} else if($typeName=='Select'){
											$addArray = explode('|', $v);
											if(is_array($addArray)){
												$uo->setAttribute($importHandles[$in], $addArray);
											} else {
												$uo->setAttribute($importHandles[$in], $v);
											}
										} else { 
											$uo->setAttribute($importHandles[$in], $v);
										}
									}
								$in++;
								}//end foreach
							}//end if is object
							
							//enter groups
							if(is_array($_POST['group'])){
								foreach($_POST['group'] as $gk => $gv){
	
									$group = Group::getByID($gv);
									$uID = $uo->getUserID();
									$u = User::getByUserID($uID);
									$u->enterGroup($group);
								}
							}
							
						}
						//END REGISTER
					}	
					$i++;
				}//end reading of the csv
				
				fclose($file_handle);
				$success_message_final = $importCount . " user(s) imported.";
				
				if(is_array($success_message)){
					$success_message_final .= " Results: <ul><li>". implode("</li><li>", $success_message) . "</li></ul>";
				}
				
				$this->set('message', $success_message_final);
			}
			
		}
	}
	

	public function download() {
		
		Loader::model('attribute/categories/user');
	
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"AdvancedUserImport.csv\"");
		$headers	=	"username,password,email";
		
		$attribs = UserAttributeKey::getList();
		foreach($attribs as $ak) {
			$headers	.=	"," . $ak->getAttributeKeyHandle();
		}
		
		$headers	.=	"\n";
		echo $headers; 
		exit;
	}
			
		
}//END CLASS
