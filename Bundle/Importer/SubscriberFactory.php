<?php
namespace KwcNewsletter\Bundle\Importer;

use KwcNewsletter\Bundle\Model\Subscribers;

class SubscriberFactory
{
    /**
     * @var Subscribers
     */
    private $model;
    /**
     * @var string
     */
    private $subscriberClass;

    public function __construct(Subscribers $model, $subscriberClass)
    {
        $this->model = $model;
        $this->subscriberClass = $subscriberClass;
    }

    public function create($newsletterComponentId, $logSource, $categoryId = null, array $options = array())
    {
        return new $this->subscriberClass($this->model, $newsletterComponentId, $logSource, $categoryId, $options);
    }
}
