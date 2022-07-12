<?php

namespace Truonglv\UserGroupWidget\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $user_id
 * @property string $profile_url
 */
class User extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_ugw_user';
        $structure->primaryKey = 'user_id';
        $structure->shortName = 'Truonglv\UserGroupWidget:User';

        $structure->columns = [
            'user_id' => ['type' => self::UINT, 'required' => true],
            'profile_url' => ['type' => self::STR, 'default' => '', 'maxLength' => 255]
        ];

        return $structure;
    }
}
