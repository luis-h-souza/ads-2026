# 📚 Painel de Estudos - Arquitetura MVC com PHP

Sistema completo para gestão de semestres letivos, disciplinas, unidades de estudo, aulas, exercícios práticos e acompanhamento de progresso em tempo real, construído em **arquitetura MVC (Model-View-Controller) pura com PHP e MySQL**.

---

## 🏗️ 1. Estrutura do Projeto

```plaintext
ads-2026/
├── app/
│   ├── Config/
│   │   ├── Database.php          # Gerenciador de conexão PDO (Singleton)
│   │   └── bootstrap.php         # Autoload PSR-4, carregamento de .env e helpers
│   ├── Controllers/
│   │   ├── BaseController.php    # Métodos base para renderização de Views e JSON
│   │   ├── AuthController.php    # Login, registro, logout e sessão
│   │   ├── DashboardController.php # Renderização da interface do painel de estudos
│   │   ├── SemesterController.php  # Endpoints de semestres
│   │   └── StudyController.php     # Endpoints de disciplinas, unidades e aulas
│   ├── Core/
│   │   ├── App.php               # Ciclo de vida da aplicação (CORS, Sessão e Router)
│   │   ├── Auth.php              # Auxiliar de autenticação e sessão do usuário
│   │   ├── Request.php           # Tratamento de requisições e payloads JSON
│   │   ├── Response.php          # Respostas HTTP, cabeçalhos e JSON
│   │   ├── Router.php            # Roteador de URLs amigáveis com suporte a parâmetros
│   │   └── View.php              # Renderizador de views com layouts compartilhados
│   ├── Models/
│   │   ├── BaseModel.php         # Model base abstrato com conexão PDO
│   │   ├── User.php              # Operações de usuários (senhas com BCRYPT)
│   │   ├── Semester.php          # Gestão de semestres e montagem da árvore hierárquica
│   │   ├── Discipline.php        # Disciplinas e validação de propriedade
│   │   ├── Unit.php              # Unidades das disciplinas
│   │   └── StudyItem.php         # Aulas, exercícios e progresso por usuário
│   └── Views/
│       ├── layouts/
│       │   ├── header.php        # Head HTML, meta tags, fontes e CSS global
│       │   └── footer.php        # Rodapé compartilhado
│       ├── auth/
│       │   └── login.php         # View da tela de login estilizada
│       └── dashboard/
│           └── index.php         # View principal do painel com modais integrados
├── database/
│   ├── schema.sql                # Script de criação das tabelas e chaves estrangeiras
│   └── seed_mock_data.php        # Script de migração dos dados curriculares para o banco
├── public/
│   ├── .htaccess                 # Reescrita para redirecionar todas as rotas para index.php
│   ├── index.php                 # Front Controller único da aplicação
│   └── assets/
│       ├── css/
│       │   └── style.css         # Estilização completa (tema espacial/dark com detalhes em âmbar)
│       └── js/
│           ├── login.js          # Lógica de login assíncrona integrada à sessão PHP
│           └── script.js         # Lógica do painel e sincronização com a API MySQL
├── .env                          # Variáveis de ambiente locais (XAMPP)
├── .env.example                  # Modelo de variáveis de ambiente
├── .htaccess                     # Redirecionamento da raiz do projeto para /public
└── README.md                     # Documentação técnica da aplicação
```

---

## ⚙️ 2. Como Configurar e Executar

### Pré-requisitos
* **XAMPP** com **Apache** e **MySQL** (PHP 8.1 ou superior).

### Passo 1: Configurar o Banco de Dados
1. Inicie o Apache e o MySQL no painel de controle do XAMPP.
2. Abra o phpMyAdmin (`http://localhost/phpmyadmin`) ou o terminal MySQL e execute o script [database/schema.sql](file:///c:/xampp/htdocs/ads-2026/database/schema.sql):
   ```sql
   CREATE DATABASE IF NOT EXISTS `ads-aulas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE `ads-aulas`;
   -- Importar o conteúdo de database/schema.sql
   ```

### Passo 2: Configurar o arquivo `.env`
Verifique o arquivo `.env` na raiz do projeto:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ads-aulas
DB_USER=root
DB_PASSWORD=

APP_URL=http://localhost/ads-2026
APP_ENV=local
```

### Passo 3: Popular o Banco de Dados (Seed Inicial)
Para migrar a grade completa de matérias e unidades para o seu usuário:
```bash
C:\xampp\php\php.exe database/seed_mock_data.php
```

---

## 🌐 3. Rotas de Páginas (Views Web)

| Método | URL | Descrição |
|---|---|---|
| `GET` | `/` | Redireciona para `/dashboard` |
| `GET` | `/login` | Exibe o formulário de login estilizado |
| `GET` | `/dashboard` | Exibe o Painel de Estudos com semestres, matérias e progresso |
| `GET` | `/logout` | Encerra a sessão PHP e redireciona para o login |

---

## 📡 4. Documentação dos Endpoints REST (API)

Todos os endpoints da API aceitam e respondem no formato `application/json` e utilizam cookies de sessão (`painel_estudos`).

### 4.1. Verificação de Saúde (Health Check)
* **GET `/health`** ou **GET `/api/health`**
* **Resposta (200 OK):**
  ```json
  {
    "status": "ok",
    "app": "ads-2026"
  }
  ```

---

### 4.2. Autenticação

#### Registro de Novo Usuário
* **POST `/auth/register`** ou **POST `/api/auth/register`**
* **Payload:**
  ```json
  {
    "nome": "Luis Henrique",
    "email": "luis.h.souza@outlook.com.br",
    "senha": "senha_com_minimo_8_caracteres"
  }
  ```
* **Resposta (201 Created):**
  ```json
  {
    "id": 1,
    "nome": "Luis Henrique",
    "email": "luis.h.souza@outlook.com.br"
  }
  ```

#### Login de Usuário
* **POST `/auth/login`** ou **POST `/api/auth/login`**
* **Payload:**
  ```json
  {
    "email": "luis.h.souza@outlook.com.br",
    "senha": "sua_senha_aqui"
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "id": 1,
    "nome": "Luis Henrique",
    "email": "luis.h.souza@outlook.com.br"
  }
  ```

#### Obter Dados do Usuário Logado
* **GET `/api/me`**
* **Resposta (200 OK):**
  ```json
  {
    "autenticado": true,
    "usuario": {
      "id": 1,
      "nome": "Luis Henrique",
      "email": "luis.h.souza@outlook.com.br"
    }
  }
  ```

#### Logout
* **POST `/auth/logout`** ou **POST `/api/auth/logout`**
* **Resposta (200 OK):**
  ```json
  {
    "ok": true
  }
  ```

---

### 4.3. Semestres

#### Listar Semestres do Usuário
* **GET `/semestres`** ou **GET `/api/semestres`**
* **Resposta (200 OK):**
  ```json
  {
    "semestres": [
      {
        "id": 1,
        "nome": "2º Semestre 2026",
        "ano": 2026,
        "data_inicio": "2026-08-01",
        "data_fim": "2026-12-12"
      }
    ]
  }
  ```

#### Obter Árvore Completa de um Semestre (com Disciplinas, Unidades e Aulas)
* **GET `/semestres/{id}`** ou **GET `/api/semestres/{id}`**
* **Resposta (200 OK):**
  ```json
  {
    "id": 1,
    "nome": "2º Semestre 2026",
    "ano": 2026,
    "data_inicio": "2026-08-01",
    "data_fim": "2026-12-12",
    "disciplinas": [
      {
        "id": 1,
        "nome": "Engenharia de Software",
        "descricao": "Conteúdo já concluído · prova em 02/09",
        "data_inicio": "2026-08-01",
        "data_fim": "2026-09-02",
        "data_prova": "2026-09-02",
        "ordem": 1,
        "unidades": [
          {
            "id": 1,
            "titulo": "Prova e Avaliação",
            "ordem": 1,
            "itens": [
              {
                "id": 1,
                "titulo": "Fazer a prova de Engenharia de Software (02/09)",
                "tipo": "tarefa",
                "ordem": 1,
                "concluido": true,
                "concluido_em": "2026-09-07 13:30:00"
              }
            ]
          }
        ]
      }
    ]
  }
  ```

#### Criar Novo Semestre
* **POST `/semestres`** ou **POST `/api/semestres`**
* **Payload:**
  ```json
  {
    "nome": "1º Semestre 2027",
    "ano": 2027,
    "data_inicio": "2027-02-01",
    "data_fim": "2027-06-30"
  }
  ```
* **Resposta (201 Created):**
  ```json
  {
    "id": 2
  }
  ```

#### Atualizar Semestre
* **PATCH `/semestres/{id}`** ou **PATCH `/api/semestres/{id}`**
* **Payload:**
  ```json
  {
    "nome": "1º Semestre 2027 (Atualizado)",
    "ano": 2027
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "id": 2,
    "atualizado": true
  }
  ```

#### Excluir Semestre
* **DELETE `/semestres/{id}`** ou **DELETE `/api/semestres/{id}`**
* **Resposta (200 OK):**
  ```json
  {
    "id": 2,
    "removido": true
  }
  ```

---

### 4.4. Disciplinas (Matérias)

#### Criar Disciplina
* **POST `/disciplinas`** ou **POST `/api/disciplinas`**
* **Payload:**
  ```json
  {
    "semestre_id": 1,
    "nome": "Banco de Dados II",
    "descricao": "Estudo 15/09–10/10 · Prova 20/10",
    "data_inicio": "2026-09-15",
    "data_fim": "2026-10-10",
    "data_prova": "2026-10-20",
    "ordem": 1
  }
  ```
* **Resposta (201 Created):**
  ```json
  {
    "id": 8
  }
  ```

#### Atualizar Disciplina
* **PATCH `/disciplinas/{id}`** ou **PATCH `/api/disciplinas/{id}`**
* **Payload:**
  ```json
  {
    "nome": "Banco de Dados Avançado",
    "descricao": "Nova descrição das aulas",
    "data_prova": "2026-10-25"
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "id": 8,
    "atualizado": true
  }
  ```

#### Excluir Disciplina (em cascata)
* **DELETE `/disciplinas/{id}`** ou **DELETE `/api/disciplinas/{id}`**
* **Resposta (200 OK):**
  ```json
  {
    "id": 8,
    "removido": true
  }
  ```

---

### 4.5. Unidades

#### Criar Unidade
* **POST `/unidades`** ou **POST `/api/unidades`**
* **Payload:**
  ```json
  {
    "disciplina_id": 8,
    "titulo": "Unidade 1 - Modelagem Relacional",
    "ordem": 1
  }
  ```
* **Resposta (201 Created):**
  ```json
  {
    "id": 23
  }
  ```

#### Atualizar Unidade
* **PATCH `/unidades/{id}`** ou **PATCH `/api/unidades/{id}`**
* **Payload:**
  ```json
  {
    "titulo": "Unidade 1 - Modelagem e Otimização",
    "ordem": 1
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "id": 23,
    "atualizado": true
  }
  ```

#### Excluir Unidade (em cascata)
* **DELETE `/unidades/{id}`** ou **DELETE `/api/unidades/{id}`**
* **Resposta (200 OK):**
  ```json
  {
    "id": 23,
    "removido": true
  }
  ```

---

### 4.6. Itens de Estudo (Aulas, Exercícios e Tarefas)

#### Criar Item de Estudo
* **POST `/itens-estudo`** ou **POST `/api/itens-estudo`**
* **Payload:**
  ```json
  {
    "unidade_id": 23,
    "titulo": "Aula 1 · Formas Normais e Normalização",
    "tipo": "aula",
    "ordem": 1
  }
  ```
  *(Opções para `tipo`: `"aula"`, `"exercicio"`, `"tarefa"`)*
* **Resposta (201 Created):**
  ```json
  {
    "id": 85
  }
  ```

#### Atualizar Item de Estudo
* **PATCH `/itens-estudo/{id}`** ou **PATCH `/api/itens-estudo/{id}`**
* **Payload:**
  ```json
  {
    "titulo": "Aula 1 · Normalização na Prática",
    "tipo": "exercicio"
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "id": 85,
    "atualizado": true
  }
  ```

#### Excluir Item de Estudo
* **DELETE `/itens-estudo/{id}`** ou **DELETE `/api/itens-estudo/{id}`**
* **Resposta (200 OK):**
  ```json
  {
    "id": 85,
    "removido": true
  }
  ```

#### Atualizar Progresso (Marcar / Desmarcar Concluído)
* **PATCH `/itens-estudo/{id}/progresso`** ou **PATCH `/api/itens-estudo/{id}/progresso`**
* **Payload:**
  ```json
  {
    "concluido": true
  }
  ```
* **Resposta (200 OK):**
  ```json
  {
    "item_id": 85,
    "concluido": true
  }
  ```

---

## 🔒 5. Segurança e Boas Práticas Implementadas
* **Prepared Statements (PDO)**: Proteção completa contra SQL Injection em todas as queries.
* **BCRYPT Hashing**: Senhas criptografadas com algoritmo nativo `password_hash()` do PHP.
* **Validação de Propriedade (Ownership Check)**: Nenhum usuário consegue acessar, alterar ou excluir matérias/unidades pertencentes a outro usuário.
* **Sessões HTTP-Only**: Cookies de sessão protegidos contra ataques XSS.
* **Front Controller e Roteamento Amigável**: Apenas o diretório `public/` é exposto ao servidor web, mantendo o núcleo e os arquivos de configuração isolados e protegidos.
