<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250603044621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create custom_genre table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE custom_genre (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            entity_type VARCHAR(10) NOT NULL,
            entity_id INT NOT NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX IDX_CUSTOM_GENRE_CREATED_BY (created_by),
            INDEX IDX_CUSTOM_GENRE_ENTITY (entity_type, entity_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE custom_genre ADD CONSTRAINT FK_CUSTOM_GENRE_CREATED_BY FOREIGN KEY (created_by) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE custom_genre');
    }
} 