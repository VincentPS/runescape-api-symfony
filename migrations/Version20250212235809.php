<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212235809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds last_used_at to known_player table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE known_player ADD last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN known_player.last_used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE known_player SET last_used_at = NOW()');
        $this->addSql('ALTER TABLE known_player ALTER last_used_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE known_player DROP last_used_at');
    }
}
