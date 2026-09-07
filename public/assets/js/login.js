/**
 * Login Logic & API Integration — Painel de Estudos MVC
 */

const BASE_URL = window.APP_BASE_URL || "";
const API_BASE_URL = BASE_URL ? `${BASE_URL}/api` : "/api";
const AUTH_STORAGE_KEY = "estudos_auth_user";

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const togglePasswordBtn = document.getElementById("togglePasswordBtn");
  const eyeIcon = document.getElementById("eyeIcon");
  const rememberCheckbox = document.getElementById("rememberMe");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = submitBtn.querySelector(".btn-text");
  const btnSpinner = submitBtn.querySelector(".btn-spinner");
  const btnArrow = submitBtn.querySelector(".btn-arrow");
  const authAlert = document.getElementById("authAlert");
  const alertMessage = document.getElementById("alertMessage");
  const fillDemoBtn = document.getElementById("fillDemoBtn");
  const forgotPasswordBtn = document.getElementById("forgotPasswordBtn");

  // Restaura e-mail salvo quando "Lembrar neste navegador" foi usado antes
  try {
    const savedEmail = localStorage.getItem("estudos_remembered_email");
    if (savedEmail) {
      emailInput.value = savedEmail;
      rememberCheckbox.checked = true;
      passwordInput.focus();
    } else {
      emailInput.focus();
    }
  } catch (e) {
    console.warn("localStorage indisponível:", e);
  }

  // Toggle de visibilidade da senha
  togglePasswordBtn.addEventListener("click", () => {
    const isPassword = passwordInput.getAttribute("type") === "password";
    passwordInput.setAttribute("type", isPassword ? "text" : "password");

    if (isPassword) {
      eyeIcon.innerHTML = `
        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
        <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
        <line x1="2" x2="22" y1="2" y2="22"/>
      `;
    } else {
      eyeIcon.innerHTML = `
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
        <circle cx="12" cy="12" r="3" />
      `;
    }
  });

  // Botão de preenchimento rápido para demonstração
  fillDemoBtn.addEventListener("click", () => {
    emailInput.value = "aluno@faculdade.edu.br";
    passwordInput.value = "senha123";
    clearAlert();
    showAlert("info", "Credenciais de demonstração preenchidas. Clique em 'Entrar no Painel'.");
  });

  // Link "Esqueceu?"
  forgotPasswordBtn.addEventListener("click", (e) => {
    e.preventDefault();
    showAlert("info", "Para redefinir sua senha, entre em contato com a secretaria do curso.");
  });

  // Helpers de alerta
  function showAlert(type, message) {
    authAlert.className = `auth-alert ${type}`;
    alertMessage.textContent = message;
    authAlert.classList.remove("hidden");
  }

  function clearAlert() {
    authAlert.classList.add("hidden");
    authAlert.className = "auth-alert hidden";
    alertMessage.textContent = "";
  }

  function setLoading(loading) {
    submitBtn.disabled = loading;
    if (loading) {
      btnText.textContent = "Autenticando...";
      btnSpinner.classList.remove("hidden");
      btnArrow.classList.add("hidden");
      submitBtn.classList.add("loading");
    } else {
      btnText.textContent = "Entrar no Painel";
      btnSpinner.classList.add("hidden");
      btnArrow.classList.remove("hidden");
      submitBtn.classList.remove("loading");
    }
  }

  // Validação básica do formulário
  function validateForm() {
    clearAlert();
    const email = emailInput.value.trim();
    const password = passwordInput.value;

    if (!email) {
      showAlert("error", "Por favor, informe seu e-mail institucional ou usuário.");
      emailInput.focus();
      return false;
    }

    if (!password) {
      showAlert("error", "Por favor, digite sua senha de acesso.");
      passwordInput.focus();
      return false;
    }

    return { email, password };
  }

  // Envio do formulário
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = validateForm();
    if (!data) return;

    setLoading(true);

    try {
      // Tenta a API local do MVC
      const endpoint = `${BASE_URL}/auth/login`;
      const response = await fetch(endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
        },
        credentials: "same-origin",
        body: JSON.stringify({
          email: data.email,
          senha: data.password,
        }),
      });

      const resData = await response.json().catch(() => ({}));

      if (!response.ok) {
        throw new Error(resData.erro || "Falha na autenticação. Verifique suas credenciais.");
      }

      // Sucesso no login
      const userData = {
        id: resData.id,
        name: resData.nome || data.email.split("@")[0],
        email: resData.email || data.email,
        token: resData.id,
        loggedAt: new Date().toISOString(),
      };

      localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(userData));

      if (rememberCheckbox.checked) {
        localStorage.setItem("estudos_remembered_email", data.email);
      } else {
        localStorage.removeItem("estudos_remembered_email");
      }

      showAlert("success", `Bem-vindo(a), ${userData.name}! Redirecionando...`);

      setTimeout(() => {
        window.location.href = `${BASE_URL}/dashboard`;
      }, 700);

    } catch (err) {
      console.warn("Erro no login:", err);
      showAlert("error", err.message || "Não foi possível conectar ao servidor de autenticação.");
      setLoading(false);
    }
  });
});
