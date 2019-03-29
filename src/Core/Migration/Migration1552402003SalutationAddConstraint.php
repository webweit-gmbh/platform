<?php declare(strict_types=1);

namespace Shopware\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1552402003SalutationAddConstraint extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1552402003;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery('
            ALTER TABLE salutation
            ADD CONSTRAINT `uniq.salutation_key`
              UNIQUE `uniq.salutation_key` (`salutation_key`);
        ');
        $this->updateSalutations($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function updateSalutations(Connection $connection): void
    {
        $divers = Uuid::randomBytes();
        $languageEn = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);
        $languageDe = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM_DE);

        $connection->executeQuery('
            DELETE FROM `salutation`
            WHERE `salutation_key` = :divers;
        ', [':divers' => 'divers']);

        // Inserts for: Divers
        $connection->insert('salutation', [
            'id' => $divers,
            'salutation_key' => 'divers',
            'created_at' => date(Defaults::DATE_FORMAT),
        ]);
        $connection->insert('salutation_translation', [
            'salutation_id' => $divers,
            'language_id' => $languageEn,
            'name' => 'Mx.',
            'created_at' => date(Defaults::DATE_FORMAT),
        ]);
        $connection->insert('salutation_translation', [
            'salutation_id' => $divers,
            'language_id' => $languageDe,
            'name' => 'Divers',
            'created_at' => date(Defaults::DATE_FORMAT),
        ]);
    }
}
