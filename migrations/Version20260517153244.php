<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517153244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fazenda (id INT AUTO_INCREMENT NOT NULL, nome VARCHAR(150) NOT NULL, responsavel VARCHAR(100) NOT NULL, tamanho_ha DOUBLE PRECISION NOT NULL, usuario_id INT DEFAULT NULL, INDEX IDX_70559C46DB38439E (usuario_id), UNIQUE INDEX unique_usuario_nome (usuario_id, nome), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE fazenda_veterinario (fazenda_id INT NOT NULL, veterinario_id INT NOT NULL, INDEX IDX_4D394109D4A3545F (fazenda_id), INDEX IDX_4D3941091454BD8B (veterinario_id), PRIMARY KEY (fazenda_id, veterinario_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gado (id INT AUTO_INCREMENT NOT NULL, codigo INT NOT NULL, leite DOUBLE PRECISION NOT NULL, racao DOUBLE PRECISION NOT NULL, peso DOUBLE PRECISION NOT NULL, nascimento DATE NOT NULL, abatido TINYINT NOT NULL, data_abate DATETIME DEFAULT NULL, fazenda_id INT NOT NULL, INDEX IDX_123C63DBD4A3545F (fazenda_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, nome VARCHAR(150) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE veterinario (id INT AUTO_INCREMENT NOT NULL, nome VARCHAR(100) NOT NULL, crmv VARCHAR(20) NOT NULL, usuario_id INT DEFAULT NULL, INDEX IDX_B0490CADDB38439E (usuario_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE fazenda ADD CONSTRAINT FK_70559C46DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('ALTER TABLE fazenda_veterinario ADD CONSTRAINT FK_4D394109D4A3545F FOREIGN KEY (fazenda_id) REFERENCES fazenda (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE fazenda_veterinario ADD CONSTRAINT FK_4D3941091454BD8B FOREIGN KEY (veterinario_id) REFERENCES veterinario (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE gado ADD CONSTRAINT FK_123C63DBD4A3545F FOREIGN KEY (fazenda_id) REFERENCES fazenda (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE veterinario ADD CONSTRAINT FK_B0490CADDB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fazenda DROP FOREIGN KEY FK_70559C46DB38439E');
        $this->addSql('ALTER TABLE fazenda_veterinario DROP FOREIGN KEY FK_4D394109D4A3545F');
        $this->addSql('ALTER TABLE fazenda_veterinario DROP FOREIGN KEY FK_4D3941091454BD8B');
        $this->addSql('ALTER TABLE gado DROP FOREIGN KEY FK_123C63DBD4A3545F');
        $this->addSql('ALTER TABLE veterinario DROP FOREIGN KEY FK_B0490CADDB38439E');
        $this->addSql('DROP TABLE fazenda');
        $this->addSql('DROP TABLE fazenda_veterinario');
        $this->addSql('DROP TABLE gado');
        $this->addSql('DROP TABLE usuario');
        $this->addSql('DROP TABLE veterinario');
    }
}
