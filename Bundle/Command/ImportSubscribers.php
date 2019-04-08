<?php
namespace KwcNewsletter\Bundle\Command;

use KwcNewsletter\Bundle\Importer\Parser\Csv as CsvParser;
use KwcNewsletter\Bundle\Importer\SubscriberFactory;
use KwcNewsletter\Bundle\Importer\Excluder\Manager as ExcluderManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Exception\RuntimeException;


class ImportSubscribers extends Command
{
    /**
     * @var CsvParser
     */
    protected $parser;
    /**
     * @var SubscriberFactory
     */
    protected $subscriberFactory;
    /**
     * @var ExcluderManager
     */
    protected $excluderManager;

    /**
     * @var string
     */
    private $file;
    /**
     * @var string
     */
    protected $newsletterComponentId;
    /**
     * @var string
     */
    protected $newsletterSource;
    /**
     * @var string
     */
    protected $logSource;
    /**
     * @var integer
     */
    protected $categoryId;
    /**
     * @var boolean
     */
    protected $dryRun = false;
    /**
     * @var ProgressBar
     */
    private $progressBar;
    /**
     * @var boolean
     */
    protected $ignoreDoubleOptIn = false;

    public function __construct(CsvParser $parser, SubscriberFactory $subscriberFactory, ExcluderManager $excluderManager)
    {
        $this->parser = $parser;
        $this->subscriberFactory = $subscriberFactory;
        $this->excluderManager = $excluderManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kwc_newsletter:import-subscribers')
            ->setDescription('Import an CSV file of subscribers')
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Path to file you want to import'
            )
            ->addOption(
                'newsletterComponentId',
                'nc',
                InputOption::VALUE_OPTIONAL,
                'Newsletter component ID where subscribers should be imported'
            )
            ->addOption(
                'newsletterSource',
                'ns',
                InputOption::VALUE_OPTIONAL,
                'Newsletter source where subscribers should be imported'
            )
            ->addOption(
                'source',
                'so',
                InputOption::VALUE_OPTIONAL,
                'Source where file comes from, is shown in log'
            )
            ->addOption(
                'categoryId',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Category ID which subscribers should be added'
            )
            ->addOption(
                'ignoreDoubleOptIn',
                'i',
                InputOption::VALUE_NONE,
                'Import subscribers ignoring double opt in'
            )
            ->addOption(
                'dryRun',
                'dr',
                InputOption::VALUE_NONE,
                'Executes a dry run'
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);

        $this->file = $input->getOption('file');
        $this->newsletterComponentId = $input->getOption('newsletterComponentId');
        $this->newsletterSource = $input->getOption('newsletterSource');
        $this->categoryId = $input->getOption('categoryId');
        $this->logSource = $input->getOption('source');
        $this->ignoreDoubleOptIn = $input->getOption('ignoreDoubleOptIn');
        $this->dryRun = $input->getOption('dryRun');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startQuestions($input, $output);

        $this->parser->setFile($this->file);
        $header = $this->parser->getHeader();
        if (!in_array('email', $header)) {
            throw new RuntimeException("First line of CSV must include \"email\" column");
        }

        $subscriber = $this->createSubscriberServiceFromFactory();
        $this->progressBar = new ProgressBar($output, $this->parser->count());
        $this->progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $this->progressBar->start();

        foreach ($this->parser as $data) {
            $this->progressBar->advance();

            if ($this->excluderManager->isExcluded($data['email'])) continue;
            $subscriber->save($data);
        }

        $this->progressBar->finish();
    }

    protected function createSubscriberServiceFromFactory()
    {
        return $this->subscriberFactory->create(
            $this->newsletterComponentId,
            $this->newsletterSource,
            $this->logSource,
            $this->categoryId,
            array(
                'ignoreDoubleOptIn' => $this->ignoreDoubleOptIn,
                'dryRun' => $this->dryRun,
            )
        );
    }

    protected function startQuestions(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        if (!$input->getOption('file')) {
            $question = new Question('Please enter the path to the csv file: ');
            $this->file = $helper->ask($input, $output, $question);
        }

        if (!$input->getOption('newsletterComponentId')) {
            $question = new Question('Please enter the newsletter component id where subscribers should be imported: ');
            $this->newsletterComponentId = $helper->ask($input, $output, $question);
        }

        if (!$input->getOption('newsletterSource')) {
            $question = new Question('Please enter the newsletter source where subscribers should be imported: ');
            $this->newsletterSource = $helper->ask($input, $output, $question);
        }

        if (!$input->getOption('categoryId')) {
            $question = new Question('Please enter the category ID where subscribers should be imported: ');
            $this->categoryId = $helper->ask($input, $output, $question);
        }

        if (!$input->getOption('source')) {
            $question = new Question('Please enter the source where file comes from: ');
            $this->logSource = $helper->ask($input, $output, $question);
        }

        if (!$input->getOption('dryRun')) {
            $question = new ConfirmationQuestion('Do you want a dry-run? [y/N] ', false);
            $this->dryRun = $helper->ask($input, $output, $question);
        }
    }
}
