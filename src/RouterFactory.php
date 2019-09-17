<?php

/**
 * This file is part of Nepttune (https://www.peldax.com)
 *
 * Copyright (c) 2018 Václav Pelíšek (info@peldax.com)
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <https://www.peldax.com>.
 */

declare(strict_types = 1);

namespace Nepttune;

use Nette\Application\Routers\RouteList,
    Nette\Application\Routers\Route;

class RouterFactory
{
    private static $defaultConfig = [
        'hashids' => false,
        'hashidsSalt' => 'nGXWFaJfxl',
        'hashidsPadding' => 10,
        'hashidsCharset' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        'subdomain' => false,
        'apimodule' => false,
        'modules' => [],
        'defaultModule' => 'Www'
    ];
    
    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = \array_merge(self::$defaultConfig, $config);
    }

    public function createRouter() : RouteList
    {
        return $this->config['subdomain']
            ? static::createSubdomainRouter()
            : static::createStandardRouter();
    }

    protected static function createRouteList() : RouteList
    {
        $router = new RouteList();

        $router[] = new Route('/robots.txt', 'Tool:robots');
        $router[] = new Route('/sitemap.xml', 'Tool:sitemap');
        $router[] = new Route('/worker.js', 'Tool:worker');
        $router[] = new Route('/manifest.json', 'Tool:manifest');
        $router[] = new Route('/browserconfig.xml', 'Tool:browserconfig');
        $router[] = new Route('/security.txt', 'Tool:security');
        $router[] = new Route('/.well-known/security.txt', 'Tool:security');
        $router[] = new Route('/push-subscribe', 'Tool:subscribe');

        return $router;
    }

    protected function createSubdomainRouter() : RouteList
    {
        $router = static::createRouteList();

        $router[] = new Route('//<module>.%domain%/[<locale>/]<presenter>/<action>[/<id>]', [
            'locale' => [Route::PATTERN => '[a-z]{2}'],
            'presenter' => 'Default',
            'action' => 'default',
            'id' => $this->getIdConfig(),
            null => $this->getGlobalConfig()
        ]);
        
        return $router;
    }

    protected function createStandardRouter() : RouteList
    {
        $router = static::createRouteList();

        if ($this->config['apimodule']) {
            $router[] = new Route('/api/<presenter>/<action>[/<id>]', [
                'module' => 'Api',
                'presenter' => 'Default',
                'action' => 'default',
                'id' => $this->getIdConfig(),
                null => $this->getGlobalConfig()
            ]);
        }

        foreach ($this->config['modules'] as $url => $nmspc) {
            $router[] = new Route('/[<locale>/]' . \lcfirst($url) . '/<presenter>/<action>[/<id>]', [
                'locale' => [Route::PATTERN => '[a-z]{2}'],
                'module' => \ucfirst($nmspc),
                'presenter' => 'Default',
                'action' => 'default',
                'id' => $this->getIdConfig(),
                null => $this->getGlobalConfig()
            ]);
        }

        $router[] = new Route('/[<locale>/]<presenter>/<action>[/<id>]', [
            'locale' => [Route::PATTERN => '[a-z]{2}'],
            'module' => \ucfirst($this->config['defaultModule']),
            'presenter' => 'Default',
            'action' => 'default',
            'id' => $this->getIdConfig(),
            null => $this->getGlobalConfig()
        ]);

        return $router;
    }

    protected function getIdConfig() : array
    {
        if ($this->config['hashids']) {
            return [];
        }

        return [Route::PATTERN => '\d+'];
    }

    protected function getGlobalConfig() : array
    {
        if ($this->config['hashids']) {
            return [Route::FILTER_IN => [$this, 'filterIn'], Route::FILTER_OUT => [$this, 'filterOut']];
        }

        return [];
    }

    public function filterIn(array $parameters) : array
    {
        if (\array_key_exists('id', $parameters) && \is_string($parameters['id'])) {
            $hashIds = $this->getHashIds($parameters);
            $decoded = $hashIds->decode($parameters['id']);

            if (\count($decoded) === 0) {
                throw new \Nette\InvalidStateException('Hashids failed to decode ID.');
            }

            $parameters['id'] = (int) \current($decoded);
        }

        return $parameters;
    }

    public function filterOut(array $parameters) : array
    {
        if (\array_key_exists('id', $parameters) && (\is_int($parameters['id']) || \is_numeric($parameters['id']))) {
            $hashIds = $this->getHashIds($parameters);
            $encoded = $hashIds->encode($parameters['id']);

            if (\strlen($encoded) === 0) {
                throw new \Nette\InvalidStateException('Hashids failed to encode ID.');
            }

            $parameters['id'] = $encoded;
        }

        return $parameters;
    }

    private function getHashIds(array $parameters) : \Hashids\Hashids
    {
        $dest = "{$parameters['module']}-{$parameters['presenter']}-{$parameters['action']}";

        return new \Hashids\Hashids(
            $this->config['hashidsSalt'] . $dest,
            $this->config['hashidsPadding'],
            $this->config['hashidsCharset']
        );
    }
}
