<?php

namespace KwcNewsletter\Bundle\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use KwcNewsletter\Bundle\Model\Subscribers;
use KwcNewsletter\Bundle\Security\ApiUser;
use KwfBundle\Validator\ErrorCollectValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Ip;

/**
 * @Route("/api/v1/open", service="kwc_newsletter.controller.open_api_subscribers")
 */
class OpenApiSubscribersController extends Controller
{
    /* @var ErrorCollectValidator */
    protected $validator;
    /* @var \Kwf_Component_Data */
    protected $newsletterComponent;
    /* @var ApiUser */
    protected $user;
    /**
     * @var Subscribers
     */
    private $model;

    public function __construct(Subscribers $model, ErrorCollectValidator $validator, TokenStorage $tokenStorage)
    {
        $this->model = $model;
        $this->validator = $validator;
        $this->user = $tokenStorage->getToken() && $tokenStorage->getToken()->getUser() != 'anon.' ?
            $tokenStorage->getToken()->getUser() :
            null;
        $this->newsletterComponent = $this->user->getNewsletterComponent();
    }

    /**
     * @Route("/subscribers")
     * @QueryParam(name="email", requirements=".+", strict=true, nullable=false, array=true)
     * @Method("GET")
     * @View(serializerGroups={"user"})
     */
    public function getSubscriberAction(ParamFetcher $paramFetcher)
    {
        $this->validator->validateAndThrow($paramFetcher);
        $email = $paramFetcher->get('email');

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $s->whereEquals('email', $email);
        $rows = $this->model->getRows($s);

        if (!count($rows)) {
            throw new NotFoundHttpException('Subscriber not found');
        }

        return $rows;
    }

    /**
     * @Route("/subscribers/{id}", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="gender", requirements="(female|male)", nullable=true)
     * @RequestParam(name="title", strict=true, nullable=true)
     * @RequestParam(name="firstname", strict=true, nullable=true)
     * @RequestParam(name="lastname", strict=true, nullable=true)
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @Method("PUT")
     * @View(serializerGroups={"user"})
     */
    public function putSubscriberAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $s->whereEquals('id', $id);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Subscriber not found');
        }

        $this->_updateRow($row, $paramFetcher);

        $row->setLogSource(
            ($source = $paramFetcher->get('source')) ?
                $source :
                trlKwf('Subscribe Open API. API Key: {0}', array($this->getUser()->getUsername())
                ));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());
        $row->writeLog(trlKwf('Updated'));

        if ($row->isDirty()) {
            $row->save();
        }

        return $row;
    }

    protected function _updateRow(\Kwf_Model_Row_Abstract $row, ParamFetcher $paramFetcher)
    {
        $row->gender = strtolower($paramFetcher->get('gender'));
        $row->title = ($title = $paramFetcher->get('title')) ? $title : '';
        $row->firstname = $paramFetcher->get('firstname');
        $row->lastname = $paramFetcher->get('lastname');
    }

    public function getUser()
    {
        if (!$this->user) {
            throw new AccessDeniedHttpException('No authenticated user found');
        }
        return $this->user;
    }

    /**
     * @Route("/subscribers/unsubscribe/{id}", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @Method("PUT")
     */
    public function putUnsubscribeAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $s->whereEquals('id', $id);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Subscriber not found');
        }

        // already unsubscribed
        if ($row->unsubscribed) {
            throw new UnprocessableEntityHttpException('Subscriber already unsunscribed');
        }

        // unsubscribe and log
        $row->setLogSource(
            ($source = $paramFetcher->get('source')) ?
                $source :
                trlKwf('Subscribe Open API. API Key: {0}', array($this->getUser()->getUsername())
                ));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());
        $row->writeLog(trlKwf('Unsubscribed'), 'unsubscribed');

        $row->unsubscribed = true;
        $row->save();

        // empty body and (automatic) http/204 - no content
        return null;
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
     * @RequestParam(name="categories", requirements="\d+", strict=true, nullable=true, array=true)
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @RequestParam(name="doubleOptIn", requirements="(1|)", strict=true, nullable=true, default=true)
     */
    public function postSubscribeAction(ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $doubleOptIn = $paramFetcher->get('doubleOptIn');

        $email = $paramFetcher->get('email');
        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $s->whereEquals('email', $email);
        $row = $this->model->getRow($s);
        if (!$row) {
            $row = $this->model->createRow(array(
                'newsletter_component_id' => $this->newsletterComponent->dbId,
                'email' => $email,
            ));
        }

        // activate it immediately
        if (!$doubleOptIn) {
            $row->activated = true;
        }

        // fetch optional parameters
        $this->_updateRow($row, $paramFetcher);

        $row->setLogSource(
            ($source = $paramFetcher->get('source')) ?
                $source :
                trlKwf('Subscribe Open API. API Key: {0}', array($this->getUser()->getUsername())
                ));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());

        $sendActivationMail = false;
        if ($row->activated) {
            $row->writeLog(trlKwf('Subscribed and activated'), 'subscribed');
            $row->writeLog(trlKwf('Subscribed and activated'), 'activated');
        } elseif (!$row->activated || $row->unsubscribed) {
            $row->writeLog(trlKwf('Subscribed'), 'subscribed');

            $row->unsubscribed = false;
            $row->activated = false;

            $sendActivationMail = true;
        }

        // statistics
        $categoriesRet = array(
            'total' => 0,
            'added' => 0,
            'not_found' => 0,
            'exists' => 0,
        );
        if (count($categories = $request->get('categories'))) {

            $categoriesRet = array(
                'total' => count($categories),
                'added' => 0,
                'not_found' => 0,
                'exists' => 0,
            );

            $subscribersToCategoryModel = $this->model->getDependentModel('ToCategories');
            $categoriesModel = $subscribersToCategoryModel->getReferencedModel('Category');

            foreach ($categories as $categoryId) {
                $s = $categoriesModel->select();
                $s->whereEquals('id', $categoryId);
                $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
                $categoryRow = $categoriesModel->getRow($s);

                if ($categoryRow) {
                    $s = $this->model->select();
                    $s->whereEquals('category_id', $categoryId);
                    if (!$row->countChildRows('ToCategories', $s)) {
                        $row->createChildRow('ToCategories', array(
                            'category_id' => $categoryId
                        ));
                        $categoriesRet['added']++;
                    } else {
                        $categoriesRet['exists']++;
                    }
                } else {
                    $categoriesRet['not_found']++;
                }
            }
        }

        if ($row->isDirty()) $row->save();

        $sendOneActivationMailForEmailPerHourCacheId = 'send-one-activation-mail-for-email-per-hour-' . md5($email);
        $sendOneActivationMailForEmailPerHour = \Kwf_Cache_Simple::fetch($sendOneActivationMailForEmailPerHourCacheId);
        if (!$sendOneActivationMailForEmailPerHour && $sendActivationMail) {
            $this->_sendActivationMail($this->newsletterComponent, $row);

            \Kwf_Cache_Simple::add($sendOneActivationMailForEmailPerHourCacheId, true, 3600);
        }

        return array(
            'message' => trlKwf(
                $doubleOptIn ?
                    'Thank you for your subscription. If you have not been added to our newsletter-distributor yet, you will shortly receive an email with your activation link. Please click on the link to confirm your subscription.' :
                    'Thank you for your subscription, it was successfully activated.'
            ),
            'categories' => $categoriesRet,
        );
    }

    protected function _sendActivationMail(\Kwf_Component_Data $newsletterComponent, \Kwc_Mail_Recipient_Interface $row)
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
