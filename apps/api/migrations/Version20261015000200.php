<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align DBAL 4 immutable date mapping and stable unique index.';
    }
    public function up(Schema $schema): void
    {
        $this->addSql("COMMENT ON COLUMN stored_file.created_at IS ''");
        $this->addSql("COMMENT ON COLUMN stored_file.updated_at IS ''");
        $this->addSql('ALTER INDEX uniq_file_storage_key RENAME TO UNIQ_C339E77C111795A5');
    }
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Forward-only migration.');
    }
}
