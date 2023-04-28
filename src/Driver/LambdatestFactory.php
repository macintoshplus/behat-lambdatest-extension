<?php

namespace Macintoshplus\Lambdatest\Driver;

use Facebook\WebDriver\Remote\WebDriverBrowserType;
use Macintoshplus\Lambdatest\Exception\LambdatestServiceException;
use Macintoshplus\Lambdatest\Exception\TooManyParallelExecutionException;
use SilverStripe\MinkFacebookWebDriver\FacebookFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use WebDriver\Service\CurlService;

final class LambdatestFactory extends FacebookFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'lambdatest';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
            ->scalarNode('user')->end()
            ->scalarNode('key')->end()
            ->booleanNode('restart_session_between_scenario')->defaultFalse()->end()
            ->end();
        parent::configure($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        $envValues = getenv();
        if ((!isset($config['user']) || !isset($config['key'])) && (!isset($envValues['LT_USERNAME']) || !isset($envValues['LT_USERKEY']))) {
            throw new LambdatestServiceException('Configure environment variable LT_USERNAME and LT_USERKEY with credential from Lambdatest');
        }

        $user = urlencode($envValues['LT_USERNAME'] ?? $config['user'] ?? null);
        $key = urlencode($envValues['LT_USERKEY'] ?? $config['key'] ?? null);

        $config['capabilities']['extra_capabilities']['user'] = $user;
        $config['capabilities']['extra_capabilities']['accessKey'] = $key;

        $curl = new CurlService();
        $url = sprintf('https://%s:%s@api.lambdatest.com/automation/api/v1/org/concurrency', $user, $key);
        list($result, $infos) = $curl->execute('GET', $url);

        // Example : {"data":{"created":0,"max_concurrency":1,"max_queue":150,"pqueued":0,"queued":0,"running":0},"status":"success"}
        $data = json_decode($result, true);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new LambdatestServiceException('JSON Error on decode Lambdatest response : '.json_last_error().' '.json_last_error_msg().' Content: '.$result);
        }
        if (isset($data['data']) === false || isset($data['data']['max_concurrency']) === false || isset($data['data']['running']) === false) {
            throw new LambdatestServiceException('Concurency response is a valid JSON but does not contrains expected keys');
        }

        if ((int) $data['data']['max_concurrency'] <= (int) $data['data']['running']) {
            throw new TooManyParallelExecutionException(sprintf('Unable to launch anothe parallel automation test. max concurency: %s, current running test: %s', $data['data']['max_concurrency'], $data['data']['running']));
        }

        $browser = $config['browser'];
        $extraCapabilities = $config['capabilities']['extra_capabilities'];
        $chromeW3c = isset($extraCapabilities['chromeOptions']) === true && isset($extraCapabilities['chromeOptions']['w3c']) === true ? $extraCapabilities['chromeOptions']['w3c'] : null;

        $def = parent::buildDriver($config);
        $def->setClass(LambdatestWebDriver::class);
        $def->setArgument(3, $config['restart_session_between_scenario']);
        $capabilities = $def->getArgument(1);

        // Remove w3c option is no Chrome browser
        if ($browser !== WebDriverBrowserType::CHROME && isset($capabilities['chromeOptions']) === true && isset($capabilities['chromeOptions']['w3c']) === true) {
            unset($capabilities['chromeOptions']['w3c']);
        }

        // Restore w3c chromeOption value if defined in configuration
        if ($browser === WebDriverBrowserType::CHROME &&
            isset($capabilities['chromeOptions']) === true &&
            isset($capabilities['chromeOptions']['w3c']) === true &&
            $capabilities['chromeOptions']['w3c'] !== $chromeW3c) {
            // Restore the configuration value
            $capabilities['chromeOptions']['w3c'] = $chromeW3c;
            // If no option in behat configuration, remove option
            if ($chromeW3c === null) {
                unset($capabilities['chromeOptions']['w3c']);
            }
        }

        if (\array_key_exists('chromeOptions', $capabilities) && \count($capabilities['chromeOptions']) === 0) {
            unset($capabilities['chromeOptions']);
        }
        // Fix Lambdatest 2023-04-27, `acceptInsecureCerts` must be user if used in `capabilities.firstMatch`
        if (isset($capabilities['acceptSslCerts'])) {
            $capabilities['acceptInsecureCerts'] = $capabilities['acceptSslCerts'];
        }

        $def->setArgument(1, $capabilities);

        return $def;
    }
}
