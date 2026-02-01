<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201130420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, est_actif TINYINT NOT NULL, duree_minutes INT DEFAULT NULL, note_sur DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat_quiz (id INT AUTO_INCREMENT NOT NULL, date_realisation DATETIME NOT NULL, score_obtenu DOUBLE PRECISION NOT NULL, nb_questions_repondues INT NOT NULL, temps_pris_secondes INT DEFAULT NULL, statut VARCHAR(50) NOT NULL, reponses_detaillees JSON DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_2A776B3853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3853CD175');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE resultat_quiz');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
