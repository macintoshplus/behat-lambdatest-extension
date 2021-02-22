<?php

namespace Macintoshplus\Lambdatest\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\Selenium2Factory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class LambdatestFactory extends Selenium2Factory
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
            throw new \Exception('Configure environment variable LT_USERNAME and LT_USERKEY with credential from Lambdatest');
        }

        $user = urlencode($envValues['LT_USERNAME'] ?? $config['user'] ?? null);
        $key = urlencode($envValues['LT_USERKEY'] ?? $config['key'] ?? null);

        $wd_host = $config['wd_host'];

        $infos = parse_url($wd_host);

        $wd_host = sprintf(
            '%s://%s:%s@%s%s%s%s%s',
            $infos['scheme'],
            $user,
            $key,
            $infos['host'],
            isset($infos['port']) ? ':'.$infos['port'] : '',
            $infos['path'] ?? '',
            isset($infos['query']) ? '?'.$infos['query'] : '',
            isset($infos['fragment']) ? '#'.$infos['fragment'] : ''
        );

        $config['wd_host'] = $wd_host;

        return parent::buildDriver($config);
    }
}
