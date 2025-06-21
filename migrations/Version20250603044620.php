<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250603044620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE favorite_song (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                song_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX IDX_DDEBF79EA76ED395 (user_id),
                INDEX IDX_DDEBF79EA0BDB2F3 (song_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_song ADD CONSTRAINT FK_DDEBF79EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_song ADD CONSTRAINT FK_DDEBF79EA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_song DROP FOREIGN KEY FK_DDEBF79EA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favorite_song DROP FOREIGN KEY FK_DDEBF79EA0BDB2F3
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE favorite_song
        SQL);
    }
}
