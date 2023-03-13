<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230304174431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE player_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
        CREATE TABLE player
        (
            id                 INT                            NOT NULL,
            total_skill        INT                            NOT NULL,
            total_xp           INT                            NOT NULL,
            rank               VARCHAR(255)                   NOT NULL,
            combat_level       INT                            NOT NULL,
            name               VARCHAR(255)                   NOT NULL,
            clan               VARCHAR(255) DEFAULT NULL,
            quests_completed   INT                            NOT NULL,
            quests_started     INT                            NOT NULL,
            quests_not_started INT                            NOT NULL,
            activities         jsonb                          NOT NULL,
            skill_values       jsonb                          NOT NULL,
            quests             jsonb                          NOT NULL,
            created_at         TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at         TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )
    SQL
        );
        $this->addSql('CREATE INDEX IDX_98197A655E237E06 ON player (name)');
        $this->addSql('CREATE INDEX IDX_98197A658B8E8428 ON player (created_at)');
        $this->addSql(<<<SQL
        CREATE TABLE messenger_messages
        (
            id           BIGSERIAL                      NOT NULL,
            body         TEXT                           NOT NULL,
            headers      TEXT                           NOT NULL,
            queue_name   VARCHAR(190)                   NOT NULL,
            created_at   TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id)
        )
    SQL
        );
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql(<<<SQL
        CREATE TRIGGER notify_trigger
            AFTER INSERT OR UPDATE
            ON messenger_messages
            FOR EACH ROW
        EXECUTE PROCEDURE notify_messenger_messages();
    SQL
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE player_id_seq CASCADE');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
