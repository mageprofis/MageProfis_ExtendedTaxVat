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

    const XML_SYSTEM_TAX_BASES_ON = "tax/calculation/based_on";

    public function onSaveShipping($event)
    {
        $storeId = Mage::app()->getStore()->getStoreId();
        $taxBasedOn = Mage::getStoreConfig(self::XML_SYSTEM_TAX_BASES_ON, $storeId);
        if ($taxBasedOn == 'shipping') {
            $this->setQuoteAndSessionInCheckout($event, "shipping");
        }
    }

    public function onSaveBilling($event)
    {
        $this->setQuoteAndSessionInCheckout($event, "billing");
    }

    public function onOneCheckoutSetAddress($event)
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $type = 'billing';
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        $controller = $event->getControllerAction();
        /* @var $controller Mage_Customer_AccountController */
        $address = Mage::app()->getRequest()->getPost($type);
        $taxvat = (isset($address['taxvat'])) ? trim($address['taxvat']) : false;
        $group_id = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        
        if ($taxvat && strlen($taxvat) > 4) {
            $taxvat = $this->cleanTaxvat($taxvat);
            $address['taxvat'] = $taxvat;
            $address['vat_id'] = $taxvat;
            Mage::app()->getRequest()->setPost($type, $address);

            $customer = new Varien_Object();
            $customer->setData($address);

            $service = Mage::getModel('extendedtaxvat/service');
            /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
            $group_id = $service->getCustomerGroupIdByVatId($taxvat, $customer);
            if ($helper->getClearVatField() && (!$service || !$service->getTaxVatModel() || !$service->getTaxVatModel()->isValid())) {
                $address['taxvat'] = '';
                $address['vat_id'] = '';
                Mage::app()->getRequest()->setParam($type, $address);
            }
        } elseif (Mage::helper('customer')->isLoggedIn()) {
            $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
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
        
        $errors = array();
        if (isset($service)) {
            $message = $service->getMessage();
            if ($message) {
                foreach ($message['messages'] as $_message) {
                    switch ($message['type']) {
                        case 'error':
                            $errors[] = $_message;
                    }
                }
            }
            
            if($service->isChecked())
            {
                $object = $event->getObject();
                $result = $object->getResult();
                $result['taxvat'] = array(
                    'is_valid'      => $service->isValid(),
                    'error'         => $errors ? true : false,
                    'error_message' => implode(', ', $errors),
                );
                $object->setResult($result);
            }
        }

        return $this;
    }

        /**
     *
     * @mageEvent controller_action_predispatch_checkout_onepage_saveBilling
     * @param Varien_Object $event
     */
    public function setQuoteAndSessionInCheckout($event, $type)
    {
        if (!$this->isEnabled()) {
            return false;
        }
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        $controller = $event->getControllerAction();
        /* @var $controller Mage_Customer_AccountController */
        $address = $controller->getRequest()->getPost($type);
        $taxvat = (isset($address['taxvat'])) ? trim($address['taxvat']) : false;
        if ($type == 'shipping' && !$taxvat)
        {
            // if same do not do anything on shipping
            if (isset($address['same_as_billing']) && (int)$address['same_as_billing'] == 1)
            {
                return $this;
            }
            // otherwise load taxvat from billing
            $address['taxvat'] = Mage::getSingleton('checkout/session')
                    ->getQuote()
                    ->getBillingAddress()
                    ->getVatId();
            $taxvat = (isset($address['taxvat'])) ? trim($address['taxvat']) : false;
        }
        $group_id = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;

        if ($taxvat && strlen($taxvat) > 4) {
            $taxvat = $this->cleanTaxvat($taxvat);
            $address['taxvat'] = $taxvat;
            $address['vat_id'] = $taxvat;
            $controller->getRequest()->setPost($type, $address);

            $customer = new Varien_Object();
            $customer->setData($address);

            $service = Mage::getModel('extendedtaxvat/service');
            /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
            $group_id = $service->getCustomerGroupIdByVatId($taxvat, $customer);
            if ($helper->getClearVatField() && (!$service || !$service->getTaxVatModel() || !$service->getTaxVatModel()->isValid())) {
                $address['taxvat'] = '';
                $address['vat_id'] = '';
                $controller->getRequest()->setParam($type, $address);
            }
        } elseif (Mage::helper('customer')->isLoggedIn()) {
            $group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
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
        if (isset($service)) {
            $message = $service->getMessage();
            if ($message) {
                foreach ($message['messages'] as $_message) {
                    switch ($message['type']) {
                        case 'error':
                            $error = array(
                                'error' => 1,
                                'message' => $_message
                            );
                            echo Mage::helper('core')->jsonEncode($error);
                            exit;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @mageEvent customer_save_before
     * @param string $event
     */
    public function onCustomerSaveBefore($event)
    {
        // disable function if magento default is active
        if (!$this->isEnabled()) {
            return;
        }
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        if( (int) $customer->getDisableAutoGroupChange() == 1)
        {
            return;
        }
        $service = Mage::getModel('extendedtaxvat/service');
        /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
        $taxvat = $this->cleanTaxvat($customer->getTaxvat());
        $customer->setTaxvat($taxvat);
        if (is_null($customer->getTaxvat()) || strlen($customer->getTaxvat()) < 1) {
            $groupId = $helper->getDefaultCustomerGroup();
            $customer->setGroupId($groupId);
        }

        $groupId = $service->getCustomerGroupIdByVatId($taxvat, $customer);
        // check on customer create the customer group, when it is "NOT LOGGED IN", we set the default group
        if ($groupId == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID) {
            $groupId = $helper->getDefaultCustomerGroup();
        }

        $customer->setGroupId($groupId);

        if ($service && $service->getTaxVatModel() && $result = $service->getTaxVatModel()->getResult()) {
            $customer->setData('validation_result', $result);
            $customer->setData('validation_result_text', $service->getTaxVatModel()->getResultAsText());
        }

        $customer->setShowTaxMessage(true);
        $customer->setTaxMessage($service->getMessage());

        if ($helper->getClearVatField() && (!$service || !$service->getTaxVatModel() || !$service->getTaxVatModel()->isValid())) {
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
    public function onCustomerSaveAfter($event)
    {
        if (!$this->isEnabled()) {
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
            ->addFieldToFilter('customer_id', (int) $customer->getId())
            ->addFieldToFilter('customer_tax_class_id', array('neq' => (int)$customer->getGroupId()));

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
        if ($customer->getShowTaxMessage() && !$customer->getHideTaxMessage()) {
            $message = $customer->getTaxMessage();
            if ($message) {
                foreach ($message['messages'] as $_message) {
                    switch ($message['type']) {
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
     *
     * @mageEvent customer_login
     * @return void
     */
    public function onCustomerLogin($event)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        $taxvat = $customer->getTaxvat();
        if (strlen($taxvat) > 4) {
            $service = Mage::getModel('extendedtaxvat/service');
            /* @var $service MageProfis_ExtendedTaxvat_Model_Service */
            $group_id = (int) $service->getCustomerGroupIdByVatId($taxvat, $customer);
            if ($group_id != (int) $customer->getGroupId()) {
                $customer->setHideTaxMessage(true);
                $customer->setGroupId($group_id);
                if ($group_id == $helper->getDefaultCustomerGroup()) {
                    $customer->setShowTaxMessage(true);
                    $customer->setHideTaxMessage(false);
                }
                $customer->save();
                if ($group_id == $helper->getDefaultCustomerGroup()) {
                    Mage::app()->getResponse()
                        ->setRedirect(Mage::helper('customer')->getEditUrl(), 301)
                        ->sendResponse();
                    exit;
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
        if (!$this->isEnabled() || Mage::helper('customer')->isLoggedIn()) {
            return $this;
        }
        $group_id = Mage::getSingleton('core/session')->getVatCustomerGroupId();
        if (!is_null($group_id) && intval($group_id) != 0) {
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

    /**
     * do not enable this when the magento api is enabled
     *
     * @return boolean
     */
    protected function isEnabled()
    {
        $helper = Mage::helper('extendedtaxvat');
        if (!$helper->isEnabled() || Mage::getStoreConfigFlag('customer/create_account/auto_group_assign')) {
            return false;
        }
        return true;
    }
}
