<?php
namespace KwcNewsletter\Bundle\Importer;

use Exception;
use KwcNewsletter\Bundle\Model\Subscribers;
use KwcNewsletter\Bundle\Model\SubscribersToCategories;
use KwcNewsletter\Bundle\Model\Categories;
use KwcNewsletter\Bundle\Model\Row\Subscribers as RowSubscribers;

class Subscriber
{
    /**
     * @var Subscribers
     */
    private $subscribersModel;
    /**
     * @var SubscribersToCategories
     */
    private $subscribersToCategoriesModel;
    /**
     * @var Categories
     */
    private $categoriesModel;
    /**
     * @var \Kwf_Component_Data
     */
    private $newsletterComponent;
    /**
     * @var integer
     */
    private $categoryId;
    /**
     * @var boolean
     */
    private $ignoreDoubleOptIn = false;
    /**
     * @var string
     */
    private $logSource;
    /**
     * @var boolean
     */
    private $dryRun = false;

    public function __construct(Subscribers $model, $newsletterComponentId, $logSource, $categoryId = null, $ignoreDoubleOptIn = false, $dryRun = false)
    {
        $this->subscribersModel = $model;
        $this->subscribersToCategoriesModel = $this->subscribersModel->getDependentModel('ToCategories');
        $this->categoriesModel = $this->subscribersToCategoriesModel->getReferencedModel('Category');

        $component = \Kwf_Component_Data_Root::getInstance()->getComponentById($newsletterComponentId);
        if (!$component) {
            throw new Exception("Newsletter component with id \"{$newsletterComponentId}\" not found");
        }
        $this->newsletterComponent = $component;
        $this->logSource = $logSource;

        $select = new \Kwf_Model_Select();
        $select->whereId($categoryId);
        $select->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        if (!$this->categoriesModel->countRows($select)) {
            throw new Exception("Category ID \"$categoryId\" for newsletter \"{$this->newsletterComponent->dbId}\" not found");
        }
        $this->categoryId = $categoryId;

        $this->ignoreDoubleOptIn = $ignoreDoubleOptIn;
        $this->dryRun = $dryRun;
    }

    public function save(array $data)
    {
        if (!$this->newsletterComponent) {
            throw new Exception('Newsletter component isn\'t set');
        } else if(!$this->logSource) {
            throw new Exception('Log source isn\'t set');
        }

        $db = $this->subscribersModel->getAdapter();
        $db->beginTransaction();
        try {
            $select = $this->subscribersModel->select();
            $select->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
            $select->whereEquals('email', $data['email']);
            $subscriber = $this->subscribersModel->getRow($select);
            if (!$subscriber) {
                $row = array(
                    'newsletter_component_id' => $this->newsletterComponent->dbId,
                    'email' => $data['email'],
                    'format' => 'html',
                    'unsubscribed' => false,
                    'activated' => $this->ignoreDoubleOptIn
                );
                $subscriber = $this->subscribersModel->createRow($row);
                $this->applyData($subscriber, $data);
                $subscriber->setLogSource($this->logSource);
                if ($this->ignoreDoubleOptIn) {
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Subscribed and activated'), 'activated');
                } else {
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Subscribed'), 'subscribed');
                }
            } else {
                $this->applyData($subscriber, $data);

                if ($this->ignoreDoubleOptIn && !$subscriber->activated && !$subscriber->unsubscribed) {
                    $subscriber->activated = true;
                    $subscriber->setLogSource($this->logSource);
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Activated'), 'activated');
                }
            }

            $subscriber->save();

            if ($this->categoryId) {
                $this->addSubscriberToCategory($subscriber);
            }

            if (!$this->dryRun) {
                if (!$subscriber->activated && !$subscriber->unsubscribed) $this->sendActivationMail($subscriber);

                $db->commit();
            } else {
                $db->rollBack();
            }
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function addSubscriberToCategory(RowSubscribers $subscriber)
    {
        $select = $this->subscribersToCategoriesModel->select();
        $select->whereEquals('subscriber_id', $subscriber->id);
        $select->whereEquals('category_id', $this->categoryId);
        if (!$this->subscribersToCategoriesModel->countRows($select)) {
            $category = $this->subscribersToCategoriesModel->createRow(array(
                'subscriber_id' => $subscriber->id,
                'category_id' => $this->categoryId
            ));
            $subscriber->setLogSource($this->logSource);
            $category->save();
        }
    }

    protected function applyData(RowSubscribers $row, array $data)
    {
        if (array_key_exists('gender', $data)) {
            if (in_array($data['gender'], array('m', 'male'))) {
                $row->gender = 'male';
            } else if (in_array($data['gender'], array('f', 'female'))) {
                $row->gender = 'female';
            }
        }

        $row->title = array_key_exists('title', $data) ? $data['title'] : '';
        $row->firstname = array_key_exists('firstname', $data) ? $data['firstname'] : '';
        $row->lastname = array_key_exists('lastname', $data) ? $data['lastname'] : '';
    }

    protected function sendActivationMail(RowSubscribers $row)
    {
        $subscribe = \Kwf_Component_Data_Root::getInstance()->getComponentByClass('KwcNewsletter_Kwc_Newsletter_Subscribe_Component', array(
            'subroot' => $this->newsletterComponent->getSubroot()
        ));
        $mail = $subscribe->getChildComponent('_mail')->getComponent();
        $mail->send($row, array(
            'formRow' => $row,
            'host' => $this->newsletterComponent->getDomain(),
            'editComponent' => $this->newsletterComponent->getChildComponent('_editSubscriber'),
            'doubleOptInComponent' => $subscribe->getChildComponent('_doubleOptIn')
        ));
    }

}
