<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209124013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds clan_name and updated_at to known_player table, and sets clan_name based on player table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE known_player ADD clan_name VARCHAR(125) DEFAULT NULL');
        $this->addSql('ALTER TABLE known_player ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN known_player.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE known_player SET updated_at = NOW()');
        $this->addSql('ALTER TABLE known_player ALTER updated_at SET NOT NULL');

        //set clan name based on player table
        $this->addSql("UPDATE known_player kp SET clan_name = NULLIF((SELECT clan FROM player p WHERE p.name = kp.name ORDER BY created_at DESC LIMIT 1), '');");
        $this->addSql('ALTER TABLE player DROP COLUMN clan');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE known_player DROP clan_name');
        $this->addSql('ALTER TABLE known_player DROP updated_at');

        $this->addSql('ALTER TABLE player ADD clan VARCHAR(125) DEFAULT NULL');
    }
}
