<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260824172751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assignation_tache (id INT AUTO_INCREMENT NOT NULL, tache_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A84C7AB7D2235D39 (tache_id), INDEX IDX_A84C7AB7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE famille (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, code_invitation INT NOT NULL, UNIQUE INDEX UNIQ_2473F213D9B39C44 (code_invitation), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tache (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, priorite VARCHAR(255) NOT NULL, is_private TINYINT NOT NULL, date_echeance DATE DEFAULT NULL, famille_id INT NOT NULL, createur_id INT NOT NULL, INDEX IDX_9387207597A77B84 (famille_id), INDEX IDX_9387207573A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, api_token VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) NOT NULL, famille_id INT DEFAULT NULL, INDEX IDX_8D93D64997A77B84 (famille_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE assignation_tache ADD CONSTRAINT FK_A84C7AB7D2235D39 FOREIGN KEY (tache_id) REFERENCES tache (id)');
        $this->addSql('ALTER TABLE assignation_tache ADD CONSTRAINT FK_A84C7AB7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207597A77B84 FOREIGN KEY (famille_id) REFERENCES famille (id)');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT FK_9387207573A201E5 FOREIGN KEY (createur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64997A77B84 FOREIGN KEY (famille_id) REFERENCES famille (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignation_tache DROP FOREIGN KEY FK_A84C7AB7D2235D39');
        $this->addSql('ALTER TABLE assignation_tache DROP FOREIGN KEY FK_A84C7AB7A76ED395');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_9387207597A77B84');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY FK_9387207573A201E5');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64997A77B84');
        $this->addSql('DROP TABLE assignation_tache');
        $this->addSql('DROP TABLE famille');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE user');
    }
}
