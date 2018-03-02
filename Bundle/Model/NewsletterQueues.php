<?php
namespace KwcNewsletter\Bundle\Model;

class NewsletterQueues extends \Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletter_queues';
    protected $_rowClass = 'KwcNewsletter\Bundle\Model\Row\NewsletterQueues';

    protected function _init()
    {
        $this->_referenceMap['Newsletter'] = array(
            'column' => 'newsletter_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Newsletters'
        );

        parent::_init();
    }

    public function deleteRows($where)
    {
        $whereEquals = $where->getPart('whereEquals');
        if (!$whereEquals || !isset($whereEquals['newsletter_id'])) throw new Kwf_Exception('No newsletter_id set');
        $select = new Kwf_Model_Select();
        $select->whereEquals('id', $whereEquals['newsletter_id']);
        $newsletter = $this->getReferencedModel('Newsletter')->getRow($select);
        if (!$newsletter) throw new Kwf_Exception('No Newsletter found');
        if (in_array($newsletter->status, array('start', 'stop', 'finished', 'sending'))) {
            throw new Kwf_ClientException(trlKwf('Can only remove users from a paused newsletter'));
        }

        parent::deleteRows($where);
    }
}
