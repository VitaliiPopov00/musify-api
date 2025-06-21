<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250603044623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_playlist and user_playlist_song tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_playlist (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(60) NOT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            FOREIGN KEY (created_by) REFERENCES user(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_playlist_song (
            id INT AUTO_INCREMENT NOT NULL,
            user_playlist_id INT NOT NULL,
            song_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            FOREIGN KEY (user_playlist_id) REFERENCES user_playlist(id),
            FOREIGN KEY (song_id) REFERENCES song(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_playlist_song');
        $this->addSql('DROP TABLE user_playlist');
    }
} 