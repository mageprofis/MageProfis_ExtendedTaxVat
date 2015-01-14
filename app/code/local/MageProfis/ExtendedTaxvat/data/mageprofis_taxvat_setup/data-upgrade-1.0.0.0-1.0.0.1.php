<?php


$html='<body style="background: #F6F6F6; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; margin: 0; padding: 0;">
    <div style="background: #F6F6F6; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 12px; margin: 0; padding: 0;">
        <table cellspacing="0" cellpadding="0" border="0" height="100%" width="100%">
            <tr>
                <td align="center" valign="top" style="padding: 20px 0 20px 0">
                    <table bgcolor="FFFFFF" cellspacing="0" cellpadding="10" border="0" width="650" style="border:1px solid #E0E0E0;">
                        <tr>
                            <td valign="top">
                                <a href="{{store url=""}}" style="color:#1E7EC8;"><img src="{{var logo_url}}" alt="{{var logo_alt}}" border="0"/></a>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <h1 style="font-size: 22px; font-weight: normal; line-height: 22px; margin: 0 0 11px 0;">Erfolgreiche Validierung für {{var name}},</h1>
                                <p style="font-size: 12px; line-height: 16px; margin: 0 0 8px 0;">Kundeninformationen:</p>
<table align="center" valign="top" style="padding: 20px 0 20px 0;width:400px;">

<tr><td>Steuernr.</td><td>{{var taxvat}}</td>

{{if customer.company}}
<tr><td>Firma</td><td>{{var customer.company}}</td>
{{/if}}
{{if customer.postcode}}
<tr><td>PLZ</td><td>{{var customer.postcode}}</td>
{{/if}}
{{if customer.city}}
<tr><td>Stadt</td><td>{{var customer.city}}</td>
{{/if}}
</table>

                            </td>
                        </tr>
                        <tr>
                            <td style="background-color: #EAEAEA; text-align: center;"><p style="font-size:12px; margin:0; text-align: center;">Danke!, <strong>{{var store.getFrontendName()}}</strong></p></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
';
$name='Erfolgreiche - Ust-Id - Prüfung';
$subject='Erfolgreiche Validierung';
$emailModel = Mage::getModel('core/email_template');
$emailModel->setTemplateText($html)
    ->setTemplateCode($name)
    ->setTemplateSubject($subject)
    ->save();
			
		
$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttribute('customer', 'validation_result', array(
    'input'         => 'text',
    'type'          => 'varchar',
    'label'         => 'Ust.Id- Validation Result',
    'visible'       => 1,
    'required'      => 0,
    'user_defined' => 1,
));

$setup->addAttributeToGroup(
 $entityTypeId,
 $attributeSetId,
 $attributeGroupId,
 'validation_result',
 '999'  //sort_order
);
$used_in_forms=array();
 $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "validation_result");

$used_in_forms[]="adminhtml_customer";
        $attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 1)
        ->setData("sort_order", 100)
;
$attribute->save();

$setup->endSetup();
$installer->endSetup();