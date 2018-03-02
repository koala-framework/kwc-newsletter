<?php
class KwcNewsletter_Kwc_NewsletterCategory_Subscribe_FrontendForm extends KwcNewsletter_Kwc_Newsletter_Subscribe_FrontendForm
{
    protected $_modelName = 'KwcNewsletter\Bundle\Model\Subscribers';

    protected function _initFields()
    {
        parent::_initFields();

        $categories = $this->_getCategories();
        if (count($categories) > 1) {
            $this->add(new Kwf_Form_Field_MultiCheckbox('ToCategories', 'Category', trlKwfStatic('Categories')))
                ->setValues($categories)
                ->setWidth(255)
                ->setAllowBlank(false);
        }
    }

    protected function _afterSave(Kwf_Model_Row_Interface $row)
    {
        $this->addCategoryIfOnlyOne($row);
    }

    public function getCategories()
    {
        return $this->_getCategories();
    }

    protected function _getCategories()
    {
        // Newsletterkategorien werden zum Newsletter gespeichert, welcher
        // Newsletter grade aktuell ist weiß nur die Komponente, deswegen
        // $this->_subscribeComponentId
        // KwcNewsletter_Kwc_Newsletter_EditSubscriber_Component calls without subscribeComponentId
        if (!$this->_subscribeComponentId) {
            return array();
        }
        $model = Kwf_Component_Model::getInstance('KwcNewsletter\Bundle\Model\SubscribeCategories');
        $select = $model->select()
            ->whereEquals('component_id', $this->_subscribeComponentId)
            ->order('pos');
        $categories = array();
        foreach ($model->getRows($select) as $row) {
            $categories[$row->category_id] = $row->name;
        }
        return $categories;
    }

    public function addCategoryIfOnlyOne(Kwf_Model_Row_Interface $row)
    {
        $categories = $this->_getCategories();
        if (count($categories) == 1) {
            $model = Kwf_Model_Abstract::getInstance('KwcNewsletter\Bundle\Model\SubscribersToCategories');
            $row = $model->createRow(array(
                'subscriber_id' => $row->id,
                'category_id' => key($categories)
            ));
            $row->save();
        }
    }
}
