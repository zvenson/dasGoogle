<?php declare(strict_types=1);

namespace Sven\DasGoogle;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class SvenDasGoogle extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);
        $connection->executeStatement('DROP TABLE IF EXISTS `sven_das_google_review`');
        $connection->executeStatement(
            "DELETE FROM `migration` WHERE `class` LIKE '%Sven\\\\DasGoogle\\\\Migration%'"
        );
    }
}
