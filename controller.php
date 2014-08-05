<?php      

defined('C5_EXECUTE') or die(_("Access Denied."));

class EsitefulUserImportPackage extends Package {

	protected $pkgHandle = 'esiteful_user_import';
	protected $appVersionRequired = '5.4.1';
	protected $pkgVersion = '0.9.0'; 
	
	public function getPackageName() {
		return t("eSiteful User Importer"); 
	}	
	
	public function getPackageDescription() {
		return t("Import or delete users via CSV.");
	}
	 
	
	public function install() {
		$pkg = parent::install();		
		
		//install single pages for the dashboard
		
			
		$this->setup($pkg);
		
	}
	
	public function upgrade(){
		parent::upgrade();	
		
		$this->setup($this);
	}
	
	
	public function setup($pkg){
		$this->setupSinglePages($pkg);	
	}
	
	
	
	public function setupSinglePages($pkg){
		Loader::model('single_page');
		$helper = Loader::helper('esiteful_user_import/package', 'esiteful_user_import');
		
		$page = $helper->addSinglePage('/dashboard/users/esiteful_csv_import', array(
			'cName'=>'CSV Import',
			'cDescription'=>t('Import users via CSV.')
		), $pkg);
		
		$page = $helper->addSinglePage('/dashboard/users/esiteful_csv_delete', array(
			'cName'=>'CSV Delete',
			'cDescription'=>t('Delete users via CSV.')
		), $pkg);

	}

}//end class