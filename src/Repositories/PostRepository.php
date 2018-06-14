<?php

namespace Railroad\Railforums\Repositories;

use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;
use Railroad\Railforums\Services\ConfigService;
use Railroad\Railforums\Repositories\Traits\SoftDelete;

class PostRepository extends RepositoryBase
{
    const STATE_PUBLISHED = 'published';
    const ACCESSIBLE_STATES = [self::STATE_PUBLISHED];

    use SoftDelete;

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePosts);
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}
