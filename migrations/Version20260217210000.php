<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260217210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tags column to blog table manually';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog ADD tags JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog DROP tags');
    }
}
