const DEADLINE = new Date("2026-12-12T23:59:59");
const CONTEUDO_LIMITE = new Date("2026-11-22T23:59:59");

const BASE_URL = window.APP_BASE_URL || "";

// Dados locais padrão (usados apenas em modo Convidado / Demonstração)
const FALLBACK_DATA = [
  {
    id: "demo",
    key: "demo",
    nome: "Disciplina Demonstrativa (Modo Convidado)",
    descricao: "Exemplo para visualização · Faça login para acessar todas as suas disciplinas reais",
    unidades: [
      {
        id: "demo-u1",
        titulo: "Unidade 1 · Primeiros Passos",
        itens: [
          { id: "demo-i1", key: "demo:0", titulo: "Introdução à plataforma de estudos", tipo: "aula" },
          { id: "demo-i2", key: "demo:1", titulo: "Exemplo de aula interativa", tipo: "aula" },
          { id: "demo-i3", key: "demo:2", titulo: "Exercício prático de fixação", tipo: "exercicio" },
          { id: "demo-i4", key: "demo:3", titulo: "Faça login para gerenciar seus semestres e matérias", tipo: "tarefa" },
        ],
      },
    ],
  },
];

let appDisciplinas = [];
let appSemestres = [];
let activeSemesterId = null;
let state = {};
let isDbMode = false;
let openSubjects = {};
let pendingDeletedUnits = [];
let pendingDeletedItems = [];
const STORAGE_KEY = "estudos-progress-v1";

// ─── CARREGAMENTO DE DADOS ───────────────────────────────────────────────────

async function loadSemesters() {
  const semesterSelect = document.getElementById("semesterSelect");
  const modalSemesterSelect = document.getElementById("selectDisciplineSemester");

  if (!window.CURRENT_USER || !window.CURRENT_USER.id) {
    return;
  }

  try {
    const res = await fetch(`${BASE_URL}/semestres`, { credentials: "same-origin" });
    const data = await res.json();

    appSemestres = (data && data.semestres) ? data.semestres : [];

    if (semesterSelect) {
      semesterSelect.innerHTML = "";
      if (appSemestres.length === 0) {
        semesterSelect.innerHTML = '<option value="">Nenhum semestre cadastrado</option>';
      } else {
        appSemestres.forEach((s) => {
          const opt = document.createElement("option");
          opt.value = s.id;
          opt.textContent = `${s.nome} (${s.ano})`;
          semesterSelect.appendChild(opt);
        });
      }
    }

    if (modalSemesterSelect) {
      modalSemesterSelect.innerHTML = "";
      appSemestres.forEach((s) => {
        const opt = document.createElement("option");
        opt.value = s.id;
        opt.textContent = `${s.nome} (${s.ano})`;
        modalSemesterSelect.appendChild(opt);
      });
    }

    if (appSemestres.length > 0 && !activeSemesterId) {
      activeSemesterId = appSemestres[0].id;
      if (semesterSelect) semesterSelect.value = activeSemesterId;
    }
  } catch (e) {
    console.error("Erro ao carregar lista de semestres:", e);
  }
}

async function loadDataAndProgress() {
  const loadStatusEl = document.getElementById("loadStatus");

  // Modo autenticado: consome API do banco
  if (window.CURRENT_USER && window.CURRENT_USER.id) {
    try {
      await loadSemesters();

      if (activeSemesterId) {
        if (loadStatusEl) loadStatusEl.textContent = "carregando matérias do banco de dados...";
        const treeRes = await fetch(`${BASE_URL}/semestres/${activeSemesterId}`, { credentials: "same-origin" });
        const tree = await treeRes.json();

        if (tree && tree.disciplinas) {
          appDisciplinas = tree.disciplinas;
          isDbMode = true;

          // Mapeia o progresso retornado do banco
          state = {};
          appDisciplinas.forEach((d) => {
            (d.unidades || []).forEach((u) => {
              (u.itens || []).forEach((item) => {
                state[item.id] = !!item.concluido;
              });
            });
          });

          if (loadStatusEl) loadStatusEl.textContent = "sincronizado com o banco de dados";
          return;
        }
      }
    } catch (e) {
      console.warn("Erro ao buscar dados do banco, usando fallback local:", e);
    }
  }

  // Fallback para modo visitante
  appDisciplinas = FALLBACK_DATA;
  isDbMode = false;
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    state = raw ? JSON.parse(raw) : {};
  } catch (e) {
    state = {};
  }
  if (loadStatusEl) loadStatusEl.textContent = "progresso salvo automaticamente no navegador";
}

// ─── PROGRESSO E ITENS ───────────────────────────────────────────────────────

async function toggleItem(itemId) {
  state[itemId] = !state[itemId];
  const isDone = !!state[itemId];

  render();

  if (isDbMode && typeof itemId === "number") {
    try {
      const loadStatusEl = document.getElementById("loadStatus");
      if (loadStatusEl) loadStatusEl.textContent = "salvando no banco...";

      await fetch(`${BASE_URL}/itens-estudo/${itemId}/progresso`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify({ concluido: isDone }),
      });

      if (loadStatusEl) loadStatusEl.textContent = "sincronizado com o banco de dados";
    } catch (e) {
      console.error("Erro ao salvar progresso na API:", e);
    }
  } else {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch (e) {
      console.error("Erro ao salvar no storage local:", e);
    }
  }
}

async function deleteSubject(subjectId, subjectName) {
  if (!confirm(`Tem certeza que deseja excluir a matéria "${subjectName}" e todas as suas unidades e aulas?`)) {
    return;
  }

  try {
    const res = await fetch(`${BASE_URL}/disciplinas/${subjectId}`, {
      method: "DELETE",
      credentials: "same-origin",
    });

    if (res.ok) {
      const modalDisc = document.getElementById("modalDiscipline");
      if (modalDisc) modalDisc.classList.add("hidden");
      await loadDataAndProgress();
      render();
    } else {
      alert("Não foi possível excluir a matéria.");
    }
  } catch (e) {
    console.error("Erro ao excluir disciplina:", e);
  }
}

// ─── RENDERIZAÇÃO ────────────────────────────────────────────────────────────

function countdown() {
  const now = new Date();
  const diffMs = DEADLINE - now;
  const days = Math.max(0, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
  const numEl = document.getElementById("countdownNum");
  const labelEl = document.getElementById("countdownLabel");
  if (numEl) numEl.textContent = days;
  if (labelEl) {
    labelEl.textContent = days === 1 ? "dia até a entrega final" : "dias até a entrega final";
  }
}

function buildItemHtml(item) {
  const itemKey = isDbMode ? item.id : (item.key || item.id);
  const done = !!state[itemKey];
  const isExercicio = item.tipo === "exercicio";
  const isTarefa = item.tipo === "tarefa";
  const extraClass = isExercicio ? "exercicio" : (isTarefa ? "tarefa" : "");

  return `
    <div class="item ${extraClass} ${done ? "done" : ""}" data-item-id="${itemKey}">
      <div class="checkbox">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M4 12l6 6L20 6" stroke="#0B0F1E" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="item-text">${item.titulo || item.nome}</div>
    </div>
  `;
}

function subjectProgress(sub) {
  let total = 0;
  let done = 0;

  (sub.unidades || []).forEach((u) => {
    (u.itens || []).forEach((item) => {
      total++;
      const itemKey = isDbMode ? item.id : (item.key || item.id);
      if (state[itemKey]) done++;
    });
  });

  return { total, done };
}

function overallProgress() {
  let total = 0;
  let done = 0;

  appDisciplinas.forEach((sub) => {
    const p = subjectProgress(sub);
    total += p.total;
    done += p.done;
  });

  return { total, done };
}

function render() {
  countdown();

  const op = overallProgress();
  const pct = op.total ? Math.round((100 * op.done) / op.total) : 0;
  const pctEl = document.getElementById("overallPct");
  const barEl = document.getElementById("overallBar");
  if (pctEl) pctEl.textContent = pct + "%";
  if (barEl) barEl.style.width = pct + "%";

  const container = document.getElementById("subjects");
  if (!container) return;

  if (appDisciplinas.length === 0) {
    container.innerHTML = `
      <div style="text-align:center; padding: 40px 20px; background: var(--surface); border: 1px dashed var(--border); border-radius: 16px;">
        <h3 style="margin-top:0; color:var(--text);">Nenhuma matéria cadastrada neste semestre</h3>
        <p style="color:var(--text-dim); font-size:14px;">Clique em "+ Nova Matéria / Aulas" acima para adicionar suas disciplinas e cronograma.</p>
      </div>
    `;
    return;
  }

  let html = "";

  appDisciplinas.forEach((sub, subIdx) => {
    const subKey = String(sub.id || sub.key || subIdx);
    const p = subjectProgress(sub);
    const spct = p.total ? Math.round((100 * p.done) / p.total) : 0;
    const isOpen = !!openSubjects[subKey];

    html += `
      <div class="subject ${isOpen ? "open" : ""}" data-sub="${subKey}">
        <div class="subject-head" data-toggle="${subKey}">
          <div class="subject-head-row">
            <div>
              <div class="subject-title">${sub.nome}</div>
              <div class="subject-phase">${sub.descricao || ""}</div>
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
              <span class="subject-pct">${p.done}/${p.total}</span>
              ${isDbMode ? `
                <button type="button" class="edit-subject-btn" title="Editar ou Excluir Matéria" data-edit-sub="${sub.id}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                  Editar
                </button>
              ` : ""}
              <span class="chevron"></span>
            </div>
          </div>
          <div class="bar-track"><div class="bar-fill" style="width:${spct}%"></div></div>
        </div>
        <div class="subject-body" style="max-height:${isOpen ? "3500px" : "0"}">
    `;

    (sub.unidades || []).forEach((u) => {
      html += `
        <div class="unit">
          <div class="unit-title">${u.titulo || u.name}</div>
      `;
      (u.itens || []).forEach((item) => {
        html += buildItemHtml(item);
      });
      html += `</div>`;
    });

    html += `</div></div>`;
  });

  container.innerHTML = html;

  // Toggle card
  container.querySelectorAll("[data-toggle]").forEach((el) => {
    el.addEventListener("click", () => {
      const k = el.getAttribute("data-toggle");
      openSubjects[k] = !openSubjects[k];
      render();
    });
  });

  // Toggle item click
  container.querySelectorAll(".item").forEach((el) => {
    el.addEventListener("click", (e) => {
      e.stopPropagation();
      const rawId = el.getAttribute("data-item-id");
      const parsedId = !isNaN(Number(rawId)) ? Number(rawId) : rawId;
      toggleItem(parsedId);
    });
  });

  // Edit subject click
  container.querySelectorAll("[data-edit-sub]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const id = Number(btn.getAttribute("data-edit-sub"));
      openEditDisciplineModal(id);
    });
  });
}

// ─── GERENCIAMENTO DE MODAIS E FORMULÁRIOS ───────────────────────────────────

function openEditDisciplineModal(disciplineId) {
  const modalDisc = document.getElementById("modalDiscipline");
  const modalTitle = document.getElementById("modalDisciplineTitle");
  const inputEditId = document.getElementById("inputEditingDiscId");
  const btnDelete = document.getElementById("btnDeleteCurrentDiscipline");
  const selectSem = document.getElementById("selectDisciplineSemester");
  const inputName = document.getElementById("inputDiscName");
  const inputDesc = document.getElementById("inputDiscDesc");
  const inputExam = document.getElementById("inputDiscExam");
  const unitsContainer = document.getElementById("unitsFormContainer");

  const disc = appDisciplinas.find((d) => Number(d.id) === Number(disciplineId));
  if (!disc) return;

  // Limpa filas de exclusão pendente
  pendingDeletedUnits = [];
  pendingDeletedItems = [];

  modalTitle.textContent = `Editar Matéria: ${disc.nome}`;
  inputEditId.value = disc.id;
  btnDelete.classList.remove("hidden");

  if (selectSem) selectSem.value = activeSemesterId;
  inputName.value = disc.nome || "";
  inputDesc.value = disc.descricao || "";
  inputExam.value = disc.data_prova || "";

  // Carrega unidades e itens existentes com seus IDs
  unitsContainer.innerHTML = "";
  if (disc.unidades && disc.unidades.length > 0) {
    disc.unidades.forEach((u, uIdx) => {
      addUnitRowToForm(u.titulo || `Unidade ${uIdx + 1}`, u.itens || [], u.id);
    });
  } else {
    addUnitRowToForm("Unidade 1", []);
  }

  modalDisc.classList.remove("hidden");
  inputName.focus();
}

function initModals() {
  const btnOpenSemester = document.getElementById("btnOpenModalSemester");
  const btnOpenDisc = document.getElementById("btnOpenModalDiscipline");
  const modalSemester = document.getElementById("modalSemester");
  const modalDisc = document.getElementById("modalDiscipline");
  const btnDeleteDisc = document.getElementById("btnDeleteCurrentDiscipline");

  if (btnOpenSemester && modalSemester) {
    btnOpenSemester.addEventListener("click", () => {
      modalSemester.classList.remove("hidden");
      document.getElementById("inputSemesterName").focus();
    });
  }

  if (btnOpenDisc && modalDisc) {
    btnOpenDisc.addEventListener("click", () => {
      pendingDeletedUnits = [];
      pendingDeletedItems = [];
      document.getElementById("modalDisciplineTitle").textContent = "Cadastrar Matéria & Aulas";
      document.getElementById("inputEditingDiscId").value = "";
      if (btnDeleteDisc) btnDeleteDisc.classList.add("hidden");
      
      const formDisc = document.getElementById("formNewDiscipline");
      if (formDisc) formDisc.reset();

      const container = document.getElementById("unitsFormContainer");
      if (container) {
        container.innerHTML = "";
        addUnitRowToForm("Unidade 1", []);
      }

      if (document.getElementById("selectDisciplineSemester")) {
        document.getElementById("selectDisciplineSemester").value = activeSemesterId;
      }

      modalDisc.classList.remove("hidden");
      document.getElementById("inputDiscName").focus();
    });
  }

  // Ação de Excluir Matéria dentro do modal de edição
  if (btnDeleteDisc) {
    btnDeleteDisc.addEventListener("click", () => {
      const editId = Number(document.getElementById("inputEditingDiscId").value);
      const name = document.getElementById("inputDiscName").value;
      if (editId) {
        deleteSubject(editId, name);
      }
    });
  }

  // Fechamento genérico
  document.querySelectorAll("[data-close-modal]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modalId = btn.getAttribute("data-close-modal");
      const el = document.getElementById(modalId);
      if (el) el.classList.add("hidden");
    });
  });

  // Fecha clicando no backdrop
  [modalSemester, modalDisc].forEach((m) => {
    if (m) {
      m.addEventListener("click", (e) => {
        if (e.target === m) m.classList.add("hidden");
      });
    }
  });

  // Seletor de Semestre na toolbar
  const semesterSelect = document.getElementById("semesterSelect");
  if (semesterSelect) {
    semesterSelect.addEventListener("change", async (e) => {
      activeSemesterId = Number(e.target.value);
      await loadDataAndProgress();
      render();
    });
  }

  // ─── FORM: NOVO SEMESTRE ─────────────────────────────────────────────────
  const formSemester = document.getElementById("formNewSemester");
  if (formSemester) {
    formSemester.addEventListener("submit", async (e) => {
      e.preventDefault();
      const nome = document.getElementById("inputSemesterName").value.trim();
      const ano = Number(document.getElementById("inputSemesterYear").value);
      const data_inicio = document.getElementById("inputSemesterStart").value || null;
      const data_fim = document.getElementById("inputSemesterEnd").value || null;

      try {
        const res = await fetch(`${BASE_URL}/semestres`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: "same-origin",
          body: JSON.stringify({ nome, ano, data_inicio, data_fim }),
        });

        const data = await res.json();
        if (res.ok && data.id) {
          activeSemesterId = data.id;
          formSemester.reset();
          modalSemester.classList.add("hidden");
          await loadDataAndProgress();
          render();
        } else {
          alert(data.erro || "Erro ao cadastrar semestre.");
        }
      } catch (err) {
        console.error("Erro ao criar semestre:", err);
      }
    });
  }

  // ─── FORM: SALVAR DISCIPLINA (CRIAÇÃO OU EDIÇÃO) ───────────────────────────
  const btnAddUnit = document.getElementById("btnAddUnitRow");
  if (btnAddUnit) {
    btnAddUnit.addEventListener("click", () => {
      addUnitRowToForm();
    });
  }

  const formDiscipline = document.getElementById("formNewDiscipline");
  if (formDiscipline) {
    formDiscipline.addEventListener("submit", async (e) => {
      e.preventDefault();
      const saveBtn = document.getElementById("btnSaveDiscipline");
      if (saveBtn) saveBtn.disabled = true;

      const editIdRaw = document.getElementById("inputEditingDiscId").value;
      const isEditing = !!editIdRaw;
      const editDiscId = Number(editIdRaw);

      const semestre_id = Number(document.getElementById("selectDisciplineSemester").value);
      const nome = document.getElementById("inputDiscName").value.trim();
      const descricao = document.getElementById("inputDiscDesc").value.trim();
      const data_prova = document.getElementById("inputDiscExam").value || null;

      try {
        let discId = editDiscId;

        // 1. Executa exclusões pendentes de aulas e unidades que foram removidas no formulário
        for (const itId of pendingDeletedItems) {
          await fetch(`${BASE_URL}/itens-estudo/${itId}`, { method: "DELETE", credentials: "same-origin" });
        }
        for (const unId of pendingDeletedUnits) {
          await fetch(`${BASE_URL}/unidades/${unId}`, { method: "DELETE", credentials: "same-origin" });
        }

        if (isEditing) {
          // Atualiza dados da matéria existente
          const resDisc = await fetch(`${BASE_URL}/disciplinas/${editDiscId}`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ nome, descricao, data_prova }),
          });

          if (!resDisc.ok) {
            const errData = await resDisc.json();
            throw new Error(errData.erro || "Falha ao atualizar matéria.");
          }
        } else {
          // Cria nova matéria
          const resDisc = await fetch(`${BASE_URL}/disciplinas`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ semestre_id, nome, descricao, data_prova, ordem: appDisciplinas.length + 1 }),
          });

          const discData = await resDisc.json();
          if (!resDisc.ok || !discData.id) {
            throw new Error(discData.erro || "Falha ao criar matéria.");
          }
          discId = discData.id;
        }

        // 2. Processa Unidades e Aulas do formulário
        const unitCards = document.querySelectorAll(".unit-card-row");
        for (let uIdx = 0; uIdx < unitCards.length; uIdx++) {
          const uCard = unitCards[uIdx];
          const uTitleInput = uCard.querySelector(".unit-title-input");
          const uTitle = uTitleInput ? uTitleInput.value.trim() : `Unidade ${uIdx + 1}`;
          const existingUnitId = uCard.getAttribute("data-unit-id");

          let unitId = existingUnitId ? Number(existingUnitId) : null;

          if (!unitId) {
            const resUnit = await fetch(`${BASE_URL}/unidades`, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              credentials: "same-origin",
              body: JSON.stringify({ disciplina_id: discId, titulo: uTitle, ordem: uIdx + 1 }),
            });
            const unitData = await resUnit.json();
            if (resUnit.ok && unitData.id) unitId = unitData.id;
          } else {
            await fetch(`${BASE_URL}/unidades/${unitId}`, {
              method: "PATCH",
              headers: { "Content-Type": "application/json" },
              credentials: "same-origin",
              body: JSON.stringify({ titulo: uTitle, ordem: uIdx + 1 }),
            });
          }

          if (unitId) {
            const aulaRows = uCard.querySelectorAll(".aula-item-row");
            for (let aIdx = 0; aIdx < aulaRows.length; aIdx++) {
              const aRow = aulaRows[aIdx];
              const aInput = aRow.querySelector(".aula-title-input");
              const aTypeSelect = aRow.querySelector(".aula-type-select");
              const aTitle = aInput ? aInput.value.trim() : "";
              const aType = aTypeSelect ? aTypeSelect.value : "aula";
              const existingItemId = aRow.getAttribute("data-item-id");

              if (aTitle) {
                if (existingItemId) {
                  await fetch(`${BASE_URL}/itens-estudo/${existingItemId}`, {
                    method: "PATCH",
                    headers: { "Content-Type": "application/json" },
                    credentials: "same-origin",
                    body: JSON.stringify({ titulo: aTitle, tipo: aType, ordem: aIdx + 1 }),
                  });
                } else {
                  await fetch(`${BASE_URL}/itens-estudo`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    credentials: "same-origin",
                    body: JSON.stringify({ unidade_id: unitId, titulo: aTitle, tipo: aType, ordem: aIdx + 1 }),
                  });
                }
              }
            }
          }
        }

        formDiscipline.reset();
        document.getElementById("unitsFormContainer").innerHTML = "";
        modalDisc.classList.add("hidden");

        await loadDataAndProgress();
        render();

      } catch (err) {
        console.error("Erro ao salvar matéria:", err);
        alert(err.message || "Não foi possível salvar todos os dados da matéria.");
      } finally {
        if (saveBtn) saveBtn.disabled = false;
      }
    });
  }
}

function addUnitRowToForm(initialTitle = "", initialItens = [], unitId = null) {
  const container = document.getElementById("unitsFormContainer");
  if (!container) return;

  const unitIndex = container.children.length + 1;
  const unitCard = document.createElement("div");
  unitCard.className = "unit-card unit-card-row";
  if (unitId) unitCard.setAttribute("data-unit-id", unitId);

  const defaultTitle = initialTitle || `Unidade ${unitIndex}`;

  unitCard.innerHTML = `
    <div class="unit-card-header">
      <span>Unidade ${unitIndex}</span>
      <button type="button" class="remove-unit-btn" title="Remover Unidade">&times; Remover</button>
    </div>
    <div class="field" style="margin-bottom: 12px;">
      <input type="text" class="unit-title-input" placeholder="Título da Unidade" required value="${defaultTitle}">
    </div>
    <div class="aula-list"></div>
    <div class="unit-actions">
      <button type="button" class="add-btn add-aula-to-unit-btn">+ Adicionar Aula/Item</button>
    </div>
  `;

  const list = unitCard.querySelector(".aula-list");

  // Se já tiver itens passados, adiciona eles
  if (initialItens && initialItens.length > 0) {
    initialItens.forEach((item) => {
      addAulaRow(list, item.titulo || item.nome, item.tipo || "aula", item.id);
    });
  } else {
    addAulaRow(list, `Aula 1 · Conteúdo Inicial`, "aula");
  }

  // Remover unidade
  unitCard.querySelector(".remove-unit-btn").addEventListener("click", () => {
    if (unitId) {
      pendingDeletedUnits.push(unitId);
    }
    // Marca todos os itens filhos dessa unidade para exclusão também
    unitCard.querySelectorAll("[data-item-id]").forEach((el) => {
      const itId = Number(el.getAttribute("data-item-id"));
      if (itId) pendingDeletedItems.push(itId);
    });
    unitCard.remove();
  });

  // Adicionar novas aulas na unidade
  unitCard.querySelector(".add-aula-to-unit-btn").addEventListener("click", () => {
    const count = list.children.length + 1;
    addAulaRow(list, `Aula ${count}`, "aula");
  });

  container.appendChild(unitCard);
}

function addAulaRow(containerList, title = "", type = "aula", itemId = null) {
  const row = document.createElement("div");
  row.className = "aula-row aula-item-row";
  if (itemId) row.setAttribute("data-item-id", itemId);

  row.innerHTML = `
    <input type="text" class="aula-title-input" placeholder="Nome da Aula / Item" required value="${title}">
    <select class="type-select aula-type-select" style="width: auto;">
      <option value="aula" ${type === "aula" ? "selected" : ""}>Aula</option>
      <option value="exercicio" ${type === "exercicio" ? "selected" : ""}>Exercício</option>
      <option value="tarefa" ${type === "tarefa" ? "selected" : ""}>Tarefa</option>
    </select>
    <button type="button" class="remove-row-btn" title="Remover aula">&times;</button>
  `;

  row.querySelector(".remove-row-btn").addEventListener("click", () => {
    if (itemId) {
      pendingDeletedItems.push(Number(itemId));
    }
    row.remove();
  });

  containerList.appendChild(row);
}

// ─── INICIALIZAÇÃO ───────────────────────────────────────────────────────────

const resetBtn = document.getElementById("resetBtn");
if (resetBtn) {
  resetBtn.addEventListener("click", async () => {
    if (confirm("Isso vai desmarcar todo o progresso marcado. Confirmar?")) {
      state = {};
      if (isDbMode) {
        for (const sub of appDisciplinas) {
          for (const u of (sub.unidades || [])) {
            for (const item of (u.itens || [])) {
              toggleItem(item.id);
            }
          }
        }
      } else {
        localStorage.removeItem(STORAGE_KEY);
      }
      render();
    }
  });
}

function initAuthState() {
  const userStatusEl = document.getElementById("userStatus");
  const userNameEl = document.getElementById("userNameDisplay");
  const authNavBtn = document.getElementById("authNavBtn");
  const authNavText = document.getElementById("authNavText");

  if (window.CURRENT_USER && window.CURRENT_USER.nome) {
    if (userStatusEl) userStatusEl.classList.add("logged-in");
    if (userNameEl) userNameEl.textContent = window.CURRENT_USER.nome;
    if (authNavText) authNavText.textContent = "Sair";
    if (authNavBtn) {
      authNavBtn.href = `${BASE_URL}/logout`;
      authNavBtn.title = "Encerrar sessão";
    }
    return;
  }

  try {
    const rawAuth = localStorage.getItem("estudos_auth_user");
    if (rawAuth) {
      const user = JSON.parse(rawAuth);
      if (user && (user.name || user.email)) {
        if (userStatusEl) userStatusEl.classList.add("logged-in");
        if (userNameEl) userNameEl.textContent = user.name || user.email;
        if (authNavText) authNavText.textContent = "Sair";
        if (authNavBtn) {
          authNavBtn.title = "Encerrar sessão";
          authNavBtn.addEventListener("click", (e) => {
            e.preventDefault();
            localStorage.removeItem("estudos_auth_user");
            window.location.href = `${BASE_URL}/logout`;
          });
        }
        return;
      }
    }
  } catch (e) {
    console.warn("Erro ao ler auth:", e);
  }

  if (userStatusEl) userStatusEl.classList.remove("logged-in");
  if (userNameEl) userNameEl.textContent = "Modo Convidado";
  if (authNavText) authNavText.textContent = "Entrar";
  if (authNavBtn) authNavBtn.href = `${BASE_URL}/login`;
}

(async () => {
  initAuthState();
  initModals();
  await loadDataAndProgress();
  if (appDisciplinas.length > 0) {
    const firstKey = String(appDisciplinas[0].id || appDisciplinas[0].key || 0);
    openSubjects[firstKey] = true;
  }
  render();
  setInterval(countdown, 60000);
})();
