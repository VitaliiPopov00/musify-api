<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250401193542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Заполняет базовыми ролями таблицу `role`';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO `role` (`title`) VALUES 
                ('admin'),
                ('user')
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            TRUNCATE TABLE IF EXISTS `role`
        SQL);
    }
}
