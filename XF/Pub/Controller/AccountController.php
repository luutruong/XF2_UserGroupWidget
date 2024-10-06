<?php

namespace Truonglv\UserGroupWidget\XF\Pub\Controller;

use XF;
use Truonglv\UserGroupWidget\Entity\User;

class AccountController extends XFCP_AccountController
{
    public function actionUGWPartners()
    {
        $visitor = XF::visitor();
        if (!$visitor->hasPermission('general', 'ugw_advertiserUrl')) {
            return $this->noPermission();
        }

        $ourUser = $this->em()->find(User::class, $visitor->user_id);
        if ($ourUser === null) {
            /** @var User $ourUser */
            $ourUser = $this->em()->create(User::class);
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
