<h1 class="itmThesisEntryTitle"><?= $topic ?></h1>

<?php
switch ($type)
{
	case 0:
		$typePlain = t('Bachelor thesis');
		break;

	case 1 :
		$typePlain = t('Master thesis');
		break;

	default :
		$typePlain = t('Bachelor or master thesis');
		break;
}

switch ($status)
{
	case 0 :
		$statusPlain = t('Open');
		break;

	case 1 :
		$statusPlain = t('Running');
		break;

	default :
		$statusPlain = t('Finished');
		break;
}

if (empty($beginning) || $beginning == '0')
{
	$beginningPlain = 'As soon as possible';
}
else
{
	$beginningPlain = "From $beginning";
}

if (empty($student))
{
	$studentPlain = 'N/A';
}
else
{
	$studentPlain = $student;
}
?>

<div class="itmThesisEntryType">
	<span class="itmThesisEntryCaption"><?= t('Type') ?>:</span>
	<span class="itmThesisEntryValue"><?= $typePlain ?></span>
</div>
<div class="itmThesisEntryStatus">
	<span class="itmThesisEntryCaption"><?= t('Status') ?>:</span>
	<span class="itmThesisEntryValue"><?= $statusPlain ?></span>
</div>
<div class="itmThesisEntryBegin">
	<span class="itmThesisEntryCaption"><?= t('Begin') ?>:</span>
	<span class="itmThesisEntryValue"><?= $beginningPlain ?></span>
</div>
<div class="itmThesisEntryStudent"">
	 <span class="itmThesisEntryCaption"><?= t('Student') ?>:</span>
	<span class="itmThesisEntryValue"><?= $studentPlain ?></span>
</div>
<div class="itmThesisEntryTutor">
	<span class="itmThesisEntryCaption"><?= t('Tutor') ?>:</span>
	<span class="itmThesisEntryValue">
		<?php
		$ldapHelper = Loader::helper('itm_ldap', 'itm_ldap');
		if ($this->controller->isLdapTutor())
		{
			$ui = UserInfo::getByUserName($this->controller->getTutorName());
			if (!empty($ui))
			{
				$name = $ui->getAttribute('name');
				if (!empty($name))
				{
					$fullName = $ldapHelper->getFullName($ui);
					$link = $ldapHelper->getUserPageLink($this->controller->getTutorName());
					if ($link)
					{
						echo '<a href="' . $link . '">' . $fullName . '</a>';
					}
					else
					{
						echo $fullName;
					}
				}
			}
		}
		else
		{
			echo $tutor;
		}
		?>
	</span>
</div>
<div class="itmThesisEntrySupervisor">
	<span class="itmThesisEntryCaption"><?= t('Supervisor') ?>:</span>
	<span class="itmThesisEntryValue">
		<?php
		if ($this->controller->isLdapSupervisor())
		{
			$ui = UserInfo::getByUserName($this->controller->getSupervisorName());
			if (!empty($ui))
			{
				$name = $ui->getAttribute('name');
				if (!empty($name))
				{
					$fullName = $ldapHelper->getFullName($ui);;
					$link = $ldapHelper->getUserPageLink($this->controller->getSupervisorName());
					if ($link)
					{
						echo '<a href="' . $link . '">' . $fullName . '</a>';
					}
					else
					{
						echo $fullName;
					}
				}
			}
		}
		else
		{
			echo $supervisor;
		}
		?>
	</span>
</div>
