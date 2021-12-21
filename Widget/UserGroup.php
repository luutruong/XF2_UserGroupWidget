<?php

namespace Truonglv\UserGroupWidget\Widget;

use XF\Finder\User;
use XF\Widget\AbstractWidget;

class UserGroup extends AbstractWidget
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'user_group_ids' => [],
        'limit' => 5,
        'order' => 'username',
        'direction' => 'desc',
        'cache_ttl' => 300,
        'last_activity_start' => '',
        'last_activity_end' => '',
        'display_avatar' => true,
    ];

    /**
     * @return string
     */
    public function render()
    {
        $userIds = $this->getDataFromCache();
        if ($userIds === null) {
            $userIds = $this->getUserIdsForCache();
            $this->saveCacheData($userIds);
        }

        if (count($userIds) === 0) {
            return '';
        }

        /** @var User $finder */
        $finder = $this->finder('XF:User');
        $finder->where('user_id', $userIds);
        $finder->isValidUser();

        $users = $finder->fetch()->sortByList($userIds);
        if ($users->count() > $this->options['limit']) {
            $users = $users->slice(0, $this->options['limit']);
        }

        return $this->renderer('ugw_widget_users', [
            'users' => $users,
            'title' => $this->getTitle(),
            'options' => $this->options
        ]);
    }

    /**
     * @return string
     */
    public function getOptionsTemplate()
    {
        return 'admin:ugw_widget_def_options_user_groups';
    }

    protected function getUserIdsForCache(): array
    {
        $options = $this->options;
        $finder = $this->finder('XF:User');

        $userGroupIds = $options['user_group_ids'];
        $userGroupIds = array_map('intval', $userGroupIds);
        if (count($userGroupIds) > 0) {
            $whereOr = [
                ['user_group_id', $userGroupIds]
            ];

            $secondaryGroupsColumn = $finder->columnSqlName('secondary_group_ids');
            foreach ($userGroupIds as $userGroupId) {
                $whereOr[] = $finder->expression('FIND_IN_SET(' . $userGroupId . ',' . $secondaryGroupsColumn . ') > 0');
            }

            $finder->whereOr($whereOr);
        }

        if ($options['last_activity_start'] !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $options['last_activity_start']);
            if ($dt !== false) {
                $dt->setTime(0, 0, 0);
                $finder->where('last_activity', '>=', $dt->getTimestamp());
            }
        }
        if ($options['last_activity_end'] !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $options['last_activity_end']);
            if ($dt !== false) {
                $dt->setTime(23, 59, 59);
                $finder->where('last_activity', '<=', $dt->getTimestamp());
            }
        }

        $finder->order($options['order'], $options['direction']);
        $finder->limit($options['limit'] * 2);

        $users = $finder->fetchColumns('user_id');

        return array_column($users, 'user_id');
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getDefaultTemplateParams($context)
    {
        $params = parent::getDefaultTemplateParams($context);

        if ($context === 'options') {
            /** @var \XF\Repository\UserGroup $userGroupRepo */
            $userGroupRepo = $this->app()->repository('XF:UserGroup');
            $params['userGroups'] = $userGroupRepo->getUserGroupOptionsData(false);
        }

        return $params;
    }

    protected function getDataFromCache(): ?array
    {
        $cache = $this->app()->cache();
        if ($cache === null) {
            return null;
        }

        $value = $cache->fetch($this->getCacheId());
        if (!is_array($value)) {
            return null;
        }

        return $value;
    }

    protected function saveCacheData(array $userIds): void
    {
        $cache = $this->app()->cache();
        if ($cache === null
            || $this->options['cache_ttl'] <= 0
        ) {
            return;
        }

        $cache->save($this->getCacheId(), $userIds, $this->options['cache_ttl']);
    }

    protected function getCacheId(): string
    {
        return md5(
            $this->widgetConfig->widgetKey
            . $this->widgetConfig->widgetId
            . serialize($this->options)
        );
    }

    /**
     * @param \XF\Http\Request $request
     * @param array $options
     * @param mixed $error
     * @return bool
     */
    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'user_group_ids' => 'array-uint',
            'order' => 'str',
            'direction' => 'str',
            'limit' => 'uint',
            'cache_ttl' => 'uint',
            'last_activity_start' => 'datetime',
            'last_activity_end' => 'datetime',
            'display_avatar' => 'bool'
        ]);

        if ($options['limit'] < 1) {
            $options['limit'] = 1;
        }

        $visitor = \XF::visitor();
        $language = \XF::app()->language($visitor->language_id);
        if ($options['last_activity_start'] > 0) {
            $options['last_activity_start'] = $language->date($options['last_activity_start'], 'picker');
        } else {
            $options['last_activity_start'] = '';
        }

        if ($options['last_activity_end'] > 0) {
            $options['last_activity_end'] = $language->date($options['last_activity_end'], 'picker');
        } else {
            $options['last_activity_end'] = '';
        }

        return true;
    }
}
