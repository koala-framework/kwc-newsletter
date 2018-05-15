<?php
class KwcNewsletter_Kwc_Newsletter_Update_20180503RecipientModelShortcut extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        $db->query('ALTER TABLE `kwc_newsletter_queues` ADD `recipient_model_shortcut` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `recipient_model`');
        $db->query('ALTER TABLE `kwc_newsletter_queue_logs` ADD `recipient_model_shortcut` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `recipient_model`');

        $classes = array();
        foreach (Kwc_Abstract::getComponentClasses() as $c) {
            if (is_subclass_of($c, 'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Component')) {
                $classes[] = $c;
            }
        }
        if (empty($classes)) return $ret;

        foreach ($classes as $class) {
            foreach (Kwc_Abstract::getSetting($class, 'recipientSources') as $shortcut => $recipientSource) {
                $db->query("UPDATE `kwc_newsletter_queues` SET `recipient_model_shortcut` = \"{$shortcut}\" WHERE `recipient_model` = \"{$recipientSource['model']}\"");
                $db->query("UPDATE `kwc_newsletter_queue_logs` SET `recipient_model_shortcut` = \"{$shortcut}\" WHERE `recipient_model` = \"{$recipientSource['model']}\"");
            }
        }

        $db->query('ALTER TABLE `kwc_newsletter_queues` DROP `recipient_model`');
        $db->query('ALTER TABLE `kwc_newsletter_queue_logs` DROP `recipient_model`');

        return $ret;
    }
}
