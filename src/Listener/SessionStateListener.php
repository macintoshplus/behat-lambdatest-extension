<?php

declare(strict_types=1);

namespace Macintoshplus\Lambdatest\Listener;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseAborted;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
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
            ScenarioTested::AFTER => ['afterScenario', 1024],
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
        if ($driver === null || $driver->isSplitVideo() === false) {
            return;
        }
        $tags = $driver->getDesiredCapabilities()->getCapability('tags');
        foreach ($event->getScenario()->getTags() as $tag) {
            $tags[] = $tag;
        }
        $tags = array_unique($tags);

        $driver->getDesiredCapabilities()->setCapability('tags', $tags);
        $driver->getDesiredCapabilities()->setCapability('name', 'Behat: '.$event->getScenario()->getTitle());
        $driver->start();
    }

    public function afterScenario(AfterScenarioTested $event): void
    {
        $driver = $this->getMinkLambdatestSession();
        if ($driver === null || $driver->getWebDriver() === null || $driver->isSplitVideo() === false) {
            return;
        }
        $driver->executeScript('lambda-status='.($event->getTestResult()->isPassed() ? 'passed' : 'failed'));
        usleep(1200000);
        $driver->stop();
        if ($driver->isStarted()) {
            usleep(2000000);
        }
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
