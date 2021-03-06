<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>
<div>
	<?php
	$ldapPkg = Package::getByHandle('itm_ldap');

	if (empty($ldapPkg)) :
		?>

		<h4><?= t('No options available.') ?></h4>
		<p>
			<?= t('Please install ITM LDAP package to gain filter functionality.') ?>
		</p>
		<?php
	else :
		?>
		<h4><?= t('Filter theses by selecting a user') ?></h4>

		<?php if ($this->controller->hasUsers()) : ?>
			<p id="userp">
				<?= $form->select('uName', $this->controller->getLdapUsers(), $uName, array('style' => 'width: 100%')) ?>
			</p>
		<?php else : ?>
			<?php echo t('There are currently no users available. Confirm dialog to show all theses.'); ?>
		<?php endif; ?>
	<?php endif; ?>
</div>