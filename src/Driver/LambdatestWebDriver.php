<?php

declare(strict_types=1);

namespace Macintoshplus\Lambdatest\Driver;

use Behat\Mink\Exception\DriverException;
use Facebook\WebDriver\Exception\InvalidSessionIdException;
use Facebook\WebDriver\Exception\UnrecognizedExceptionException;
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

    public static function getDefaultCapabilities()
    {
        return [
            'browserName' => self::DEFAULT_BROWSER,
            'platform' => 'ANY',
            'browser' => self::DEFAULT_BROWSER,
            'name' => 'Behat Test',
        ];
    }

    public function stop()
    {
        if ($this->isStarted() === false) {
            return;
        }

        parent::stop();

        // Try to quit in the case of session status has not set and stop is needed.
        try {
            $this->webDriver->quit();
        } catch (InvalidSessionIdException $e) {
            //Silent error because the session has a status and close is sufficent.
        } catch (UnrecognizedExceptionException $e) {
            //Manage the Lambdatest 'session-not-found' error:
            //Silent error (only if contains 'Unable to find the session') because the session has a status and close is sufficent.
            if (strpos($e->getMessage(), 'Unable to find the session') === false) {
                throw new DriverException('Unknow exception from sub driver', 400, $e);
            }
        } catch (\Exception $e) {
            throw new DriverException('Could not quit connection', 400, $e);
        }
    }

    public function isVisible($xpath)
    {
        // Fix to mitigate the unsupported `displayed` command https://w3c.github.io/webdriver/#element-displayedness
        if (strtolower($this->getDesiredCapabilities()->getBrowserName()) === 'safari') {
            return $this->evaluateScript("(function (){ let x =  document.evaluate(\"" . $xpath . "\", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; if(x === null) {return false;} return window.getComputedStyle(x).display !== 'none';})();");
        }
        return parent::isVisible($xpath);
    }
}
