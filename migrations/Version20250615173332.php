<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250615173332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE IF NOT EXISTS `releases` (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                date DATE NOT NULL,
                time TIME NOT NULL,
                is_released TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');

        $this->addSql('
            CREATE TABLE IF NOT EXISTS `release_singer` (
                id INT AUTO_INCREMENT NOT NULL,
                release_id INT NOT NULL,
                singer_id INT NOT NULL,
                INDEX IDX_AB88034AB12A727D (release_id),
                INDEX IDX_AB88034A271FD47C (singer_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4
        ');

        $this->addSql('
            ALTER TABLE `release_singer` ADD CONSTRAINT `FK_AB88034AB12A727D` FOREIGN KEY (`release_id`) REFERENCES `releases` (`id`)
        ');
        $this->addSql('
            ALTER TABLE `release_singer` ADD CONSTRAINT `FK_AB88034A271FD47C` FOREIGN KEY (`singer_id`) REFERENCES `singer` (`id`)
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE `release_singer` DROP FOREIGN KEY `FK_AB88034AB12A727D`
        ');
        $this->addSql('
            ALTER TABLE `release_singer` DROP FOREIGN KEY `FK_AB88034A271FD47C`
        ');
        $this->addSql('
            DROP TABLE `release_singer`
        ');
        $this->addSql('
            DROP TABLE `releases`
        ');
    }
}
