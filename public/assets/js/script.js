const BASE_URL = window.APP_BASE_URL || "";

// Dados locais padrão (usados apenas em modo Convidado / Demonstração)
const FALLBACK_DATA = [
    {
        id: "demo",
        key: "demo",
        nome: "Disciplina Demonstrativa (Modo Convidado)",
        descricao:
            "Exemplo para visualização · Faça login para acessar todas as suas disciplinas reais",
        unidades: [
            {
                id: "demo-u1",
                titulo: "Unidade 1 · Primeiros Passos",
                itens: [
                    {
                        id: "demo-i1",
                        key: "demo:0",
                        titulo: "Introdução à plataforma de estudos",
                        tipo: "aula",
                    },
                    {
                        id: "demo-i2",
                        key: "demo:1",
                        titulo: "Exemplo de aula interativa",
                        tipo: "aula",
                    },
                    {
                        id: "demo-i3",
                        key: "demo:2",
                        titulo: "Exercício prático de fixação",
                        tipo: "exercicio",
                    },
                    {
                        id: "demo-i4",
                        key: "demo:3",
                        titulo: "Faça login para gerenciar seus semestres e matérias",
                        tipo: "tarefa",
                    },
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
    const modalSemesterSelect = document.getElementById(
        "selectDisciplineSemester",
    );

    if (!window.CURRENT_USER || !window.CURRENT_USER.id) {
        return;
    }

    const res = await fetch(`${BASE_URL}/semestres`, {
        credentials: "same-origin",
    });
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.erro || "Não foi possível carregar os semestres.");
    }

    appSemestres = data && data.semestres ? data.semestres : [];

    if (semesterSelect) {
        semesterSelect.innerHTML = "";
        if (appSemestres.length === 0) {
            semesterSelect.innerHTML =
                '<option value="">Nenhum semestre cadastrado</option>';
        } else {
            appSemestres.forEach((s) => {
                const opt = document.createElement("option");
                opt.value = s.id;
                opt.textContent = `${s.nome} (${s.ano})`;
                semesterSelect.appendChild(opt);
            });
            if (activeSemesterId) semesterSelect.value = activeSemesterId;
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

    updateSemesterDates();
}

async function loadDataAndProgress() {
    const loadStatusEl = document.getElementById("loadStatus");

    // Modo autenticado: consome API do banco
    if (window.CURRENT_USER && window.CURRENT_USER.id) {
        try {
            await loadSemesters();

            if (activeSemesterId) {
                if (loadStatusEl)
                    loadStatusEl.textContent = "carregando matérias do banco de dados...";
                const treeRes = await fetch(
                    `${BASE_URL}/semestres/${activeSemesterId}`,
                    { credentials: "same-origin" },
                );
                const tree = await treeRes.json();
                if (!treeRes.ok) {
                    throw new Error(
                        tree.erro || "Não foi possível carregar as matérias do semestre.",
                    );
                }

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

                    if (loadStatusEl) loadStatusEl.textContent = "";
                    return;
                }
            } else {
                appDisciplinas = [];
                state = {};
                isDbMode = true;
                if (loadStatusEl) {
                    loadStatusEl.textContent =
                        "Cadastre um semestre para começar a organizar suas matérias.";
                }
                return;
            }
        } catch (e) {
            console.error("Erro ao buscar dados do banco:", e);
            appDisciplinas = [];
            state = {};
            isDbMode = true;
            if (loadStatusEl) {
                loadStatusEl.textContent =
                    "Erro ao carregar os dados. Atualize o banco de dados e tente novamente.";
            }
            return;
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
    if (loadStatusEl)
        loadStatusEl.textContent = "progresso salvo automaticamente no navegador";
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

            const response = await fetch(
                `${BASE_URL}/itens-estudo/${itemId}/progresso`,
                {
                    method: "PATCH",
                    headers: { "Content-Type": "application/json" },
                    credentials: "same-origin",
                    body: JSON.stringify({ concluido: isDone }),
                },
            );
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.erro || "Não foi possível salvar o progresso.");
            }

            if (loadStatusEl) loadStatusEl.textContent = "";
        } catch (error) {
            state[itemId] = !isDone;
            render();
            console.error("Erro ao salvar progresso na API:", error);
            alert(error.message || "Não foi possível salvar o progresso.");
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
    if (
        !confirm(
            `Tem certeza que deseja excluir a matéria "${subjectName}" e todas as suas unidades e aulas?`,
        )
    ) {
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
    const numEl = document.getElementById("countdownNum");
    const labelEl = document.getElementById("countdownLabel");
    const semester = appSemestres.find(
        (item) => Number(item.id) === Number(activeSemesterId),
    );
    const endDate = semester ? parseSemesterDate(semester.data_fim) : null;

    updateSemesterDates();

    if (!endDate) {
        if (numEl) numEl.textContent = "—";
        if (labelEl)
            labelEl.textContent = semester
                ? "data final não cadastrada"
                : "sem semestre ativo";
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const diffDays = Math.round(
        (endDate.getTime() - today.getTime()) / (24 * 60 * 60 * 1000),
    );
    if (numEl) numEl.textContent = Math.abs(diffDays);
    if (labelEl) {
        if (diffDays < 0) {
            labelEl.textContent = `${Math.abs(diffDays) === 1 ? "dia" : "dias"} desde o fim do semestre`;
        } else if (diffDays === 0) {
            labelEl.textContent = "último dia do semestre";
        } else {
            labelEl.textContent =
                diffDays === 1
                    ? "dia até o fim do semestre"
                    : "dias até o fim do semestre";
        }
    }
}

function parseSemesterDate(value) {
    if (!value) return null;
    const [year, month, day] = value.split("-").map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
}

function formatSemesterDate(value) {
    const date = parseSemesterDate(value);
    return date ? date.toLocaleDateString("pt-BR") : null;
}

function updateSemesterDates() {
    const datesEl = document.getElementById("semesterDates");
    if (!datesEl) return;

    const semester = appSemestres.find(
        (item) => Number(item.id) === Number(activeSemesterId),
    );
    if (!semester) {
        datesEl.textContent =
            "Selecione ou cadastre um semestre para ver as datas.";
        return;
    }

    const start = formatSemesterDate(semester.data_inicio);
    const end = formatSemesterDate(semester.data_fim);
    if (start && end) {
        datesEl.textContent = `Período do semestre · ${start} até ${end}`;
    } else if (start) {
        datesEl.textContent = `Início do semestre · ${start} · Data final não cadastrada`;
    } else if (end) {
        datesEl.textContent = `Fim do semestre · ${end} · Data de início não cadastrada`;
    } else {
        datesEl.textContent = "Datas do semestre ainda não cadastradas.";
    }
}

function buildItemHtml(item) {
    const itemKey = isDbMode ? item.id : item.key || item.id;
    const done = !!state[itemKey];
    const isExercicio = item.tipo === "exercicio";
    const isTarefa = item.tipo === "tarefa";
    const extraClass = isExercicio ? "exercicio" : isTarefa ? "tarefa" : "";

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
            const itemKey = isDbMode ? item.id : item.key || item.id;
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
    updateExamSummary();

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
        const resultClass =
            sub.resultado && isDisciplineComplete(sub)
                ? `subject-result-${sub.resultado}`
                : "";

        html += `
      <div class="subject ${isOpen ? "open" : ""} ${resultClass}" data-sub="${subKey}">
        <div class="subject-head" data-toggle="${subKey}">
          <div class="subject-head-row">
            <div>
              <div class="subject-title">${sub.nome}</div>
              <div class="subject-phase">${sub.descricao || ""}</div>
            </div>
            <div class="subject-actions">
              <span class="subject-pct">${p.done}/${p.total}</span>
              ${isDbMode
                ? `
                <button type="button" class="edit-subject-btn" title="Editar ou Excluir Matéria" data-edit-sub="${sub.id}">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                  </svg>
                  Editar
                </button>
              `
                : ""
            }
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

function updateExamSummary() {
    const scheduledCount = document.getElementById("scheduledExamCount");
    const completedCount = document.getElementById("completedExamCount");
    if (!scheduledCount || !completedCount) return;

    const scheduledExams = appDisciplinas.filter(
        (discipline) => discipline.data_prova,
    );
    scheduledCount.textContent = String(scheduledExams.length);
    completedCount.textContent = String(
        scheduledExams.filter(
            (discipline) => Number(discipline.prova_concluida) === 1,
        ).length,
    );
}

function isDisciplineComplete(discipline) {
    const items = (discipline.unidades || []).flatMap((unit) => unit.itens || []);
    return (
        items.length > 0 &&
        items.every((item) => !!state[item.id]) &&
        Number(discipline.prova_concluida) === 1
    );
}

function hasPendingStudyPlanChanges() {
    return (
        pendingDeletedItems.length > 0 ||
        pendingDeletedUnits.length > 0 ||
        !!document.querySelector(
            ".unit-card-row:not([data-unit-id]), .aula-item-row:not([data-item-id])",
        )
    );
}

function refreshDisciplineResultControls(discipline) {
    const controls = document.getElementById("disciplineResultControls");
    if (!controls) return;

    const eligible =
        !!discipline &&
        isDisciplineComplete(discipline) &&
        !hasPendingStudyPlanChanges();
    controls.querySelectorAll("[data-discipline-result]").forEach((button) => {
        button.disabled = !eligible;
    });
    document.getElementById("disciplineResultHelp").textContent = eligible
        ? "Aulas, exercícios e prova concluídos. Selecione o resultado final."
        : "Conclua todas as aulas, exercícios e a prova para registrar o resultado.";
}

function refreshDisciplineResultControlsForCurrent() {
    const inputEditId = document.getElementById("inputEditingDiscId");
    const discipline = inputEditId
        ? appDisciplinas.find(
            (item) => Number(item.id) === Number(inputEditId.value),
        )
        : null;
    refreshDisciplineResultControls(discipline);
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
    const resultControls = document.getElementById("disciplineResultControls");

    const disc = appDisciplinas.find(
        (d) => Number(d.id) === Number(disciplineId),
    );
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
    if (resultControls) {
        resultControls.classList.remove("hidden");
        resultControls
            .querySelectorAll("[data-discipline-result]")
            .forEach((button) => {
                const selected = button.dataset.disciplineResult === disc.resultado;
                button.classList.toggle("is-selected", selected);
                button.setAttribute("aria-pressed", String(selected));
            });
        refreshDisciplineResultControls(disc);
    }

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
    const btnOpenSemesterManager = document.getElementById(
        "btnOpenSemesterManager",
    );
    const modalSemesterManager = document.getElementById("modalManageSemesters");
    const semesterManagerList = document.getElementById("semesterManagerList");
    const semesterListView = document.getElementById("semesterListView");
    const formEditSemester = document.getElementById("formEditSemester");
    const semesterManagerTitle = document.getElementById("semesterManagerTitle");
    const examSummaryCard = document.getElementById("examSummaryCard");
    const modalExams = document.getElementById("modalExams");
    const examList = document.getElementById("examList");
    const btnDeleteDisc = document.getElementById("btnDeleteCurrentDiscipline");
    const resultControls = document.getElementById("disciplineResultControls");

    function renderExamList() {
        if (!examList) return;
        examList.replaceChildren();

        if (!isDbMode || appDisciplinas.length === 0) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "exam-empty";
            emptyMessage.textContent = isDbMode
                ? "Cadastre uma matéria neste semestre para gerenciar suas provas."
                : "Cadastre ou selecione um semestre para gerenciar suas provas.";
            examList.appendChild(emptyMessage);
            return;
        }

        appDisciplinas.forEach((discipline) => {
            const card = document.createElement("section");
            card.className = "exam-entry";
            card.dataset.disciplineId = discipline.id;

            const heading = document.createElement("h3");
            heading.className = "exam-entry-title";
            heading.textContent = discipline.nome;

            const schedule = document.createElement("div");
            schedule.className = "exam-entry-schedule";
            const dateField = createExamField(
                "Data da prova",
                "date",
                "exam-date",
                discipline.data_prova || "",
            );
            const timeField = createExamField(
                "Horário",
                "time",
                "exam-time",
                (discipline.horario_prova || "").slice(0, 5),
            );
            schedule.append(dateField.container, timeField.container);

            const result = document.createElement("div");
            result.className = "exam-entry-result";

            const completedLabel = document.createElement("label");
            completedLabel.className = "exam-completed";
            const completedInput = document.createElement("input");
            completedInput.type = "checkbox";
            completedInput.className = "exam-completed-input";
            completedInput.checked = Number(discipline.prova_concluida) === 1;
            const completedText = document.createElement("span");
            completedText.textContent = "Prova finalizada";
            completedLabel.append(completedInput, completedText);

            const gradeField = createExamField(
                "Nota",
                "number",
                "exam-grade",
                discipline.prova_nota ?? "",
            );
            gradeField.input.min = "0";
            gradeField.input.max = "999999.99";
            gradeField.input.step = "0.01";
            gradeField.input.placeholder = "Informe a nota após a prova";
            result.append(completedLabel, gradeField.container);

            const saveButton = document.createElement("button");
            saveButton.type = "button";
            saveButton.className = "primary-btn exam-save-btn";
            saveButton.textContent = "Salvar prova";

            card.append(heading, schedule, result, saveButton);
            examList.appendChild(card);
        });
    }

    function createExamField(labelText, inputType, inputClass, value) {
        const container = document.createElement("label");
        container.className = "exam-field";
        const label = document.createElement("span");
        label.textContent = labelText;
        const input = document.createElement("input");
        input.type = inputType;
        input.className = inputClass;
        input.value = value;
        container.append(label, input);
        return { container, input };
    }

    if (examSummaryCard && modalExams) {
        examSummaryCard.addEventListener("click", () => {
            renderExamList();
            modalExams.classList.remove("hidden");
        });
    }

    if (examList) {
        examList.addEventListener("click", async (event) => {
            const saveButton =
                event.target instanceof Element
                    ? event.target.closest(".exam-save-btn")
                    : null;
            if (!saveButton) return;

            const card = saveButton.closest(".exam-entry");
            const disciplineId = Number(card.dataset.disciplineId);
            const data_prova = card.querySelector(".exam-date").value || null;
            const horario_prova = card.querySelector(".exam-time").value || null;
            const prova_concluida = card.querySelector(
                ".exam-completed-input",
            ).checked;
            const gradeValue = card.querySelector(".exam-grade").value;
            const prova_nota = gradeValue === "" ? null : Number(gradeValue);

            if (prova_concluida && !data_prova) {
                alert("Informe a data da prova antes de marcá-la como finalizada.");
                return;
            }
            if (
                prova_nota !== null &&
                (!Number.isFinite(prova_nota) ||
                    prova_nota < 0 ||
                    prova_nota > 999999.99)
            ) {
                alert("Informe uma nota válida entre 0 e 999999,99.");
                return;
            }

            saveButton.disabled = true;
            try {
                const response = await fetch(
                    `${BASE_URL}/disciplinas/${disciplineId}/prova`,
                    {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        credentials: "same-origin",
                        body: JSON.stringify({
                            data_prova,
                            horario_prova,
                            prova_concluida,
                            prova_nota,
                        }),
                    },
                );
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(
                        result.erro || "Não foi possível salvar os dados da prova.",
                    );
                }

                await loadDataAndProgress();
                render();
                renderExamList();
            } catch (error) {
                console.error("Erro ao salvar prova:", error);
                alert(error.message || "Não foi possível salvar os dados da prova.");
            } finally {
                saveButton.disabled = false;
            }
        });
    }

    if (resultControls) {
        resultControls.addEventListener("click", async (event) => {
            const button =
                event.target instanceof Element
                    ? event.target.closest("[data-discipline-result]")
                    : null;
            if (!button || button.disabled) return;

            const disciplineId = Number(
                document.getElementById("inputEditingDiscId").value,
            );
            const resultado = button.dataset.disciplineResult;
            button.disabled = true;
            try {
                const response = await fetch(
                    `${BASE_URL}/disciplinas/${disciplineId}/resultado`,
                    {
                        method: "PATCH",
                        headers: { "Content-Type": "application/json" },
                        credentials: "same-origin",
                        body: JSON.stringify({ resultado }),
                    },
                );
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(
                        result.erro || "Não foi possível salvar o resultado da matéria.",
                    );
                }

                await loadDataAndProgress();
                render();
                openEditDisciplineModal(disciplineId);
            } catch (error) {
                console.error("Erro ao salvar resultado da matéria:", error);
                alert(
                    error.message || "Não foi possível salvar o resultado da matéria.",
                );
            } finally {
                button.disabled = false;
            }
        });
    }

    function showSemesterList() {
        if (!semesterListView || !formEditSemester) return;
        semesterListView.classList.remove("hidden");
        formEditSemester.classList.add("hidden");
        semesterManagerTitle.textContent = "Editar Semestres";
    }

    function renderSemesterList() {
        if (!semesterManagerList) return;
        semesterManagerList.replaceChildren();

        if (appSemestres.length === 0) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "semester-manager-empty";
            emptyMessage.textContent = "Nenhum semestre cadastrado.";
            semesterManagerList.appendChild(emptyMessage);
            return;
        }

        appSemestres.forEach((semester) => {
            const row = document.createElement("div");
            row.className = "semester-manager-item";

            const details = document.createElement("div");
            const name = document.createElement("h3");
            name.textContent = semester.nome;
            const year = document.createElement("p");
            year.textContent = `Ano letivo: ${semester.ano}`;
            details.append(name, year);

            const editButton = document.createElement("button");
            editButton.type = "button";
            editButton.className = "edit-subject-btn";
            editButton.title = "Editar semestre";
            editButton.innerHTML = `
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        Editar
      `;
            editButton.addEventListener("click", () => {
                document.getElementById("inputEditingSemesterId").value = semester.id;
                document.getElementById("inputEditSemesterName").value =
                    semester.nome || "";
                document.getElementById("inputEditSemesterYear").value =
                    semester.ano || "";
                document.getElementById("inputEditSemesterStart").value =
                    semester.data_inicio || "";
                document.getElementById("inputEditSemesterEnd").value =
                    semester.data_fim || "";
                semesterListView.classList.add("hidden");
                formEditSemester.classList.remove("hidden");
                semesterManagerTitle.textContent = `Editar: ${semester.nome}`;
                document.getElementById("inputEditSemesterName").focus();
            });

            refreshDisciplineResultControls(
                appDisciplinas.find(
                    (discipline) => Number(discipline.id) === Number(disc.id),
                ),
            );

            row.append(details, editButton);
            semesterManagerList.appendChild(row);
        });
    }

    if (btnOpenSemesterManager && modalSemesterManager) {
        btnOpenSemesterManager.addEventListener("click", async () => {
            closeSettingsMenu();
            showSemesterList();
            try {
                await loadSemesters();
                renderSemesterList();
                modalSemesterManager.classList.remove("hidden");
            } catch (err) {
                console.error("Erro ao carregar semestres para edição:", err);
                alert(err.message || "Não foi possível carregar os semestres.");
            }
        });
    }

    const btnBackSemesterList = document.getElementById("btnBackSemesterList");
    if (btnBackSemesterList) {
        btnBackSemesterList.addEventListener("click", () => {
            showSemesterList();
            renderSemesterList();
        });
    }

    if (formEditSemester) {
        formEditSemester.addEventListener("submit", async (e) => {
            e.preventDefault();
            const semesterId = Number(
                document.getElementById("inputEditingSemesterId").value,
            );
            const nome = document
                .getElementById("inputEditSemesterName")
                .value.trim();
            const ano = Number(
                document.getElementById("inputEditSemesterYear").value,
            );
            const data_inicio =
                document.getElementById("inputEditSemesterStart").value || null;
            const data_fim =
                document.getElementById("inputEditSemesterEnd").value || null;
            const saveButton = document.getElementById("btnSaveSemester");

            if (data_inicio && data_fim && data_fim < data_inicio) {
                alert("A data de fim não pode ser anterior à data de início.");
                return;
            }

            saveButton.disabled = true;
            try {
                const response = await fetch(`${BASE_URL}/semestres/${semesterId}`, {
                    method: "PATCH",
                    headers: { "Content-Type": "application/json" },
                    credentials: "same-origin",
                    body: JSON.stringify({ nome, ano, data_inicio, data_fim }),
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(
                        result.erro || "Não foi possível atualizar o semestre.",
                    );
                }

                await loadSemesters();
                renderSemesterList();
                showSemesterList();
                if (Number(activeSemesterId) === semesterId) {
                    await loadDataAndProgress();
                    render();
                }
            } catch (err) {
                console.error("Erro ao atualizar semestre:", err);
                alert(err.message || "Não foi possível atualizar o semestre.");
            } finally {
                saveButton.disabled = false;
            }
        });
    }

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
            document.getElementById("modalDisciplineTitle").textContent =
                "Cadastrar Matéria / Aulas";
            document.getElementById("inputEditingDiscId").value = "";
            document
                .getElementById("disciplineResultControls")
                .classList.add("hidden");
            if (btnDeleteDisc) btnDeleteDisc.classList.add("hidden");

            const formDisc = document.getElementById("formNewDiscipline");
            if (formDisc) formDisc.reset();

            const container = document.getElementById("unitsFormContainer");
            if (container) {
                container.innerHTML = "";
                addUnitRowToForm("Unidade 1", []);
            }

            if (document.getElementById("selectDisciplineSemester")) {
                document.getElementById("selectDisciplineSemester").value =
                    activeSemesterId;
            }

            modalDisc.classList.remove("hidden");
            document.getElementById("inputDiscName").focus();
        });
    }

    // Ação de Excluir Matéria dentro do modal de edição
    if (btnDeleteDisc) {
        btnDeleteDisc.addEventListener("click", () => {
            const editId = Number(
                document.getElementById("inputEditingDiscId").value,
            );
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
    [modalSemester, modalDisc, modalSemesterManager, modalExams].forEach((m) => {
        if (m) {
            m.addEventListener("click", (e) => {
                if (e.target === m) {
                    m.classList.add("hidden");
                    if (m === modalSemesterManager) showSemesterList();
                }
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
            const data_inicio =
                document.getElementById("inputSemesterStart").value || null;
            const data_fim =
                document.getElementById("inputSemesterEnd").value || null;

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

            const semestre_id = Number(
                document.getElementById("selectDisciplineSemester").value,
            );
            const nome = document.getElementById("inputDiscName").value.trim();
            const descricao = document.getElementById("inputDiscDesc").value.trim();
            const data_prova = document.getElementById("inputDiscExam").value || null;

            try {
                let discId = editDiscId;

                // 1. Executa exclusões pendentes de aulas e unidades que foram removidas no formulário
                for (const itId of pendingDeletedItems) {
                    await fetch(`${BASE_URL}/itens-estudo/${itId}`, {
                        method: "DELETE",
                        credentials: "same-origin",
                    });
                }
                for (const unId of pendingDeletedUnits) {
                    await fetch(`${BASE_URL}/unidades/${unId}`, {
                        method: "DELETE",
                        credentials: "same-origin",
                    });
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
                        body: JSON.stringify({
                            semestre_id,
                            nome,
                            descricao,
                            data_prova,
                            ordem: appDisciplinas.length + 1,
                        }),
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
                    const uTitle = uTitleInput
                        ? uTitleInput.value.trim()
                        : `Unidade ${uIdx + 1}`;
                    const existingUnitId = uCard.getAttribute("data-unit-id");

                    let unitId = existingUnitId ? Number(existingUnitId) : null;

                    if (!unitId) {
                        const resUnit = await fetch(`${BASE_URL}/unidades`, {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            credentials: "same-origin",
                            body: JSON.stringify({
                                disciplina_id: discId,
                                titulo: uTitle,
                                ordem: uIdx + 1,
                            }),
                        });
                        const unitData = await resUnit.json();
                        if (!resUnit.ok || !unitData.id) {
                            throw new Error(unitData.erro || "Falha ao criar unidade.");
                        }
                        unitId = unitData.id;
                    } else {
                        const resUnit = await fetch(`${BASE_URL}/unidades/${unitId}`, {
                            method: "PATCH",
                            headers: { "Content-Type": "application/json" },
                            credentials: "same-origin",
                            body: JSON.stringify({ titulo: uTitle, ordem: uIdx + 1 }),
                        });
                        if (!resUnit.ok) {
                            const unitData = await resUnit.json();
                            throw new Error(unitData.erro || "Falha ao atualizar unidade.");
                        }
                    }

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
                                const resItem = await fetch(
                                    `${BASE_URL}/itens-estudo/${existingItemId}`,
                                    {
                                        method: "PATCH",
                                        headers: { "Content-Type": "application/json" },
                                        credentials: "same-origin",
                                        body: JSON.stringify({
                                            titulo: aTitle,
                                            tipo: aType,
                                            ordem: aIdx + 1,
                                        }),
                                    },
                                );
                                if (!resItem.ok) {
                                    const itemData = await resItem.json();
                                    throw new Error(
                                        itemData.erro || "Falha ao atualizar aula/item.",
                                    );
                                }
                            } else {
                                const resItem = await fetch(`${BASE_URL}/itens-estudo`, {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    credentials: "same-origin",
                                    body: JSON.stringify({
                                        unidade_id: unitId,
                                        titulo: aTitle,
                                        tipo: aType,
                                        ordem: aIdx + 1,
                                    }),
                                });
                                const itemData = await resItem.json();
                                if (!resItem.ok || !itemData.id) {
                                    throw new Error(itemData.erro || "Falha ao criar aula/item.");
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
                alert(
                    err.message || "Não foi possível salvar todos os dados da matéria.",
                );
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
        refreshDisciplineResultControlsForCurrent();
    });

    // Adicionar novas aulas na unidade
    unitCard
        .querySelector(".add-aula-to-unit-btn")
        .addEventListener("click", () => {
            const count = list.children.length + 1;
            addAulaRow(list, `Aula ${count}`, "aula");
        });

    container.appendChild(unitCard);
    refreshDisciplineResultControlsForCurrent();
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
        refreshDisciplineResultControlsForCurrent();
    });

    containerList.appendChild(row);
    refreshDisciplineResultControlsForCurrent();
}

// ─── INICIALIZAÇÃO ───────────────────────────────────────────────────────────

const resetBtn = document.getElementById("resetBtn");
const settingsToggle = document.getElementById("settingsToggle");
const settingsMenu = document.getElementById("settingsMenu");

function closeSettingsMenu() {
    if (!settingsToggle || !settingsMenu) return;
    settingsMenu.classList.add("hidden");
    settingsToggle.setAttribute("aria-expanded", "false");
    settingsToggle.closest(".top-nav-bar")?.classList.remove("settings-open");
}

if (settingsToggle && settingsMenu) {
    settingsToggle.addEventListener("click", () => {
        const isOpen = settingsToggle.getAttribute("aria-expanded") === "true";
        settingsToggle.setAttribute("aria-expanded", String(!isOpen));
        settingsMenu.classList.toggle("hidden", isOpen);
        settingsToggle
            .closest(".top-nav-bar")
            ?.classList.toggle("settings-open", !isOpen);
    });

    document.addEventListener("click", (event) => {
        if (
            !settingsMenu.contains(event.target) &&
            !settingsToggle.contains(event.target)
        ) {
            closeSettingsMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeSettingsMenu();
            settingsToggle.focus();
        }
    });
}

if (resetBtn) {
    resetBtn.addEventListener("click", async () => {
        if (confirm("Isso vai desmarcar todo o progresso marcado. Confirmar?")) {
            closeSettingsMenu();
            if (isDbMode) {
                for (const sub of appDisciplinas) {
                    for (const u of sub.unidades || []) {
                        for (const item of u.itens || []) {
                            if (state[item.id]) {
                                await toggleItem(item.id);
                            }
                        }
                    }
                }
            } else {
                state = {};
                localStorage.removeItem(STORAGE_KEY);
                render();
            }
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
