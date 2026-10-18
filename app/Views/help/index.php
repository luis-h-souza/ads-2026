<main class="help-wrap">
    <nav class="help-nav" aria-label="Navegação da ajuda">
        <a class="nav-btn" href="<?= htmlspecialchars($backUrl ?? base_url('/dashboard'), ENT_QUOTES, 'UTF-8') ?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6" />
            </svg>
            Voltar
        </a>
    </nav>

    <header class="help-header">
        <div class="eyebrow">Central de ajuda</div>
        <h1>Conheça o Painel de Estudos</h1>
        <p>Organize seu semestre, acompanhe sua rotina de estudos e registre os resultados de cada matéria em um só lugar.</p>
    </header>

    <section class="help-grid" aria-label="Funcionalidades da aplicação">
        <article class="help-card">
            <span class="help-card-number">01</span>
            <h2>Semestres</h2>
            <p>Crie semestres informando nome, ano e datas de início e fim. O painel usa as datas do semestre selecionado para exibir a contagem de dias. Em <strong>Opções</strong>, você pode editar semestres já cadastrados.</p>
        </article>

        <article class="help-card">
            <span class="help-card-number">02</span>
            <h2>Matérias e cronograma</h2>
            <p>Cadastre matérias e organize o conteúdo em unidades. Em cada unidade, adicione aulas, exercícios ou tarefas. Você pode voltar à edição da matéria para ajustar nomes e itens do cronograma.</p>
        </article>

        <article class="help-card">
            <span class="help-card-number">03</span>
            <h2>Progresso de estudos</h2>
            <p>Clique em uma aula, exercício ou tarefa para marcar como concluído. As barras mostram o avanço por matéria e no semestre. Com sua conta, seu progresso fica salvo e disponível quando você voltar ao painel.</p>
        </article>

        <article class="help-card">
            <span class="help-card-number">04</span>
            <h2>Provas e notas</h2>
            <p>Use o card <strong>Provas do semestre</strong> para informar a data e o horário de cada prova. Depois de realizá-la, marque-a como finalizada e, se desejar, registre a nota.</p>
        </article>

        <article class="help-card">
            <span class="help-card-number">05</span>
            <h2>Aprovado ou reprovado</h2>
            <p>Na edição da matéria, os botões de resultado são liberados quando todos os itens de estudo e a prova estiverem concluídos. Escolha o resultado para destacá-lo com uma borda verde ou vermelha no card da matéria.</p>
        </article>

        <article class="help-card">
            <span class="help-card-number">06</span>
            <h2>Opções da conta</h2>
            <p>Abra a engrenagem para editar semestres, reiniciar o progresso ou sair da conta. A opção de reiniciar remove as marcações de conclusão; use-a com atenção.</p>
        </article>
    </section>

    <aside class="help-tip">
        <strong>Dica</strong>
        <p>Entre na sua conta para criar semestres e matérias e manter seus dados salvos. No modo convidado, o painel exibe apenas um exemplo demonstrativo.</p>
    </aside>
</main>
