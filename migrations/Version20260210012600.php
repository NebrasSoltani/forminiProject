<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260210012600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD is_email_verified TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD email_verification_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD email_verified_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP is_email_verified');
        $this->addSql('ALTER TABLE user DROP email_verification_token');
        $this->addSql('ALTER TABLE user DROP email_verification_token_expires_at');
        $this->addSql('ALTER TABLE user DROP email_verified_at');
    }
}
