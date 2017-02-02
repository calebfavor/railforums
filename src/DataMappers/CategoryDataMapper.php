<?php

namespace Railroad\Railforums\DataMappers;

use Railroad\Railforums\Entities\Category;
use Railroad\Railmap\DataMapper\DatabaseDataMapperBase;

class CategoryDataMapper extends DatabaseDataMapperBase
{
    public $table = 'forum_categories';

    public function mapTo()
    {
        return [
            'id' => 'id',
            'title' => 'title',
            'slug' => 'slug',
            'description' => 'description',
            'weight' => 'weight',
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
            'deletedAt' => 'deleted_at',
        ];
    }

    /**
     * @return Category
     */
    public function entity()
    {
        return new Category();
    }
}