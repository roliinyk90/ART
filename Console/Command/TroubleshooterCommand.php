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
use Magento\Framework\HTTP\Client\Curl;

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
     * @var CurlClient
     */
    private $curlClient;

    /**
     * @var string
     */
    protected $apiUrl = 'https://web74.us-west-2.prd.sparta.ceng.magento.com/roliinyk/api-bridge/public/mbilogs/';

    /**
     * @var string
     */
    protected $urlPrefix = 'https://';

    /**
     * TroubleshooterCommand constructor.
     * @param DbDataSeeker $dbDataSeeker
     * @param Curl $curl
     */
    public function __construct(DbDataSeeker $dbDataSeeker, Curl $curl)
    {
        $this->dbDataSeeker = $dbDataSeeker;
        $this->curlClient = $curl;
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
        $io->progressStart(2);
        $dbData = $this->dbDataSeeker->seekDbData();
        $io->progressAdvance(1);
        $logData = null;
        if (isset($this->questionData['project_id'])) {
            $projectId = $this->questionData['project_id'];
            try {
                if (strpos($_SERVER['SERVER_NAME'], 'sparta.ceng.magento.com') !== false) {
                    $this->urlPrefix = 'http://';
                }
                $this->curlClient->get($this->urlPrefix.$this->apiUrl.$projectId);
                $logData = $this->curlClient->getBody();
            }
            catch (\Exception $e) {
                throw new \RuntimeException(
                    $e->getMessage()
                );
            }
        }
        $io->progressAdvance(2);
        $io->progressFinish();
        $this->renderOutput($logData, $dbData, $output);
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
     * @param $output
     */
    private function renderOutput($logData, $dbData,$output) {

        $output->writeln("Is module Enabled?: ". $dbData['isModuleEnabled']);
        $output->writeln("Analytics Cron Execution time: ". $dbData['cronExecTime']);
        $output->writeln("Checking  Analytic token: ". $dbData['isTokenPresent']);
        $output->writeln("Search Cron Job in Database: ". $dbData['analytic_cron_job']);
        $output->writeln("Flag table:". $dbData['flagTable']);
        $output->writeln("Check escaped quotes and slashes in order_item table:". $dbData['escapedQuotes']);
        $output->writeln("Check multi currency:". $dbData['isMultiCurrency']);
        $output->writeln("Data FROM NR access Logs -----------------------------------------------------------");
        $output->writeln(print_r($logData));

    }
}
