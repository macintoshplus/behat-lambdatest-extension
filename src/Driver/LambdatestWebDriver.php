<?php

declare(strict_types=1);

namespace Macintoshplus\Lambdatest\Driver;

use Behat\Mink\Exception\DriverException;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/*
 * Override the WebDriver to add quit call on session stop.
 * Use the LambdatestWebDriver allow to set the test result on lambdatest.
 */
final class LambdatestWebDriver extends \SilverStripe\MinkFacebookWebDriver\FacebookWebDriver
{
    protected $webDriver;

    private $started = false;

    public function setWebDriver(RemoteWebDriver $webDriver)
    {
        $this->webDriver = $webDriver;

        return parent::setWebDriver($webDriver);
    }

    public function stop()
    {
        if ($this->isStarted() === false) {
            return;
        }

        parent::stop();

        try {
            $this->webDriver->quit();
        } catch (Exception $e) {
            throw new DriverException('Could not quit connection', 0, $e);
        }
    }
}
