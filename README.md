# AgroPanel - Sistema de Controle de Fazenda de Bovinos

<p align="center">
  <img src="https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white" alt="Symfony" />
  <img src="https://img.shields.io/badge/mysql-%2300f.svg?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/bootstrap-%238511FA.svg?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap" />
  <img src="https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E" alt="JavaScript" />
</p>

AgroPanel é uma aplicação web desenvolvida em **PHP 8.4** e **Symfony 8.0** para o controle e gestão de uma fazenda de bovinos. Este projeto foi desenvolvido como um teste prático.

## 📑 Sumário

* [Requisitos e Funcionalidades](#-requisitos-e-funcionalidades)
  * [Entidades Principais](#entidades-principais)
  * [Regras de Negócio e Validações](#regras-de-negócio-e-validações)
  * [Relatórios (Dashboard)](#relatórios-dashboard)
  * [Pontos Extras Implementados](#pontos-extras-implementados)
* [Tecnologias Utilizadas](#-tecnologias-utilizadas)
* [Como Executar o Projeto Localmente](#️-como-executar-o-projeto-localmente)
* [Visão Geral da Arquitetura do Frontend](#-visão-geral-da-arquitetura-do-frontend)

## 📋 Requisitos e Funcionalidades

O sistema foi construído visando gerenciar as seguintes entidades e regras de negócio:

### Entidades Principais
*   **Veterinários:** CRUD completo com campos de Nome e CRMV (único por veterinário).
*   **Fazendas:** CRUD contendo Nome (único), Tamanho em Hectares (HA), Responsável e associação com Veterinários (ManyToMany).
*   **Gados:** CRUD gerenciando Código (único), produção semanal de Leite (L), Ração consumida semanalmente (Kg), Peso (Kg), Data de Nascimento (não pode ser futura) e a Fazenda à qual pertencem (ManyToOne).

### Regras de Negócio e Validações
*   A quantidade máxima de animais em uma fazenda é limitada pelo seu tamanho: máximo de **18 animais por hectare**.
*   **Controle de Abate:** Um animal só pode ser enviado para abate se atender a **pelo menos uma** das seguintes condições:
    *   Ter mais de 5 anos de idade.
    *   Produzir menos de 40 litros de leite por semana.
    *   Produzir menos de 70 litros de leite por semana **E** ingerir mais de 50 kg de ração por dia (quantidade semanal dividida por 7).
    *   Ter peso superior a 18 arrobas (1 arroba = 15 kg, logo > 270 kg).
*   Apenas os animais que atendem às regras acima podem ser listados e marcados para o abate.

### Relatórios (Dashboard)
A tela inicial (Dashboard) provê as seguintes métricas gerais baseadas em funções customizadas de banco de dados (`Repository`):
*   Relatório de animais abatidos.
*   Quantidade total de leite produzido por semana.
*   Quantidade total de ração necessária por semana.
*   Quantidade de animais jovens (até 1 ano) com alto consumo de ração (> 500Kg/semana).

### Pontos Extras Implementados
*   **Frontend Responsivo e Componentizado:** Interface construída com **Bootstrap**, utilizando componentização no frontend (via Twig) para a criação de modais de alerta e listas paginadas. A interface possui cards em dispositivos móveis e tabelas em telas grandes para garantir usabilidade fluída.
*   **Paginação e Ordenação:** Utilização do pacote `KnpPaginatorBundle` com renderização no lado do servidor (Server-Side Pagination), permitindo gerenciar milhares de registros sem comprometer a performance e implementando acessibilidade WCAG.
*   **Feedback ao Usuário:** Notificações em modais utilizando as *flash messages* nativas do Symfony, substituindo os antigos *Browser Alerts* garantindo assim uma experiência premium e SaaS.
*   **Docker Automatizado e Seguro:** Ambiente configurado com Docker e Docker Compose, de forma que a inicialização do container roda dinamicamente o `composer install` e executa as migrações de banco automaticamente na inicialização (`migrations`), evitando a necessidade de executar comandos extras dentro do container.
*   **Padrão de Commits:** Utilização rigorosa das boas práticas do Git com mensagens semânticas.
*   **Consultas Otimizadas:** Todo o processamento de regras complexas, como abate e relatórios, é conduzido por QueryBuilders eficientes nos repositórios.

## 🚀 Tecnologias Utilizadas

*   **Linguagem:** PHP 8.4
*   **Framework:** Symfony 8.0
*   **Banco de Dados:** MySQL / MariaDB
*   **ORM:** Doctrine ORM & Migrations
*   **Dependências de Backend Extras:**
    *   `knplabs/knp-paginator-bundle` (Paginação Server-Side)
    *   `lexik/jwt-authentication-bundle` (Autenticação JWT API)
*   **Frontend:** Bootstrap, JavaScript Vanilla (Modular com Async/Await), CSS Customizado, Twig Template Engine
*   **Infraestrutura e DevOps:** Docker e Docker Compose

## ⚙️ Como Executar o Projeto Localmente

A aplicação e seus serviços dependentes rodam dentro do ambiente Docker, proporcionando segurança e praticidade. Certifique-se de que o Docker e Docker Compose estejam rodando adequadamente na sua máquina local.

1.  **Clone este repositório:**
    ```bash
    git clone https://github.com/Keltonmd/Fazenda.git
    cd Fazenda
    ```

2.  **Inicie os containers da aplicação:**
    A imagem configurada construirá o ambiente PHP, instalará o Composer, e de forma autônoma aguardará o banco de dados ficar saudável para aplicar as migrações:
    ```bash
    docker compose up -d --build
    ```

3.  **Gere as chaves de autenticação JWT:**
    O projeto utiliza autenticação via JWT. Para que funcione corretamente, é necessário gerar o par de chaves (pública/privada) e a *passphrase*. Execute o comando abaixo no terminal da sua máquina (que acessará o container PHP):
    ```bash
    docker compose exec php php bin/console lexik:jwt:generate-keypair
    ```
    *Obs: O comando irá criar as chaves dentro do diretório `config/jwt` do container.*

4.  **Acesse a aplicação no navegador:**
    A aplicação estará pronta para ser acessada na porta padrão:
    ```
    http://localhost:8000
    ```
    (Ou porta compatível que tenha sido exportada em seu docker-compose.yml)

## 📂 Visão Geral da Arquitetura do Frontend

Um grande esforço de refatoração foi feito com foco na otimização de densidade da tela, melhorando de forma substancial a usabilidade (UX/UI).
*   **`templates/components/`**: Módulos padronizados em Twig para reuso, especialmente as telas de modais e de paginação de dados.
*   **`public/js/`**: Scripts JavaScript separados por módulos funcionais da tela, sem depender de JQuery e usando API Fetch e listeners nativos.
*   **Dashboard e Auth**: Páginas que foram focadas em possuir a menor quantidade de scrolling possível em *viewports* padrão.
