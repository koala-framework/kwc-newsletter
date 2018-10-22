<?php
namespace KwcNewsletter\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use KwcNewsletter\Bundle\Model\Subscribers;

/**
 * @Route("/api/v1", service="kwc_newsletter.controller.subscribers_api")
 */
class SubscribersApiController extends Controller
{
    /**
     * @var Subscribers
     */
    private $model;
    /**
     * @var boolean
     */
    private $requireCountry;

    public function __construct(Subscribers $model, $requireCountry)
    {
        $this->model = $model;
        $this->requireCountry = $requireCountry;
    }

    /**
     * @Route("/subscribers")
     * @Method("POST")
     *
     * @RequestParam(name="gender", requirements="(male|female)", strict=true, nullable=true)
     * @RequestParam(name="title", strict=true, nullable=true)
     * @RequestParam(name="firstname", strict=true)
     * @RequestParam(name="lastname", strict=true)
     * @RequestParam(name="email", requirements=@Email, strict=true)
     * @RequestParam(name="categoryId", requirements="\d+", strict=true, nullable=true)
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     */
    public function postAction(ParamFetcher $paramFetcher, Request $request)
    {
        $root = \Kwf_Component_Data_Root::getInstance();
        $subroot = ($country = $this->getCountry($paramFetcher)) ? $root->getComponentById('root-' . strtolower($country)) : $root;
        if (!$subroot) throw new \Kwf_Exception('Subroot not found');
        $newsletterComponent = \Kwf_Component_Data_Root::getInstance()->getComponentByClass(
            'KwcNewsletter_Kwc_Newsletter_Component', array('subroot' => $subroot)
        );

        $email = $paramFetcher->get('email');
        $s = new \Kwf_Model_Select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('email', $email);
        $row = $this->model->getRow($s);
        if (!$row) {
            $row = $this->model->createRow(array(
                'newsletter_component_id' => $newsletterComponent->dbId,
                'email' => $email
            ));
        }

        $this->updateRow($row, $paramFetcher);

        $row->setLogSource(($source = $paramFetcher->get('source')) ? $source : $subroot->trlKwf('Subscribe API'));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());

        $sendActivationMail = false;
        if (!$row->activated || $row->unsubscribed) {
            $row->writeLog($subroot->trlKwf('Subscribed'), 'subscribed');

            $row->unsubscribed = false;
            $row->activated = false;

            $sendActivationMail = true;
        }

        if ($categoryId = $request->get('categoryId')) {
            $s = new \Kwf_Model_Select();
            $s->whereEquals('category_id', $categoryId);
            if (!$row->countChildRows('ToCategories', $s)) {
                $row->createChildRow('ToCategories', array(
                    'category_id' => $categoryId
                ));
            }
        }

        if ($row->isDirty()) $row->save();

        $sendOneActivationMailForEmailPerHourCacheId = 'send-one-activation-mail-for-email-per-hour-' . md5($email);
        $sendOneActivationMailForEmailPerHour = \Kwf_Cache_Simple::fetch($sendOneActivationMailForEmailPerHourCacheId);
        if (!$sendOneActivationMailForEmailPerHour && $sendActivationMail) {
            $this->sendActivationMail($newsletterComponent, $row);

            \Kwf_Cache_Simple::add($sendOneActivationMailForEmailPerHourCacheId, true, 3600);
        }

        return new JsonResponse(array(
            'message' => $this->getMessage($subroot)
        ), JsonResponse::HTTP_OK);
    }

    protected function getCountry(ParamFetcher $paramFetcher)
    {
        $ret = null;

        if ($this->requireCountry) {
            $country = new RequestParam();
            $country->name = 'country';
            $country->requirements = new Country();
            $country->strict = true;
            $paramFetcher->addParam($country);

            $ret = $paramFetcher->get('country');
        }

        return $ret;
    }

    protected function getMessage(\Kwf_Component_Data $subroot)
    {
        return $subroot->trlKwf(
            'Thank you for your subscription. If you have not been added to our newsletter-distributor yet, you will shortly receive an email with your activation link. Please click on the link to confirm your subscription.'
        );
    }

    protected function updateRow(\Kwf_Model_Row_Abstract $row, ParamFetcher $paramFetcher)
    {
        $row->gender = strtolower($paramFetcher->get('gender'));
        $row->title = ($title = $paramFetcher->get('title')) ? $title : '';
        $row->firstname = $paramFetcher->get('firstname');
        $row->lastname = $paramFetcher->get('lastname');
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
}
