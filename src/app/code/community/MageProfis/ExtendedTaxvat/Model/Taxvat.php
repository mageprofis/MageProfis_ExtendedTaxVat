<?php

class MageProfis_ExtendedTaxvat_Model_Taxvat
extends Mage_Core_Model_Abstract
{

    const XML_EMAIL_RECEIVER = 'mageprofis_taxvat_tab/email/receiver';
    const XML_EMAIL_TEMPLATE = 'mageprofis_taxvat_tab/email/template';

    public function _construct()
    {
        parent::_construct();
        $this->_init('extendedtaxvat/taxvat');
        $this->setSendEmail(true);
        $this->setForceSendEmail(true);
    }

    /**
     * 
     * @param Varien_Object $customer
     * @param int $store_id
     * @return MageProfis_ExtendedTaxvat_Model_Taxvat
     */
    public function sendMail($customer = null, $store_id = null)
    {
        if(!Mage::helper('extendedtaxvat')->canSendMail() || !$this->getSendEmail())
        {
            return $this;
        }
        if(!Mage::helper('extendedtaxvat')->getSendEmailOnDomestic()
                && Mage::helper('extendedtaxvat')->getCountryType($this->getCountryCode()) == MageProfis_ExtendedTaxvat_Helper_Data::TYPE_DOMESTIC)
        {
            return $this;
        }
        if(is_null($store_id))
        {
            $store_id = Mage::app()->getStore()->getStoreId();
        }

        $receiverId = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Model_Taxvat::XML_EMAIL_RECEIVER);
        $template = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Model_Taxvat::XML_EMAIL_TEMPLATE);

        $sender = array(
            'name' => Mage::getStoreConfig('trans_email/ident_'.$receiverId.'/name', $store_id),
            'email' => Mage::getStoreConfig('trans_email/ident_'.$receiverId.'/email', $store_id)
        );
        $name = '';
        if ($customer)
        {
            $name = $customer->getFirstname() . ' ' . $customer->getLastname();
        } else {
            $customer = new Varien_Object();
        }

        $vars = array(
            'taxvat'       => $this->getVat(),
            'customername' => $name,
            'customer'     => $customer,
            'vatmodel'     => $this
        );

        $email = $sender['email'];

        $mail = Mage::getModel('core/email_template')->load($template);
        /* @var $mail Mage_Core_Model_Email_Template */
        $mail->setSenderName($sender['name'])
                ->setSenderEmail($sender['email'])
                ->getProcessTemplate($vars);
        try {
            $mail->send($email, $name, $vars);
        } catch (exception $e) {
            Mage::throwException($e->getMessage());
        }
        return $this;
    }

    /**
     * is Taxvat ID Valid
     * 
     * @return bool
     */
    public function isValid()
    {
        return (intval($this->getValid())) ? true : false;
    }

    /**
     * get Vat Combined Country Code and VatID
     * 
     * @return string
     */
    public function getVat()
    {
        return $this->getCountryCode() . $this->getVatNo();
    }

    /**
     * get Result as Array
     * 
     * @return array
     */
    public function getResultAsArray()
    {
        return json_decode($this->getData('result'), true);
    }

    /**
     * get Result as Text
     * 
     * @param string $before
     * @param string $spacer
     * @param string $after
     * @return string
     */
    public function getResultAsText($before = '', $spacer = ': ' , $after = "\n")
    {
        $text = '';
        foreach ($this->getResultAsArray() as $key => $value) {
            $text .= $before .
                    $key .
                    $spacer .
                    $value .
                    $after;
        }
        return $text;
    }

    /**
     * get Result as HTML Code
     * 
     * @param string $before
     * @param string $spacer
     * @param string $after
     * @return string
     */
    public function getResultAsHtml($before = '', $spacer = ': ' , $after = '<br />')
    {
        $text = '';
        foreach ($this->getResultAsArray() as $key => $value) {
            $text .= $before .
                    Mage::helper('core')->escapeHtml($key) .
                    $spacer .
                    Mage::helper('core')->escapeHtml($value) .
                    $after;
        }
        return $text;
    }

    /**
     * 
     * @return MageProfis_ExtendedTaxvat_Model_Taxvat
     */
    protected function _beforeSave()
    {
        if(is_null($this->getCreatedAt()))
        {
            $this->setCreatedAt(date('Y-m-d H:i:s'));
        } else {
            $this->setCreatedAt($this->getCreatedAt());
        }
        return parent::_beforeSave();
    }
}
