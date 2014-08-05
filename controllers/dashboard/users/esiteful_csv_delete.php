<?php  

class DashboardUsersHsecDeleteController extends Controller {

	public function delete() {
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
			
			if (!is_uploaded_file($_FILES['csvFile']['tmp_name'])) {
				$error[] = "Select a CSV file to upload";	
				
			}else{
				
				$file_handle = fopen($_FILES['csvFile']['tmp_name'], "r");
				
				$identifier = NULL;
				$deleteCount = 0;
				$i = 0;
				while (!feof($file_handle) ) {
					$line = fgetcsv($file_handle, 0);
					
					
					if($i==0){//first row
						$identifier = strtolower($line[0]);
						
						if(!in_array($identifier, array('id', 'username', 'email'))){
							$error[] = 'Header row not formatted correctly.';
							break;
						}
					}else{
						if(empty($line[0])){
							continue;
						}
						$userInfo = NULL;
						if($identifier == 'id'){
							$userInfo = UserInfo::getByID($line[0]);
						}else if($identifier == 'username'){
							$userInfo = UserInfo::getByUserName($line[0]);
						}else if($identifier == 'email'){
							$userInfo = UserInfo::getByEmail($line[0]);
						}
						if(!is_object($userInfo)){
							$error[] = 'Unable to retrieve user "'.$line[0].'" from line '.$i.'.';
						}else if($userInfo->isError()){
							$error[] = $userInfo->error;
						}else{
							//do stuff with user	
							$userInfo->delete();
							$deleteCount++;
							$success_message[] = t('Deleted: %s - %s - %s', $userInfo->getUserID(), $userInfo->getUserName(), $userInfo->getUserEmail());
							
						}
						
					}	
				$i++;
				}//end reading of the csv
				
				fclose($file_handle);


			} 

			
			if ($error && (is_array($error) && count($error) > 0)) {
				$this->set('error', $error);
			} else { 
				
				$success_message_final = $deleteCount . " user(s) deleted.";
				
				if(is_array($success_message)){
					$success_message_final .= " Results: <ul><li>". implode("</li><li>", $success_message) . "</li></ul>";
				}
				
				$this->set('message', $success_message_final);
			}
			
		}
	}
	

			
		
}//END CLASS
