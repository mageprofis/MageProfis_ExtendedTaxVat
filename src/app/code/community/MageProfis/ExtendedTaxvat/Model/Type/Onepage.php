<?php

class MageProfis_ExtendedTaxvat_Model_Type_Onepage extends Mage_Checkout_Model_Type_Onepage{
    
    protected function _validateCustomerData(array $data){
        parent::_validateCustomerData($data);
        $this->getQuote()->setCustomerGroupId(Mage::getSingleton('core/session')->getVatId());
        $this->getQuote()->getCustomer()->setGroupId(Mage::getSingleton('core/session')->getVatId());
        return true;
    }
}

