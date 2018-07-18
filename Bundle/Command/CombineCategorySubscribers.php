<?php
namespace KwcNewsletter\Bundle\Command;

use KwcNewsletter\Bundle\Model\Newsletters;
use KwcNewsletter\Bundle\Model\SubscribersToCategories;
use KwcNewsletter\Bundle\Model\SubscriberLogs;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Exception\RuntimeException;


class CombineCategorySubscribers extends Command
{
    /**
     * @var Newsletters
     */
    private $newsletters;

    /**
     * @var SubscribersToCategories
     */
    private $subscribersToCategories;

    /**
     * @var SubscriberLogs
     */
    private $subscriberLogs;


    public function __construct(Newsletters $newsletters, SubscribersToCategories $subscribersToCategories, SubscriberLogs $subscriberLogs)
    {
        $this->subscribersToCategories = $subscribersToCategories;
        $this->newsletters = $newsletters;
        $this->subscriberLogs = $subscriberLogs;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kwc_newsletter:combine-category-subscribers')
            ->setDescription('Move subscribers from the source-categories into the target-category (deletes the source-categories)')
            ->addOption(
                'newsletterComponentId',
                'nc',
                InputOption::VALUE_REQUIRED,
                'Newsletter component ID where the subscribers category should be changed'
            )
            ->addOption(
                'sourceCategoryIds',
                'sc',
                InputOption::VALUE_REQUIRED,
                'IDs of the source categories (separate by ",")'
            )
            ->addOption(
                'targetCategoryId',
                'tc',
                InputOption::VALUE_REQUIRED,
                'ID of the target category'
            )
            ->addOption(
                'source',
                'so',
                InputOption::VALUE_REQUIRED,
                'Source for subscriber logs'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $helper = $this->getHelper('question');

        $options = array(
            array(
                'name' => 'newsletterComponentId',
                'question' => 'Please enter the newsletter component ID where the subscribers category should be changed: '
            ),
            array(
                'name' => 'sourceCategoryIds',
                'question' => 'Please enter the IDs of the source categories (separate by ","): '
            ),
            array(
                'name' => 'targetCategoryId',
                'question' => 'Please enter the ID of the target category: '
            ),
            array(
                'name' => 'source',
                'question' => 'Please enter a source for the subscriber logs: '
            )
        );

        foreach ($options as $option) {
            if (!$input->getOption($option['name'])) {
                $question = new Question($option['question']);
                $input->setOption($option['name'], $helper->ask($input, $output, $question));
            }

            if (!$input->getOption($option['name'])) {
                throw new RuntimeException("The option \"{$option['name']}\" must not be empty");
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Kwf_Util_MemoryLimit::set(512);

        $subscribersModel = $this->subscribersToCategories->getReferencedModel('Subscriber');
        $categoriesModel = $this->subscribersToCategories->getReferencedModel('Category');

        $newsletterSelect = new \Kwf_Model_Select();
        $newsletterSelect->whereEquals('component_id', $input->getOption('newsletterComponentId'));

        $component = \Kwf_Component_Data_Root::getInstance()
            ->getComponentById($input->getOption('newsletterComponentId'), array('ignoreVisible' => true));

        if (!$component) {
            throw new RuntimeException("There is no newsletter with the component-id \"{$input->getOption('newsletterComponentId')}\"");
        }

        $this->validateCategory($input->getOption('targetCategoryId'), $input->getOption('newsletterComponentId'));

        $categoryNames = array(
            $input->getOption('targetCategoryId') => $categoriesModel->getRow($input->getOption('targetCategoryId'))->category
        );

        $sourceCategoryIds = array_map('trim', explode(',', $input->getOption('sourceCategoryIds')));
        foreach ($sourceCategoryIds as $categoryId) {
            $this->validateCategory($categoryId, $input->getOption('newsletterComponentId'));
            $categoryNames[$categoryId] = $categoriesModel->getRow($categoryId)->category;
        }

        $categorySelect = new \Kwf_Model_Select();
        $categorySelect->whereEquals('category_id', $sourceCategoryIds);

        $subscriberSelect = new \Kwf_Model_Select();
        $subscriberSelect->whereEquals('newsletter_component_id', $input->getOption('newsletterComponentId'));
        $subscriberSelect->where(
            new \Kwf_Model_Select_Expr_Child_Contains('ToCategories', $categorySelect)
        );

        $subscribersToCategoriesData = array();
        $subscriberLogsData = array();

        $currentDate = date('Y-m-d H:i:s');

        foreach ($subscribersModel->export(
            \Kwf_Model_Abstract::FORMAT_ARRAY, $subscriberSelect, array('columns' => array('id', 'category_ids'))
        ) as $subscriber) {
            $subscribedCategoryIds = explode(',', $subscriber['category_ids']);

            if (!in_array($input->getOption('targetCategoryId'), $subscribedCategoryIds)) {
                $subscribersToCategoriesData[] = array(
                    'subscriber_id' => $subscriber['id'],
                    'category_id' => $input->getOption('targetCategoryId')
                );

                $subscriberLogsData[] = array(
                    'subscriber_id' => $subscriber['id'],
                    'date' => $currentDate,
                    'message' => $component->trlKwf('Added to category {0}', $categoryNames[$input->getOption('targetCategoryId')]),
                    'source' => $input->getOption('source')
                );
            }

            foreach ($sourceCategoryIds as $sourceCategoryId) {
                if (in_array($sourceCategoryId, $subscribedCategoryIds)) {
                    $subscriberLogsData[] = array(
                        'subscriber_id' => $subscriber['id'],
                        'date' => $currentDate,
                        'message' => $component->trlKwf('Removed from category {0}', $categoryNames[$sourceCategoryId]),
                        'source' => $input->getOption('source')
                    );
                }
            }
        }

        \Kwf_Registry::get('db')->beginTransaction();

        $select = new \Kwf_Model_Select();
        $select->whereEquals('category_id', $sourceCategoryIds);

        $this->subscribersToCategories->deleteRows($select);
        $this->subscribersToCategories->import(\Kwf_Model_Abstract::FORMAT_ARRAY, $subscribersToCategoriesData);

        $select = new \Kwf_Model_Select();
        $select->whereEquals('id', $sourceCategoryIds);
        $categoriesModel->deleteRows($select);

        $this->subscriberLogs->import(\Kwf_Model_Abstract::FORMAT_ARRAY, $subscriberLogsData);

        \Kwf_Registry::get('db')->commit();
    }

    protected function validateCategory($id, $componentId)
    {
        $categoriesModel = $this->subscribersToCategories->getReferencedModel('Category');
        $select = new \Kwf_Model_Select();
        $select->whereId($id);
        $select->whereEquals('newsletter_component_id', $componentId);

        if (!$categoriesModel->countRows($select)) {
            throw new RuntimeException("There is no category with id \"$id\" in \"$componentId\"");
        }
    }
}
