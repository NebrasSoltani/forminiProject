<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participation_evenement table for event participation (front Participer)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE participation_evenement (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            evenement_id INT NOT NULL,
            date_participation DATETIME NOT NULL,
            UNIQUE INDEX user_evenement_unique (user_id, evenement_id),
            INDEX IDX_part_user (user_id),
            INDEX IDX_part_evenement (evenement_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_part_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_part_evenement FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE participation_evenement');
    }
}
