<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Infrastructure\IndexManagement;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JeroenG\Explorer\Application\Aliased;
use JeroenG\Explorer\Application\Explored;
use JeroenG\Explorer\Application\IndexSettings;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationBuilder;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationInterface;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationNotFoundException;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationRepositoryInterface;
use RuntimeException;

class ElasticIndexConfigurationRepository implements IndexConfigurationRepositoryInterface
{
    private array $indexConfigurations;

    private bool $pruneOldAliases;

    private array $defaultSettings;

    private string $prefix;

    public function __construct(array $indexConfigurations, bool $pruneOldAliases = true, array $defaultSettings = [], string $prefix = '')
    {
        $this->indexConfigurations = $indexConfigurations;
        $this->pruneOldAliases = $pruneOldAliases;
        $this->defaultSettings = $defaultSettings;
        $this->prefix = $prefix;
    }

    private function prefixName(string $name): string
    {
        if ($this->prefix === '' || Str::startsWith($name, $this->prefix)) {
            return $name;
        }

        return $this->prefix . $name;
    }

    /**
     * @return iterable<IndexConfigurationInterface>
     */
    public function getConfigurations(): iterable
    {
        foreach ($this->indexConfigurations as $key => $index) {
            if (is_string($index)) {
                yield $this->getIndexConfigurationByClass($index);
            } elseif (is_string($key) && is_array($index)) {
                yield $this->getIndexConfigurationByArray($key, $index);
            } else {
                $data = var_export($index, true);
                throw new RuntimeException(sprintf('Unable to create index for "%s"', $data));
            }
        }
    }

    public function findForIndex(string $index): IndexConfigurationInterface
    {
        $prefixedIndex = $this->prefixName($index);

        foreach ($this->getConfigurations() as $indexConfiguration) {
            if ($indexConfiguration->getName() === $prefixedIndex) {
                return $indexConfiguration;
            }
        }

        throw IndexConfigurationNotFoundException::index($prefixedIndex);
    }

    private function getIndexConfigurationByClass(string $index): IndexConfigurationInterface
    {
        $class = (new $index());

        if (!$class instanceof Explored) {
            throw new RuntimeException(sprintf('Unable to create index %s, ensure it implements Explored', $index));
        }

        $properties = $class->mappableAs();
         
        $hasAnalyzer = collect(array_keys(Arr::dot($properties)))->contains(function ($key) {
             return Str::endsWith($key, 'analyzer');
         });

        if($hasAnalyzer && !$class instanceof IndexSettings) {
            throw new RuntimeException(sprintf('Unable to create index %s, ensure it implements IndexSettings as an analyzer is defined', $index) );
        }

        $settings = $class instanceof IndexSettings ? $class->indexSettings() : $this->defaultSettings;
        
        $builder = IndexConfigurationBuilder::named($this->prefixName($class->searchableAs()))
            ->withModel(get_class($class))
            ->withProperties($properties)
            ->withSettings($settings);

        if ($class instanceof Aliased) {
            $builder = $builder->asAliased($this->pruneOldAliases);
        }

        return $builder->buildIndexConfiguration();
    }

    private function getIndexConfigurationByArray(string $name, array $index): IndexConfigurationInterface
    {
        $useAlias = $index['aliased'] ?? false;

        $builder = IndexConfigurationBuilder::named($this->prefixName($name))
            ->withProperties($index['properties'] ?? [])
            ->withSettings($index['settings'] ?? $this->defaultSettings)
            ->withModel($index['model'] ?? null);

        if ($useAlias) {
            $builder = $builder->asAliased($this->pruneOldAliases);
        }

        return $builder->buildIndexConfiguration();
    }
}
