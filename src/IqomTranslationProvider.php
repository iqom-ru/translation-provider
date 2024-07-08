<?php

namespace Iqom\TranslationProviderBundle;

use Iqom\IqomClient\IqomClient;
use Iqom\IqomClient\IqomRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Provider\ProviderInterface;
use Symfony\Component\Translation\TranslatorBag;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Yaml\Yaml;

class IqomTranslationProvider implements ProviderInterface
{

    private LoggerInterface $logger;
    private IqomClient $iqomClient;
    private string $projectId;

    public function __construct(
        LoggerInterface $logger,
        IqomClient $iqomClient,
        string $projectId
    ) {
        $this->logger = $logger;
        $this->iqomClient = $iqomClient;
        $this->projectId = $projectId;
    }

    public function __toString(): string
    {
        return 'IqomTranslationProvider';
    }

    public function write(TranslatorBagInterface $translatorBag): void
    {
        $translations = [];
        foreach ($translatorBag->getCatalogues() as $catalogue) {
            $locale = $catalogue->getLocale();
            $translations[$locale] = [];
            foreach ($catalogue->getDomains() as $domain) {
                $translations[$locale][$domain] = $catalogue->all($domain);
            }
        }
        $this->logger->debug('Translation provider :: WRITE :: ' . json_encode($translations));
        $request = IqomRequest::makeTranslationWriteRequest($this->projectId, $translations);
        $response = $this->iqomClient->makeRequest($request);
        if ($response->isSuccess()) {
            $this->logger->info('Translation wrote: OK!');
        } elseif ($response->isError()) {
            $this->logger->info('Translation ERROR: ' . $response->getError());
        }
    }

    public function read(array $domains, array $locales): TranslatorBag
    {
        $this->logger->debug(
            'Translation provider :: READ :: DOMAINS :: ' .
            json_encode($domains) .
            ' :: LOCALES :: ' .
            json_encode($locales)
        );
        $bag = new TranslatorBag();
        $request = IqomRequest::makeTranslationReadRequest($this->projectId, $domains, $locales);
        $response = $this->iqomClient->makeRequest($request);
        if ($response->isSuccess()) {
            $this->logger->info('Translation read: OK!');
            $data = $response->getData();
            foreach ($data as $locale => $domainsData) {
                $catalog = new MessageCatalogue($locale);
                foreach ($domainsData as $domain => $messages) {
                    $catalog->add($messages, $domain);
                }
                $bag->addCatalogue($catalog);
            }
        } elseif ($response->isError()) {
            $this->logger->info('Translation ERROR: ' . $response->getError());
        }
        return $bag;
    }

    public function delete(TranslatorBagInterface $translatorBag): void
    {
        $keys = [];
        foreach ($translatorBag->getCatalogues() as $catalogue) {
            $locale = $catalogue->getLocale();
            $keys[$locale] = [];
            foreach ($catalogue->getDomains() as $domain) {
                $keys[$locale][$domain] = $catalogue->all($domain);
            }
        }
        $request = IqomRequest::makeTranslationDeleteRequest($this->projectId, $keys);
        $response = $this->iqomClient->makeRequest($request);
        if ($response->isSuccess()) {
            $this->logger->info('Translation deleted: OK!');
        } elseif ($response->isError()) {
            $this->logger->info('Translation ERROR: ' . $response->getError());
        }
    }

}