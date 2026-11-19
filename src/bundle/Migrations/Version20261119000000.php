<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The identifier-like columns were created with the ascii character set, which the mapping of the entities does not
 * declare: Doctrine wanted to convert them (doctrine:schema:update --dump-sql). They take the character set of the table
 * (utf8mb4) now; what they hold is ascii text, so nothing changes in the data.
 */
final class Version20261119000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Gives the ascii columns of masilia_site and masilia_setting the character set of their table.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'These tables are created for MySQL / MariaDB.');

        $this->addSql('ALTER TABLE masilia_site MODIFY identifier VARCHAR(30) NOT NULL, MODIFY status VARCHAR(12) NOT NULL, MODIFY design VARCHAR(80) NOT NULL, MODIFY base_group VARCHAR(80) DEFAULT NULL, MODIFY session_cookie_domain VARCHAR(255) DEFAULT NULL, MODIFY admin_siteaccess VARCHAR(80) DEFAULT NULL, MODIFY var_dir VARCHAR(255) DEFAULT NULL, MODIFY layout_remote_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE masilia_setting MODIFY name VARCHAR(60) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Nothing to undo: utf8mb4 holds what ascii held.
    }
}
