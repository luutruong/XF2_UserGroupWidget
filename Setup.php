<?php

namespace Truonglv\UserGroupWidget;

use XF\Db\Schema\Create;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use Truonglv\UserGroupWidget\DevHelper\SetupTrait;

class Setup extends AbstractSetup
{
    use SetupTrait;
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function installStep1(): void
    {
        $this->doCreateTables($this->getTables());
    }

    public function uninstallStep1(): void
    {
        $this->doDropTables($this->getTables());
    }

    public function upgrade1000400Step1(): void
    {
        $this->doCreateTables($this->getTables1());
    }

    public function getTables1(): array
    {
        return [
            'xf_ugw_user' => function (Create $table) {
                $table->addColumn('user_id', 'int')->primaryKey();
                $table->addColumn('profile_url', 'varchar', 255);
            },
        ];
    }
}
