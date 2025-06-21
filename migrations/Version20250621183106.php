<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250621183106 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE listening_history (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                song_id INT NOT NULL,
                listened_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                INDEX IDX_17102927A76ED395 (user_id),
                INDEX IDX_17102927A0BDB2F3 (song_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE listening_history ADD CONSTRAINT FK_17102927A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE listening_history ADD CONSTRAINT FK_17102927A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE listening_history DROP FOREIGN KEY FK_17102927A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE listening_history DROP FOREIGN KEY FK_17102927A0BDB2F3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE listening_history
        SQL);
    }
}
