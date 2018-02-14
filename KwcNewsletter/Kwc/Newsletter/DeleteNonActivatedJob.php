<?php
class KwcNewsletter_Kwc_Newsletter_DeleteNonActivatedJob extends Kwf_Util_Maintenance_Job_Abstract
{
    public function getFrequency()
    {
        return self::FREQUENCY_DAILY;
    }

    public function execute($debug)
    {
        foreach ($this->_getSubscribers() as $row) {
            $row->delete();
        }
    }

    private function _getSubscribers()
    {
        $date = new Kwf_Date(strtotime('-7 days');

        $s = new Kwf_Model_Select();
        $s->where(new Kwf_Model_Select_Expr_Or(array(
            new Kwf_Model_Select_Expr_And(array(
                new Kwf_Model_Select_Expr_Equal('state', 'subscribed'),
                new Kwf_Model_Select_Expr_LowerEqual('date', $date)
            )),
            new Kwf_Model_Select_Expr_Not(
                new Kwf_Model_Select_Expr_And(array(
                    new Kwf_Model_Select_Expr_Equal('state', array('activated', 'unsubscribed')),
                    new Kwf_Model_Select_Expr_HigherEqual('date', $date)
                ))
            )
        )));
        $s->limit(1);

        $select = new Kwf_Model_Select();
        $select->whereEquals('activated', false);
        $select->where(new Kwf_Model_Select_Expr_Child_Contains('Logs', $s));
        return Kwf_Model_Abstract::getInstance('KwcNewsletter_Kwc_Newsletter_Subscribe_Model')->getRows($select);
    }
}
