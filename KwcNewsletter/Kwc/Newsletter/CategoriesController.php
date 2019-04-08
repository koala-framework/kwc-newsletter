<?php
use \KwcNewsletter\Bundle\Model\Subscribers;

class KwcNewsletter_Kwc_Newsletter_CategoriesController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_modelName = 'KwcNewsletter\Bundle\Model\Categories';
    protected $_position = 'pos';
    protected $_buttons = array('save', 'add', 'delete', 'csv');

    public function indexAction()
    {
        parent::indexAction();

        $newsletterComponent = Kwf_Component_Data_Root::getInstance()
            ->getComponentByDbId($this->_getParam('componentId'), array('ignoreVisible' => true));
        $admin = Kwc_Abstract_Admin::getInstance($this->_getParam('class'));

        $this->view->xtype = "kwf.tabpanel";
        $this->view->activeTab = 0;
        $this->view->tabs = array();
        foreach (Subscribers::getSources($newsletterComponent) as $newsletterSourceId => $newsletterSourceName) {
            $this->view->tabs[$newsletterSourceId] = array(
                'xtype' => 'kwf.autogrid',
                'title' => $newsletterSourceName,
                'controllerUrl' => $admin->getControllerUrl('Categories'),
                'baseParams' => array(
                    'componentId' => $this->_getParam('componentId'),
                    'newsletterSource' => $newsletterSourceId
                )
            );
        }
    }

    protected function _initColumns()
    {
        $this->_columns->add(new Kwf_Grid_Column('category', trlKwf('Category'), 200))
            ->setEditor(new Kwf_Form_Field_TextField());
        $this->_columns->add(new Kwf_Grid_Column('id', trlKwf('id'), 40));

        $this->_columns->add(new Kwf_Grid_Column('count_not_activated', trlKwf('not activated')));
        $this->_columns->add(new Kwf_Grid_Column('count_activated', trlKwf('active')));
        $this->_columns->add(new Kwf_Grid_Column('count_unsubscribed', trlKwf('unsubscribed')));
    }

    protected function _getSelect()
    {
        $ret = parent::_getSelect();
        $ret->whereEquals('newsletter_component_id', $this->_getParam('componentId'));
        $ret->whereEquals('newsletter_source', $this->_getParam('newsletterSource'));
        return $ret;
    }

    protected function _beforeInsert(Kwf_Model_Row_Interface $row, $submitRow)
    {
        parent::_beforeInsert($row, $submitRow);
        $row->newsletter_component_id = $this->_getParam('componentId');
        $row->newsletter_source = $this->_getParam('newsletterSource');
    }
}
