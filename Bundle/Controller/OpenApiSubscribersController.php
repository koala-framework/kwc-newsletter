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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
    /**
     * @var Subscribers
     */
    private $model;

    /* @var ErrorCollectValidator */
    protected $validator;

    /* @var \Kwf_Component_Data */
    protected $newsletterComponent;

    /* @var ApiUser */
    protected $user;

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

        $this->updateRow($row, $paramFetcher);

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

    protected function updateRow(\Kwf_Model_Row_Abstract $row, ParamFetcher $paramFetcher)
    {
        $row->gender = strtolower($paramFetcher->get('gender'));
        $row->title = ($title = $paramFetcher->get('title')) ? $title : '';
        $row->firstname = $paramFetcher->get('firstname');
        $row->lastname = $paramFetcher->get('lastname');
    }

    public function getUser()
    {
        if (!$this->user) {
            throw new UnauthorizedHttpException('User not logged in');
        }
        return $this->user;
    }
}
