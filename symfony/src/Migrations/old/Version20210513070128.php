<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210513070128 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_settings DROP FOREIGN KEY FK_5C844C59D86650F');
        $this->addSql('DROP INDEX IDX_5C844C59D86650F ON user_settings');
        $this->addSql('ALTER TABLE user_settings CHANGE user_id_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5C844C5A76ED395 ON user_settings (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_settings DROP FOREIGN KEY FK_5C844C5A76ED395');
        $this->addSql('DROP INDEX IDX_5C844C5A76ED395 ON user_settings');
        $this->addSql('ALTER TABLE user_settings CHANGE user_id user_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C59D86650F FOREIGN KEY (user_id_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_5C844C59D86650F ON user_settings (user_id_id)');
    }
}
