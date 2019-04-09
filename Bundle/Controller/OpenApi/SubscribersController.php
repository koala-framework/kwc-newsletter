<?php

namespace KwcNewsletter\Bundle\Controller\OpenApi;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use KwcNewsletter\Bundle\Model\Subscribers;
use KwcNewsletter\Bundle\Service\Subscribers as SubscribersService;
use KwfBundle\Validator\ErrorCollectValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
 * @Route("/api/v1/open", service="kwc_newsletter.controller.open_api.subscribers")
 */
class SubscribersController extends Controller
{
    /* @var ErrorCollectValidator */
    protected $validator;
    /* @var TokenStorage */
    protected $tokenStorage;
    /* @var Subscribers */
    protected $model;
    /**
     * @var SubscribersService
     */
    protected $subscribersService;

    public function __construct(Subscribers $model, SubscribersService $subscribersService, ErrorCollectValidator $validator, TokenStorage $tokenStorage)
    {
        $this->model = $model;
        $this->validator = $validator;
        $this->tokenStorage = $tokenStorage;
        $this->subscribersService = $subscribersService;
    }

    /**
     * @Route("/subscribers")
     * @QueryParam(name="email", requirements=".+", strict=true, nullable=false, array=true)
     * @QueryParam(name="newsletterSource", strict=true, default=Subscribers::DEFAULT_NEWSLETTER_SOURCE)
     * @Method("GET")
     * @View(serializerGroups={"openApi"})
     */
    public function getSubscribersAction(ParamFetcher $paramFetcher)
    {
        $this->validator->validateAndThrow($paramFetcher);
        $email = $paramFetcher->get('email');
        $newsletterSource = $paramFetcher->get('newsletterSource');

        $newsletterComponent = $this->tokenStorage->getToken()->getUser()->getNewsletterComponent();

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('newsletter_source', $newsletterSource);
        $s->whereEquals('email', $email);
        $rows = $this->model->getRows($s);

        if (!count($rows)) {
            throw new NotFoundHttpException($newsletterComponent->trlKwf('Subscriber not found'));
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
     * @View(serializerGroups={"openApi"})
     */
    public function putSubscriberAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $openApiUser = $this->tokenStorage->getToken()->getUser();
        $newsletterComponent = $openApiUser->getNewsletterComponent();

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('id', $id);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException($newsletterComponent->trlKwf('Subscriber not found'));
        }

        $this->updateRow($row, $paramFetcher);

        $row->setLogSource(
            ($source = $paramFetcher->get('source')) ?
                $source :
                $openApiUser->getNewsletterComponent()->trlKwf('Subscribe Open API. API Key: {0}', array($openApiUser->getUsername())
                ));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());
        $row->writeLog($openApiUser->getNewsletterComponent()->trlKwf('Updated'));

        if ($row->isDirty()) {
            $row->save();
        }

        return $row;
    }

    protected function updateRow(\Kwf_Model_Row_Abstract $row, ParamFetcher $paramFetcher)
    {
        $row->gender = strtolower($paramFetcher->get('gender'));
        $row->title = ($title = $paramFetcher->get('title')) ? $title : '';
        $row->firstname = $paramFetcher->get('firstname');
        $row->lastname = $paramFetcher->get('lastname');
    }

    /**
     * @Route("/subscribers/{id}/unsubscribe", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @Method("PUT")
     */
    public function putUnsubscribeAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $openApiUser = $this->tokenStorage->getToken()->getUser();
        $newsletterComponent = $openApiUser->getNewsletterComponent();

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('id', $id);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException($newsletterComponent->trlKwf('Subscriber not found'));
        }

        // already unsubscribed
        if ($row->unsubscribed) {
            throw new UnprocessableEntityHttpException($newsletterComponent->trlKwf('Subscriber already unsunscribed'));
        }

        // unsubscribe and log
        $row->setLogSource(
            ($source = $paramFetcher->get('source')) ?
                $source :
                $openApiUser->getNewsletterComponent()->trlKwf('Subscribe Open API. API Key: {0}', array($openApiUser->getUsername())
                ));
        $row->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());
        $row->writeLog($openApiUser->getNewsletterComponent()->trlKwf('Unsubscribed'), 'unsubscribed');

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
     * @RequestParam(name="newsletterSource", strict=true, default=Subscribers::DEFAULT_NEWSLETTER_SOURCE)
     */
    public function postSubscribeAction(ParamFetcher $paramFetcher, Request $request)
    {
        $openApiUser = $this->tokenStorage->getToken()->getUser();
        $newsletterComponent = $openApiUser->getNewsletterComponent();
        $doubleOptIn = $paramFetcher->get('doubleOptIn');

        // call service our parameters
        $message = $this->subscribersService->createSubscriberFromRequest(
            $paramFetcher,
            $request,
            $newsletterComponent,
            $newsletterComponent->trlKwf('Subscribe Open API. API Key: {0}', array($openApiUser->getUsername())),
            $doubleOptIn
        );

        // handle categories
        $email = $paramFetcher->get('email');
        $newsletterSource = $paramFetcher->get('newsletterSource');
        $s = new \Kwf_Model_Select();
        $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
        $s->whereEquals('newsletter_source', $newsletterSource);
        $s->whereEquals('email', $email);
        $row = $this->model->getRow($s);

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
                $s->whereEquals('newsletter_component_id', $newsletterComponent->dbId);
                $s->whereEquals('newsletter_source', $newsletterSource);
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

            if ($row->isDirty()) $row->save();
        }

        return array(
            'message' => $message,
            'categories' => $categoriesRet,
        );
    }
}
