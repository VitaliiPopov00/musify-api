<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616171510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE subscribe (
                id INT AUTO_INCREMENT NOT NULL,
                singer_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                INDEX IDX_SUBSCRIBE_SINGER (singer_id),
                INDEX IDX_SUBSCRIBE_USER (user_id),
                CONSTRAINT FK_SUBSCRIBE_SINGER FOREIGN KEY (singer_id) REFERENCES singer (id) ON DELETE CASCADE,
                CONSTRAINT FK_SUBSCRIBE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE subscribe
        SQL);
    }
}
