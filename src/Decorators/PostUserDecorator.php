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

    public function __construct(DatabaseManager $databaseManager, UserProviderInterface $userProvider)
    {
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

        foreach ($posts as $postIndex => $post) {

            if (!empty($users[$post['author_id']])) {
                $user = $users[$post['author_id']];

                $posts[$postIndex]['author_display_name'] = $user->getDisplayName();
                $posts[$postIndex]['author_avatar_url'] =
                    $user->getProfilePictureUrl() ?? config('railforums.author_default_avatar_url');
                $posts[$postIndex]['author_total_posts'] = $userPosts[$post['author_id']] ?? 0;
                $posts[$postIndex]['author_days_as_member'] =
                    Carbon::parse($user->getCreatedAt())
                        ->diffInDays(Carbon::now());
            }
        }

        return $posts;
    }
}
