<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Login · Painel de Estudos') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('/assets/css/style.css') ?>">
</head>

<body class="auth-page">
  <div class="stars"></div>

  <div class="auth-wrap">
    <!-- Header / Branding Lamp -->
    <header class="auth-header">
      <div class="eyebrow">Acesso ao Sistema &middot; Semestre Letivo</div>
      <div class="lamp auth-lamp">
        <div class="auth-icon-badge">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
            <line x1="9" y1="7" x2="15" y2="7"></line>
            <line x1="9" y1="11" x2="13" y2="11"></line>
          </svg>
        </div>
      </div>
      <h1>Painel de Estudos</h1>
      <p class="auth-subtitle">Entre com suas credenciais para acompanhar seu cronograma, metas e entregas.</p>
    </header>

    <!-- Login Card -->
    <div class="auth-card">
      <div class="auth-card-top">
        <span class="auth-card-title">Identificação do Aluno</span>
        <span class="auth-badge">2026.2</span>
      </div>

      <!-- Feedback / Alert message container -->
      <div id="authAlert" class="auth-alert hidden" role="alert">
        <div class="alert-icon"></div>
        <div class="alert-message" id="alertMessage"></div>
      </div>

      <form id="loginForm" novalidate>
        <!-- Email / User Field -->
        <div class="form-group">
          <label for="email" class="form-label">
            <span>E-mail institucional ou Usuário</span>
          </label>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2" />
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
              </svg>
            </span>
            <input type="email" id="email" name="email" class="form-input"
              placeholder="exemplo@faculdade.edu.br" autocomplete="username" required>
          </div>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <div class="form-label-row">
            <label for="password" class="form-label">
              <span>Senha de Acesso</span>
            </label>
            <a href="#" id="forgotPasswordBtn" class="link-subtle" tabindex="-1">Esqueceu?</a>
          </div>
          <div class="input-wrapper">
            <span class="input-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
            </span>
            <input type="password" id="password" name="password" class="form-input"
              placeholder="Digite sua senha" autocomplete="current-password" required>
            <button type="button" id="togglePasswordBtn" class="toggle-password-btn" aria-label="Mostrar ou ocultar senha" title="Mostrar/Ocultar senha">
              <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Options: Remember Me -->
        <div class="auth-options">
          <label class="custom-checkbox-container">
            <input type="checkbox" id="rememberMe" name="rememberMe" checked>
            <span class="custom-checkbox-box">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 12l6 6L20 6" stroke="#0B0F1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            <span class="custom-checkbox-label">Lembrar neste navegador</span>
          </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="submitBtn" class="auth-submit-btn">
          <span class="btn-text">Entrar no Painel</span>
          <span class="btn-spinner hidden" aria-hidden="true"></span>
          <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"></path>
            <path d="m12 5 7 7-7 7"></path>
          </svg>
        </button>
      </form>

      <!-- Quick Demo Access / Auto-fill button for testing -->
      <div class="auth-demo-box">
        <div class="demo-box-header">
          <span class="demo-label">Atalho rápido (Demonstração)</span>
          <button type="button" id="fillDemoBtn" class="demo-fill-btn">Preencher dados demo</button>
        </div>
        <p class="demo-tip">Suporta autenticação via sessão PHP ou entrada com credenciais cadastradas.</p>
      </div>
    </div>

    <!-- Back to panel or support link -->
    <div class="auth-footer-nav">
      <a href="<?= base_url('/dashboard') ?>" class="nav-back-link">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
        Acessar painel como convidado
      </a>
      <span class="nav-divider">&middot;</span>
      <span class="support-text">Ajuda com acesso? Contate o suporte</span>
    </div>
  </div>

  <footer class="auth-page-footer">
    <div>Painel de Estudos &middot; Gestão de Aulas e Atividades</div>
    <div class="version-tag">v2.0 &bull; 2026</div>
  </footer>

  <script>
    window.APP_BASE_URL = "<?= rtrim(base_url('/'), '/') ?>";
  </script>
  <script src="<?= base_url('/assets/js/login.js') ?>"></script>
</body>

</html>
