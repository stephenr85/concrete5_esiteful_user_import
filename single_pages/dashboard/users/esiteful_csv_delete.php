<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));
$form = Loader::helper('form');
$ast = Loader::helper('concrete/asset_library'); 
$ih = Loader::helper('concrete/interface');
?>

<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('Delete Users'), false, 'span10 offset1', false)?>

<div class="ccm-pane-body">	

<h2>Upload File</h2>
<form method="post" id="hsec-users-delete" enctype="multipart/form-data" action="<?php  echo $this->url('/dashboard/users/user_import_delete/', 'delete')?>">

    <label>CSV File<br/>
      <input type="file" name="csvFile" id="csvFile" />
    </label>
    
    
    <p class="ccm-note">
    The first row of this file should contain the column name with the identifier used, i.e. "ID", "Username", or "Email". The first column of each row should contain that identifier.
    </p>
    
  </p>



	<?php
		
	?>
    
    <div class="alert warn">
    <p><?php echo t('Users will be permanently deleted from the system. Make sure you have a reliable backup.'); ?></p>
    <p><?php echo t('A large delete could take several minutes.'); ?></p>
    </div>
    <div class="ccm-buttons">
    	<?php echo $form->submit('import', t('Delete Users')); ?>
    </div>
	
</form>

</div>

<script>
$(document).ready(function(){
	$('form#hsec-users-delete').on('submit', function(evt){
		if(!confirm('<?php echo t('Are you absolutely sure you want to delete the users in the specified CSV?') ?>')){
			evt.preventDefault();	
		}
	});
});
</script>


<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper()?>
	