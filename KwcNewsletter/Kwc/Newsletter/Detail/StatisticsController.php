<?php
class KwcNewsletter_Kwc_Newsletter_Detail_StatisticsController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_buttons = array();
    protected $_position = 'pos';

    protected function _initColumns()
    {
        parent::_initColumns();

        $this->_filters['date'] = array(
            'type' => 'DateRange',
            'width' => 80
        );

        $this->_columns->add(new Kwf_Grid_Column('pos'));
        $this->_columns->add(new Kwf_Grid_Column('link', trlKwf('Link'), 600));
        $this->_columns->add(new Kwf_Grid_Column('count', trlKwf('Count'), 50))
            ->setCssClass('kwf-renderer-decimal');
        $this->_columns->add(new Kwf_Grid_Column('percent', trlKwf('[%]'), 50));
    }

    protected function _getNewsletterId()
    {
        return substr(strrchr($this->_getParam('componentId'), '_'), 1);
    }

    protected function _getNewsletterMailComponentId()
    {
        return $this->_getParam('componentId') . '_mail';
    }

    protected function _fetchData($order, $limit, $start)
    {
        $db = Kwf_Registry::get('db');
        $pos = 1;

        $ret = array();
        $newsletterComponent = Kwf_Component_Data_Root::getInstance()->getComponentByDbId(
            $this->_getNewsletterMailComponentId(),
            array('ignoreVisible' => true)
        );
        $newsletterRow = $newsletterComponent->parent->row;
        $total = $newsletterRow->count_sent;
        if (!$total) { return array(); }

        $trackViews = Kwc_Abstract::getSetting($newsletterComponent->componentClass, 'trackViews');
        if ($trackViews) {
            $count = $newsletterComponent->getComponent()->getTotalViews($this->_getDateSql('date'));
            if ($count) {
                $ret[] = array(
                    'pos' => $pos++,
                    'link' => trlKwf('view rate') . ' (' . trlKwf('percentage of users which opened the html newsletter') . ')',
                    'count' => $count,
                    'percent' => number_format(($count / $total)*100, 2) . '%'
                );
            }
        }
        $count = $newsletterComponent->getComponent()->getTotalClicks($this->_getDateSql('click_date'));
        $ret[] = array(
            'pos' => $pos++,
            'link' => trlKwf('click rate') . ' (' . trlKwf('percentage of users which clicked at least one link in newsletter') . ')',
            'count' => $count,
            'percent' => number_format(($count / $total)*100, 2) . '%'
        );
        foreach (Kwf_Component_Data_Root::getInstance()->getPlugins('KwcNewsletter_Kwc_Newsletter_PluginInterface') as $plugin) {
            $options = array();
            if ($this->getParam('date_from')) $options['dateFrom'] = $this->getParam('date_from');
            if ($this->getParam('date_to')) $options['dateTo'] = $this->getParam('date_to');

            $statisticRows = $plugin->getNewsletterStatisticRows($newsletterRow, $options);
            foreach ($statisticRows as $row) {
                $ret[] = array(
                    'pos' => $pos++,
                    'link' => $row['name'],
                    'count' => $row['count'],
                    'percent' => $row['percent']
                );
            }
        }
        $ret[] = array(
            'pos' => $pos++,
            'link' => ' ',
            'count' => '',
            'percent' => '',
        );

        $select = new Zend_Db_Select($db);
        $select
            ->from(
                array('s' => 'kwc_mail_redirect_statistics'),
                array('c' => new Zend_Db_Expr('count(distinct(concat(s.recipient_id,s.recipient_model_shortcut)))'))
            )
            ->join(array('r' => 'kwc_mail_redirect'), 's.redirect_id=r.id', array('r.value'))
            ->where('s.mail_component_id = ?', $newsletterComponent->componentId)
            ->group('s.redirect_id')
            ->order('c DESC');
        if ($dateSql = $this->_getDateSql('s.click_date')) $select->where($dateSql);

        foreach ($db->fetchAll($select) as $row) {
            $link = $row['value'];
            $row['value'] = $link;
            $ret[] = array(
                'pos' => $pos++,
                'link' => $link,
                'count' => $row['c'],
                'percent' => number_format(($row['c'] / $total)*100, 2) . '%'
            );
        }
        return $ret;
    }

    protected function _getDateSql($dateField)
    {
        $dateFrom = ($from = $this->_getParam('date_from')) ? new Kwf_Date($from) : null;
        $dateTo = ($to = $this->_getParam('date_to')) ? new Kwf_Date($to) : null;

        $ret = null;
        if ($dateFrom && $dateTo) {
            $ret = "DATE({$dateField}) BETWEEN DATE(\"{$dateFrom->format()}\") AND DATE(\"{$dateTo->format()}\")";
        } else if ($dateFrom) {
            $ret = "DATE({$dateField}) >= DATE(\"{$dateFrom->format()}\")";
        } else if ($dateTo) {
            $ret = "DATE({$dateField}) <= DATE(\"{$dateTo->format()}\")";
        }

        if ($ret) $ret = new Zend_Db_Expr($ret);

        return $ret;
    }
}
