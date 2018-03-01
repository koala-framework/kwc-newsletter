<?php
class KwcNewsletter_Kwc_NewsletterCategory_Subscribe_ApiController extends KwcNewsletter_Kwc_Newsletter_Subscribe_ApiController
{
    protected $_model = 'KwcNewsletter\Bundle\Model\Subscribers';

    protected function _insertSubscription(Kwf_Model_Row_Abstract $row)
    {
        //TODO: multiple categories
        if (!(int)$this->_getParam('categoryId')) {
            //parameter used in _afterInsertedSubscription
            throw new Kwf_Exception("parameter categoryId required");
        }
        return $this->_subscribe->getComponent()->insertSubscriptionWithCategory($row, (int)$this->_getParam('categoryId'));
    }

}
