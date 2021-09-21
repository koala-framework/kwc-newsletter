<?php
namespace KwcNewsletter\Bundle\Command;

use KwcNewsletter\Bundle\Model\Subscribers;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;

class DeleteNewsletterSubscribers extends Command
{
    /**
     * @var Subscribers
     */
    private $subscribers;

    /**
     * @var string
     */
    protected $newsletterComponentId;

    /**
     * @var string
     */
    protected $newsletterSource;

    public function __construct(Subscribers $subscribers)
    {
        parent::__construct();
        $this->subscribers = $subscribers;
    }

    protected function configure()
    {
        $this
            ->setName('kwc_newsletter:delete-newsletter-subscribers')
            ->setDescription('Delete all subscribers of a newsletter, by newsletter component-id and newsletter source')
            ->addOption(
                'newsletterComponentId',
                'nc',
                InputOption::VALUE_REQUIRED,
                'Newsletter component-id of which the subscribers will be deleted'
            )
            ->addOption(
                'newsletterSource',
                'ns',
                InputOption::VALUE_REQUIRED,
                'Newsletter source of which the subscribers will be deleted'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $helper = $this->getHelper('question');

        $options = array(
            array(
                'name' => 'newsletterComponentId',
                'question' => 'Enter the newsletter component-id, of which the subscribers should be deleted: ',
            ),
            array(
                'name' => 'newsletterSource',
                'question' => 'Enter the newsletter source, of which the subscribers should be deleted: ',
            ),
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
        $helper = $this->getHelper('question');
        $batchSize = 5000;

        $subscriberSelect = new \Kwf_Model_Select();
        $subscriberSelect->whereEquals('newsletter_component_id', $input->getOption('newsletterComponentId'));
        $subscriberSelect->whereEquals('newsletter_source', $input->getOption('newsletterSource'));
        $subscriberSelect->limit($batchSize);

        $subscribersCount = $this->subscribers->countRows($subscriberSelect);

        if (!$subscribersCount) {
            throw new RuntimeException("There are no subscribers with component-id \"{$input->getOption('newsletterComponentId')}\" and newsletter-source \"{$input->getOption('newsletterSource')}\".");
        }

        $confirmDeletionQuestion = new ConfirmationQuestion("Are you sure you want to permanently delete {$subscribersCount} subscribers of {$input->getOption('newsletterComponentId')}/{$input->getOption('newsletterSource')} [y/N] ", false);
        $confirmDeletion = $helper->ask($input, $output, $confirmDeletionQuestion);

        if (!$confirmDeletion) {
            $output->writeln("No subscribers were deleted.", OutputInterface::VERBOSITY_NORMAL);
            exit;
        }

        $amountOfBatches = ceil($subscribersCount / $batchSize);

        $progressBar = new ProgressBar($output, $subscribersCount);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        for ($currentBatch = 1; $currentBatch <= $amountOfBatches; $currentBatch++) {
            if ($currentBatch > 1) {
                \Kwf_Model_Abstract::clearAllRows();
            }

            foreach ($this->subscribers->getRows($subscriberSelect) as $subscriberRow) {
                $subscriberRow->deleteAndHash();
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $output->writeln("Deleted {$subscribersCount} subscribers.", OutputInterface::VERBOSITY_NORMAL);
    }
}
