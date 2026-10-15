<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Technical private file metadata; no product tables.';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stored_file (id VARCHAR(64) NOT NULL, owner VARCHAR(128) NOT NULL, purpose VARCHAR(64) NOT NULL, mime VARCHAR(128) NOT NULL, size INT NOT NULL, checksum VARCHAR(64) NOT NULL, storage_key VARCHAR(64) NOT NULL, backend VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FILE_STORAGE_KEY ON stored_file (storage_key)');
        $this->addSql("COMMENT ON COLUMN stored_file.created_at IS '(DC2Type:datetime_immutable)'");
        $this->addSql("COMMENT ON COLUMN stored_file.updated_at IS '(DC2Type:datetime_immutable)'");
    }
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Forward-only: restore a backup instead.');
    }
}
