<?php

class MageProfis_ExtendedTaxvat_Helper_Messages
extends Mage_Core_Helper_Abstract
{
    /**
     * 
     * @return array
     */
    public function onSuccessDomestic()
    {
        return array(
            'type' => 'success',
            'messages' => array(
                $this->__('Your VAT-ID is valid.'),
                $this->__('We have identified you as business customer.'),
            )
        );
    }

    /**
     * 
     * @return array
     */
    public function onSuccessUnion()
    {
        return array(
            'type' => 'success',
            'messages' => array (
                $this->__('Your VAT-ID is valid.'),
                $this->__('We have identified you as EU business, you can order VAT-exempt in our shop now.')
            )
        );
    }

    /**
     * 
     * @return array
     */
    public function isInvalid()
    {
        return  array(
            'type' => 'error',
            'messages' => array(
                $this->__('Your VAT-ID is invalid, please check the syntax. If this error remains please contact us directly.'),
            )
        );
    }

    /**
     * 
     * @return array
     */
    public function isTimeoutAsValid()
    {
        return  array(
            'type' => 'warning',
            'messages' => array(
                $this->__('We could not check if your VAT-ID is valid.'),
                $this->__('But you can order VAT-exempt in our shop now.')
            )
        );
    }

    /**
     * 
     * @return array
     */
    public function isTimeoutAsInValid()
    {
        return  array(
            'type' => 'warning',
            'messages' => array(
                $this->__('We could not check if your VAT-ID is valid.'),
                $this->__('please try it again later.')
            )
        );
    }
}