<?php

namespace Truonglv\UserGroupWidget\Widget;

use XF\Widget\AbstractWidget;

class UserGroup extends AbstractWidget
{
    protected $defaultOptions = [
        'user_group_ids' => []
    ];

    public function render()
    {
        $userGroupIds = $this->options['user_group_ids'];
        if (empty($userGroupIds)) {
            return null;
        }

        $finder = $this->finder('XF:User');
        $whereOr = [
            ['user_group_id', $userGroupIds]
        ];

        foreach ($userGroupIds as $userGroupId) {
            $whereOr[] = $finder->expression('FIND_IN_SET(' . intval($userGroupId) . ', secondary_group_ids) > 0');
        }

        $finder->whereOr($whereOr);
        $users = $finder->fetch();

        return $this->renderer('ugw_widget_users', [
            'users' => $users,
            'title' => $this->getTitle()
        ]);
    }

    public function getOptionsTemplate()
    {
        return 'admin:ugw_widget_def_options_user_groups';
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

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
    {
        $options = $request->filter([
            'user_group_ids' => 'array-uint'
        ]);

        return true;
    }
}
