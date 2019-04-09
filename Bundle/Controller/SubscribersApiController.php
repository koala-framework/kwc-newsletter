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
use KwcNewsletter\Bundle\Service\Subscribers as SubscribersService;

/**
 * @Route("/api/v1", service="kwc_newsletter.controller.subscribers_api")
 */
class SubscribersApiController extends Controller
{
    /**
     * @var Subscribers
     */
    protected $model;
    /**
     * @var SubscribersService
     */
    protected $subscribersService;
    /**
     * @var boolean
     */
    private $requireCountry;

    public function __construct(Subscribers $model, SubscribersService $subscribersService, $requireCountry)
    {
        $this->model = $model;
        $this->requireCountry = $requireCountry;
        $this->subscribersService = $subscribersService;
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
     * @RequestParam(name="newsletterSource", strict=true, default=Subscribers::DEFAULT_NEWSLETTER_SOURCE)
     */
    public function postAction(ParamFetcher $paramFetcher, Request $request)
    {
        $root = \Kwf_Component_Data_Root::getInstance();
        $subroot = ($country = $this->getCountry($paramFetcher)) ? $root->getComponentById('root-' . strtolower($country)) : $root;
        if (!$subroot) throw new \Kwf_Exception('Subroot not found');

        $newsletterComponent = \Kwf_Component_Data_Root::getInstance()->getComponentByClass(
            'KwcNewsletter_Kwc_Newsletter_Component', array('subroot' => $subroot)
        );

        return new JsonResponse(array(
            'message' => $this->subscribersService->createSubscriberFromRequest(
                $paramFetcher,
                $request,
                $newsletterComponent
            ),
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
}
