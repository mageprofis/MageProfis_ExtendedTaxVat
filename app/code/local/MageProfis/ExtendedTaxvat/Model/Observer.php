<?php

class MageProfis_ExtendedTaxvat_Model_Observer {

    /**
     * we need only one element in our checkout, so you can remove one of them!
     *
     * @mageEvent controller_action_predispatch_checkout_onepage_saveBilling
     * @param Varien_Object $event
     */
    public function setQuoteAndSessionInCheckout($event) {
         $helper = Mage::helper('extendedtaxvat');
         if(!$helper->isEnabled() || Mage::getStoreConfig('customer/create_account/auto_group_assign')){
             return false;
         }
        $controller = $event->getControllerAction();
        /* @var $controller Mage_Customer_AccountController */
        $billing = $controller->getRequest()->getParam('billing');
        $taxvat = (isset($billing['taxvat'])) ? $billing['taxvat'] : false;

           $customer = new Varien_Object();
           $customer->setData($billing);
           
           $service = Mage::getModel('extendedtaxvat/service');
           $group_id = $service->getCustomerGroupIdByVatId($taxvat,$customer);
           
            // set group in quote and customer session!
           
            Mage::getSingleton('customer/session')
                    ->setCustomerGroupId($group_id);
            Mage::getSingleton('customer/session')
                    ->getCustomer()->setGroupId($group_id);
            
            Mage::getSingleton('checkout/session')->getQuote()
                    ->setCustomerGroupId($group_id)
                    ->setTotalsCollectedFlag(false)
                    ->getCustomerTaxClassId();
            
            Mage::getSingleton('core/session')->setVatId($group_id);

    }

    /**
     * @mageEvent customer_save_before
     * @param string $event
     */
    public function onCustomerSaveBefore($event) {
        $helper = Mage::helper('extendedtaxvat');
         if(!$helper->isEnabled() || Mage::getStoreConfig('customer/create_account/auto_group_assign')){
             return;
         }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $service = Mage::getModel('extendedtaxvat/service');
        $addr = null;
        foreach ($customer->getAddresses() as $address) {
            $addr = $address;
        }
        $groupId = $service->getCustomerGroupIdByVatId($customer->getTaxvat(),$addr);
        
        $customer->setGroupId($groupId);
        
        if($service->getTaxVatModel() && $result = $service->getTaxVatModel()->getResult()){
            $customer->setData('validation_result', $result);
        }
        if($customer->getGroupId() != $customer->getOrigData('group_id')){
            $customer->setShowTaxMessage(true);
        }
        Mage::getSingleton('checkout/session')->setCustomerGroupId($groupId);
        Mage::getSingleton('customer/session')->setCustomerGroupId($groupId);
    }

    /**
     * @mageEvent customer_save_after
     * @param string $event
     */
    public function onCustomerSaveAfter($event) {
        $helper = Mage::helper('extendedtaxvat');
         if(!$helper->isEnabled() || Mage::getStoreConfig('customer/create_account/auto_group_assign')){
             return;
         }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $collection = Mage::getModel('sales/quote')->getCollection()
                ->addFieldToSelect(array('customer_group_id', 'customer_tax_class_id'))
                ->addFieldToFilter('store_id', array('neq' => 0))
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('converted_at', array('null' => true))
                ->addFieldToFilter('customer_id', (int) $customer->getId());

        foreach ($collection as $_quote) {
            /* @var $_quote Mage_Sales_Model_Quote */
            if ((int) $_quote->getCustomerGroupId() != (int) $customer->getGroupId()) {
                $quote = Mage::getModel('sales/quote')->load($_quote->getId())
                        ->setTotalsCollectedFlag(false)
                        ->setCustomerTaxClassId((int) $customer->getTaxClassId())
                        ->setData('customer_tax_class_id', (int) $customer->getTaxClassId())
                        ->setCustomerGroupId((int) $customer->getGroupId())
                        ->collectTotals()
                        ->save()
                ;
            }
        }
        if($customer->getShowTaxMessage()){
            Mage::getSingleton('customer/session')->addSuccess($helper->__('Customer Group was changed'));
        }
        
    }

}
