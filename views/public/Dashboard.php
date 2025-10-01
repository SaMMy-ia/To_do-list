<?php
session_start();
require_once '../../models/sessaoDAO.php';
require_once '../../database/DBConnection.php';

// Verificar sessão usando SessaoDAO
try {
    $conn = DBConnection::getInstance();
    $sessaoDAO = new SessaoDAO($conn);
    
    $sessionId = session_id();
    $sessaoAtiva = $sessaoDAO->selecionarSessao($sessionId);
    
    if (!$sessaoAtiva || !isset($_SESSION['user_id'])) {
        header("Location: ../views/index.php");
        exit();
    }
    
    // Verificar se o usuário da sessão corresponde ao usuário logado
    if ($sessaoAtiva->user_id != $_SESSION['user_id']) {
        // Sessão inválida - fazer logout
        $sessaoDAO->invalidarSessao($sessionId);
        session_destroy();
        header("Location: ../views/index.php");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Erro ao verificar sessão: " . $e->getMessage());
    header("Location: ../views/index.php");
    exit();
}

require_once '../../controllers/categorias.php';

// Verificar se a variável $categorias está definida
if (!isset($categorias)) {
    $categorias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Sistema To-Do List</title>
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">To-Do List</div>
                <div class="user-info">
                    <div class="user-avatar">U</div>
                    <a href="../logout.php" class="logout-btn">Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="container">
            <section class="welcome-section">
                <h1>Bem-vindo ao seu Dashboard!</h1>
                <p>Aqui você pode gerenciar suas tarefas de forma organizada e eficiente.</p>
            </section>

            <section class="stats-cards">
                <div class="stat-card">
                    <h3>Tarefas Totais</h3>
                    <div class="stat-number" id="total-tasks">0</div>
                </div>
                <div class="stat-card">
                    <h3>Tarefas Pendentes</h3>
                    <div class="stat-number" id="pending-tasks">0</div>
                </div>
                <div class="stat-card">
                    <h3>Tarefas Concluídas</h3>
                    <div class="stat-number" id="completed-tasks">0</div>
                </div>
                <div class="stat-card">
                    <h3>Tarefas com Alta Prioridade</h3>
                    <div class="stat-number" id="high-priority-tasks">0</div>
                </div>
            </section>

            <section class="todo-section">
                <div class="todo-form">
                    <h2 class="section-title">Adicionar Nova Tarefa</h2>
                    <form id="task-form">
                        <div class="form-group">
                            <label for="task-title">Título da Tarefa *</label>
                            <input type="text" id="task-title" class="form-control" placeholder="Digite o título da tarefa" required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="task-description">Descrição (Opcional)</label>
                            <textarea id="task-description" class="form-control" rows="3" placeholder="Digite uma descrição para a tarefa" maxlength="500"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="task-priority">Prioridade</label>
                            <select id="task-priority" class="form-control">
                                <option value="low">Baixa</option>
                                <option value="medium" selected>Média</option>
                                <option value="high">Alta</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="task-category">Categoria *</label>
                            <select id="task-category" class="form-control" required>
                                <option value="" selected disabled>Escolha uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria['id']) ?>">
                                        <?= htmlspecialchars($categoria['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="task-due-date">Data de Vencimento (Opcional)</label>
                            <input type="date" id="task-due-date" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>

                        <button type="submit" class="btn btn-block">Adicionar Tarefa</button>
                    </form>
                </div>

                <div class="todo-list-container">
                    <h2 class="section-title">Suas Tarefas</h2>

                    <div class="todo-filters">
                        <button class="filter-btn active" data-filter="all">Todas</button>
                        <button class="filter-btn" data-filter="pending">Pendentes</button>
                        <button class="filter-btn" data-filter="completed">Concluídas</button>
                        <button class="filter-btn" data-filter="high">Alta Prioridade</button>
                    </div>

                    <div class="search-box">
                        <input type="text" id="task-search" class="form-control" placeholder="Buscar tarefas...">
                    </div>

                    <ul class="todo-list" id="todo-list">
                        <li class="empty-state">
                            <i>📝</i>
                            <p>Nenhuma tarefa encontrada. Adicione sua primeira tarefa!</p>
                        </li>
                    </ul>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Sistema To-Do List. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Variáveis globais
        let todasTarefas = [];
        let filtroAtual = 'all';
        let termoBusca = '';

        // Adicionar Tarefa
        $("#task-form").submit(function(e) {
            e.preventDefault();

            let formData = {
                title: $("#task-title").val().trim(),
                description: $("#task-description").val().trim(),
                priority: $("#task-priority").val(),
                due_date: $("#task-due-date").val(),
                category_id: $("#task-category").val()
            };

            // Validação básica no frontend
            if (!formData.title) {
                alert("Por favor, insira um título para a tarefa.");
                $("#task-title").focus();
                return;
            }

            if (!formData.category_id || formData.category_id === "") {
                alert("Por favor, selecione uma categoria.");
                $("#task-category").focus();
                return;
            }

            // Validação de data
            if (formData.due_date && new Date(formData.due_date) < new Date().setHours(0,0,0,0)) {
                if (!confirm("A data de vencimento é anterior à data atual. Deseja continuar?")) {
                    return;
                }
            }

            $.ajax({
                url: "../../controllers/adicionar_tasks.php",
                type: "POST",
                data: formData,
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            alert("Tarefa adicionada com sucesso!");
                            $("#task-form")[0].reset();
                            carregarTarefas();
                        } else {
                            alert("Erro: " + (result.message || "Erro desconhecido"));
                        }
                    } catch (e) {
                        console.error("Erro ao processar resposta:", e, response);
                        alert("Erro inesperado ao adicionar tarefa.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro AJAX:", error);
                    alert("Erro de conexão ao adicionar tarefa.");
                }
            });
        });

        // Carregar tarefas
        function carregarTarefas() {
            $.ajax({
                url: "../../controllers/listar_tasks.php",
                type: "POST",
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (result.success) {
                            todasTarefas = result.data || [];
                            renderizarTarefas();
                            atualizarEstatisticas(todasTarefas);
                        } else {
                            console.error("Erro ao carregar tarefas:", result.message);
                            mostrarErroCarregamento(result.message);
                        }
                    } catch (e) {
                        console.error("Erro ao processar tarefas:", e, response);
                        mostrarErroCarregamento();
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro AJAX:", error);
                    alert("Erro de conexão ao carregar tarefas.");
                }
            });
        }

        // Renderizar tarefas com filtros
        function renderizarTarefas() {
            let tarefasFiltradas = filtrarTarefas(todasTarefas);
            let lista = $("#todo-list");
            lista.empty();

            if (tarefasFiltradas.length > 0) {
                tarefasFiltradas.forEach(t => {
                    const priorityClass = `priority-${t.priority || 'medium'}`;
                    const statusClass = t.status === 'concluida' ? 'completed' : '';
                    const categoriaNome = t.category_name || 'Sem Categoria';
                    
                    lista.append(`
                        <li class="task-item ${priorityClass} ${statusClass}" data-id="${t.id}">
                            <div class="task-content">
                                <div class="task-header">
                                    <h4>${escapeHtml(t.title)}</h4>
                                    <span class="task-category">${escapeHtml(categoriaNome)}</span>
                                </div>
                                ${t.description ? `<p>${escapeHtml(t.description)}</p>` : ''}
                                <div class="task-meta">
                                    <span class="status ${t.status}">${t.status === 'concluida' ? 'Concluída' : 'Pendente'}</span>
                                    ${t.due_date ? `<span class="due-date ${isVencida(t.due_date) ? 'vencida' : ''}">Vence: ${formatarData(t.due_date)}</span>` : ''}
                                    <span class="priority ${t.priority}">${getPrioridadeTexto(t.priority)}</span>
                                </div>
                            </div>
                            <div class="task-actions">
                                ${t.status !== 'concluida' ? 
                                    `<button class="btn-complete" onclick="marcarConcluida(${t.id})" title="Marcar como concluída">✓</button>` : 
                                    `<button class="btn-undo" onclick="marcarPendente(${t.id})" title="Marcar como pendente">↶</button>`
                                }
                                <button class="btn-delete" onclick="excluirTarefa(${t.id})" title="Excluir tarefa">✕</button>
                            </div>
                        </li>
                    `);
                });
            } else {
                lista.html(`
                    <li class="empty-state">
                        <i>🔍</i>
                        <p>Nenhuma tarefa encontrada com os filtros atuais.</p>
                    </li>
                `);
            }
        }

        // Filtrar tarefas
        function filtrarTarefas(tarefas) {
            let filtradas = tarefas;
            
            // Aplicar filtro principal
            if (filtroAtual === 'pending') {
                filtradas = filtradas.filter(t => t.status !== 'concluida');
            } else if (filtroAtual === 'completed') {
                filtradas = filtradas.filter(t => t.status === 'concluida');
            } else if (filtroAtual === 'high') {
                filtradas = filtradas.filter(t => t.priority === 'high');
            }
            
            // Aplicar busca
            if (termoBusca) {
                const busca = termoBusca.toLowerCase();
                filtradas = filtradas.filter(t => 
                    t.title.toLowerCase().includes(busca) || 
                    (t.description && t.description.toLowerCase().includes(busca))
                );
            }
            
            return filtradas;
        }

        // Funções de manipulação de tarefas
        function marcarConcluida(id) {
            alterarStatusTarefa(id, 'concluida');
        }

        function marcarPendente(id) {
            alterarStatusTarefa(id, 'pendente');
        }

        function alterarStatusTarefa(id, status) {
            $.ajax({
                url: "../../controllers/alterar_status_task.php",
                type: "POST",
                data: { id: id, status: status },
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            carregarTarefas();
                        } else {
                            alert("Erro: " + (result.message || "Erro ao alterar status"));
                        }
                    } catch (e) {
                        console.error("Erro ao processar resposta:", e);
                        alert("Erro inesperado ao alterar status.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erro AJAX:", error);
                    alert("Erro de conexão ao alterar status.");
                }
            });
        }

        function excluirTarefa(id) {
            if (confirm("Tem certeza que deseja excluir esta tarefa?")) {
                $.ajax({
                    url: "../../controllers/apagar_tasks.php",
                    type: "POST",
                    data: { id: id },
                    success: function(response) {
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                carregarTarefas();
                            } else {
                                alert("Erro: " + (result.message || "Erro ao excluir tarefa"));
                            }
                        } catch (e) {
                            console.error("Erro ao processar resposta:", e);
                            alert("Erro inesperado ao excluir tarefa.");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Erro AJAX:", error);
                        alert("Erro de conexão ao excluir tarefa.");
                    }
                });
            }
        }

        // Funções auxiliares
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatarData(dataString) {
            if (!dataString) return '';
            const data = new Date(dataString + 'T00:00:00');
            return data.toLocaleDateString('pt-BR');
        }

        function isVencida(dataString) {
            if (!dataString) return false;
            const dataVencimento = new Date(dataString + 'T23:59:59');
            return dataVencimento < new Date();
        }

        function getPrioridadeTexto(prioridade) {
            const prioridades = {
                'low': 'Baixa',
                'medium': 'Média',
                'high': 'Alta'
            };
            return prioridades[prioridade] || 'Média';
        }

        function atualizarEstatisticas(tarefas) {
            const total = tarefas.length;
            const pendentes = tarefas.filter(t => t.status !== 'concluida').length;
            const concluidas = tarefas.filter(t => t.status === 'concluida').length;
            const altaPrioridade = tarefas.filter(t => t.priority === 'high').length;

            $('#total-tasks').text(total);
            $('#pending-tasks').text(pendentes);
            $('#completed-tasks').text(concluidas);
            $('#high-priority-tasks').text(altaPrioridade);
        }

        function mostrarErroCarregamento(mensagem) {
            $("#todo-list").html(`
                <li class="empty-state">
                    <i>❌</i>
                    <p>Erro ao carregar tarefas: ${mensagem || 'Erro desconhecido'}</p>
                </li>
            `);
        }

        // Event Listeners
        $(document).ready(function() {
            carregarTarefas();

            // Filtros
            $(".filter-btn").click(function() {
                $(".filter-btn").removeClass("active");
                $(this).addClass("active");
                filtroAtual = $(this).data("filter");
                renderizarTarefas();
            });

            // Busca
            $("#task-search").on("input", function() {
                termoBusca = $(this).val().trim();
                renderizarTarefas();
            });

            // Prevenir envio de formulário com Enter
            $("#task-form").on("keypress", function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    $(this).submit();
                }
            });
        });
    </script>
</body>
</html>