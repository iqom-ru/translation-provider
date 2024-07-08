<?php

namespace Iqom\TranslationProviderBundle;

use Iqom\IqomClient\IqomClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\Provider\AbstractProviderFactory;
use Symfony\Component\Translation\Provider\Dsn;
use Symfony\Component\Translation\Provider\ProviderInterface;

class IqomTranslationProviderFactory extends AbstractProviderFactory
{

    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    protected function getSupportedSchemes(): array
    {
        return ['iqom'];
        // TODO: Implement getSupportedSchemes() method.
    }

    public function create(Dsn $dsn): ProviderInterface
    {
        $timeout = $dsn->getOption('timeout', null);
        $addr = ($dsn->getHost() == '127.0.0.1' ? 'http' : 'https') . '://' .
            'v1:' . $dsn->getPassword() . '@' . $dsn->getHost() .
            ($dsn->getHost() == '127.0.0.1' ? ':8000' : '') .
            (is_null($timeout) ? '' : '?timeout=' . $timeout);
        $this->logger->debug(
            "Iqom client DSN: " . $addr
        );
        $iqomClient = new IqomClient($addr, $this->logger);
        return new IqomTranslationProvider($this->logger, $iqomClient, $dsn->getUser());
    }

}