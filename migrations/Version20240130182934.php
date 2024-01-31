<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240130182934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_98197A65B5F1AFE5 ON player USING gin(activities)');
        $this->addSql('CREATE INDEX IDX_98197A6515ED18F2 ON player USING gin(skill_values)');
        $this->addSql('CREATE INDEX IDX_98197A65989E5D34 ON player USING gin(quests)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_98197A65B5F1AFE5');
        $this->addSql('DROP INDEX IDX_98197A6515ED18F2');
        $this->addSql('DROP INDEX IDX_98197A65989E5D34');
    }
}
