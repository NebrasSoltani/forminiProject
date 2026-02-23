<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218201639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evenement ADD image360 VARCHAR(255) DEFAULT NULL, ADD url_street_view LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY `FK_part_evenement`');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY `FK_part_user`');
        $this->addSql('DROP INDEX idx_part_user ON participation_evenement');
        $this->addSql('CREATE INDEX IDX_65A14675A76ED395 ON participation_evenement (user_id)');
        $this->addSql('DROP INDEX idx_part_evenement ON participation_evenement');
        $this->addSql('CREATE INDEX IDX_65A14675FD02F13 ON participation_evenement (evenement_id)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT `FK_part_evenement` FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT `FK_part_user` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question ADD explications_detaillees LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reponse ADD explication_reponse LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE societe DROP logo, DROP is_verifie');
        $this->addSql('ALTER TABLE user CHANGE telephone telephone VARCHAR(12) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evenement DROP image360, DROP url_street_view');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675A76ED395');
        $this->addSql('ALTER TABLE participation_evenement DROP FOREIGN KEY FK_65A14675FD02F13');
        $this->addSql('DROP INDEX idx_65a14675a76ed395 ON participation_evenement');
        $this->addSql('CREATE INDEX IDX_part_user ON participation_evenement (user_id)');
        $this->addSql('DROP INDEX idx_65a14675fd02f13 ON participation_evenement');
        $this->addSql('CREATE INDEX IDX_part_evenement ON participation_evenement (evenement_id)');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_evenement ADD CONSTRAINT FK_65A14675FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE question DROP explications_detaillees');
        $this->addSql('ALTER TABLE reponse DROP explication_reponse');
        $this->addSql('ALTER TABLE societe ADD logo VARCHAR(255) DEFAULT NULL, ADD is_verifie TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE telephone telephone VARCHAR(20) NOT NULL');
    }
}
