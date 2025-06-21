<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601140517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create song table and related tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE song (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                play_count INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE singer_song (
                singer_id INT NOT NULL,
                song_id INT NOT NULL,
                INDEX IDX_58B4FF1F271FD47C (singer_id),
                INDEX IDX_58B4FF1FA0BDB2F3 (song_id),
                PRIMARY KEY(singer_id, song_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE song_genre (
                song_id INT NOT NULL,
                genre_id INT NOT NULL,
                INDEX IDX_4EF4A6BDA0BDB2F3 (song_id),
                INDEX IDX_4EF4A6BD4296D31F (genre_id),
                PRIMARY KEY(song_id, genre_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE singer_song
                ADD CONSTRAINT FK_58B4FF1F271FD47C FOREIGN KEY (singer_id) REFERENCES singer (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE singer_song
                ADD CONSTRAINT FK_58B4FF1FA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE song_genre
                ADD CONSTRAINT FK_4EF4A6BDA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE song_genre
                ADD CONSTRAINT FK_4EF4A6BD4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE song_genre');
        $this->addSql('DROP TABLE singer_song');
        $this->addSql('DROP TABLE song');
    }
}
