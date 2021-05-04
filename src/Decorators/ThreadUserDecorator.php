<?php

namespace Railroad\Railforums\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Railforums\Contracts\UserProviderInterface;
use Railroad\Resora\Collections\BaseCollection;
use Railroad\Resora\Decorators\DecoratorInterface;

class ThreadUserDecorator implements DecoratorInterface
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
     * @param BaseCollection $threads
     * @return BaseCollection
     */
    public function decorate($threads)
    {
        $userIds = array_merge(
            $threads->pluck('author_id')
                ->toArray(),
            $threads->pluck('last_post_user_id')
                ->toArray()
        );

        $userIds = array_unique($userIds);

        $users = $this->userProvider->getUsersByIds($userIds);

        foreach ($threads as $threadIndex => $thread) {

            $threads[$threadIndex]['mobile_app_url'] =
                url()->route('railforums.mobile-app.show.thread', [$thread['id']]);

            $threads[$threadIndex]['author_display_name'] =
                (isset($users[$thread['author_id']])) ? $users[$thread['author_id']]->getDisplayName() : '';

            $threads[$threadIndex]['author_avatar_url'] =
                $users[$thread['author_id']]->getProfilePictureUrl() ?? config('railforums.author_default_avatar_url');

            $threads[$threadIndex]['author_access_level'] =
                $this->userProvider->getUserAccessLevel($thread['author_id']);
        }

        return $threads;
    }
}
