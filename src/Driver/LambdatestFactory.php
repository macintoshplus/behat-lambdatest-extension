<?php

namespace Macintoshplus\Lambdatest\Driver;

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

        //Example : {"data":{"created":0,"max_concurrency":1,"max_queue":150,"pqueued":0,"queued":0,"running":0},"status":"success"}
        $data = json_decode($result, true);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new LambdatestServiceException('JSON Error on decode Lambdatest response : '.json_last_error().' '.json_last_error_msg());
        }
        if (isset($data['data']) === false || isset($data['data']['max_concurrency']) === false || isset($data['data']['running']) === false) {
            throw new LambdatestServiceException('Concurency response is a valid JSON but does not contrains expected keys');
        }

        if ((int) ($data['data']['max_concurrency']) <= (int) ($data['data']['running'])) {
            throw new TooManyParallelExecutionException(sprintf('Unable to launch anothe parallel automation test. max concurency: %s, current running test: %s', $data['data']['max_concurrency'], $data['data']['running']));
        }

        $def = parent::buildDriver($config);
        $def->setClass(LambdatestWebDriver::class);

        return $def;
    }
}
