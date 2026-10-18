<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Painel de Estudos') ?></title>
    <link rel="icon" href="<?= base_url('/assets/images/study.png') ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('/assets/css/style.css') ?>">
</head>

<body>
    <div class="stars"></div>
    <div class="wrap">
        <div class="top-nav-bar">
            <div class="user-status <?= !empty($user) ? 'logged-in' : '' ?>" id="userStatus">
                <span class="user-dot"></span>
                <span id="userNameDisplay"><?= !empty($user['nome']) ? htmlspecialchars($user['nome']) : 'Modo Convidado' ?></span>
            </div>
            <div class="nav-links">
                <a href="<?= base_url('/ajuda') ?>" class="settings-toggle help-toggle" aria-label="Ajuda" title="Ajuda">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3" />
                        <path d="M12 17h.01" />
                    </svg>
                </a>
                <div class="settings-menu-container">
                    <button type="button" id="settingsToggle" class="settings-toggle" aria-label="Opções" title="Opções"
                        aria-haspopup="true" aria-expanded="false" aria-controls="settingsMenu">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3" />
                            <path d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V4a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2.9Z" />
                        </svg>
                    </button>
                    <div id="settingsMenu" class="settings-menu hidden" role="menu" aria-labelledby="settingsToggle">
                        <button type="button" class="reset-btn settings-menu-item" id="resetBtn" role="menuitem">Reiniciar progresso</button>
                        <?php if (!empty($user)): ?>
                            <button type="button" class="nav-btn settings-menu-item" id="btnOpenSemesterManager" role="menuitem">Editar semestres</button>
                        <?php endif; ?>
                        <a href="<?= base_url(!empty($user) ? '/logout' : '/login') ?>" id="authNavBtn" class="nav-btn settings-menu-item"
                            title="<?= !empty($user) ? 'Encerrar sessão' : 'Entrar no sistema' ?>" role="menuitem">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php if (!empty($user)): ?>
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                <?php else: ?>
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                                    <polyline points="10 17 15 12 10 7" />
                                    <line x1="15" y1="12" x2="3" y2="12" />
                                <?php endif; ?>
                            </svg>
                            <span id="authNavText"><?= !empty($user) ? 'Sair' : 'Entrar' ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <header>
            <div class="eyebrow">Rotina de estudo</div>
            <div class="lamp">
                <div class="countdown-num" id="countdownNum">--</div>
                <div class="countdown-label" id="countdownLabel">carregando semestre...</div>
            </div>
            <h1>Painel de Estudos</h1>
            <div class="deadline-date" id="semesterDates">Carregando datas do semestre...</div>
        </header>

        <?php if (!empty($user)): ?>
            <!-- Barra de Ações do Usuário Logado -->
            <div class="admin-action-bar">
                <div class="semester-selector-group">
                    <label for="semesterSelect">Semestre:</label>
                    <select id="semesterSelect" class="custom-select">
                        <option value="">Carregando semestres...</option>
                    </select>
                </div>
                <div class="admin-action-btns">
                    <button type="button" id="btnOpenModalSemester" class="action-pill-btn secondary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Novo Semestre
                    </button>
                    <button type="button" id="btnOpenModalDiscipline" class="action-pill-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        Nova Matéria / Aulas
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="overall-card">
            <div class="overall-top">
                <span class="label">Progresso geral</span>
                <span class="pct" id="overallPct">0%</span>
            </div>
            <div class="bar-track">
                <div class="bar-fill" id="overallBar" style="width:0%"></div>
            </div>
        </div>

        <?php if (!empty($user)): ?>
            <button type="button" id="examSummaryCard" class="exam-summary-card">
                <div class="exam-summary-title">Provas do semestre</div>
                <div class="exam-summary-stats">
                    <span><strong id="scheduledExamCount">0</strong> marcadas</span>
                    <span><strong id="completedExamCount">0</strong> finalizadas</span>
                </div>
            </button>
        <?php endif; ?>

        <div id="subjects"></div>
    </div>

    <?php if (!empty($user)): ?>
        <!-- ─── MODAL: PROVAS DO SEMESTRE ───────────────────────────────────── -->
        <div id="modalExams" class="modal-backdrop hidden">
            <div class="modal exam-modal">
                <div class="modal-header">
                    <h2>Provas do semestre</h2>
                    <button type="button" class="close-btn" data-close-modal="modalExams" aria-label="Fechar">&times;</button>
                </div>
                <p class="exam-modal-intro">Consulte e atualize a data, o horário e o resultado das provas das matérias deste semestre.</p>
                <div id="examList" class="exam-list"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($user)): ?>
        <!-- ─── MODAL: EDITAR SEMESTRES ─────────────────────────────────────── -->
        <div id="modalManageSemesters" class="modal-backdrop hidden">
            <div class="modal" style="max-width: 560px;">
                <div class="modal-header">
                    <h2 id="semesterManagerTitle">Editar Semestres</h2>
                    <button type="button" class="close-btn" data-close-modal="modalManageSemesters">&times;</button>
                </div>
                <div id="semesterListView">
                    <div id="semesterManagerList" class="semester-manager-list"></div>
                </div>
                <form id="formEditSemester" class="hidden">
                    <input type="hidden" id="inputEditingSemesterId">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="inputEditSemesterName" class="form-label">Nome do semestre *</label>
                        <input type="text" id="inputEditSemesterName" class="form-input" maxlength="100" required>
                    </div>
                    <div class="form-grid" style="margin-bottom: 14px;">
                        <div class="field">
                            <label for="inputEditSemesterYear">Ano letivo *</label>
                            <input type="number" id="inputEditSemesterYear" min="1" required>
                        </div>
                        <div class="field">
                            <label for="inputEditSemesterStart">Data de início</label>
                            <input type="date" id="inputEditSemesterStart">
                        </div>
                    </div>
                    <div class="field" style="margin-bottom: 18px;">
                        <label for="inputEditSemesterEnd">Data fim / entrega</label>
                        <input type="date" id="inputEditSemesterEnd">
                    </div>
                    <div class="modal-actions">
                        <button type="button" id="btnBackSemesterList" class="secondary-btn">Voltar à lista</button>
                        <button type="submit" id="btnSaveSemester" class="primary-btn">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ─── MODAL: NOVO SEMESTRE ──────────────────────────────────────────────── -->
    <div id="modalSemester" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 480px;">
            <div class="modal-header">
                <h2>Novo Semestre</h2>
                <button type="button" class="close-btn" data-close-modal="modalSemester">&times;</button>
            </div>
            <form id="formNewSemester">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Nome do Semestre *</label>
                    <input type="text" id="inputSemesterName" class="form-input" placeholder="Ex: 1º Semestre 2027" required>
                </div>
                <div class="form-grid" style="margin-bottom: 14px;">
                    <div class="field">
                        <label>Ano Letivo *</label>
                        <input type="number" id="inputSemesterYear" value="2027" min="2020" max="2035" required>
                    </div>
                    <div class="field">
                        <label>Data Início</label>
                        <input type="date" id="inputSemesterStart">
                    </div>
                </div>
                <div class="field" style="margin-bottom: 18px;">
                    <label>Data Fim / Entrega</label>
                    <input type="date" id="inputSemesterEnd">
                </div>
                <div class="modal-actions">
                    <button type="button" class="secondary-btn" data-close-modal="modalSemester">Cancelar</button>
                    <button type="submit" class="primary-btn">Criar Semestre</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ─── MODAL: NOVA DISCIPLINA COM UNIDADES E AULAS ────────────────────────── -->
    <div id="modalDiscipline" class="modal-backdrop hidden">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalDisciplineTitle">Cadastrar Matéria / Aulas</h2>
                <button type="button" class="close-btn" data-close-modal="modalDiscipline">&times;</button>
            </div>
            <form id="formNewDiscipline">
                <div class="form-grid">
                    <div class="field">
                        <label>Vincular ao Semestre *</label>
                        <select id="selectDisciplineSemester" required></select>
                    </div>
                    <div class="field">
                        <label>Nome da Matéria *</label>
                        <input type="text" id="inputDiscName" placeholder="Ex: Banco de Dados II" required>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Fase / Descrição Curta</label>
                        <input type="text" id="inputDiscDesc" placeholder="Ex: Estudo 15/09–10/10 · Prova 20/10">
                    </div>
                    <div class="field">
                        <label>Data da Prova</label>
                        <input type="date" id="inputDiscExam">
                    </div>
                </div>

                <div id="disciplineResultControls" class="discipline-result-controls hidden">
                    <div>
                        <strong>Resultado da matéria</strong>
                        <p id="disciplineResultHelp">Conclua todas as aulas, exercícios e a prova para registrar o resultado.</p>
                    </div>
                    <div class="discipline-result-buttons">
                        <button type="button" class="result-btn result-approved" data-discipline-result="aprovado" disabled>Aprovado</button>
                        <button type="button" class="result-btn result-failed" data-discipline-result="reprovado" disabled>Reprovado</button>
                    </div>
                </div>

                <div class="units-header">
                    <h3>Unidades e Aulas / Itens</h3>
                    <button type="button" id="btnAddUnitRow" class="add-btn">+ Adicionar Unidade</button>
                </div>

                <div id="unitsFormContainer" class="units-container">
                    <!-- Unidades adicionadas dinamicamente via JS -->
                </div>

                <input type="hidden" id="inputEditingDiscId" value="">

                <div class="modal-actions" style="justify-content: space-between;">
                    <button type="button" id="btnDeleteCurrentDiscipline" class="danger-btn hidden">Excluir Matéria</button>
                    <div style="display: flex; gap: 10px; margin-left: auto;">
                        <button type="button" class="secondary-btn" data-close-modal="modalDiscipline">Cancelar</button>
                        <button type="submit" id="btnSaveDiscipline" class="primary-btn">Salvar Matéria</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div id="loadStatus">carregando progresso salvo...</div>
        <div>Painel de Estudos &middot; Gestão de Aulas e Atividades</div>
        <div class="version-tag">v2.0 &bull; 2026</div>
    </footer>

    <script>
        window.APP_BASE_URL = "<?= rtrim(base_url('/'), '/') ?>";
        window.CURRENT_USER = <?= json_encode($user ?? null, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="<?= base_url('/assets/js/script.js') ?>"></script>
</body>

</html>