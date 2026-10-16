<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015163651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Individual accounts, cabinet drafts, immutable revisions and public manufacturing requests.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cabinet_project (id VARCHAR(32) NOT NULL, owner_id VARCHAR(32) NOT NULL, title VARCHAR(300) NOT NULL, configuration JSON NOT NULL, version INT DEFAULT 1 NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE cabinet_publication (project_id VARCHAR(32) NOT NULL, revision_id VARCHAR(32) NOT NULL, token VARCHAR(32) NOT NULL, city VARCHAR(300) NOT NULL, description TEXT NOT NULL, active BOOLEAN NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (project_id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E8256F35F37A13B ON cabinet_publication (token)');
        $this->addSql('CREATE TABLE cabinet_revision (id VARCHAR(32) NOT NULL, project_id VARCHAR(32) NOT NULL, version INT NOT NULL, title VARCHAR(300) NOT NULL, configuration JSON NOT NULL, estimate JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_revision_version ON cabinet_revision (project_id, version)');
        $this->addSql('CREATE TABLE pilot_account (id VARCHAR(32) NOT NULL, email VARCHAR(254) NOT NULL, name VARCHAR(240) NOT NULL, password_hash VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BB4ACDF4E7927C74 ON pilot_account (email)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Forward-only product migration.');
    }
}
