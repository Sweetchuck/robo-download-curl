<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl\Tests\Unit\Task;

use Codeception\Test\Unit;
use League\Container\Container as LeagueContainer;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\Config\Config;
use Robo\Config\Config as RoboConfig;
use Robo\Robo;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcessHelper;
use Sweetchuck\Robo\DownloadCurl\Tests\Helper\Dummy\DummyTaskBuilder;
use Sweetchuck\Robo\DownloadCurl\Tests\UnitTester;
use Symfony\Component\Console\Application as SymfonyApplication;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;

abstract class TaskTestBase extends Unit
{
    protected ContainerInterface $container;

    protected RoboConfig $config;

    protected CollectionBuilder $builder;

    protected UnitTester $tester;

    /**
     * @var \Sweetchuck\Robo\DownloadCurl\Task\DownloadTask
     */
    protected $task;

    protected DummyTaskBuilder $taskBuilder;

    protected function selfProjectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @inheritdoc
     */
    public function _before()
    {
        parent::_before();

        Robo::unsetContainer();
        DummyProcess::reset();

        $this->container = new LeagueContainer();
        $application = new SymfonyApplication('Sweetchuck - Robo PHPUnit', '1.0.0');
        $application->getHelperSet()->set(new DummyProcessHelper(), 'process');
        $this->config = new Config();
        $input = null;
        $output = new DummyOutput([
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
        ]);

        $this->container->add('container', $this->container);

        Robo::configureContainer($this->container, $application, $this->config, $input, $output);
        $this->container->share('logger', BufferingLogger::class);

        $this->builder = CollectionBuilder::create($this->container, null);
        $this->taskBuilder = new DummyTaskBuilder();
        $this->taskBuilder->setContainer($this->container);
        $this->taskBuilder->setBuilder($this->builder);

        $this->initTask();
    }

    /**
     * @return $this
     */
    abstract protected function initTask();
}
