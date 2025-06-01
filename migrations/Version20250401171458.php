<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401171458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Создание таблицы с ролями пользователей';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE IF NOT EXISTS `role` (
                `id`    INT AUTO_INCREMENT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                PRIMARY KEY(`id`)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `role`
        SQL);
    }
}
