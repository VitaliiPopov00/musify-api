<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401194356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблицы `user`';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `user` (
                `id`         INT AUTO_INCREMENT NOT NULL,
                `login`      VARCHAR(255) NOT NULL,
                `password`   VARCHAR(255) NOT NULL,
                `token`      VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `role_id`    INT NOT NULL,
                UNIQUE INDEX `UNIQ_8D93D649AA08CB10` (`login`),
                UNIQUE INDEX `UNIQ_8D93D6495F37A13B` (`token`),
                INDEX `IDX_8D93D649D60322AC` (`role_id`),
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);

        $this->addSql(<<<SQL
            ALTER TABLE `user` ADD CONSTRAINT `FK_8D93D649D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE `user` DROP FOREIGN KEY `FK_8D93D649D60322AC`
        SQL);

        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `user`
        SQL);
    }
}
