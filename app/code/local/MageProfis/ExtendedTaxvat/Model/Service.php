<?php

class MageProfis_ExtendedTaxvat_Model_Service{

    private $_taxVatModel = null;
    
    
    
    public function getCustomerGroupIdByVatId($taxVat,$customer=null){
        $helper = Mage::helper('extendedtaxvat');
        if(!$taxVat){
           if(Mage::getSingleton('customer/session')->isLoggedIn()){
               return $helper->getDefaultCustomerGroup(); 
           }else{
               return Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
           }
        }
        $country_code = substr($taxVat,0,2);
        $vat_no = substr($taxVat,2);
        $wasChecked = false;

        $taxvat = Mage::getModel('extendedtaxvat/taxvat')->getCollection()
                    ->addFieldToFilter('country_code',array('eq'=>$country_code))
                    ->addFieldToFilter('vat_no',array('eq'=>$vat_no))
                    ->getFirstItem();
        
        if($taxvat->getEntityId()){
            $this->_taxVatModel = $taxvat;
            $isValid = $taxvat->isValid();
        }else{
            
            $result = $this->checkVat($country_code,$vat_no);
            
            $isValid = 0;
            if($result){
                $isValid = (int) $result->valid;
            }
            $result = json_encode((array)$result);
            $taxvatModel = Mage::getModel('extendedtaxvat/taxvat');
            $taxvatModel->setResult($result);
            $taxvatModel->setCountryCode($country_code);
            $taxvatModel->setVatNo($vat_no);
            $taxvatModel->setValid($isValid);
            //$taxvatModel->save();
            $taxvatModel->sendMail($customer);
            $this->_taxVatModel = $taxvatModel;
            $wasChecked=true;
        }
       $groupId=0;
        if($isValid){
            if($helper->getCountryType($country_code)=='domestic'){
                //INLAND
                $groupId= $helper->getInlandCustomerGroup();
            }else if($helper->getCountryType($country_code)=='union'){
                //EU
                $groupId =  $helper->getEuCustomerGroup();
            }else{
               $groupId = $helper->getDefaultCustomerGroup(); 
            }
        }else if ($wasChecked){
            $groupId = $helper->getInvalidCustomerGroup();
        }
        else{
            $groupId = Mage::getSingleton('checkout/session')->getCustomerGroupId();
        }
        return $groupId;
    }
    
    public function checkVat($countryCode,$vatNo){
        $client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        $result = $client->checkVat(array(
          'countryCode' => $countryCode,
          'vatNumber' => $vatNo
        ));
        return $result;
    }
    public function getTaxVatModel(){
        return $this->_taxVatModel;
    }
}

