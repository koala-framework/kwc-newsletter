<?php
namespace KwcNewsletter\Bundle\Service;

use KwcNewsletter\Bundle\Model\Subscribers as SubscribersModel;
use Symfony\Component\HttpFoundation\Request;

class Subscribers {

    /**
     * @var SubscribersModel
     */
    protected $subscribersModel;

    public function __construct(SubscribersModel $subscribersModel)
    {
        $this->subscribersModel = $subscribersModel;
    }

    /**
     * @param array $params
     * @param \Kwf_Component_Data $newsletterComponent
     * @return string Message about subscription to be sent back to frontend / in response
     */
    public function createSubscriberFromRequest(array $params, \Kwf_Component_Data $newsletterComponent)
    {
        $subroot = $newsletterComponent->getSubroot();

        $newsletterSource = $params['newsletterSource'];
        $email = $params['email'];
        $doubleOptIn = array_key_exists('doubleOptIn', $params) ? $params['doubleOptIn'] : true;

        $s = new \Kwf_Model_Select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('newsletter_source', $newsletterSource);
        $s->whereEquals('email', $email);
        $row = $this->subscribersModel->getRow($s);
        if (!$row) {
            $row = $this->subscribersModel->createRow(array(
                'newsletter_component_id' => $newsletterComponent->dbId,
                'newsletter_source' => $newsletterSource,
                'email' => $email
            ));
        }

        // activate it immediately
        if (!$doubleOptIn) {
            $row->activated = true;
        }

        $this->updateRow($row, $params);

        $row->setLogSource($params['source']);
        $row->setLogIp($params['ip']);

        $sendActivationMail = false;
        if ($row->activated) {
            $row->writeLog($subroot->trlKwf('Subscribed and activated'), 'subscribed');
            $row->writeLog($subroot->trlKwf('Subscribed and activated'), 'activated');
        } elseif (!$row->activated || $row->unsubscribed) {
            $row->writeLog($subroot->trlKwf('Subscribed'), 'subscribed');

            $row->unsubscribed = false;
            $row->activated = false;

            $sendActivationMail = true;
        }

        if ($params['categoryId']) {
            $s = new \Kwf_Model_Select();
            $s->whereEquals('category_id', $params['categoryId']);
            if (!$row->countChildRows('ToCategories', $s)) {
                $row->createChildRow('ToCategories', array(
                    'category_id' => $params['categoryId']
                ));
            }
        }

        if ($row->isDirty()) $row->save();

        $sendOneActivationMailForEmailPerHourCacheId = 'send-one-activation-mail-for-email-per-hour-' . md5("{$newsletterSource}-{$email}");
        $sendOneActivationMailForEmailPerHour = \Kwf_Cache_Simple::fetch($sendOneActivationMailForEmailPerHourCacheId);
        if (!$sendOneActivationMailForEmailPerHour && $sendActivationMail) {
            $this->sendActivationMail($newsletterComponent, $row);
            \Kwf_Cache_Simple::add($sendOneActivationMailForEmailPerHourCacheId, true, 3600);
        }

        return $this->getMessage($subroot, $doubleOptIn);
    }

    protected function sendActivationMail(\Kwf_Component_Data $newsletterComponent, \Kwc_Mail_Recipient_Interface $row)
    {
        $subscribe = \Kwf_Component_Data_Root::getInstance()->getComponentByClass(
            'KwcNewsletter_Kwc_Newsletter_Subscribe_Component', array('subroot' => $newsletterComponent->getSubroot(), 'limit' => 1)
        );
        $subscribe->getChildComponent('_mail')->getComponent()->send($row, array(
            'formRow' => $row,
            'host' => $newsletterComponent->getDomain(),
            'unsubscribeComponent' => null,
            'editComponent' => $newsletterComponent->getChildComponent('_editSubscriber'),
            'doubleOptInComponent' => $subscribe->getChildComponent('_doubleOptIn')
        ));
    }

    protected function getMessage(\Kwf_Component_Data $subroot, $doubleOptIn=true)
    {
        return $subroot->trlKwf(
            $doubleOptIn ?
                'Thank you for your subscription. If you have not been added to our newsletter-distributor yet, you will shortly receive an email with your activation link. Please click on the link to confirm your subscription.' :
                'Thank you for your subscription, it was successfully activated.'
        );
    }

    protected function updateRow(\Kwf_Model_Row_Abstract $row, array $params)
    {
        $row->gender = strtolower($params['gender']);
        $row->title = ($title = $params['title']) ? $title : '';
        $row->firstname = $params['firstname'];
        $row->lastname = $params['lastname'];
    }
}
