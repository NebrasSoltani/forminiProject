<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207235547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {    
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE apprenant (id INT AUTO_INCREMENT NOT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, date_naissance DATE DEFAULT NULL, genre VARCHAR(20) DEFAULT NULL, etat_civil VARCHAR(50) DEFAULT NULL, objectif LONGTEXT DEFAULT NULL, domaines_interet JSON DEFAULT NULL, user_id INT NOT NULL, domaine_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_C4EB462EA76ED395 (user_id), INDEX IDX_C4EB462E4272FC9F (domaine_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, contenu LONGTEXT NOT NULL, image VARCHAR(255) DEFAULT NULL, date_publication DATETIME NOT NULL, categorie VARCHAR(100) NOT NULL, is_publie TINYINT NOT NULL, resume LONGTEXT DEFAULT NULL, auteur_id INT NOT NULL, evenement_id INT DEFAULT NULL, INDEX IDX_C015514360BB6FE6 (auteur_id), INDEX IDX_C0155143FD02F13 (evenement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(50) NOT NULL, lettre_motivation LONGTEXT NOT NULL, cv VARCHAR(255) DEFAULT NULL, date_candidature DATETIME NOT NULL, commentaire LONGTEXT DEFAULT NULL, offre_stage_id INT NOT NULL, apprenant_id INT NOT NULL, INDEX IDX_E33BD3B8195A2A28 (offre_stage_id), INDEX IDX_E33BD3B8C5697D6D (apprenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(100) NOT NULL, date_commande DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, total NUMERIC(10, 2) NOT NULL, adresse_livraison LONGTEXT DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, utilisateur_id INT NOT NULL, UNIQUE INDEX UNIQ_6EEAA67DAEA34913 (reference), INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande_item (id INT AUTO_INCREMENT NOT NULL, nom_produit VARCHAR(255) NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_747724FD82EA2E54 (commande_id), INDEX IDX_747724FDF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE domaine (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, lieu VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, nombre_places INT DEFAULT NULL, is_actif TINYINT NOT NULL, type VARCHAR(50) NOT NULL, organisateur_id INT NOT NULL, INDEX IDX_B26681ED936B2FA (organisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE favori (id INT AUTO_INCREMENT NOT NULL, date_ajout DATETIME NOT NULL, apprenant_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_EF85A2CCC5697D6D (apprenant_id), INDEX IDX_EF85A2CC5200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formateur (id INT AUTO_INCREMENT NOT NULL, specialite VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, experience_annees INT DEFAULT NULL, linkedin VARCHAR(255) DEFAULT NULL, portfolio VARCHAR(255) DEFAULT NULL, cv VARCHAR(255) DEFAULT NULL, note_moyenne DOUBLE PRECISION DEFAULT NULL, is_verifie TINYINT DEFAULT 0 NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_ED767E4FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, categorie VARCHAR(100) NOT NULL, niveau VARCHAR(50) NOT NULL, langue VARCHAR(50) NOT NULL, description_courte VARCHAR(500) NOT NULL, description_detaillee LONGTEXT NOT NULL, objectifs_pedagogiques LONGTEXT NOT NULL, prerequis LONGTEXT DEFAULT NULL, programme LONGTEXT NOT NULL, duree INT NOT NULL, nombre_lecons INT NOT NULL, format VARCHAR(50) NOT NULL, date_debut DATETIME DEFAULT NULL, planning LONGTEXT DEFAULT NULL, lien_live VARCHAR(255) DEFAULT NULL, nombre_seances INT DEFAULT NULL, type_acces VARCHAR(50) NOT NULL, prix NUMERIC(10, 2) DEFAULT NULL, type_achat VARCHAR(50) DEFAULT NULL, prix_promo NUMERIC(10, 2) DEFAULT NULL, date_fin_promo DATETIME DEFAULT NULL, image_couverture VARCHAR(255) DEFAULT NULL, video_promo VARCHAR(255) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, date_publication DATETIME DEFAULT NULL, certificat TINYINT NOT NULL, has_quiz TINYINT NOT NULL, fichiers_telechargeables TINYINT NOT NULL, forum TINYINT NOT NULL, formateur_id INT NOT NULL, INDEX IDX_404021BF155D8F51 (formateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inscription (id INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, progression INT NOT NULL, date_terminee DATETIME DEFAULT NULL, mode_paiement VARCHAR(50) DEFAULT NULL, montant_paye NUMERIC(10, 2) DEFAULT NULL, certificat_obtenu TINYINT NOT NULL, apprenant_id INT NOT NULL, formation_id INT NOT NULL, INDEX IDX_5E90F6D6C5697D6D (apprenant_id), INDEX IDX_5E90F6D65200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE lecon (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, ordre INT NOT NULL, duree INT DEFAULT NULL, video_url VARCHAR(255) DEFAULT NULL, fichier VARCHAR(255) DEFAULT NULL, gratuit TINYINT NOT NULL, formation_id INT NOT NULL, INDEX IDX_94E6242E5200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre_stage (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, entreprise VARCHAR(255) NOT NULL, domaine VARCHAR(50) DEFAULT NULL, competences_requises LONGTEXT DEFAULT NULL, profil_demande VARCHAR(100) DEFAULT NULL, duree VARCHAR(100) NOT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, type_stage VARCHAR(100) NOT NULL, lieu VARCHAR(255) NOT NULL, remuneration VARCHAR(100) DEFAULT NULL, contact_email VARCHAR(100) DEFAULT NULL, contact_tel VARCHAR(20) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_publication DATETIME NOT NULL, societe_id INT NOT NULL, INDEX IDX_955674F2FCF77503 (societe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, montant NUMERIC(10, 2) NOT NULL, methode_paiement VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, date_validation DATETIME DEFAULT NULL, reference_transaction VARCHAR(255) DEFAULT NULL, details_paiement LONGTEXT DEFAULT NULL, numero_telephone VARCHAR(255) DEFAULT NULL, nom_titulaire VARCHAR(255) DEFAULT NULL, inscription_id INT NOT NULL, INDEX IDX_B1DC7A1E5DAC5993 (inscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, categorie VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, prix NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE progression_lecon (id INT AUTO_INCREMENT NOT NULL, terminee TINYINT NOT NULL, date_terminee DATETIME DEFAULT NULL, apprenant_id INT NOT NULL, lecon_id INT NOT NULL, INDEX IDX_974209DCC5697D6D (apprenant_id), INDEX IDX_974209DCEC1308A5 (lecon_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, enonce LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, points INT NOT NULL, ordre INT NOT NULL, explication LONGTEXT DEFAULT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, duree INT NOT NULL, note_minimale INT NOT NULL, afficher_correction TINYINT NOT NULL, melanger TINYINT NOT NULL, formation_id INT NOT NULL, INDEX IDX_A412FA925200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reponse (id INT AUTO_INCREMENT NOT NULL, texte VARCHAR(500) NOT NULL, est_correcte TINYINT NOT NULL, question_id INT NOT NULL, INDEX IDX_5FB6DEC71E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat_quiz (id INT AUTO_INCREMENT NOT NULL, note NUMERIC(5, 2) NOT NULL, nombre_bonnes_reponses INT NOT NULL, nombre_total_questions INT NOT NULL, date_tentative DATETIME NOT NULL, reussi TINYINT NOT NULL, details_reponses LONGTEXT DEFAULT NULL, apprenant_id INT NOT NULL, quiz_id INT NOT NULL, INDEX IDX_2A776B3C5697D6D (apprenant_id), INDEX IDX_2A776B3853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE societe (id INT AUTO_INCREMENT NOT NULL, nom_societe VARCHAR(255) NOT NULL, secteur VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, site_web VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, is_verifie TINYINT DEFAULT 0 NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_19653DBDA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
$this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) NOT NULL, gouvernorat VARCHAR(255) DEFAULT NULL, date_naissance DATE NOT NULL, profession VARCHAR(100) DEFAULT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, role_utilisateur VARCHAR(50) NOT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');        //$this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, telephone VARCHAR(20) NOT NULL, gouvernorat VARCHAR(255) DEFAULT NULL, date_naissance DATE NOT NULL, profession VARCHAR(100) DEFAULT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, role_utilisateur VARCHAR(50) NOT NULL, photo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462E4272FC9F FOREIGN KEY (domaine_id) REFERENCES domaine (id)');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_C015514360BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE blog ADD CONSTRAINT FK_C0155143FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8195A2A28 FOREIGN KEY (offre_stage_id) REFERENCES offre_stage (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B8C5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FD82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande_item ADD CONSTRAINT FK_747724FDF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681ED936B2FA FOREIGN KEY (organisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CCC5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE favori ADD CONSTRAINT FK_EF85A2CC5200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE formateur ADD CONSTRAINT FK_ED767E4FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BF155D8F51 FOREIGN KEY (formateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D6C5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inscription ADD CONSTRAINT FK_5E90F6D65200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE lecon ADD CONSTRAINT FK_94E6242E5200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE offre_stage ADD CONSTRAINT FK_955674F2FCF77503 FOREIGN KEY (societe_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E5DAC5993 FOREIGN KEY (inscription_id) REFERENCES inscription (id)');
        $this->addSql('ALTER TABLE progression_lecon ADD CONSTRAINT FK_974209DCC5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE progression_lecon ADD CONSTRAINT FK_974209DCEC1308A5 FOREIGN KEY (lecon_id) REFERENCES lecon (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA925200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE reponse ADD CONSTRAINT FK_5FB6DEC71E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3C5697D6D FOREIGN KEY (apprenant_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE resultat_quiz ADD CONSTRAINT FK_2A776B3853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE societe ADD CONSTRAINT FK_19653DBDA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462EA76ED395');
        $this->addSql('ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462E4272FC9F');
        $this->addSql('ALTER TABLE blog DROP FOREIGN KEY FK_C015514360BB6FE6');
        $this->addSql('ALTER TABLE blog DROP FOREIGN KEY FK_C0155143FD02F13');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8195A2A28');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B8C5697D6D');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FD82EA2E54');
        $this->addSql('ALTER TABLE commande_item DROP FOREIGN KEY FK_747724FDF347EFB');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681ED936B2FA');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CCC5697D6D');
        $this->addSql('ALTER TABLE favori DROP FOREIGN KEY FK_EF85A2CC5200282E');
        $this->addSql('ALTER TABLE formateur DROP FOREIGN KEY FK_ED767E4FA76ED395');
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BF155D8F51');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D6C5697D6D');
        $this->addSql('ALTER TABLE inscription DROP FOREIGN KEY FK_5E90F6D65200282E');
        $this->addSql('ALTER TABLE lecon DROP FOREIGN KEY FK_94E6242E5200282E');
        $this->addSql('ALTER TABLE offre_stage DROP FOREIGN KEY FK_955674F2FCF77503');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E5DAC5993');
        $this->addSql('ALTER TABLE progression_lecon DROP FOREIGN KEY FK_974209DCC5697D6D');
        $this->addSql('ALTER TABLE progression_lecon DROP FOREIGN KEY FK_974209DCEC1308A5');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA925200282E');
        $this->addSql('ALTER TABLE reponse DROP FOREIGN KEY FK_5FB6DEC71E27F6BF');
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3C5697D6D');
        $this->addSql('ALTER TABLE resultat_quiz DROP FOREIGN KEY FK_2A776B3853CD175');
        $this->addSql('ALTER TABLE societe DROP FOREIGN KEY FK_19653DBDA76ED395');
        $this->addSql('DROP TABLE apprenant');
        $this->addSql('DROP TABLE blog');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_item');
        $this->addSql('DROP TABLE domaine');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE favori');
        $this->addSql('DROP TABLE formateur');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE inscription');
        $this->addSql('DROP TABLE lecon');
        $this->addSql('DROP TABLE offre_stage');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE progression_lecon');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE reponse');
        $this->addSql('DROP TABLE resultat_quiz');
        $this->addSql('DROP TABLE societe');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
