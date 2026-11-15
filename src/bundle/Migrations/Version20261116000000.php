<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** The contact email and the postal address of a site (what its pages and emails show). */
final class Version20261116000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the email and the address of a site to masilia_site.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'This table is created for MySQL / MariaDB.');

        $this->addSql('ALTER TABLE masilia_site ADD COLUMN IF NOT EXISTS email VARCHAR(255) DEFAULT NULL, ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE masilia_site DROP COLUMN IF EXISTS email, DROP COLUMN IF EXISTS address');
    }
}
