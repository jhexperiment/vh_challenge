<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210512004213 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_setting DROP FOREIGN KEY FK_C779A6929D86650F');
        $this->addSql('DROP INDEX IDX_C779A6929D86650F ON user_setting');
        $this->addSql('ALTER TABLE user_setting CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_setting ADD CONSTRAINT FK_C779A692A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C779A692A76ED395 ON user_setting (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_setting DROP FOREIGN KEY FK_C779A692A76ED395');
        $this->addSql('DROP INDEX IDX_C779A692A76ED395 ON user_setting');
        $this->addSql('ALTER TABLE user_setting CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_setting ADD CONSTRAINT FK_C779A6929D86650F FOREIGN KEY (user_id_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_C779A6929D86650F ON user_setting (user_id_id)');
    }
}
