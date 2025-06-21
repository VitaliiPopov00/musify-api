<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250615221136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE release_song (
                id INT AUTO_INCREMENT NOT NULL,
                release_id INT NOT NULL,
                song_id INT NOT NULL,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX IDX_7E1A5EB6B12A727D (release_id),
                INDEX IDX_7E1A5EB6A0BDB2F3 (song_id),
                INDEX IDX_7E1A5EB6B03A8386 (created_by),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song ADD CONSTRAINT FK_7E1A5EB6B12A727D FOREIGN KEY (release_id) REFERENCES releases (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song ADD CONSTRAINT FK_7E1A5EB6A0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song ADD CONSTRAINT FK_7E1A5EB6B03A8386 FOREIGN KEY (created_by) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song DROP FOREIGN KEY FK_7E1A5EB6B12A727D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song DROP FOREIGN KEY FK_7E1A5EB6A0BDB2F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE release_song DROP FOREIGN KEY FK_7E1A5EB6B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE release_song
        SQL);
    }
}
