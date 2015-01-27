<?php

class MageProfis_ExtendedTaxvat_Model_Service_Vies
{
    protected $_vat_id = null;
    protected $_country_code = null;
    protected $_result_type = 'UNKNOWN';
    
    const XML_PATH_SOAPAPI_WSDL = 'mageprofis_taxvat_tab/general/soap_api_url';

    protected $_knownTypes = array(
        'INVALID_INPUT',       // like wrong or empty "county code"/"vat id"
        'SERVICE_UNAVAILABLE', // like timeout
        'MS_UNAVAILABLE',      // Country VATID Validation is offline
        'TIMEOUT',             // Country VATID Validation could not response in a defined time
        'SERVER_BUSY',         // Service can not handle your request, try later!
        'UNKNOWN'              // uknown specifited by this script
    );

    /**
     * 
     * @param string $vatid
     * @return MageProfis_ExtendedTaxvat_Model_Service_Vies
     */
    public function setVatId($vatid)
    {
        $this->_vat_id = $vatid;
        return $this;
    }

    /**
     * 
     * @param string $code
     * @return MageProfis_ExtendedTaxvat_Model_Service_Vies
     */
    public function setCountryCode($code)
    {
        $this->_country_code = $code;
        return $this;
    }

    /**
     * Validate Vat Id before API Call
     * 
     * @return boolean
     */
    protected function validate()
    {
        // incorrect country
        if(!Mage::helper('extendedtaxvat')->getCountryType($this->_country_code))
        {
            $this->_result_type = 'INVALID_INPUT';
            return false;
        }
        // incorrect number
        if(strlen($this->_vat_id) < 4)
        {
            $this->_result_type = 'INVALID_INPUT';
            return false;
        }

        return true;
    }

    /**
     * 
     * @return array
     */
    public function result()
    {
        if(!$this->validate())
        {
            return array(
                'messageType' => $this->_result_type,
                'result'      => array()
            );
        }
        $_messageType = 'SUCCESS';
        try {
            $wsdl = Mage::getStoreConfig(self::XML_PATH_SOAPAPI_WSDL);
            $client = new SoapClient($wsdl);
            $result = $client->checkVat(array(
                'countryCode' => $this->_country_code,
                'vatNumber'   => $this->_vat_id
            ));
        } catch(SoapFault $e)
        {
            /* @var $e SoapFault */
            $result = array();
            $faultString = (string) $e->faultstring;
            $matches = array();
            preg_match('/\{ \'([A-Z_]*)\' \}/', $faultString, $matches);
            $_messageType = isset($matches[1]) ? trim($matches[1]) : 'UNKNOWN';
            $_messageType = (in_array($_messageType, $this->_knownTypes)) ? $_messageType : 'UNKNOWN';
        } catch (Exception $e) {
            $_messageType = 'TIMEOUT';
            $result = array();
        }
        return array(
            'messageType' => $_messageType,
            'result'      => $result
        );
    }
}