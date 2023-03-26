<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230318074226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player ALTER activities TYPE jsonb');
        $this->addSql('ALTER TABLE player ALTER skill_values TYPE jsonb');
        $this->addSql('ALTER TABLE player ALTER quests TYPE jsonb');
        $this->addSql('CREATE INDEX IDX_98197A655E237E068B8E8428A5D4CED1 ON player (name, created_at, total_xp)');
        $this->addSql('CREATE INDEX IDX_98197A658B8E8428A5D4CED1 ON player (created_at, total_xp)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_98197A655E237E068B8E8428A5D4CED1');
        $this->addSql('DROP INDEX IDX_98197A658B8E8428A5D4CED1');
        $this->addSql('ALTER TABLE player ALTER activities TYPE jsonb');
        $this->addSql('ALTER TABLE player ALTER skill_values TYPE jsonb');
        $this->addSql('ALTER TABLE player ALTER quests TYPE jsonb');
    }
}
