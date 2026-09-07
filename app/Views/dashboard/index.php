<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Painel de Estudos') ?></title>
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
        <?php if (!empty($user)): ?>
          <a href="<?= base_url('/logout') ?>" id="authNavBtn" class="nav-btn" title="Encerrar sessão">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
              <polyline points="16 17 21 12 16 7"/>
              <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span id="authNavText">Sair</span>
          </a>
        <?php else: ?>
          <a href="<?= base_url('/login') ?>" id="authNavBtn" class="nav-btn" title="Entrar no sistema">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
              <polyline points="10 17 15 12 10 7"/>
              <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            <span id="authNavText">Entrar</span>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <header>
      <div class="eyebrow">Rotina de estudo noturno &middot; 3–4h/dia</div>
      <div class="lamp">
        <div class="countdown-num" id="countdownNum">--</div>
        <div class="countdown-label" id="countdownLabel">dias até a entrega</div>
      </div>
      <h1>Painel de Estudos</h1>
      <div class="deadline-date">conteúdo (aulas) até 22/11 &middot; entrega final até 12/12/2026</div>
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
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Semestre
          </button>
          <button type="button" id="btnOpenModalDiscipline" class="action-pill-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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

    <div id="subjects"></div>
  </div>

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
        <h2 id="modalDisciplineTitle">Cadastrar Matéria & Aulas</h2>
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
    <div class="footer-actions">
      <button class="reset-btn" id="resetBtn">reiniciar progresso</button>
      <?php if (empty($user)): ?>
        <a href="<?= base_url('/login') ?>" class="secondary-btn" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">Área de Login</a>
      <?php endif; ?>
    </div>
  </footer>

  <script>
    window.APP_BASE_URL = "<?= rtrim(base_url('/'), '/') ?>";
    window.CURRENT_USER = <?= json_encode($user ?? null, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="<?= base_url('/assets/js/script.js') ?>"></script>
</body>

</html>
