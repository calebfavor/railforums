<?php

namespace Railroad\Railforums\Decorators;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Railroad\Railforums\Contracts\UserProviderInterface;
use Railroad\Railforums\Services\ConfigService;
use Railroad\Resora\Collections\BaseCollection;
use Railroad\Resora\Decorators\DecoratorInterface;

class PostUserDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * PostUserDecorator constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        DatabaseManager $databaseManager,
        UserProviderInterface $userProvider
    ) {
        $this->databaseManager = $databaseManager;
        $this->userProvider = $userProvider;
    }

    /**
     * @param BaseCollection $posts
     * @return BaseCollection
     */
    public function decorate($posts)
    {
        $userIds =
            $posts->pluck('author_id')
                ->toArray();

        $userIds = array_unique($userIds);

        $postsCount =
            $this->databaseManager->connection(config('railforums.database_connection'))
                ->table(ConfigService::$tablePosts)
                ->selectRaw('author_id, COUNT(' . ConfigService::$tablePosts . '.id) as count')
                ->whereIn('author_id', $userIds)
                ->groupBy('author_id')
                ->get()
                ->toArray();

        $userPosts = array_combine(array_column($postsCount, 'author_id'), array_column($postsCount, 'count'));

        $users = $this->userProvider->getUsersByIds($userIds);

        $usersAccessLevel = $this->userProvider->getUsersAccessLevel($userIds);

        $usersXp = $this->userProvider->getUsersXPAndRank($userIds);

        $signatures =
            $this->databaseManager->connection(config('railforums.database_connection'))
                ->table(ConfigService::$tableUserSignatures)
                ->select('user_id', 'signature')
                ->whereIn(ConfigService::$tableUserSignatures . '.user_id', $userIds)
                ->where('brand', config('railforums.brand'))
                ->get()
                ->toArray();

        $userSignatures = array_combine(array_column($signatures, 'user_id'), array_column($signatures, 'signature'));

        $postLikes =
            $this->databaseManager->connection(config('railforums.database_connection'))
                ->table(ConfigService::$tablePostLikes)
                ->selectRaw('COUNT(' . ConfigService::$tablePostLikes . '.id) as count')
                ->addSelect('liker_id')
                ->whereIn(ConfigService::$tablePostLikes . '.liker_id', $userIds)
                ->groupBy(ConfigService::$tablePostLikes . '.liker_id')
                ->get()
                ->toArray();

        $userLikes = array_combine(array_column($postLikes, 'liker_id'), array_column($postLikes, 'count'));

        foreach ($posts as $postIndex => $post) {
            $posts[$postIndex]['published_on_diff'] = Carbon::parse($post['published_on'])
                ->diffforHumans();
            $posts[$postIndex]['is_liked_by_viewer'] =
                isset($post['is_liked_by_viewer']) && $post['is_liked_by_viewer'] == 1;

            if (!empty($users[$post['author_id']])) {
                $user = $users[$post['author_id']];
                $posts[$postIndex]['author']['display_name'] = $user->getDisplayName();
                $posts[$postIndex]['author']['avatar_url'] =
                    $user->getProfilePictureUrl() ?? config('railforums.author_default_avatar_url');
                $posts[$postIndex]['author']['total_posts'] = $userPosts[$post['author_id']] ?? 0;
                $posts[$postIndex]['author']['days_as_member'] =
                    Carbon::parse($user->getCreatedAt())
                        ->diffInDays(Carbon::now());
                $posts[$postIndex]['author']['signature'] = $userSignatures[$post['author_id']] ?? null;
                $posts[$postIndex]['author']['access_level'] = $usersAccessLevel[$post['author_id']] ?? null;
                $posts[$postIndex]['author']['xp'] =
                    (array_key_exists($post['author_id'], $usersXp)) ? $usersXp[$post['author_id']]['xp'] : 0;
                $posts[$postIndex]['author']['xp_rank'] = $usersXp[$post['author_id']]['xp_rank'];
                $posts[$postIndex]['author']['total_post_likes'] = $userLikes[$post['author_id']] ?? 0;
                $posts[$postIndex]['author']['created_at'] =
                    $user->getCreatedAt()
                        ->toDateTimeString();
                $posts[$postIndex]['author']['level_rank'] = $usersXp['level_rank'] ?? '1.0';
            }
        }

        return $posts;
    }
}
