<?php

class MageProfis_ExtendedTaxvat_Model_Service
{

    const XML_PATH_SOAPAPI_WSDL = 'mageprofis_taxvat_tab/general/soap_api_url';
    
    protected $_taxVatModel = null;
    protected $_messageType = null;

    /**
     * 
     * @param type $taxVat
     * @param type $customer
     * @return int
     */
    public function getCustomerGroupIdByVatId($taxVat, $customer = null)
    {
        $helper = Mage::helper('extendedtaxvat');
        /* @var $helper MageProfis_ExtendedTaxvat_Helper_Data */
        if (!$taxVat)
        {
            if (Mage::getSingleton('customer/session')->isLoggedIn())
            {
                return $helper->getDefaultCustomerGroup();
            } else {
                return Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            }
        }
        $customerModel = $customer;
        if(!$customer instanceof Mage_Customer_Model_Customer)
        {
            $customerModel = Mage::getSingleton('customer/session')->getCustomer();
        }
        /* @var $customerModel Mage_Customer_Model_Customer */
        // disable customer switch option, when the customer option is enabled
        if( (int) $customerModel->getDisableAutoGroupChange() == 1)
        {
            return $customerModel->getGroupId();
        }
        $country_code = substr($taxVat, 0, 2);
        $vat_no = substr($taxVat, 2);
        // if country is not in the EU, we will set the current group id
        if(!$helper->getCountryType($country_code))
        {
            $groupId = Mage::helper('extendedtaxvat')->getDefaultCustomerGroup();
            if(!Mage::helper('customer')->isLoggedIn())
            {
                $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            }
            return $groupId;
        }
        $wasChecked = false;

        $taxvat = Mage::getModel('extendedtaxvat/taxvat')->getCollection()
                ->addFieldToFilter('country_code', array('eq' => $country_code))
                ->addFieldToFilter('vat_no', array('eq' => $vat_no))
                ->setCurPage(1)->setPageSize(1)
                ->getFirstItem();
        /* @var $taxVat MageProfis_ExtendedTaxvat_Model_Taxvat */
        // check if items availible in the database, so we pick the informations from here
        if ($taxvat && $taxvat->getEntityId())
        {
            $this->_taxVatModel = $taxvat;
            $isValid = $taxvat->isValid();
            if(!$isValid)
            {
                $wasChecked = true;
            }
        } else {
            $isValid = 0;
            $result = $this->checkVat($country_code, $vat_no);
            if($this->getMessageType() == 'SUCCESS')
            {
                if ($result)
                {
                    $isValid = (int) $result->valid;
                }
                $result = json_encode((array) $result);
                $taxvatModel = Mage::getModel('extendedtaxvat/taxvat')
                        ->setResult($result)
                        ->setCountryCode($country_code)
                        ->setVatNo($vat_no)
                        ->setValid($isValid)
                        ->save();
                if ($taxvatModel->getValid())
                {
                    $taxvatModel->sendMail($customer);
                }
                $this->_taxVatModel = $taxvatModel;
                $wasChecked = true;
            } elseif(in_array($this->getMessageType(), array('TIMEOUT', 'SERVER_BUSY', 'MS_UNAVAILABLE', 'SERVICE_UNAVAILABLE'))) {
                if($helper->getTimeoutHandling())
                {
                    $isValid = true;
                    $this->_message = Mage::helper('extendedtaxvat/messages')->isTimeoutAsValid();
                } else {
                    $this->_message = Mage::helper('extendedtaxvat/messages')->isTimeoutAsInValid();
                }
            } elseif($this->getMessageType() == 'INVALID_INPUT')
            {
                $this->_message = Mage::helper('extendedtaxvat/messages')->isInvalid();
            }
        }
        $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        if ($isValid) {
            if ($helper->getCountryType($country_code) == MageProfis_ExtendedTaxvat_Helper_Data::TYPE_DOMESTIC) {
                //domestic
                $groupId = $helper->getInlandCustomerGroup();
                $this->_message = Mage::helper('extendedtaxvat/messages')->onSuccessDomestic();
            } else if ($helper->getCountryType($country_code) == MageProfis_ExtendedTaxvat_Helper_Data::TYPE_UNION) {
                //EU
                $groupId = $helper->getEuCustomerGroup();
                $this->_message = Mage::helper('extendedtaxvat/messages')->onSuccessUnion();
            } else {
                $groupId = $helper->getDefaultCustomerGroup();
            }
        } elseif ($wasChecked) {
            $groupId = $helper->getInvalidCustomerGroup();
            $this->_message = Mage::helper('extendedtaxvat/messages')->isInvalid();
        } else {
            $groupId = Mage::getSingleton('checkout/session')->getCustomerGroupId();
        }
        return $groupId;
    }

    /**
     * 
     * @param string $countryCode
     * @param string $vatNo
     * @return mixed
     */
    public function checkVat($countryCode, $vatNo)
    {
        $model = new MageProfis_ExtendedTaxvat_Model_Service_Vies();
        $model->setCountryCode($countryCode)
                ->setVatId($vatNo);
        $return = $model->result();
        $this->_messageType = $return['messageType'];
        return $return['result'];
    }

    /**
     * 
     * @return MageProfis_ExtendedTaxvat_Model_Taxvat
     */
    public function getTaxVatModel()
    {
        return $this->_taxVatModel;
    }
    
    /**
     * @return string
     */
    public function getMessageType()
    {
        return $this->_messageType;
    }
    
    /**
     * 
     */
    public function getMessage()
    {
        return $this->_message;
    }

}
