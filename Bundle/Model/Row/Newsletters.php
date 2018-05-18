<?php
namespace KwcNewsletter\Bundle\Model\Row;

class Newsletters extends \Kwf_Model_Proxy_Row
{
    public function __toString()
    {
        return $this->subject;
    }

    public function __get($name)
    {
        if ($name == 'info_short') {
            $info = $this->getInfo();
            return $info['shortText'];
        } else if ($name == 'info') {
            $info = $this->getInfo();
            return $info['text'];
        } else if ($name == 'subject') {
            $model = $this->getModel()->getDependentModel('Mails');
            $id = $this->component_id . '_' . $this->id . '_mail';
            $mailRow = $model->getRow($id);
            if ($mailRow) return $mailRow->subject;
            return '';
        } else {
            return parent::__get($name);
        }
    }

    public final function send($debugOutput = false)
    {
        throw new \Kwf_Exception("moved to cli controller");
    }

    protected final function _sendMail($recipient, $debugOutput = false)
    {
        throw new \Kwf_Exception("moved to cli controller");
    }

    protected function _afterSave()
    {
        parent::_afterSave();

        if ($this->getStatus() !== $this->status) {
            \Kwf_Cache_Simple::delete("kwcNewsletterStatus-{$this->id}");
        }
    }

    public function getStatus()
    {
        $cacheId = "kwcNewsletterStatus-{$this->id}";
        $status = \Kwf_Cache_Simple::fetch($cacheId, $success);
        if (!$success) {
            $status = $this->getModel()->fetchColumnByPrimaryId('status', $this->id);
            \Kwf_Cache_Simple::add($cacheId, $status);
        }
        return $status;
    }

    public function getNextQueueRows($sendProcessPid)
    {
        while (true) {
            \Kwf_Model_Abstract::clearAllRows();
            $model = $this->getModel()->getDependentModel('Queues');
            $select = $model->select()
                ->whereEquals('newsletter_id', $this->id)
                ->whereNull('send_process_pid')
                ->order(new \Zend_Db_Expr('RAND()'))
                ->limit(100);
            $queueRows = $model->getRows($select);
            if ($queueRows->count() === 0) return array(); //queue empty

            $ids = array();
            foreach ($queueRows as $row) {
                $ids[] = $row->id;
            }

            $model->getTable()->update(
                array(
                    'send_process_pid'=>$sendProcessPid,
                ),
                "id IN (".implode(",", $ids).") AND ISNULL(send_process_pid)"
            );

            $select = new \Kwf_Model_Select();
            $select->whereEquals('id', $ids);
            $sendProcessPids = array();
            foreach ($model->export(\Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('id', 'send_process_pid'))) as $row) {
                $sendProcessPids[$row['id']] = $row['send_process_pid'];
            }

            $rows = array();
            foreach ($queueRows as $row) {
                if (!array_key_exists($row->id, $sendProcessPids) || $sendProcessPids[$row->id] != $sendProcessPid) continue;
                $rows[] = $row;
            }

            if (!empty($rows)) return $rows;
            //else another process has taken our rows, try again
        }
    }

    //returns current sending speed in mails per minute
    public function getCurrentSpeed()
    {
        if (!$this->resume_date) return null;

        $startDate = max(time()-60, strtotime($this->resume_date));
        $queueLogModel = $this->getModel()->getDependentModel('QueueLogs');
        $select = $queueLogModel->select()
            ->whereEquals('newsletter_id', $this->id)
            ->where(new \Kwf_Model_Select_Expr_Higher('send_date', new \Kwf_DateTime($startDate)));
        return $queueLogModel->countRows($select) / (time()-$startDate) * 60;
    }

    public function getInfo()
    {
        $queue = $this->getModel()->getDependentModel('Queues');
        $select = $queue->select()->whereEquals('newsletter_id', $this->id);
        $ret = array();
        $ret['state']    = $this->status;
        $ret['sent']     = $this->count_sent;
        $ret['total']    = $queue->countRows($select) + $this->count_sent;
        $ret['queued']   = $queue->countRows($select->whereNull('send_process_pid'));
        $ret['lastSentDate'] = strtotime($this->last_sent_date);

        $ret['speed'] = '';
        $ret['remainingTime'] = '';
        if ($this->resume_date && time()-strtotime($this->resume_date) > 30) {
            $currentSpeed = $this->getCurrentSpeed();
            if ($currentSpeed) {
                $ret['speed'] = round($currentSpeed).' '.trlKwf('Mails/Min');

                $seconds = ($ret['queued'] / $currentSpeed) * 60;
                $hours = floor($seconds / 3600);
                $seconds -= $hours * 3600;
                $minutes = floor($seconds / 60);
                $ret['remainingTime'] = sprintf('%02d:%02d', $hours, $minutes);
            }
        }

        switch ($this->status) {
            case 'stop': $text = trlKwf('Sending stopped, cannot start again').'.'; break;
            case 'pause': $text = trlKwf('Sending paused').'.'; break;
            case 'start': case 'sending': $text = trlKwf('Sending').'.'; break;
            case 'finished': $text = trlKwf('Sending finished').'.'; break;
            default: $text = trlKwf('Newsletter waiting for start').'.'; break;
        }
        $ret['shortText'] = $text;

        if ($ret['sent'] || $ret['queued']) {
            $text .= ' ';
            $text .= trlKwf('{0} sent, {1} waiting to send.', array($ret['sent'], $ret['queued']));
            if ($ret['lastSentDate']) {
                $time = date(trlKwf('Y-m-d H:i'), $ret['lastSentDate']);
                $t = ' ' . trlKwf('Last mail sent: {0}', $time);;
                $text .= $t;
                $ret['shortText'] .= $t;
            }
            $ret['text'] = $text;
        }

        return $ret;
    }

    public function getMailComponent()
    {
        $componentId = $this->component_id . '_' . $this->id . '_mail';
        return \Kwf_Component_Data_Root::getInstance()
            ->getComponentByDbId($componentId, array('ignoreVisible' => true))
            ->getComponent();
    }

    public function getCountOfMailsPerMinute()
    {
        if ($this->mails_per_minute == 'unlimited') {
            return null;
        } else if ($this->mails_per_minute == 'fast') {
            return 100;
        } else if ($this->mails_per_minute == 'normal') {
            return 40;
        } else if ($this->mails_per_minute == 'slow') {
            return 20;
        } else {
            throw new \Kwf_Exception('unknown speed');
        }
    }
}
