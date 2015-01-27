<?php

class MageProfis_ExtendedTaxvat_Model_Type_Onepage
extends Mage_Checkout_Model_Type_Onepage
{
    /**
     * Validate customer data and set some its data for further usage in quote
     * Will return either true or array with error messages
     *
     * @deprecated 1.0.0.0
     * @see MageProfis_ExtendedTaxvat_Model_Observer::copyFieldsetCustomerAccountToQuote
     * @param array $data
     * @return true|array
     */
    protected function _validateCustomerData(array $data)
    {
        $parent = parent::_validateCustomerData($data);
        if(is_array($parent))
        {
            return $parent;
        }
        $this->getQuote()
            ->setCustomerGroupId(Mage::getSingleton('core/session')->getVatCustomerGroupId());
        $this->getQuote()
            ->getCustomer()
                ->setGroupId(Mage::getSingleton('core/session')->getVatCustomerGroupId());
        return true;
    }
}
