<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** The registry of the sites created at run time (masilia_site). */
final class Version20261115000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create masilia_site.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'This table is created for MySQL / MariaDB.');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS masilia_site (
                identifier VARCHAR(30) CHARACTER SET ascii NOT NULL PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                status VARCHAR(12) CHARACTER SET ascii NOT NULL,
                root_location_id INT NOT NULL,
                design VARCHAR(80) CHARACTER SET ascii NOT NULL,
                base_group VARCHAR(80) CHARACTER SET ascii DEFAULT NULL,
                session_cookie_domain VARCHAR(255) CHARACTER SET ascii DEFAULT NULL,
                siteaccesses JSON NOT NULL,
                admin_siteaccess VARCHAR(80) CHARACTER SET ascii DEFAULT NULL,
                var_dir VARCHAR(255) CHARACTER SET ascii DEFAULT NULL,
                layout_remote_id VARCHAR(100) CHARACTER SET ascii DEFAULT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS masilia_site');
    }
}
