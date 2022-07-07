<?php

namespace Truonglv\UserGroupWidget\XF\Pub\Controller;

use Truonglv\UserGroupWidget\Entity\User;

class Account extends XFCP_Account
{
    public function actionUGWPartners()
    {
        $visitor = \XF::visitor();
        /** @var User|null $ourUser */
        $ourUser = $this->em()->find('Truonglv\UserGroupWidget:User', $visitor->user_id);
        if ($ourUser === null) {
            /** @var User $ourUser */
            $ourUser = $this->em()->create('Truonglv\UserGroupWidget:User');
            $ourUser->user_id = $visitor->user_id;
        }

        if ($this->isPost()) {
            $input = $this->filter([
                'url_type' => 'str',
                'url' => 'str'
            ]);

            if ($input['url_type'] === 'system') {
                $ourUser->profile_url = '';
            } else {
                $ourUser->profile_url = $input['url'];
            }

            $ourUser->save();

            return $this->redirect($this->buildLink('account/ugw-partners'));
        }

        return $this->addAccountWrapperParams(
            $this->view(
                'Truonglv\UserGroupWidget:Account\Partner',
                'ugw_account_partner',
                [
                    'ourUser' => $ourUser,
                ]
            ),
            'ugw-partners'
        );
    }
}
