<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250405142530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблиц singer, genre и их связывающую таблицу';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `genre` (
                `id`         INT AUTO_INCREMENT NOT NULL,
                `title`      VARCHAR(60) NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);

        $this->addSql(<<<SQL
            INSERT INTO `genre` (`title`) VALUES
                ('Rock'),
                ('Hip-hop'),
                ('Rap')
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `singer` (
                `id`          INT AUTO_INCREMENT NOT NULL,
                `name`        VARCHAR(60) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `user_id`     INT NOT NULL,
                `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `IDX_9J93D64GD60822AC` (`user_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `singer_genre` (
                `singer_id` INT NOT NULL,
                `genre_id` INT NOT NULL,
                INDEX `IDX_AFE2F462271FD47C` (`singer_id`),
                INDEX `IDX_AFE2F4624296D31F` (`genre_id`),
                PRIMARY KEY(`singer_id`, `genre_id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);

        $this->addSql(<<<SQL
            ALTER TABLE `singer` ADD CONSTRAINT `IDX_9J93D64GD60822AC` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
        SQL);

        $this->addSql(<<<SQL
            ALTER TABLE `singer_genre` ADD CONSTRAINT `FK_AFE2F462271FD47C` FOREIGN KEY (`singer_id`) REFERENCES singer (`id`) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<SQL
            ALTER TABLE `singer_genre` ADD CONSTRAINT `FK_AFE2F4624296D31F` FOREIGN KEY (`genre_id`) REFERENCES genre (`id`) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE `singer_genre` DROP FOREIGN KEY `FK_AFE2F462271FD47C`
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE `singer_genre` DROP FOREIGN KEY `FK_AFE2F4624296D31F`
        SQL);
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `genre`
        SQL);
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `singer`
        SQL);
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `singer_genre`
        SQL);
    }
}
