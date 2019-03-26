<?php

class KwcNewsletter_Controller_ApiKeysController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_model = 'KwcNewsletter\Bundle\Model\NewsletterApiKeys';
    protected $_buttons = array('add', 'save', 'delete');
    protected $_permissions = array('add', 'save', 'delete');
    protected $_paging = 25;
    protected $_defaultOrder = 'name';
    protected $_editDialog = array(
        'controllerUrl' => '/admin/kwc-newsletter/api-key'
    );

    protected function _initColumns()
    {
        parent::_initColumns();
        $this->_columns->add(new Kwf_Grid_Column('name', trlKwf('Name'), 200))
            ->setEditor(new Kwf_Form_Field_TextField());
        $this->_columns->add(new Kwf_Grid_Column('newsletter_component_id', trlKwf('Country'), 300))
            ->setData(new KwcNewsletter_ApiKeyCountryData());
        $this->_columns->add(new Kwf_Grid_Column('key', trlKwf('Key'), 300));
    }
}
