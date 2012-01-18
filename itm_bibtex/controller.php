<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

class ItmBibtexPackage extends Package
{
	protected $pkgHandle = 'itm_bibtex';
	protected $appVersionRequired = '5.3.3';
	protected $pkgVersion = '1.0';

	public function getPackageDescription()
	{
		return t("Installs the ITM BibTex package.");
	}

	public function getPackageName()
	{
		return t("ITM BibTex.");
	}

	public function install()
	{
		$pkg = parent::install();

		$themePkg = Package::getByHandle('itm_theme');

		if (empty($themePkg))
		{
			$pkg->uninstall();
			throw new Exception("Required package <b>itm_theme</b> not found. Install it in advance.");
		}

		// install block
		BlockType::installBlockTypeFromPackage('itm_bibtex', $pkg);
		
		Loader::model('single_page');
		
		$sp1 = SinglePage::add('/dashboard/itm_bibtex', $pkg);
		$sp1->update(array('cName' => t('ITM BibTex'), 'cDescription' => t('Bib File Editor')));
	}

	public static function addUserTextAttr($handle, $name, $pkg)
	{
		UserAttributeKey::add('text', array(
			'akHandle' => $handle,
			'akName' => $name, 'akIsSearchable' => true
				), $pkg);
	}

	public static function setupLdapAttributes($pkg)
	{
		ItmLdapPackage::addUserTextAttr('room_number', t('Room number'), $pkg);
		ItmLdapPackage::addUserTextAttr('telephone_number', t('Telephone number'), $pkg);
		ItmLdapPackage::addUserTextAttr('telefax_number', t('Telefax number'), $pkg);
		ItmLdapPackage::addUserTextAttr('consultation', t('Consultation'), $pkg);
		ItmLdapPackage::addUserTextAttr('icq_number', t('ICQ number'), $pkg);
		ItmLdapPackage::addUserTextAttr('skype_number', t('Skype number'), $pkg);
		ItmLdapPackage::addUserTextAttr('name', t('Name'), $pkg);
		ItmLdapPackage::addUserTextAttr('title', t('Title'), $pkg);
	}
	
	public static function setupConfig($pkg)
	{
		$config = new Config();
		$config->setPackageObject($pkg);
		$config->save('ITM_LDAP_STREET', t('Ratzeburger Allee'));
		$config->save('ITM_LDAP_STREET_NO', '160');
		$config->save('ITM_LDAP_ZIP', '23538');
		$config->save('ITM_LDAP_CITY', t('Lübeck'));
		$config->save('ITM_LDAP_UNI_LINKTEXT', 'University of Lübeck');
		$config->save('ITM_LDAP_UNI_LINK', 'http://www.uni-luebeck.de');
		$config->save('ITM_LDAP_INST_LINKTEXT', 'Institute of Telematics');
	}

}

?>
