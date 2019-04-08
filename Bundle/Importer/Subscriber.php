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
    protected $subscribersModel;
    /**
     * @var SubscribersToCategories
     */
    protected $subscribersToCategoriesModel;
    /**
     * @var Categories
     */
    protected $categoriesModel;
    /**
     * @var \Kwf_Component_Data
     */
    protected $newsletterComponent;
    /**
     * @var string
     */
    protected $newsletterSource;
    /**
     * @var integer
     */
    protected $categoryId;
    /**
     * @var string
     */
    protected $logSource;
    /**
     * @var array
     */
    protected $options = array();

    public function __construct(Subscribers $model, $newsletterComponentId, $newsletterSource, $logSource, $categoryId = null, array $options = array())
    {
        $this->subscribersModel = $model;
        $this->subscribersToCategoriesModel = $this->subscribersModel->getDependentModel('ToCategories');
        $this->categoriesModel = $this->subscribersToCategoriesModel->getReferencedModel('Category');

        $component = \Kwf_Component_Data_Root::getInstance()->getComponentById($newsletterComponentId);
        if (!$component) {
            throw new Exception("Newsletter component with id \"{$newsletterComponentId}\" not found");
        }
        $this->newsletterComponent = $component;
        $this->newsletterSource = $newsletterSource;
        $this->logSource = $logSource;

        if (!$this->categoriesModel->countRows($this->getCategorySelect($categoryId))) {
            throw new Exception("Category ID \"$categoryId\" for newsletter \"{$this->newsletterComponent->dbId}\" not found");
        }
        $this->categoryId = $categoryId;

        $this->options = $options;
        if (!array_key_exists('ignoreDoubleOptIn', $options)) $this->options['ignoreDoubleOptIn'] = false;
        if (!array_key_exists('dryRun', $options)) $this->options['dryRun'] = false;
    }

    public function save(array $data)
    {
        if (!$this->newsletterComponent) {
            throw new Exception('Newsletter component isn\'t set');
        } else if (!$this->logSource) {
            throw new Exception('Log source isn\'t set');
        }

        $db = $this->subscribersModel->getAdapter();
        $db->beginTransaction();
        try {
            $subscriber = $this->subscribersModel->getRow($this->getSelect($data));
            if (!$subscriber) {
                $subscriber = $this->subscribersModel->createRow($this->getDefaultData($data));
                $this->applyData($subscriber, $data);
                $subscriber->setLogSource($this->logSource);
                if ($this->options['ignoreDoubleOptIn']) {
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Subscribed and activated'), 'activated');
                } else {
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Subscribed'), 'subscribed');
                }
            } else {
                $this->applyData($subscriber, $data);

                if ($this->options['ignoreDoubleOptIn'] && !$subscriber->activated && !$subscriber->unsubscribed) {
                    $subscriber->activated = true;
                    $subscriber->setLogSource($this->logSource);
                    $subscriber->writeLog($this->newsletterComponent->trlKwf('Activated'), 'activated');
                }
            }

            $subscriber->save();

            if ($this->categoryId) {
                $this->addSubscriberToCategory($subscriber);
            }

            if (!$this->options['dryRun']) {
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

    protected function getCategorySelect($categoryId)
    {
        $select = new \Kwf_Model_Select();
        $select->whereId($categoryId);
        $select->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $select->whereEquals('newsletter_source', $this->newsletterSource);
        return $select;
    }

    protected function getSelect(array $data)
    {
        $select = $this->subscribersModel->select();
        $select->whereEquals('newsletter_component_id', $this->newsletterComponent->dbId);
        $select->whereEquals('newsletter_source', $this->newsletterSource);
        $select->whereEquals('email', $data['email']);
        return $select;
    }

    protected function getDefaultData(array $data)
    {
        return array(
            'newsletter_component_id' => $this->newsletterComponent->dbId,
            'newsletter_source' => $this->newsletterSource,
            'email' => $data['email'],
            'format' => 'html',
            'unsubscribed' => false,
            'activated' => $this->options['ignoreDoubleOptIn']
        );
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
