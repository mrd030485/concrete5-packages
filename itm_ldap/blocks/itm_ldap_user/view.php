<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
$ldapHelper = Loader::helper('itm_ldap', 'itm_ldap');

if (!strlen($uName)) :
	?>
	<h1 style="color: red; font-weight: bold">
		<?php echo t('Please select a user.'); ?>
	</h1>
<?php else : ?>
	<h1><?= $ldapHelper->getFullName($userInfo) ?></h1>
	<div>
		<?php
		$config = new Config();
		$config->setPackageObject('itm_ldap');
		$street = $config->get('ITM_LDAP_STREET');
		$streetNo = $config->get('ITM_LDAP_STREET_NO');
		$zip = $config->get('ITM_LDAP_ZIP');
		$city = $config->get('ITM_LDAP_CITY');
		$uniLinkText = $config->get('ITM_LDAP_UNI_LINKTEXT');
		$uniUrl = $config->get('ITM_LDAP_UNI_LINK');
		$instLinkText = $config->get('ITM_LDAP_INST_LINKTEXT');

		$name = $userInfo->getAttribute('name');
		$room = $userInfo->getAttribute('room_number');
		$phone = $userInfo->getAttribute('telephone_number');
		$fax = $userInfo->getAttribute('telefax_number');
		$title = $userInfo->getAttribute('title');
		$email = $userInfo->uEmail;
		$consultation = $userInfo->getAttribute('consultation');
		
		$av = Loader::helper('concrete/avatar');
		$imgWidth = 150;
		$paddingRight = 10;
		
		if ($userInfo->hasAvatar())
		{
			$imgWidth = getimagesize(DIR_FILES_AVATARS . '/' . $userInfo->getUserID() . '.jpg');

			if (is_array($imgWidth))
			{
				$imgWidth = $imgWidth[0];
			}
			else
			{
				$imgWidth = 160;
			}
		}
		
		echo '<div style="width: ' . ($imgWidth + $paddingRight) . 'px; float: left">';

		if ($userInfo->hasAvatar())
		{
			
			echo $av->outputUserAvatar($userInfo, true);
		}
		else
		{
			echo '<img src="' . DIR_REL . '/packages/itm_ldap/images/noavatar.png" width="' . $imgWidth . '" style="border: 1px solid #e4e4dd; padding: 1px;" alt="' . t('No avatar available') . '"/>';
		}

		echo '</div>';
		echo '<div style="margin-left: ' . ($imgWidth + $paddingRight) . 'px;">';
		
		if ($name != '')
		{
			echo $ldapHelper->getFullName($userInfo). "<br/>";
		}
		else
		{
			echo Page::getCurrentPage()->getCollectionName()."<br/>";
		}
		echo "<a href=\"$uniUrl\" target=\"_blank\">$uniLinkText</a><br/>";
		echo "$instLinkText<br/>";
		echo "$street $streetNo<br/>";
		echo $room == '' ? '' : ("$room<br/>");
		echo "$zip $city<br/><br/>";
		echo $phone == '' ? '' : t('Phone:') . " $phone<br/>";
		echo $fax == '' ? '' : t('Fax:') . " $fax<br/>";
		echo $email == '' ? '' : t('E-Mail:') . " <a href=\"mailto:$email\">$email</a><br/>";
		echo $consultation == '' ? '' : t('Consultation:') . " $consultation";

		echo '</div>';

		echo '<div class="ccm-spacer"></div>'
		?>

	</div>
<?php endif; ?>
