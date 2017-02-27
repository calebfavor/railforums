<?php

namespace Railroad\Railforums\Services\PostLikes;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Railroad\Railforums\DataMappers\PostDataMapper;
use Railroad\Railforums\DataMappers\PostLikeDataMapper;
use Railroad\Railforums\DataMappers\UserCloakDataMapper;
use Railroad\Railforums\Entities\PostLike;

class ForumPostLikeService
{
    private $postDataMapper;
    private $postLikeDataMapper;
    private $userCloakDataMapper;

    public function __construct(
        PostDataMapper $postDataMapper,
        PostLikeDataMapper $postLikeDataMapper,
        UserCloakDataMapper $userCloakDataMapper
    ) {
        $this->postDataMapper = $postDataMapper;
        $this->postLikeDataMapper = $postLikeDataMapper;
        $this->userCloakDataMapper = $userCloakDataMapper;
    }

    /**
     * @param $postId
     * @return PostLike
     */
    public function likePost($postId)
    {
        $currentUserId = $this->userCloakDataMapper->getCurrentId();

        $existingPostLike = $this->postLikeDataMapper->ignoreCache()->getWithQuery(
            function (Builder $builder) use ($postId, $currentUserId) {
                return $builder->where('post_id', $postId)
                    ->where('liker_id', $currentUserId);
            }
        )[0] ?? null;

        if (empty($existingPostLike)) {
            $postLike = new PostLike();
            $postLike->setPostId($postId);
            $postLike->setLikerId($currentUserId);
            $postLike->setLikedOn(Carbon::now()->toDateTimeString());
            $postLike->persist();

            $this->postDataMapper->flushCache();

            return $postLike;
        }

        return $existingPostLike;
    }

    public function unLikePost($postId)
    {
        $currentUserId = $this->userCloakDataMapper->getCurrentId();

        $existingPostLikes = $this->postLikeDataMapper->ignoreCache()->getWithQuery(
            function (Builder $builder) use ($postId, $currentUserId) {
                return $builder->where('post_id', $postId)
                    ->where('liker_id', $currentUserId);
            }
        );

        if (!empty($existingPostLikes)) {
            $this->postDataMapper->flushCache();

            foreach ($existingPostLikes as $existingPostLike) {
                $existingPostLike->destroy();
            }
        }
    }
}