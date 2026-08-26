<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Empêche les assignations identiques pour une tâche et un utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX unique_tache_user ON assignation_tache (tache_id, user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX unique_tache_user ON assignation_tache');
    }
}
