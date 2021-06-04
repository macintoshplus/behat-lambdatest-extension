<?php

declare(strict_types=1);

namespace Macintoshplus\Lambdatest\Listener;

use Behat\Mink\Mink;
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
        ];
    }

    /*
     * Define the session test state for all lambdatest session
     */
    public function tearDownMinkSessions(AfterExerciseCompleted $event)
    {
        foreach ($this->javascriptsSessions as $sessionName) {
            if ($this->mink->hasSession($sessionName) === false) {
                continue;
            }
            $driver = $this->mink->getSession($sessionName)->getDriver();
            if ($driver instanceof LambdatestWebDriver) {
                $driver->executeScript('lambda-status='.($event->getTestResult()->isPassed() ? 'passed' : 'failed'));
            }
        }
    }
}
