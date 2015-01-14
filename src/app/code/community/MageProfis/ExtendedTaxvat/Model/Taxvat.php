<?php

class MageProfis_ExtendedTaxvat_Model_Taxvat extends Mage_Core_Model_Abstract {

    const XML_EMAIL_RECEIVER = 'mageprofis_taxvat_tab/general/receiver';
    const XML_EMAIL_SUBJECT = 'mageprofis_taxvat_tab/general/subject';
    const XML_EMAIL_TEMPLATE = 'mageprofis_taxvat_tab/general/template';

    public function _construct() {
        parent::_construct();
        $this->_init('extendedtaxvat/taxvat');
    }

    public function sendMail($customer=null) {
        
        $store_id = Mage::app()->getStore()->getStoreId();
        //Sender == Empfänger
        $receiverId = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Model_Taxvat::XML_EMAIL_RECEIVER);
        $subject = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Model_Taxvat::XML_EMAIL_SUBJECT);
        $template = Mage::getStoreConfig(MageProfis_ExtendedTaxvat_Model_Taxvat::XML_EMAIL_TEMPLATE);
        $sender = array('name'=>Mage::getStoreConfig("trans_email/ident_$receiverId/name",$store_id), 'email'=> Mage::getStoreConfig("trans_email/ident_$receiverId/email",$store_id));
        if($customer){
        $name = $customer->getFirstname().' '.$customer->getLastname();
        $vars = array('taxvat' => $this->getVat(), 'customername' => $name,'customer' => $customer);
        }else{$vars=array();$name="";}
        
        $email = $sender['email'];
    
        $mail = Mage::getModel('core/email_template')->load($template);
        $mail->setTemplateSubject($subject);
        $mail->setSenderName($sender['name']);
        $mail->setSenderEmail($sender['email']);
        $mail->getProcessTemplate($vars);
        try {
           
           $mail->send($email, $name, $vars);
        } catch (exception $e) {
            Mage::throwException($e->getMessage());
        }
    }

    public function isValid() {
        return ($this->getValid()) ? $this->getValid() : false;
    }

    public function getVat() {
        return $this->getCountryCode() . $this->getVatNo();
    }

}
