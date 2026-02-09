<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JeroenG\Explorer\Application\DocumentAdapterInterface;
use JeroenG\Explorer\Application\Explored;
use JeroenG\Explorer\Application\IndexAdapterInterface;
use JeroenG\Explorer\Application\Results;
use JeroenG\Explorer\Domain\IndexManagement\DirectIndexConfiguration;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationRepositoryInterface;
use JeroenG\Explorer\Infrastructure\Scout\ElasticEngine;
use JeroenG\Explorer\Infrastructure\Scout\ScoutSearchCommandBuilder;
use Laravel\Scout\Builder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ElasticEngineTest extends MockeryTestCase
{
    public function test_search_prefixes_index_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $documentAdapter->expects('search')
            ->withArgs(function (ScoutSearchCommandBuilder $builder) {
                return $builder->getIndex() === 'staging_posts';
            })
            ->andReturn(new Results(['hits' => ['hits' => [], 'total' => ['value' => 0]]]));

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');

        $model = Mockery::mock(Model::class);
        $model->allows('searchableAs')->andReturn('posts');

        $builder = Mockery::mock(Builder::class);
        $builder->model = $model;
        $builder->query = 'test';
        $builder->wheres = [];
        $builder->whereIns = [];
        $builder->whereNotIns = [];
        $builder->orders = [];
        $builder->limit = null;
        $builder->callback = null;
        $builder->index = null;

        $engine->search($builder);
    }

    public function test_search_does_not_double_prefix(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $documentAdapter->expects('search')
            ->withArgs(function (ScoutSearchCommandBuilder $builder) {
                return $builder->getIndex() === 'staging_posts';
            })
            ->andReturn(new Results(['hits' => ['hits' => [], 'total' => ['value' => 0]]]));

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');

        $model = Mockery::mock(Model::class);
        $model->allows('searchableAs')->andReturn('staging_posts');

        $builder = Mockery::mock(Builder::class);
        $builder->model = $model;
        $builder->query = 'test';
        $builder->wheres = [];
        $builder->whereIns = [];
        $builder->whereNotIns = [];
        $builder->orders = [];
        $builder->limit = null;
        $builder->callback = null;
        $builder->index = null;

        $engine->search($builder);
    }

    public function test_flush_prefixes_index_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $indexAdapter->expects('flush')->with('staging_posts')->once();

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');

        $model = Mockery::mock(Model::class);
        $model->allows('searchableAs')->andReturn('posts');

        $engine->flush($model);
    }

    public function test_create_index_prefixes_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $config = DirectIndexConfiguration::create('staging_posts', [], [], null);

        $repository->expects('findForIndex')
            ->with('staging_posts')
            ->andReturn($config);

        $indexAdapter->expects('create')->with($config)->once();

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');
        $engine->createIndex('posts');
    }

    public function test_delete_index_prefixes_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $config = DirectIndexConfiguration::create('staging_posts', [], [], null);

        $repository->expects('findForIndex')
            ->with('staging_posts')
            ->andReturn($config);

        $indexAdapter->expects('delete')->with($config)->once();

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');
        $engine->deleteIndex('posts');
    }

    public function test_empty_prefix_does_not_alter_index_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $documentAdapter->expects('search')
            ->withArgs(function (ScoutSearchCommandBuilder $builder) {
                return $builder->getIndex() === 'posts';
            })
            ->andReturn(new Results(['hits' => ['hits' => [], 'total' => ['value' => 0]]]));

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository);

        $model = Mockery::mock(Model::class);
        $model->allows('searchableAs')->andReturn('posts');

        $builder = Mockery::mock(Builder::class);
        $builder->model = $model;
        $builder->query = 'test';
        $builder->wheres = [];
        $builder->whereIns = [];
        $builder->whereNotIns = [];
        $builder->orders = [];
        $builder->limit = null;
        $builder->callback = null;
        $builder->index = null;

        $engine->search($builder);
    }

    public function test_paginate_prefixes_index_name(): void
    {
        $indexAdapter = Mockery::mock(IndexAdapterInterface::class);
        $documentAdapter = Mockery::mock(DocumentAdapterInterface::class);
        $repository = Mockery::mock(IndexConfigurationRepositoryInterface::class);

        $documentAdapter->expects('search')
            ->withArgs(function (ScoutSearchCommandBuilder $builder) {
                return $builder->getIndex() === 'staging_posts';
            })
            ->andReturn(new Results(['hits' => ['hits' => [], 'total' => ['value' => 0]]]));

        $engine = new ElasticEngine($indexAdapter, $documentAdapter, $repository, 'staging_');

        $model = Mockery::mock(Model::class);
        $model->allows('searchableAs')->andReturn('posts');

        $builder = Mockery::mock(Builder::class);
        $builder->model = $model;
        $builder->query = 'test';
        $builder->wheres = [];
        $builder->whereIns = [];
        $builder->whereNotIns = [];
        $builder->orders = [];
        $builder->limit = null;
        $builder->callback = null;
        $builder->index = null;

        $engine->paginate($builder, 15, 1);
    }
}
