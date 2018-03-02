<?php
class KwcNewsletter_Kwc_Newsletter_Update_20150309Legacy35009 extends Kwf_Update
{
    public function update()
    {
        $db = Kwf_Registry::get('db');

        $done = array();
        foreach ($db->fetchAll("SELECT component_id FROM kwc_newsletter") as $row) {
            if (in_array($row['component_id'], $done)) continue;
            $done[] = $row['component_id'];
            if ($this->_progressBar) $this->_progressBar->next(1, "35009: updating ".$row['component_id']);
            $a = new Kwf_Update_Action_Component_ConvertComponentIds(array(
                'pattern' => $row['component_id'].'_%-mail%',
                'search' => '-mail',
                'replace' => '_mail',
            ));
            $a->update();
        }
    }

    public function getProgressSteps()
    {
        try {
            return Kwf_Registry::get('db')->query("SELECT COUNT(*) FROM kwc_newsletter GROUP BY component_id")->fetchColumn();
        } catch (Exception $e) {
            return 1;
        }
    }
}
