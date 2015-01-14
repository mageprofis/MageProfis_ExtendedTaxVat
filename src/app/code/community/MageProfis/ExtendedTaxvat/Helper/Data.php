<?php

class MageProfis_ExtendedTaxvat_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_MODULE_ENABLED = 'mageprofis_taxvat_tab/general/active';
    const XML_DEFAULT_GROUP = 'mageprofis_taxvat_tab/general/default_group';
    const XML_INLAND_GROUP = 'mageprofis_taxvat_tab/general/viv_domestic_group';
    const XML_EU_GROUP = 'mageprofis_taxvat_tab/general/viv_intra_union_group';
    const XML_INVALID_GROUP = 'mageprofis_taxvat_tab/general/viv_invalid_group';
    const XML_SHOP_COUNTRY = 'general/country/default';
    
    public function isEnabled(){
        return Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_MODULE_ENABLED);
    }
    
    public function getEuCustomerGroup(){
        return Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_EU_GROUP);
    }
    
    public function getInlandCustomerGroup(){
        return Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_INLAND_GROUP);
    }
    
    public function getDefaultCustomerGroup(){
        return Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_DEFAULT_GROUP);
    }
    
    public function getInvalidCustomerGroup(){
        return Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_INVALID_GROUP);
    }
    /*
     * @return string
     * domestic = inland
     * union = EU
     * bespl. $country = 'DE'
     */
    public function getCountryType($country){
        $eu_countries = Mage::getStoreConfig('general/country/eu_countries');
        $eu_countries_array = explode(',',$eu_countries);
        $storeCountry = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Helper_Data::XML_SHOP_COUNTRY);
        if(in_array($country, $eu_countries_array)){
            return 'union';
        }else if($country==$storeCountry){
            return 'domestic';
        }
        return false;
    }
}
