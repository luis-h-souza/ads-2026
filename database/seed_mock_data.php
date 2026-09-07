<?php

declare(strict_types=1);

/**
 * Script de Migração / Seed:
 * Importa todos os dados curriculares mockados para as tabelas MySQL:
 * - semestres
 * - disciplinas
 * - unidades
 * - itens_estudo
 * 
 * Execução via CLI: php database/seed_mock_data.php
 * Ou acessando via navegador (em ambiente local)
 */

require_once dirname(__DIR__) . '/app/Config/bootstrap.php';

use App\Config\Database;
use App\Models\User;

try {
    $db = Database::connection();
    echo "====================================================\n";
    echo "  MIGRAÇÃO DE MATÉRIAS E AULAS PARA O BANCO DE DADOS \n";
    echo "====================================================\n\n";

    // 1. Localiza o usuário para vincular as matérias
    $stmtUsers = $db->query("SELECT id, nome, email FROM usuarios ORDER BY id ASC");
    $users = $stmtUsers->fetchAll();

    if (empty($users)) {
        echo "ERRO: Nenhum usuário encontrado no banco de dados. Cadastre um usuário primeiro.\n";
        exit(1);
    }

    echo "Usuários encontrados: " . count($users) . "\n";
    foreach ($users as $u) {
        echo " - [ID {$u['id']}] {$u['nome']} ({$u['email']})\n";
    }

    $db->beginTransaction();

    // 2. Estrutura de dados completa para migração
    $disciplinasData = [
        [
            'key' => 'es',
            'nome' => 'Engenharia de Software',
            'descricao' => 'Conteúdo já concluído · prova em 02/09',
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-09-02',
            'data_prova' => '2026-09-02',
            'ordem' => 1,
            'unidades' => [
                [
                    'titulo' => 'Prova e Avaliação',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Fazer a prova de Engenharia de Software (02/09)', 'tipo' => 'tarefa', 'ordem' => 1],
                    ],
                ],
            ],
        ],
        [
            'key' => 'lp',
            'nome' => 'Linguagem de Programação',
            'descricao' => 'Estudo 31/08–27/09 · Trabalho até 17/10 · Prova 26/09–03/10',
            'data_inicio' => '2026-08-31',
            'data_fim' => '2026-09-27',
            'data_prova' => '2026-10-03',
            'ordem' => 2,
            'unidades' => [
                [
                    'titulo' => 'Introdução a Linguagem Python',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'A Linguagem Python', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Estruturas Condicionais em Python', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Estruturas de Repetição em Python', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Funções em Python', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Introdução a Linguagem Python (Revisão)', 'tipo' => 'aula', 'ordem' => 5],
                        ['titulo' => 'Exercício de unidades', 'tipo' => 'exercicio', 'ordem' => 6],
                        ['titulo' => 'Atividade prática online', 'tipo' => 'exercicio', 'ordem' => 7],
                    ],
                ],
                [
                    'titulo' => 'Explorando Recursos do Python',
                    'ordem' => 2,
                    'itens' => [
                        ['titulo' => 'Estruturas de Dados em Python - Parte I', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Estruturas de Dados em Python - Parte II', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Classes e Métodos em Python', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Bibliotecas e Módulos em Python', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Explorando Recursos do Python (Revisão)', 'tipo' => 'aula', 'ordem' => 5],
                        ['titulo' => 'Exercício de unidades', 'tipo' => 'exercicio', 'ordem' => 6],
                        ['titulo' => 'Atividade prática online', 'tipo' => 'exercicio', 'ordem' => 7],
                    ],
                ],
                [
                    'titulo' => 'Introdução à Análise de Dados com Python',
                    'ordem' => 3,
                    'itens' => [
                        ['titulo' => 'Aplicação de Banco de Dados com Python', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Introdução a Biblioteca Pandas', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Introdução a manipulação de dados em Pandas', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Visualização de Dados em Python', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Introdução à Análise de Dados com Python (Revisão)', 'tipo' => 'aula', 'ordem' => 5],
                        ['titulo' => 'Exercício de unidades', 'tipo' => 'exercicio', 'ordem' => 6],
                        ['titulo' => 'Atividade prática online', 'tipo' => 'exercicio', 'ordem' => 7],
                    ],
                ],
                [
                    'titulo' => 'Aplicações com Python',
                    'ordem' => 4,
                    'itens' => [
                        ['titulo' => 'Introdução à Programação Web com Python', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Introdução a Programação Mobile com Python', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Testes com Python', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Machine Learning com Python', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Aplicações com Python (Revisão)', 'tipo' => 'aula', 'ordem' => 5],
                        ['titulo' => 'Exercício de unidades', 'tipo' => 'exercicio', 'ordem' => 6],
                        ['titulo' => 'Atividade prática online', 'tipo' => 'exercicio', 'ordem' => 7],
                    ],
                ],
            ],
        ],
        [
            'key' => 'aoc',
            'nome' => 'Arquitetura e Organização de Computadores',
            'descricao' => 'Semanas 1–3 · 01/09 – 21/09 · alimenta o Passo 5 do Síntese',
            'data_inicio' => '2026-09-01',
            'data_fim' => '2026-09-21',
            'ordem' => 3,
            'unidades' => [
                [
                    'titulo' => 'Sistemas Numéricos: Conceitos e Representação',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Sistemas Numéricos: Conceitos, Simbologia e Representação de Base Numérica', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Conversão entre Bases Numéricas: Decimal', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Conversão entre Bases Numéricas: Binário', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Conversão entre Bases Numéricas: Octal', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Fundamentos de Sistemas Computacionais',
                    'ordem' => 2,
                    'itens' => [
                        ['titulo' => 'Conceitos Básicos de Arquitetura e Organização de Computadores', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Desenvolvimento Histórico', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'A estrutura Básica de um Computador', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'A Hierarquia de Níveis de Computador', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Componentes Básicos de um Computador',
                    'ordem' => 3,
                    'itens' => [
                        ['titulo' => 'Unidade de Processamento (CPU)', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Memória Principal e Memória Cache', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Memória Secundária', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Dispositivos de Entrada e Saída', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Instruções e Modos de Endereçamento',
                    'ordem' => 4,
                    'itens' => [
                        ['titulo' => 'Instruções e Formatos de Instruções', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Modos de Endereçamento', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Conjunto de Instruções RISC e CISC', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Execução de Instruções e Ciclo de Instrução', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
            ],
        ],
        [
            'key' => 'rc',
            'nome' => 'Redes de Computadores',
            'descricao' => 'Semanas 3–5 · 15/09 – 05/10 · alimenta o Passo 3 do Síntese',
            'data_inicio' => '2026-09-15',
            'data_fim' => '2026-10-05',
            'ordem' => 4,
            'unidades' => [
                [
                    'titulo' => 'Introdução às Redes de Computadores',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Conceitos Básicos de Redes e Topologias', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Modelos de Referência: OSI e TCP/IP', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Meios de Transmissão e Cabeamento', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Dispositivos de Rede: Hubs, Switches e Roteadores', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Camada de Rede e Endereçamento IP',
                    'ordem' => 2,
                    'itens' => [
                        ['titulo' => 'Protocolo IP (IPv4 e IPv6)', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Endereçamento e Sub-redes', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Roteamento Estático e Dinâmico', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Protocolos de Roteamento (RIP, OSPF, BGP)', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Camada de Transporte e Aplicação',
                    'ordem' => 3,
                    'itens' => [
                        ['titulo' => 'Protocolos TCP e UDP', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Controle de Fluxo e de Congestionamento', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Serviços de Rede: DNS, DHCP, HTTP, FTP', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Segurança na Camada de Transporte (TLS/SSL)', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Gerenciamento e Segurança em Redes',
                    'ordem' => 4,
                    'itens' => [
                        ['titulo' => 'Conceitos de Segurança em Redes e Firewalls', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'VPNs e Criptografia', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Monitoramento e Diagnóstico de Redes', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Tendências em Redes (SDN, Nuvem e IoT)', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
            ],
        ],
        [
            'key' => 'iu',
            'nome' => 'Interface e Usabilidade',
            'descricao' => 'Semanas 5–7 · 29/09 – 19/10 · alimenta o Passo 4 do Síntese',
            'data_inicio' => '2026-09-29',
            'data_fim' => '2026-10-19',
            'ordem' => 5,
            'unidades' => [
                [
                    'titulo' => 'Fundamentos de IHC',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Conceitos de Usabilidade, Acessibilidade e UX', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Princípios de Design de Interfaces', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Modelos Mentais e Heurísticas de Nielsen', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Design Centrado no Usuário', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Pesquisa com Usuários e Personas',
                    'ordem' => 2,
                    'itens' => [
                        ['titulo' => 'Métodos de Pesquisa Quantitativa e Qualitativa', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Criação de Personas e Jornadas do Usuário', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Arquitetura de Informação e Card Sorting', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Definição de Fluxos de Tarefa', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Prototipação e Design Visual',
                    'ordem' => 3,
                    'itens' => [
                        ['titulo' => 'Tipos de Protótipos: Baixa, Média e Alta Fidelidade', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Ferramentas de Prototipação (Figma)', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Sistemas de Design (Design Systems) e Guia de Estilos', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Microinterações e Feedback Visual', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Avaliação de Usabilidade e Testes',
                    'ordem' => 4,
                    'itens' => [
                        ['titulo' => 'Métodos de Avaliação: Heurística e Testes de Usabilidade', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Planejamento e Execução de Testes com Usuários', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Métricas de Usabilidade (SUS, NPS, Tempo de Tarefa)', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Acessibilidade Digital (WCAG) e Boas Práticas', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
            ],
        ],
        [
            'key' => 'sa',
            'nome' => 'Segurança e Auditoria de Sistemas',
            'descricao' => 'Semanas 7–9 · 13/10 – 02/11 · alimenta o Passo 2 do Síntese',
            'data_inicio' => '2026-10-13',
            'data_fim' => '2026-11-02',
            'ordem' => 6,
            'unidades' => [
                [
                    'titulo' => 'Fundamentos de Segurança da Informação',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Princípios da Segurança: Confidencialidade, Integridade, Disponibilidade', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Ameaças, Vulnerabilidades e Riscos', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Políticas de Segurança da Informação (PSI)', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Normas e Padrões (ISO/IEC 27001, LGPD)', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Criptografia e Controle de Acesso',
                    'ordem' => 2,
                    'itens' => [
                        ['titulo' => 'Criptografia Simétrica e Assimétrica', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Funções Hash e Assinatura Digital', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Mecanismos de Autenticação (MFA, Biometria)', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Controle de Acesso Baseado em Papéis (RBAC)', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Segurança de Redes e Aplicações',
                    'ordem' => 3,
                    'itens' => [
                        ['titulo' => 'Segurança de Perímetro (Firewalls, IDS/IPS)', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Principais Vulnerabilidades Web (OWASP Top 10)', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Segurança no Desenvolvimento de Software (DevSecOps)', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Gestão de Incidentes e Resposta a Ataques', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
                [
                    'titulo' => 'Auditoria e Conformidade de Sistemas',
                    'ordem' => 4,
                    'itens' => [
                        ['titulo' => 'Conceitos e Tipos de Auditoria de TI', 'tipo' => 'aula', 'ordem' => 1],
                        ['titulo' => 'Trilhas de Auditoria e Gestão de Logs', 'tipo' => 'aula', 'ordem' => 2],
                        ['titulo' => 'Ferramentas e Técnicas de Auditoria', 'tipo' => 'aula', 'ordem' => 3],
                        ['titulo' => 'Relatórios de Auditoria e Planos de Ação', 'tipo' => 'aula', 'ordem' => 4],
                        ['titulo' => 'Encerramento da Unidade', 'tipo' => 'aula', 'ordem' => 5],
                    ],
                ],
            ],
        ],
        [
            'key' => 'sintese',
            'nome' => 'Projeto Síntese',
            'descricao' => 'Semanas 10–11 · 03/11 – 16/11 · Cenário CAMC (mel e chia)',
            'data_inicio' => '2026-11-03',
            'data_fim' => '2026-11-16',
            'ordem' => 7,
            'unidades' => [
                [
                    'titulo' => 'Passos do Projeto Síntese',
                    'ordem' => 1,
                    'itens' => [
                        ['titulo' => 'Passo 1 · Projeto de Software: Backlog do CRM no Trello (funcionalidades + print)', 'tipo' => 'tarefa', 'ordem' => 1],
                        ['titulo' => 'Passo 2 · Segurança e Auditoria: revisar conteúdo já visto + Plano de Segurança (acessos, backup, auditoria)', 'tipo' => 'tarefa', 'ordem' => 2],
                        ['titulo' => 'Passo 3 · Redes de Computadores: revisar conteúdo já visto + Plano de topologia/rede entre as unidades', 'tipo' => 'tarefa', 'ordem' => 3],
                        ['titulo' => 'Passo 4 · Interface e Usabilidade: Wireframe da tela de feedback (máx. 3 cliques)', 'tipo' => 'tarefa', 'ordem' => 4],
                        ['titulo' => 'Passo 5 · Arquitetura de Computadores: Plano de arquitetura do servidor (CPU, RAM, RAID)', 'tipo' => 'tarefa', 'ordem' => 5],
                        ['titulo' => 'Consolidar tudo no template ABNT, revisar coerência entre os 5 passos e postar no AVA', 'tipo' => 'tarefa', 'ordem' => 6],
                    ],
                ],
            ],
        ],
    ];

    foreach ($users as $user) {
        $userId = (int) $user['id'];
        echo "\nProcessando usuário ID {$userId} ({$user['nome']})...\n";

        // 3. Cria ou obtém o semestre 2026.2
        $stmtSem = $db->prepare("SELECT id FROM semestres WHERE usuario_id = ? AND ano = ? LIMIT 1");
        $stmtSem->execute([$userId, 2026]);
        $semesterId = $stmtSem->fetchColumn();

        if (!$semesterId) {
            $stmtNewSem = $db->prepare("INSERT INTO semestres (usuario_id, nome, ano, data_inicio, data_fim) VALUES (?, ?, ?, ?, ?)");
            $stmtNewSem->execute([$userId, '2º Semestre 2026', 2026, '2026-08-01', '2026-12-12']);
            $semesterId = (int) $db->lastInsertId();
            echo "  + Criado Semestre ID {$semesterId} ('2º Semestre 2026')\n";
        } else {
            $semesterId = (int) $semesterId;
            echo "  * Semestre existente ID {$semesterId}\n";
        }

        // 4. Insere as disciplinas
        foreach ($disciplinasData as $d) {
            $stmtDisc = $db->prepare("SELECT id FROM disciplinas WHERE semestre_id = ? AND nome = ? LIMIT 1");
            $stmtDisc->execute([$semesterId, $d['nome']]);
            $disciplineId = $stmtDisc->fetchColumn();

            if (!$disciplineId) {
                $stmtNewDisc = $db->prepare(
                    "INSERT INTO disciplinas (semestre_id, nome, descricao, data_inicio, data_fim, data_prova, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $stmtNewDisc->execute([
                    $semesterId,
                    $d['nome'],
                    $d['descricao'] ?? null,
                    $d['data_inicio'] ?? null,
                    $d['data_fim'] ?? null,
                    $d['data_prova'] ?? null,
                    $d['ordem']
                ]);
                $disciplineId = (int) $db->lastInsertId();
                echo "    + Disciplina: '{$d['nome']}' (ID {$disciplineId})\n";
            } else {
                $disciplineId = (int) $disciplineId;
                echo "    * Disciplina existente: '{$d['nome']}' (ID {$disciplineId})\n";
            }

            // 5. Insere as unidades da disciplina
            foreach ($d['unidades'] as $u) {
                $stmtUnit = $db->prepare("SELECT id FROM unidades WHERE disciplina_id = ? AND titulo = ? LIMIT 1");
                $stmtUnit->execute([$disciplineId, $u['titulo']]);
                $unitId = $stmtUnit->fetchColumn();

                if (!$unitId) {
                    $stmtNewUnit = $db->prepare("INSERT INTO unidades (disciplina_id, titulo, ordem) VALUES (?, ?, ?)");
                    $stmtNewUnit->execute([$disciplineId, $u['titulo'], $u['ordem']]);
                    $unitId = (int) $db->lastInsertId();
                    echo "      + Unidade: '{$u['titulo']}' (ID {$unitId})\n";
                } else {
                    $unitId = (int) $unitId;
                }

                // 6. Insere os itens de estudo
                foreach ($u['itens'] as $item) {
                    $stmtItem = $db->prepare("SELECT id FROM itens_estudo WHERE unidade_id = ? AND titulo = ? LIMIT 1");
                    $stmtItem->execute([$unitId, $item['titulo']]);
                    $itemId = $stmtItem->fetchColumn();

                    if (!$itemId) {
                        $stmtNewItem = $db->prepare("INSERT INTO itens_estudo (unidade_id, titulo, tipo, ordem) VALUES (?, ?, ?, ?)");
                        $stmtNewItem->execute([$unitId, $item['titulo'], $item['tipo'], $item['ordem']]);
                    }
                }
            }
        }
    }

    $db->commit();
    echo "\n====================================================\n";
    echo "  MIGRAÇÃO CONCLUÍDA COM SUCESSO NO BANCO DE DADOS! \n";
    echo "====================================================\n";

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "\n[ERRO NA MIGRAÇÃO]: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " (Linha " . $e->getLine() . ")\n";
    exit(1);
}
