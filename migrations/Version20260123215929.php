<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123215929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, date_ajout DATETIME NOT NULL, apprenant_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_EF85A2CCC5697D6D (apprenant_id), INDEX IDX_EF85A2CC5200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, categorie VARCHAR(100) NOT NULL, niveau VARCHAR(50) NOT NULL, langue VARCHAR(50) NOT NULL, description_courte VARCHAR(500) NOT NULL, description_detaillee LONGTEXT NOT NULL, objectifs_pedagogiques LONGTEXT NOT NULL, prerequis LONGTEXT DEFAULT NULL, programme LONGTEXT NOT NULL, duree INT NOT NULL, nombre_lecons INT NOT NULL, format VARCHAR(50) NOT NULL, date_debut DATETIME DEFAULT NULL, planning LONGTEXT DEFAULT NULL, lien_live VARCHAR(255) DEFAULT NULL, nombre_seances INT DEFAULT NULL, type_acces VARCHAR(50) NOT NULL, prix NUMERIC(10, 2) DEFAULT NULL, type_achat VARCHAR(50) DEFAULT NULL, prix_promo NUMERIC(10, 2) DEFAULT NULL, date_fin_promo DATETIME DEFAULT NULL, image_couverture VARCHAR(255) DEFAULT NULL, video_promo VARCHAR(255) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, date_publication DATETIME DEFAULT NULL, certificat TINYINT NOT NULL, has_quiz TINYINT NOT NULL, fichiers_telechargeables TINYINT NOT NULL, forum TINYINT NOT NULL, formateur_id INT NOT NULL, INDEX IDX_404021BF155D8F51 (formateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inscription (id INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, progression INT NOT NULL, date_terminee DATETIME DEFAULT NULL, mode_paiement VARCHAR(50) DEFAULT NULL, montant_paye NUMERIC(10, 2) DEFAULT NULL, certificat_obtenu TINYINT NOT NULL, apprenant_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_5E90F6D6C5697D6D (apprenant_id), INDEX IDX_5E90F6D65200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lecon (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, ordre INT NOT NULL, duree INT DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, fichier VARCHAR(255) DEFAULT NULL, gratuit TINYINT NOT NULL, formation_id INT NOT NULL, INDEX IDX_94E6242E5200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, enonce LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, points INT NOT NULL, ordre INT NOT NULL, explication LONGTEXT DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, duree INT NOT NULL, note_minimale INT NOT NULL, afficher_correction TINYINT NOT NULL, melanger TINYINT NOT NULL, formation_id INT NOT NULL, INDEX IDX_A412FA925200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(500) NOT NULL, est_correcte TINYINT NOT NULL, question_id INT NOT NULL, INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat_quiz (id INT AUTO_INCREMENT NOT NULL, note NUMERIC(5, 2) NOT NULL, nombre_bonnes_reponses INT NOT NULL, nombre_total_questions INT NOT NULL, date_tentative DATETIME NOT NULL, reussi TINYINT NOT NULL, details_reponses LONGTEXT DEFAULT NULL, apprenant_id INT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_2A776B3C5697D6D (apprenant_id), INDEX IDX_2A776B3853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) NOT NULL, governorat VARCHAR(100) NOT NULL, date_naissance DATE NOT NULL, profession VARCHAR(100) DEFAULT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, role_utilisateur VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCC5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CC5200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF155D8F51 FOREIGN KEY (formateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6C5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D65200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE lecon ADD CONSTRAINT FK_94E6242E5200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA925200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3C5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCC5697D6D');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CC5200282E');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF155D8F51');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6C5697D6D');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D65200282E');
        $this->addSql('ALTER TABLE lecon DROP FOREIGN KEY FK_94E6242E5200282E');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA925200282E');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3C5697D6D');
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3853CD175');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE lecon');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE resultat_quiz');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
