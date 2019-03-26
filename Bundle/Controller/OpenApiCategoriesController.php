<?php

namespace KwcNewsletter\Bundle\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcher;
use KwcNewsletter\Bundle\Model\Categories;
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
 * @Route("/api/v1/open", service="kwc_newsletter.controller.open_api_categories")
 */
class OpenApiCategoriesController extends Controller
{
    /* @var \Kwf_Component_Data */
    protected $newsletterComponent;
    /* @var ErrorCollectValidator */
    protected $validator;
    /* @var ApiUser */
    protected $user;
    /**
     * @var Categories
     */
    private $model;

    public function __construct(Categories $model, ErrorCollectValidator $validator, TokenStorage $tokenStorage)
    {
        $this->model = $model;
        $this->validator = $validator;
        $this->user = $tokenStorage->getToken() && $tokenStorage->getToken()->getUser() != 'anon.' ?
            $tokenStorage->getToken()->getUser() :
            null;
        $this->newsletterComponent = $this->user->getNewsletterComponent();
    }

    /**
     * @Route("/categories")
     * @Method("GET")
     * @View(serializerGroups={"user"})
     */
    public function getCategoriesAction(ParamFetcher $paramFetcher)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);

        return $this->model->getRows($s);
    }

    /**
     * @Route("/categories")
     * @RequestParam(name="category", requirements=".+", strict=true, nullable=false)
     * @Method("POST")
     * @View(serializerGroups={"user"}, statusCode=201)
     */
    public function postCategoryAction(ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $row = $this->model->createRow(array(
            'newsletter_component_id' => $this->newsletterComponent->dbId,
        ));
        $this->_updateRow($row, $paramFetcher);
        $row->save();

        return $row;
    }

    protected function _updateRow(\Kwf_Model_Row_Abstract $row, ParamFetcher $paramFetcher)
    {
        $row->category = $paramFetcher->get('category');
    }

    /**
     * @Route("/categories/{id}", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="category", requirements=".+", strict=true, nullable=false)
     * @Method("PUT")
     * @View(serializerGroups={"user"})
     */
    public function putCategoryAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('id', $id);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Category not found');
        }

        $this->_updateRow($row, $paramFetcher);

        if ($row->isDirty()) {
            $row->save();
        }

        return null;
    }

    /**
     * @Route("/categories/{id}/subscribers", requirements={"id"="[1-9]{1}\d*"})
     * @Method("GET")
     * @View(serializerGroups={"user"})
     */
    public function getCategorySubscribersAction($id, ParamFetcher $paramFetcher)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('id', $id);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Category not found');
        }

        // find subscribers
        $subscribersToCategoryModel = $this->model->getDependentModel('ToSubscribers');
        $subscribersModel = $subscribersToCategoryModel->getReferencedModel('Subscriber');
        $s = $subscribersModel->select();
        $s->whereEquals('id', explode(',', $row->subscriber_ids));

        return $subscribersModel->getRows($s);
    }

    /**
     * @Route("/categories/{id}/subscribers", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="subscribers", requirements="[1-9]{1}\d*", strict=true, nullable=false, array=true)
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @Method("POST")
     * @View(serializerGroups={"user"})
     */
    public function postCategorySubscribersAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('id', $id);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Category not found');
        }

        // new subscribers from request
        $subscribers = $paramFetcher->get('subscribers');

        // statistics
        $ret = array(
            'total' => count($subscribers),
            'added' => 0,
            'not_found' => 0,
            'exists' => 0,
        );

        // nothing to do
        if (!$ret['total']) {
            return $ret;
        }

        // getting required models
        $subscribersToCategoryModel = $this->model->getDependentModel('ToSubscribers');
        $subscribersModel = $subscribersToCategoryModel->getReferencedModel('Subscriber');

        // find subscribers in the database
        $s = $subscribersModel->select();
        $s->whereEquals('id', $subscribers);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $subscriberRows = $subscribersModel->getRows($s);

        // subscribers in the category
        $categorySubscribers = explode(',', $row->subscriber_ids);

        // update not found
        $ret['not_found'] = $ret['total'] - count($subscriberRows);

        foreach ($subscriberRows as $subscriber) {

            // not in the category
            if (!in_array($subscriber->id, $categorySubscribers)) {
                $category = $subscribersToCategoryModel->createRow(array(
                    'subscriber_id' => $subscriber->id,
                    'category_id' => $row->id,
                ));
                $subscriber->setLogSource(
                    ($source = $paramFetcher->get('source')) ?
                        $source :
                        trlKwf('Subscribe Open API. API Key: {0}', array($this->getUser()->getUsername())
                        ));
                $subscriber->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());

                $category->save();
                $ret['added']++;

            } else {
                $ret['exists']++;
            }
        }

        return $ret;
    }

    public function getUser()
    {
        if (!$this->user) {
            throw new AccessDeniedHttpException('User not logged in');
        }
        return $this->user;
    }

    /**
     * @Route("/categories/{id}/subscribers", requirements={"id"="[1-9]{1}\d*"})
     * @RequestParam(name="subscribers", requirements="[1-9]{1}\d*", strict=true, nullable=false, array=true)
     * @RequestParam(name="source", strict=true, nullable=true)
     * @RequestParam(name="ip", requirements=@Ip, strict=true, nullable=true)
     * @Method("DELETE")
     * @View(serializerGroups={"user"})
     */
    public function deleteCategorySubscribersAction($id, ParamFetcher $paramFetcher, Request $request)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('id', $id);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Category not found');
        }

        // subscribers to delete from request
        $subscribers = $paramFetcher->get('subscribers');

        // statistics
        $ret = array(
            'total' => count($subscribers),
            'deleted' => 0,
            'not_exists' => 0,
        );

        // nothing to do
        if (!$ret['total']) {
            return $ret;
        }

        // getting required models
        $subscribersToCategoryModel = $this->model->getDependentModel('ToSubscribers');
        $subscribersModel = $subscribersToCategoryModel->getReferencedModel('Subscriber');

        // find subscribers because of log source
        $s = $subscribersModel->select();
        $s->whereEquals('id', $subscribers);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $subscriberRows = $subscribersModel->getRows($s);
        foreach ($subscriberRows as $subscriber) {
            $subscriber->setLogSource(
                ($source = $paramFetcher->get('source')) ?
                    $source :
                    trlKwf('Subscribe Open API. API Key: {0}', array($this->getUser()->getUsername())
                    ));
            $subscriber->setLogIp(($ip = $paramFetcher->get('ip')) ? $ip : $request->getClientIp());
        }

        // find subscribers in category
        $select = $subscribersToCategoryModel->select();
        $select->whereEquals('subscriber_id', $subscribers);
        $select->whereEquals('category_id', $row->id);
        $subscribersToCategoryRows = $subscribersToCategoryModel->getRows($select);

        // update not exists counter
        $ret['not_exists'] = $ret['total'] - count($subscribersToCategoryRows);

        foreach ($subscribersToCategoryRows as $subscribersToCategoryRow) {
            $subscribersToCategoryRow->delete();
            $ret['deleted']++;
        }

        return $ret;
    }

    /**
     * @Route("/categories/{id}", requirements={"id"="[1-9]{1}\d*"})
     * @Method("GET")
     * @View(serializerGroups={"user"})
     */
    public function getCategoryAction($id, ParamFetcher $paramFetcher)
    {
        $this->validator->validateAndThrow($paramFetcher);

        $s = $this->model->select();
        $s->whereEquals('id', $id);
        $s->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $row = $this->model->getRow($s);

        if (!$row) {
            throw new NotFoundHttpException('Category not found');
        }

        return $row;
    }
}
