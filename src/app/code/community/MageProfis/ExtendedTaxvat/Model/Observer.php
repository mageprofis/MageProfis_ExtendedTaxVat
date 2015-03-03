<?php
/**
  * MageProfis_ExtendedTaxvat
  *
  * @category  MageProfis
  * @package   MageProfis_ExtendedTaxvat
  * @author    Mathis Klooss <mathis@mage-profis.de>, Christopher Boehm <christopher@mage-profis.de>
  * @copyright 2015 Mage-Profis GmbH (http://www.mage-profis.de). All rights served.
  */
class MageProfis_ExtendedTaxvat_Model_Observer
{
    /**
     *
     * @mageEvent controller_action_predispatch_checkout_onepage_saveBilling
     * @param Varien_Object $event
     */
    public function setQuoteAndSessionInCheckout($event) {
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        // do not enable this when the magento api is enabled
        if(!$helper->isEnabled() || Mage::getStoreConfig('customer/create_account/auto_group_assign'))
        {
            return false;
        }
        $controller = $event->getControllerAction();
        /* @var $controller Mage_Customer_AccountController */
        $billing = $controller->getRequest()->getParam('billing');
        $taxvat = (isset($billing['taxvat'])) ? trim($billing['taxvat']) : false;
        $group_id = null;

        if(strlen($taxvat) > 4)
        {
            $taxvat = $this->cleanTaxvat($taxvat);
            $billing['taxvat'] = $taxvat;
            $controller->getRequest()->setParam('billing', $billing);

            $customer = new Varien_Object();
            $customer->setData($billing);

            $service = Mage::getModel('extendedtaxvat/service');
            /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
            $group_id = $service->getCustomerGroupIdByVatId($taxvat, $customer);
            if($helper->getClearVatField() && (!$service || !$service->getTaxVatModel() || !$service->getTaxVatModel()->isValid()))
            {
                $billing['taxvat'] = '';
                $controller->getRequest()->setParam('billing', $billing);
            }
        } elseif(Mage::helper('customer')->isLoggedIn()) {
            $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
        } else {
            $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        }

        // set group in quote and customer session!
        Mage::getSingleton('customer/session')
            ->setCustomerGroupId($group_id);
        Mage::getSingleton('customer/session')
            ->getCustomer()
            ->setGroupId($group_id);

        Mage::getSingleton('checkout/session')->getQuote()
            ->setCustomerGroupId($group_id)
            ->setTotalsCollectedFlag(false)
            ->getCustomerTaxClassId();

        Mage::getSingleton('core/session')->setVatCustomerGroupId($group_id);

        return $this;
    }

    /**
     * @mageEvent customer_save_before
     * @param string $event
     */
    public function onCustomerSaveBefore($event)
    {
        $helper = Mage::helper('extendedtaxvat');
        // disable function if magento default is active
        if(!$helper->isEnabled() || Mage::getStoreConfig('customer/create_account/auto_group_assign'))
        {
            return;
        }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $service = Mage::getModel('extendedtaxvat/service');
        /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
        $addr = null;
        foreach ($customer->getAddresses() as $address)
        {
            $addr = $address;
        }
        $taxvat = $this->cleanTaxvat($customer->getTaxvat());
        $customer->setTaxvat($taxvat);
        if(is_null($customer->getTaxvat()) || strlen($customer->getTaxvat()) < 1)
        {
            $groupId = Mage::helper('extendedtaxvat')->getDefaultCustomerGroup();
            $customer->setGroupId($groupId);
        }

        $groupId = $service->getCustomerGroupIdByVatId($taxvat, $addr);
        // check on customer create the customer group, when it is "NOT LOGGED IN", we set the default group
        if($groupId == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
        {
            $groupId = Mage::helper('extendedtaxvat')->getDefaultCustomerGroup();
        }

        $customer->setGroupId($groupId);

        if($service && $service->getTaxVatModel() && $result = $service->getTaxVatModel()->getResult()){
            $customer->setData('validation_result', $result);
            $customer->setData('validation_result_text', $service->getTaxVatModel()->getResultAsText());
        }

        $customer->setShowTaxMessage(true);
        $customer->setTaxMessage($service->getMessage());

        if($helper->getClearVatField() && (!$service || !$service->getTaxVatModel() || !$service->getTaxVatModel()->isValid()))
        {
            $customer->setTaxvat(null);
        }

        Mage::getSingleton('checkout/session')
                ->setCustomerGroupId($groupId);

        Mage::getSingleton('customer/session')
                ->setCustomerGroupId($groupId);
    }

    /**
     * change customer group in active quotes
     * 
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
        Mage::getSingleton('core/session')->setVatCustomerGroupId($customer->getGroupId());
        $collection = Mage::getModel('sales/quote')->getCollection()
                ->addFieldToSelect(array('customer_group_id', 'customer_tax_class_id'))
                ->addFieldToFilter('store_id', array('neq' => 0))
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('converted_at', array('null' => true))
                ->addFieldToFilter('customer_id', (int) $customer->getId());

        foreach ($collection as $_quote) {
            /* @var $_quote Mage_Sales_Model_Quote */
            if ((int) $_quote->getCustomerGroupId() != (int) $customer->getGroupId()) {
                Mage::getModel('sales/quote')->load($_quote->getId())
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
            $message = $customer->getTaxMessage();
            if($message)
            {
                foreach($message['messages'] as $_message)
                {
                    switch($message['type'])
                    {
                        case 'success':
                            Mage::getSingleton('customer/session')->addSuccess($_message);
                            break;
                        case 'error':
                            Mage::getSingleton('customer/session')->addError($_message);
                            break;
                        case 'warning':
                            Mage::getSingleton('customer/session')->addWarning($_message);
                            break;
                    }
                }
            }
        }
    }

    /**
     * @mageEvent core_copy_fieldset_customer_account_to_quote
     * @return void
     */
    public function copyFieldsetCustomerAccountToQuote($event)
    {
        // check does not need for already registered accounts
        if(Mage::helper('customer')->isLoggedIn())
        {
            return $this;
        }
        $group_id = Mage::getSingleton('core/session')->getVatCustomerGroupId();
        if(!is_null($group_id) && intval($group_id) != 0)
        {
            $target = $event->getTarget();
            $target
                ->setCustomerGroupId($group_id);
            $target
                ->getCustomer()
                    ->setGroupId($group_id);
        }
    }

    /**
     * clean up taxvat
     * 
     * @see MageProfis_ExtendedTaxvat_Helper_Data::cleanTaxvat
     * @return string
     */
    protected function cleanTaxvat($taxvat)
    {
        return Mage::helper('extendedtaxvat')->cleanTaxvat($taxvat);
    }

}
