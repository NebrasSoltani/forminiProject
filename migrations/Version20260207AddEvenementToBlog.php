<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260207AddEvenementToBlog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne evenement_id dans blog';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog ADD evenement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_BLOG_EVENEMENT FOREIGN KEY (evenement_id) REFERENCES evenement(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE blog DROP FOREIGN KEY FK_BLOG_EVENEMENT');
        $this->addSql('ALTER TABLE blog DROP COLUMN evenement_id');
    }
}
