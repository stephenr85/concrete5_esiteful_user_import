<?php defined('C5_EXECUTE') or die("Access Denied.");


class EsitefulUserImportHelper {
	
	public function isUserInAnyGroup($user, $groups){
		$is = false;
		if(is_numeric($user)){
			$user = User::getByUserID($user);	
		}
		if(is_object($user) && !$user->isError()){
			$userGroups = $user->getUserGroups();
			$userGroupIDs = array_keys($userGroups);
			//print_r($groups);
			//echo "\n\n\n\n";
			//print_r($userGroupIDs);
			return count(array_intersect($groups, $userGroupIDs)) > 0;
		}
		return $is;	
	}
	
	public function importUserData($data, $update=TRUE, $rawPassword=FALSE){
		$result = array(
			'error'=>array(),
			'warning'=>array(),
			'success'=>FALSE
		);
		$db = Loader::db();
		
		//Look for existing user to update
		if($update){
			$select = 'select uID from Users ';
			$where = array();
			if(($update === TRUE || strpos($update, 'uID') !== FALSE) && $data['uID']){
				$where[]='uID = '.$db->quote($data['uID']);
				
			}elseif(($update === TRUE || strpos($update, 'uEmail') !== FALSE) && $data['uEmail']){
				$where[]='uEmail = '.$db->quote($data['uEmail']);
			}elseif(($update === TRUE || strpos($update, 'uName') !== FALSE) && $data['uName']){
				$where[]='uName = '.$db->quote($data['uName']);
				
			}			
			$query = $select;
			if(count($where)){
				$query .= ' where '.implode(' or ', $where);
			
				$uID = $db->GetOne($query);
			}
			
			if($uID){
				$subject = UserInfo::getByID($uID);	
			}
		}
		
		$result['userInfo'] = $subject;
		$result['isExisting'] = $isExisting = is_object($subject) && !$subject->isError();
		
		//Trigger event
		Events::fire('on_before_esiteful_user_import', $data, $isExisting, $subject);
		
		if($isExisting && $update == FALSE){
			$result['warning'][]=t('User %s (%s : %s) already exists.', $subject->getUserID(), $subject->getUserName(), $subject->getUserEmail());	
			return $result;
		}else if(!$isExisting){
			//Create a new user	
			unset($data['uID']);					
			$subject = UserInfo::add($data);
			//Generate a password for new users, if none is specified
			if(!$data['uPassword']){
				$newPassword = uniqid(substr($subject->getUserID(), 0, 8));
			}else{
				$newPassword = $data['uPassword'];	
			}
		}
		$result['userInfo'] = $subject;
		
		$subject->update($data);
		
		//Update the password
		if($data['uPassword'] && $rawPassword && $isExisting){
			$db->Execute("update Users set uPassword = ? where uID = ?", array($data['uPassword'], $subject->getUserID()));	
		}else if($data['uPassword'] && $isExisting){
			$subject->changePassword($data['uPassword']);
		}
		
		Loader::model('attribute/categories/user');
		
		//Set attributes
		foreach($data as $col=>$value){
			//exclude "ak_" && "custom_" prefixed values
			if(strpos($col, 'ak_') === 0){
				//try to import as an attribute
				$attrKey = str_replace('ak_', '', $col);
				$attr = UserAttributeKey::getByHandle($attrKey);
				if(is_object($attr)){
					switch($attr->atHandle){
						case 'address':
							$addressArray = explode('|',$value);
							$addArray['address1'] = $addressArray[0];
							$addArray['address2'] = $addressArray[1];
							$addArray['city'] = $addressArray[2];
							$addArray['state_province'] = $addressArray[3];
							$addArray['country'] = $addressArray[4];
							$addArray['postal_code'] = $addressArray[5];
							$subject->setAttribute($attr, $addArray);
							break;
						case 'select':
							$value = explode('|', $value);
							$subject->setAttribute($attr, count($value) > 1 ? $value : reset($value));
							break;
						default:
							$subject->setAttribute($attr, $value);
					}
				}else{
					$result['warnings'][] = t('Attribute "%s" does not exist.', $attrKey);	
				}
			}
		}
		
		if(isset($data['uIsActive'])){
			if($subject->isActive() && ($data['uIsActive'] == 'yes' || intval($data['uIsActive']) == 1)){
				$subject->activate();
			}elseif($subject->isActive()){
				$subject->deactivate();
			}
		}
		
		
		$result['success'] = TRUE;
		
		//Trigger event
		Events::fire('on_esiteful_user_import', $data, $isExisting, $subject);
		
		return $result;
	}
	
}