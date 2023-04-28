<?php

declare(strict_types=1);

namespace Macintoshplus\Lambdatest\Listener;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseAborted;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\AfterTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Macintoshplus\Lambdatest\Driver\LambdatestWebDriver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SessionStateListener implements EventSubscriberInterface
{
    /**
     * @var Mink
     */
    private $mink;

    /** @var string|null */
    private $javascriptSession;

    /** @var string[] */
    private $javascriptsSessions;

    /** @var array<string> */
    private $originalTags = [];

    /** @var string|null */
    private $originalName = null;

    /**
     * SessionStateListener constructor.
     *
     * @param string|null $javascriptSession
     * @param string[]    $javascriptsSessions
     */
    public function __construct(Mink $mink, $javascriptSession, array $javascriptsSessions = [])
    {
        $this->mink = $mink;
        $this->javascriptSession = $javascriptSession;
        $this->javascriptsSessions = $javascriptsSessions;
    }

    public static function getSubscribedEvents()
    {
        return [
            ExerciseCompleted::AFTER => ['tearDownMinkSessions', 255],
            ScenarioTested::BEFORE => ['beforeScenario', 1024],
            ScenarioTested::AFTER => ['afterScenarioOrOutline', 1024],
            OutlineTested::BEFORE => ['beforeOutline', 1024],
            OutlineTested::AFTER => ['afterScenarioOrOutline', 1024],
        ];
    }

    /**
     * Define the session test state for all lambdatest session.
     *
     * @param AfterExerciseAborted|AfterExerciseCompleted $event
     */
    public function tearDownMinkSessions($event)
    {
        if (
            $event instanceof AfterExerciseCompleted === false &&
            $event instanceof AfterExerciseAborted === false
        ) {
            return;
        }
        $driver = $this->getMinkLambdatestSession();
        if ($driver === null || $driver->getWebDriver() === null || $driver->isStarted() === false) {
            return;
        }
        $driver->executeScript(
            'lambda-status='.(
                ($event instanceof AfterExerciseCompleted && $event->getTestResult()->isPassed()) ? 'passed' : 'failed'
            )
        );
    }

    public function beforeScenario(BeforeScenarioTested $event): void
    {
        $driver = $this->getMinkLambdatestSession();
        if ($driver === null || $driver->isRestartSessionBetweenScenario() === false) {
            return;
        }
        if (empty($this->originalTags)) {
            $this->originalTags = $driver->getDesiredCapabilities()->getCapability('tags');
        }
        if (empty($this->originalName)) {
            $this->originalName = $driver->getDesiredCapabilities()->getCapability('name');
        }

        $driver->getDesiredCapabilities()->setCapability(
            'tags',
            array_unique(array_merge($this->originalTags, $event->getScenario()->getTags()))
        );
        $driver->getDesiredCapabilities()->setCapability(
            'name',
            $this->originalName.' '.$event->getScenario()->getTitle()
        );
        $driver->start();
    }

    /** @param AfterScenarioTested|AfterOutlineTested $event */
    public function afterScenarioOrOutline(AfterTested $event): void
    {
        $driver = $this->getMinkLambdatestSession();
        if ($driver === null || $driver->getWebDriver() === null || $driver->isRestartSessionBetweenScenario() === false) {
            return;
        }
        $driver->executeScript('lambda-status='.($event->getTestResult()->isPassed() ? 'passed' : 'failed'));
        usleep(1200000);
        $driver->stop();
        if ($driver->isStarted()) {
            usleep(2000000);
        }
    }

    public function beforeOutline(BeforeOutlineTested $event): void
    {
        $driver = $this->getMinkLambdatestSession();
        if ($driver === null || $driver->isRestartSessionBetweenScenario() === false) {
            return;
        }
        if (empty($this->originalTags)) {
            $this->originalTags = $driver->getDesiredCapabilities()->getCapability('tags');
        }
        if (empty($this->originalName)) {
            $this->originalName = $driver->getDesiredCapabilities()->getCapability('name');
        }

        $driver->getDesiredCapabilities()->setCapability(
            'tags',
            array_unique(array_merge($this->originalTags, $event->getOutline()->getTags()))
        );
        $driver->getDesiredCapabilities()->setCapability(
            'name',
            $this->originalName.' '.$event->getOutline()->getTitle()
        );
        $driver->start();
    }

    private function getMinkLambdatestSession(): ?LambdatestWebDriver
    {
        foreach ($this->javascriptsSessions as $sessionName) {
            if ($this->mink->hasSession($sessionName) === true) {
                $driver = $this->mink->getSession($sessionName)->getDriver();

                if ($driver instanceof LambdatestWebDriver === false) {
                    continue;
                }

                return $driver;
            }
        }

        return null;
    }
}
