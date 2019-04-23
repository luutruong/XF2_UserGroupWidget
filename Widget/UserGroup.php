<?php

namespace Truonglv\UserGroupWidget\Widget;

use XF\Widget\AbstractWidget;

class UserGroup extends AbstractWidget
{
    protected $defaultOptions = [
        'user_group_ids' => [],
        'limit' => 5,
        'order' => 'username',
        'direction' => 'desc',
        'ttl' => 60
    ];

    public function render()
    {
        $userGroupIds = $this->options['user_group_ids'];
        if (empty($userGroupIds)) {
            return '';
        }

        $userIds = $this->getDataFromCache();
        if ($userIds === null) {
            $userIds = $this->getUserIdsForCache();
        }

        if (empty($userIds)) {
            return '';
        }

        $finder = $this->finder('XF:User');
        $finder->where('user_id', $userIds);

        $users = $finder->fetch()->sortByList($userIds);

        return $this->renderer('ugw_widget_users', [
            'users' => $users,
            'title' => $this->getTitle()
        ]);
    }

    public function getOptionsTemplate()
    {
        return 'admin:ugw_widget_def_options_user_groups';
    }

    protected function getUserIdsForCache()
    {
        $userGroupIds = $this->options['user_group_ids'];

        $finder = $this->finder('XF:User');
        $userGroupIds = array_map('intval', $userGroupIds);

        $whereOr = [
            ['user_group_id', $userGroupIds]
        ];

        $secondaryGroupsColumn = $finder->columnSqlName('secondary_group_ids');
        foreach ($userGroupIds as $userGroupId) {
            $whereOr[] = $finder->expression('FIND_IN_SET(' . $userGroupId . ',' . $secondaryGroupsColumn . ') > 0');
        }

        $finder->order($this->options['order'], $this->options['direction']);

        $finder->whereOr($whereOr);
        $finder->limit($this->options['limit']);

        $users = $finder->fetchColumns('user_id');

        return array_column($users, 'user_id');
    }

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

    protected function getDataFromCache()
    {
        $cache = $this->app()->cache();
        if ($cache === null) {
            return null;
        }

        $value = $cache->fetch($this->getCacheId());
        if (!$value) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function saveCacheData(array $userIds)
    {
        $cache = $this->app()->cache();
        if ($cache === null) {
            return;
        }

        $cache->save($this->getCacheId(), json_encode($userIds), $this->options['ttl'] * 60);
    }

    protected function getCacheId()
    {
        $encoded = json_encode($this->options);

        return md5(strval($encoded));
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = array_replace($options, $request->filter([
            'user_group_ids' => 'array-uint',
            'order' => 'str',
            'direction' => 'str',
            'limit' => 'uint',
            'ttl' => 'uint'
        ]));

        if (empty($options['limit'])) {
            $options['limit'] = 1;
        }

        return true;
    }
}
