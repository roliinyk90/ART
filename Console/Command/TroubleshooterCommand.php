<?php

namespace MagentoSupport\ART\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use MagentoSupport\ART\Model\DbDataSeeker;

/**
 * Class TroubleshooterCommand
 * @package MagentoSupport\ART\Console\Command
 */
class TroubleshooterCommand extends Command
{

    /**
     * Get input data from console
     * @var array
     */
    private $questionData = [];

    /**
     * @var DbDataSeeker
     */
    private $dbDataSeeker;

    /**
     * TroubleshooterCommand constructor.
     * @param DbDataSeeker $dbDataSeeker
     */
    public function __construct(DbDataSeeker $dbDataSeeker)
    {
        $this->dbDataSeeker = $dbDataSeeker;
        parent::__construct();
    }

    /**
     * configure console command
     */
    protected function configure()
    {
        $this->setName('analytics:troubleshoot');
        $this->setDescription('Advanced Reporting Troubleshooter');
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(inputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $this->runQuestionnaire($input,$output);
        $io->progressStart(7);
        $dbData = $this->dbDataSeeker->seekDbData($io);
        $io->progressFinish();
        $this->renderOutput($dbData, $io);
        return 0;
    }

    /**
     * Get info from user
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function runQuestionnaire(inputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $envQuestion = new ChoiceQuestion(
            'Please specify your environment (defaults is magento cloud)',
            ['cloud', 'perm'],
            0
        );
        $envQuestion->setErrorMessage('Environment value %s is invalid.');

        $environmentInfo = $helper->ask($input, $output, $envQuestion);
        $output->writeln('You have just selected: '.$environmentInfo);
        $this->questionData['environmentInfo'] = $environmentInfo;

        if ($environmentInfo == 'cloud') {
            $cloudQuestion = new Question('Please enter the cloud project ID: ');
            $projectId = $helper->ask($input, $output, $cloudQuestion);
            if (!is_string($projectId)) {
                throw new \RuntimeException(
                    'Project id value %s is invalid. String expected'
                );

            }
            $this->questionData['project_id'] = $projectId;
        } elseif ($environmentInfo  == 'perm') {
            $onPermQuestion = new Question('Please provide full path to web-server access logs: ');
            $accessLogPath = $helper->ask($input, $output, $onPermQuestion);
            $this->questionData['access_log_path'] = $accessLogPath;
        }
        else {
            $envQuestion->setErrorMessage('Environment value %s is invalid.');
        }
    }

    /**
     * render output
     * @param $dbData
     * @param $io
     */
    private function renderOutput($dbData,$io) {

        $io->definitionList(
            'Is module Enabled?',
            ['' => $dbData['isModuleEnabled']],
            new TableSeparator(),
            'Analytics Cron Execution time',
            ['' => $dbData['cronExecTime']],
            new TableSeparator(),
            'Search Cron Job in Database:',
            ['' => $dbData['analytic_cron_job']],
            new TableSeparator(),
            'Checking  Analytic token:',
            ['' => $dbData['isTokenPresent']],
            new TableSeparator(),
            'Flag table:',
            ['' => $dbData['flagTable']],
            new TableSeparator(),
            'Check escaped quotes and slashes in order_item table:',
            ['' => $dbData['escapedQuotes']],
            new TableSeparator(),
            'Check multi currency:',
            ['' => $dbData['isMultiCurrency']]
        );
    }
}
