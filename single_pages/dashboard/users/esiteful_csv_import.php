<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));
$form = Loader::helper('form');
$ast = Loader::helper('concrete/asset_library'); 
$ih = Loader::helper('concrete/interface');
?>

<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('Import Users'), false, 'span10 offset1', false)?>

<div class="ccm-pane-body">
    
<h3><?php echo t('Upload File') ?></h3>
<form method="post" id="csvimport" enctype="multipart/form-data" action="<?php  echo $this->url('/dashboard/users/esiteful_csv_import/', 'upload')?>">

  <p>
    <label><?php echo t('CSV File') ?><br/>
      <input type="file" name="importFile" id="importFile" />
    </label>
  </p>
 <?php  Loader::model('search/group');
	$gl = new GroupSearch();
	$gl->updateItemsPerPage(100);
	$gResults = $gl->getPage();
?> 

<?php   if (is_array($gResults)) { ?>
<h3><?php echo t('Add Users to Group(s)'); ?></h3>

<div class="control-group">
<div class="controls">
<?php   foreach($gResults as $g): ?> 
	
	<label class="checkbox"><input type="checkbox" class="" name="uGroups[]" id="gID<?php  echo $g['gID']?>" value="<?php  echo $g['gID']?>" /><?php  echo $g['gName']?></label>
	
<?php  endforeach; ?>
</div>
</div>
<?php   } ?>

<div style="margin:1em 0;">
<h3><?php echo t('Options') ?></h3>
	
    <div><label class="checkbox"><input type="checkbox" name="update_existing[]" value="uID" /> <?php echo t('Update where uID already exists.'); ?></label></div>
    <div><label class="checkbox"><input type="checkbox" name="update_existing[]" value="uName" /> <?php echo t('Update where uName already exists.'); ?></label></div>
    <div><label class="checkbox"><input type="checkbox" name="update_existing[]" value="uEmail" /> <?php echo t('Update where uEmail already exists.'); ?></label></div>
    <br/>
    <div><label class="radio"><input type="radio" name="password_format" value="none" checked /> <?php echo t('Ignore passwords for existing users') ?>.</label></div>
    <div><label class="radio"><input type="radio" name="password_format" value="plain" /> <?php echo t('Update passwords for existing users') ?>.</label></div>
	<div><label class="radio"><input type="radio" name="password_format" value="raw" /> <?php echo t('Update pre-encrypted passwords (insert directly into DB)') ?>.</label></div>
	
	
</div>
          <div class="ccm-buttons">
          		<div class="alert warn">
                	<p><?php echo t('Always make sure you have a reliable backup before making large changes to the database.'); ?></p>
                </div>
				<?php echo $form->submit('import', t('Import Users')); ?>
		</div>
		<div class="ccm-spacer">&nbsp;</div>	
	<p>&nbsp;</p>
	
<h2>Instructions</h2>
<ul>
<li>In your CSV file, the first line should be the handles of the attributes you wish to import.</li>
<li>The first column should be "uName". If blank, uEmail will be used to generated a username.</li>
<li>The second column should be "uPassword". A password will be generated for new users, if none exists.</li>
<li>The third column should be "uEmail".</li>
<li>These are REQUIRED.</li>
</ul>

<h3>Some Notes On Attribute Types</h3>
<ul>
<li>ADDRESS: The Concrete5 address attribute type requires some special formatting in your CSV
<ul>
<li>To fill out the full address field, separate your address by using the bar: |</li>
<li>An Example: Address Line 1|Address Line 2|City|State|Country|PostalCode</li>
<li>For the country, use the country's 2 digit code. Example: US or CA</li>
</ul>
</li>
<li>CHECKBOX
<ul><li>In your CSV you can enter "yes" for a selected box and leave your cell blank for an unselected one</li></ul>
</li>

<li>DATE
<ul>
<li>If you want just the date entered, format like this: 2/26/1976 (no need for leading zeros on the month or day)</li>
<li>If you want date and time entered, format like this: 2/26/1976 1:00:00 PM</li>
</ul>
</li>

<li>SELECT
<ul>
	<li>To fill multiple values separate them by a bar: |</li>
	<li>An Example: Value One|Value Two|Value Three</li>
</ul>
</li>

</ul>

<h3>Additional Notes</h3>
<ul>
<li>This import does NOT handle custom attributes (those outside of a default Concrete5 install), so don't try to import them.</li>
<li>This import detects whether a username or email is already in use. If they are, the import will ignore that user and continue importing. You will be notified of the offending user(s) -- and the reason for not importing -- when the import is finished.</li>
</ul>
<p><a href="<?php  echo $this->url('/dashboard/users/import/', 'download')?>">Download a formatted CSV of existing users, complete with all attributes.</a></p>
	
</div>
<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper()?>
	