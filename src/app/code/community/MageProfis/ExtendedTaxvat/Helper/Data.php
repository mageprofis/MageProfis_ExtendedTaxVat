<?php
/**
  * MageProfis_ExtendedTaxvat
  *
  * @category  MageProfis
  * @package   MageProfis_ExtendedTaxvat
  * @author    Mathis Klooss <mathis@mage-profis.de>, Christopher Boehm <christopher@mage-profis.de>
  * @copyright 2015 Mage-Profis GmbH (http://www.mage-profis.de). All rights served.
  */
class MageProfis_ExtendedTaxvat_Helper_Data
extends Mage_Core_Helper_Abstract
{

    const XML_MODULE_ENABLED = 'mageprofis_taxvat_tab/general/active';
    const XML_DEFAULT_GROUP = 'mageprofis_taxvat_tab/general/default_group';
    const XML_INLAND_GROUP = 'mageprofis_taxvat_tab/general/viv_domestic_group';
    const XML_EU_GROUP = 'mageprofis_taxvat_tab/general/viv_intra_union_group';
    const XML_INVALID_GROUP = 'mageprofis_taxvat_tab/general/viv_invalid_group';
    const XML_CAN_SEND_MAIL = 'mageprofis_taxvat_tab/email/can_send_mail';
    const XML_CAN_SEND_MAIL_DOMESTIC = 'mageprofis_taxvat_tab/email/ignore_email_domestic';
    const XML_SHOP_COUNTRY = 'general/country/default';
    const XML_PATH_TIMEOUTHANDLING = 'mageprofis_taxvat_tab/failure/timeout';
    const XML_PATH_CLEARVATFIELD = 'mageprofis_taxvat_tab/failure/emptyfield';
    
    const TYPE_DOMESTIC = 'domestic';
    const TYPE_UNION    = 'union';

    /**
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return Mage::getStoreConfigFlag(MageProfis_ExtendedTaxvat_Helper_Data::XML_MODULE_ENABLED);
    }

    /**
     * can send E-Mail
     * 
     * @return bool
     */
    public function canSendMail()
    {
        if(!$this->isEnabled())
        {
            return false;
        }
        return Mage::getStoreConfigFlag(MageProfis_ExtendedTaxvat_Helper_Data::XML_CAN_SEND_MAIL);
    }

    /**
     * get Customer Group for EU Member
     * 
     * @return int
     */
    public function getEuCustomerGroup()
    {
        return (int) Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_EU_GROUP);
    }

    /**
     * get Customer Group for same country as store
     * 
     * @return int
     */
    public function getInlandCustomerGroup()
    {
        return (int) Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_INLAND_GROUP);
    }

    /**
     * get Default Customer Group
     * 
     * @return int
     */
    public function getDefaultCustomerGroup()
    {
        return (int) Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_DEFAULT_GROUP);
    }

    /**
     * get Customer Group for Invalid Customers
     * 
     * @return int
     */
    public function getInvalidCustomerGroup()
    {
        return (int) Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_INVALID_GROUP);
    }

    /**
     * 
     * @return bool
     */
    public function getTimeoutHandling()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_TIMEOUTHANDLING);
    }

    /**
     * 
     * @return type
     */
    public function getSendEmailOnDomestic()
    {
        return Mage::getStoreConfigFlag(self::XML_CAN_SEND_MAIL_DOMESTIC);
    }

    /**
     * 
     * @return bool
     */
    public function getClearVatField()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_CLEARVATFIELD);
    }

    /**
     * Check Country Code, if country is not in the EU this method returns FALSE
     * 
     * @return string|bool
     */
    public function getCountryType($country)
    {
        $eu_countries = Mage::getStoreConfig('general/country/eu_countries');
        $eu_countries_array = array_filter( (array) explode(',', $eu_countries));
        // Greece has EL as Shortcut for VAT Validations
        if(in_array('GR', $eu_countries_array))
        {
            $eu_countries_array[] = 'EL';
        }
        $storeCountry = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_SHOP_COUNTRY);
        if ($country == $storeCountry) {
            return self::TYPE_DOMESTIC;
        } elseif(in_array($country, $eu_countries_array)) {
            return self::TYPE_UNION;
        }
        return false;
    }

    /**
     * clean up taxvat id
     * 
     * @return string
     */
    public function cleanTaxvat($taxvat)
    {
        if(strlen($taxvat) > 4)
        {
            $taxvat = preg_replace("/[^A-Za-z0-9]/", '', trim($taxvat));
            $taxvat = strtoupper($taxvat);
        }
        return $taxvat;
    }
}
