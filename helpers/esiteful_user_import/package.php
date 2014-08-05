<?php defined('C5_EXECUTE') or die;

class EsitefulUserImportPackageHelper {
	
	public function addUserGroup($name, $description){
		$group = Group::getByName($name);
		if(!is_object($group)){
			$group = Group::add($name, $description);
		}else{
			$group->update($name, $description);	
		}
		return $group;
	}
	

	public function addAttributeSet($handle, $name, $category, $pkg){
		$attributeSet = AttributeSet::getByHandle($handle);
		if(!is_object($attributeSet)){
			$collectionAkc = AttributeKeyCategory::getByHandle($category);
			$collectionAkc->setAllowAttributeSets(AttributeKeyCategory::ASET_ALLOW_SINGLE);
			$attributeSet = $collectionAkc->addSet($handle, $name, $pkg);
		}
		return $attributeSet;
	}
	
	public function addAttributeType($handle, $name, $pkg){
		$attributeType = AttributeType::getByHandle($handle);
		if(!is_object($attributeType)) {
			$attributeType = AttributeType::add($handle, $name, $pkg);
		}else{
			$path = $attributeType->getAttributeTypeFilePath(FILENAME_ATTRIBUTE_DB);
			if ($path) {
				Package::installDB($path);
			}	
		}
		return $attributeType;
	}
	
	public function addCollectionAttributeKey($data, $attributeSet, $pkg){
		
		$attributeKey = CollectionAttributeKey::getByHandle($data['akHandle']);
		if(!is_object($attributeKey)){
			$attributeType = AttributeType::getByHandle($data['akType']);
			if(!is_object($attributeType)){
				throw new Exception(t('AttributeType "%s" not available for CollectionAttributeKey "%s".', $data['akType'], $data['akHandle']));	
			}else{
				$attributeKey = CollectionAttributeKey::add($attributeType, $data, $pkg);
			}
		}else if(!in_array($data['akType'], array('select'))){
			$attributeKey->update($data);
		}
		if(is_object($attributeKey) && is_object($attributeSet)){
			$attributeKey->setAttributeSet($attributeSet);
		}
		return $attributeKey;
	}
	
	public function addFileAttributeKey($data, $attributeSet, $pkg){
		$attributeKey = FileAttributeKey::getByHandle($data['akHandle']);
		if(!is_object($attributeKey)){
			$attributeType = AttributeType::getByHandle($data['akType']);
			if(!is_object($attributeType)){
				throw new Exception(t('AttributeType "%s" not available for FileAttributeKey "%s".', $data['akType'], $data['akHandle']));	
			}else{
				$attributeKey = FileAttributeKey::add($attributeType, $data, $pkg);
			}
		}else if(!in_array($data['akType'], array('select'))){
			$attributeKey->update($data);
		}
		if(is_object($attributeKey) && is_object($attributeSet)){
			$attributeKey->setAttributeSet($attributeSet);
		}
		return $attributeKey;
	}


	public function addUserAttributeKey($data, $attributeSet, $pkg){
		$attributeKey = UserAttributeKey::getByHandle($data['akHandle']);
		if(!is_object($attributeKey)){
			$attributeType = AttributeType::getByHandle($data['akType']);
			if(!is_object($attributeType)){
				throw new Exception(t('AttributeType "%s" not available for UserAttributeKey "%s".', $data['akType'], $data['akHandle']));	
			}else{
				$attributeKey = UserAttributeKey::add($attributeType, $data, $pkg);				
			}
		}else if(!in_array($data['akType'], array('select'))){
			$attributeKey->update($data);
		}
		if(is_object($attributeKey) && is_object($attributeSet)){
			$attributeKey->setAttributeSet($attributeSet);
		}
		return $attributeKey;
	}
	
	public function addSelectAttributeOption($attributeKey, $option){
		$opt = SelectAttributeTypeOption::getByValue($option, $attributeKey);
		if(!is_object($opt)){
			$opt = SelectAttributeTypeOption::add($attributeKey, $option);
		}
		return $opt;
	}



	public function addAttributeKeyToPageType($pageType, $attributeKey){
		if(is_string($attributeKey)){
			$attributeKey = CollectionAttributeKey::getByHandle($attributeKey);
			if(!is_object($attributeKey)){
				return false;	
			}
		}
		if(!$pageType->isAvailableCollectionTypeAttribute($attributeKey)){
			$pageType->assignCollectionAttribute($attributeKey);
		}
		return true;
	}

	public function addOrRefreshBlockType($handle, $pkg){
		$blockType = BlockType::getByHandle($handle);
		if(!$blockType) {
			BlockType::installBlockTypeFromPackage($handle, $pkg); 
			$blockType = BlockType::getByHandle($handle);
		}else{
			$blockType->refresh();
		}
		return $blockType;		
	}

	public function addSinglePage($path, $data, $pkg){
		Loader::model('single_page');
		$page = Page::getByPath($path);
		if(!is_object($page) || $page->isError()){
			$data['cPath'] = $path;
			$page = SinglePage::add($path, $pkg);
		}else{
			$page = SinglePage::getByID($page->getCollectionID());			
		}
		$page->update($data);
		//$page->refresh();
		return $page;
	}

	public function overrideSinglePage($page, $pkg){
		$db = Loader::db();
		if(is_string($page)){
			$page = Page::getByPath($page);
			$page = SinglePage::getByID($page->getCollectionID());
		}else if(is_numeric($page)){
			$page = SinglePage::getByID($page);
		}
		if(!is_object($page) || $page->isError()) throw new Exception(t('Single page %s does not exist', $page));
		$db->Execute('update Pages set pkgID = ? where cID = ?', array($pkg->pkgID, $page->getCollectionID()));		
		//$page->refresh(); //need to refresh manually or it reverts the package for some reason

		return $page;
	}
	
	public function releaseSinglePage($page, $pkg){
		if(is_string($page)){
			$page = Page::getByPath($page);
			$page = SinglePage::getByID($page->getCollectionID());
		}else if(is_numeric($page)){
			$page = SinglePage::getByID($page);
		}
		$db->Execute('update Pages set pkgID = ? where cID = ?', array(0, $page->getCollectionID()));
		//$page->refresh();
		return $page;
	}

}