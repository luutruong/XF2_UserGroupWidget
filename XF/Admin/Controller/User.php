<?php

namespace Truonglv\UserGroupWidget\XF\Admin\Controller;

class User extends XFCP_User
{
    /**
     * @param \XF\Entity\User $user
     * @return \XF\Mvc\Reply\View
     */
    protected function userAddEdit(\XF\Entity\User $user)
    {
        $response = parent::userAddEdit($user);

        $response->setParam(
            'ugwUser',
            $this->em()->find('Truonglv\UserGroupWidget:User', $user->user_id)
        );

        return $response;
    }

    /**
     * @param \XF\Entity\User $user
     * @return \XF\Mvc\FormAction
     * @throws \XF\Mvc\Reply\Exception
     */
    protected function userSaveProcess(\XF\Entity\User $user)
    {
        $form = parent::userSaveProcess($user);
        $input = $this->filter([
            'ugw' => [
                'url_type' => 'str',
                'url' => 'str'
            ]
        ]);

        $form->complete(function () use ($user, $input) {
            $this->app()->db()->query('
                INSERT IGNORE INTO `xf_ugw_user`
                    (`user_id`, `profile_url`)
                VALUES
                    (?, ?)
                ON DUPLICATE KEY UPDATE
                    `profile_url` = VALUES(`profile_url`)
            ', [
                $user->user_id,
                $input['ugw']['url_type'] === 'system' ? '' : $input['ugw']['url']
            ]);
        });

        return $form;
    }
}
