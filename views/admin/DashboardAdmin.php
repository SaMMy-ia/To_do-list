<?php
session_start();
require_once '../../models/sessaoDAO.php';
require_once '../../database/DBConnection.php';

// Verificar sessão e permissões
try {
    $conn = DBConnection::getInstance();
    $sessaoDAO = new SessaoDAO($conn);

    $sessionId = session_id();
    $sessaoAtiva = $sessaoDAO->selecionarSessao($sessionId);

    if (!$sessaoAtiva || !isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }

    // Verificar se o usuário da sessão corresponde ao usuário logado
    if ($sessaoAtiva->user_id != $_SESSION['user_id']) {
        $sessaoDAO->invalidarSessao($sessionId);
        session_destroy();
        header("Location: ../index.php");
        exit();
    }

    $admin_id = $_SESSION['user_id'];
} catch (Exception $e) {
    error_log("Erro ao verificar sessão: " . $e->getMessage());
    header("Location: ../index.php");
    exit();
}

// Buscar estatísticas para o dashboard
try {
    // Estatísticas de usuários
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_usuarios,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as usuarios_ativos,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as usuarios_inativos,
            SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as administradores,
            SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as usuarios_comuns
        FROM users
    ");
    $stmt->execute();
    $stats_usuarios = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estatísticas de tarefas
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_tarefas,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as tarefas_pendentes,
            SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as tarefas_concluidas,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as tarefas_alta_prioridade
        FROM tasks
    ");
    $stmt->execute();
    $stats_tarefas = $stmt->fetch(PDO::FETCH_ASSOC);

    // Estatísticas de categorias
    $stmt = $conn->prepare("SELECT COUNT(*) as total_categorias FROM categories");
    $stmt->execute();
    $stats_categorias = $stmt->fetch(PDO::FETCH_ASSOC);

    // Últimos usuários registrados
    $stmt = $conn->prepare("
        SELECT id, email, role, status, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $ultimos_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tarefas recentes
    $stmt = $conn->prepare("
        SELECT t.*, u.email as usuario_email, c.name as categoria_nome
        FROM tasks t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN categories c ON t.category_id = c.id
        ORDER BY t.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $tarefas_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $stats_usuarios = $stats_tarefas = $stats_categorias = [];
    $ultimos_usuarios = $tarefas_recentes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Sistema To-Do List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="dashboardAdmin.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-crown"></i> Admin</h2>
            <p>Painel de Controle</p>
        </div>
        <div class="sidebar-menu">
            <a href="#dashboard" class="menu-item active" data-tab="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="#usuarios" class="menu-item" data-tab="usuarios">
                <i class="fas fa-users"></i>
                <span>Gerenciar Usuários</span>
            </a>
            <a href="#tarefas" class="menu-item" data-tab="tarefas">
                <i class="fas fa-tasks"></i>
                <span>Gerenciar Tarefas</span>
            </a>
            <a href="#categorias" class="menu-item" data-tab="categorias">
                <i class="fas fa-tags"></i>
                <span>Gerenciar Categorias</span>
            </a>
            <a href="#relatorios" class="menu-item" data-tab="relatorios">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
            <a href="#configuracoes" class="menu-item" data-tab="configuracoes">
                <i class="fas fa-cog"></i>
                <span>Configurações</span>
            </a>
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <h1>Painel Administrativo</h1>
                <p>Bem-vindo de volta, Administrador!</p>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div>
                        <div style="font-weight: 600;">Administrador</div>
                        <div style="font-size: 0.875rem; color: var(--secondary);">Online</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_usuarios['total_usuarios'] ?? 0; ?></div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_usuarios['usuarios_ativos'] ?? 0; ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_tarefas['total_tarefas'] ?? 0; ?></div>
                    <div class="stat-label">Total de Tarefas</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?php echo $stats_categorias['total_categorias'] ?? 0; ?></div>
                    <div class="stat-label">Categorias</div>
                </div>
            </div>

            <!-- Charts and Recent Activity -->
            <div class="content-grid">
                <div class="chart-container">
                    <div class="section-title">
                        <i class="fas fa-chart-line"></i>
                        <span>Estatísticas do Sistema</span>
                    </div>
                    <canvas id="systemStatsChart" height="250"></canvas>
                </div>

                <div class="table-container">
                    <div class="section-title">
                        <i class="fas fa-user-plus"></i>
                        <span>Últimos Usuários</span>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $usuario['role'] === 'admin' ? 'badge-primary' : 'badge-info'; ?>">
                                            <?php echo $usuario['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $usuario['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="table-container">
                <div class="section-title">
                    <i class="fas fa-clock"></i>
                    <span>Tarefas Recentes</span>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Usuário</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Prioridade</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarefas_recentes as $tarefa): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarefa['title']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['usuario_email']); ?></td>
                                <td><?php echo htmlspecialchars($tarefa['categoria_nome']); ?></td>
                                <td>
                                    <span class="badge <?php echo $tarefa['status'] === 'concluida' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo $tarefa['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $tarefa['priority'] === 'high' ? 'badge-danger' : ($tarefa['priority'] === 'medium' ? 'badge-warning' : 'badge-info'); ?>">
                                        <?php echo $tarefa['priority']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($tarefa['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other Tabs (simplified for example) -->
        <div id="usuarios" class="tab-content" style="display: none;">
            <div class="table-container">
                <div class="section-title">
                    <i class="fas fa-users"></i>
                    <span>Gerenciar Usuários</span>
                    <button class="btn btn-primary" onclick="openUserModal()">
                        <i class="fas fa-plus"></i> Novo Usuário
                    </button>
                </div>
                <div id="usersTable">
                    <!-- Users table will be loaded via AJAX -->
                    <p>Carregando usuários...</p>
                </div>
            </div>
        </div>

        <div id="tarefas" class="tab-content" style="display: none;">
            <div class="table-container">
                <div class="section-title">
                    <i class="fas fa-tasks"></i>
                    <span>Gerenciar Tarefas</span>
                </div>
                <div id="tasksTable">
                    <!-- Tasks table will be loaded via AJAX -->
                    <p>Carregando tarefas...</p>
                </div>
            </div>
        </div>

        <!-- Add more tabs as needed -->
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gerenciar Usuário</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="userEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipo de Usuário</label>
                        <select id="userRole" class="form-control" required>
                            <option value="user">Usuário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="userStatus" class="form-control" required>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Salvar</button>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();

                    // Remove active class from all items
                    document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');

                    // Add active class to clicked item
                    this.classList.add('active');

                    // Show corresponding tab
                    const tabId = this.getAttribute('href').substring(1);
                    document.getElementById(tabId).style.display = 'block';

                    // Load tab content if needed
                    loadTabContent(tabId);
                }
            });
        });

        // Load tab content dynamically
        function loadTabContent(tabId) {
            switch (tabId) {
                case 'usuarios':
                    loadUsers();
                    break;
                case 'tarefas':
                    loadTasks();
                    break;
                    // Add other cases as needed
            }
        }

        // Chart initialization
        const ctx = document.getElementById('systemStatsChart').getContext('2d');
        const systemStatsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Usuários', 'Tarefas', 'Categorias', 'Concluídas', 'Pendentes'],
                datasets: [{
                    label: 'Estatísticas do Sistema',
                    data: [
                        <?php echo $stats_usuarios['total_usuarios'] ?? 0; ?>,
                        <?php echo $stats_tarefas['total_tarefas'] ?? 0; ?>,
                        <?php echo $stats_categorias['total_categorias'] ?? 0; ?>,
                        <?php echo $stats_tarefas['tarefas_concluidas'] ?? 0; ?>,
                        <?php echo $stats_tarefas['tarefas_pendentes'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#4a6cf7',
                        '#28a745',
                        '#17a2b8',
                        '#20c997',
                        '#ffc107'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Modal functionality
        const userModal = document.getElementById('userModal');
        const closeBtn = document.querySelector('.close');

        function openUserModal(userId = null) {
            if (userId) {
                // Load user data for editing
                loadUserData(userId);
            } else {
                // Clear form for new user
                document.getElementById('userForm').reset();
                document.getElementById('userId').value = '';
            }
            userModal.style.display = 'block';
        }

        function closeUserModal() {
            userModal.style.display = 'none';
        }

        closeBtn.onclick = closeUserModal;

        window.onclick = function(event) {
            if (event.target == userModal) {
                closeUserModal();
            }
        }

        // AJAX functions
        function loadUsers() {
            $.ajax({
                url: '../../controllers/listar_users.php',
                type: 'GET',
                success: function(response) {
                    $('#usersTable').html(response);
                },
                error: function() {
                    $('#usersTable').html('<p>Erro ao carregar usuários.</p>');
                }
            });
        }

        function loadTasks() {
            $.ajax({
                url: '../../controllers/list_all_task.php',
                type: 'GET',
                success: function(response) {
                    $('#tasksTable').html(response);
                },
                error: function() {
                    $('#tasksTable').html('<p>Erro ao carregar tarefas.</p>');
                }
            });
        }

        function saveUser() {
            const formData = {
                id: $('#userId').val(),
                email: $('#userEmail').val(),
                role: $('#userRole').val(),
                status: $('#userStatus').val()
            };

            $.ajax({
                url: '../../controllers/save_users.php',
                type: 'POST',
                data: formData,
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        closeUserModal();
                        loadUsers();
                        alert('Usuário salvo com sucesso!');
                    } else {
                        alert('Erro: ' + result.message);
                    }
                },
                error: function() {
                    alert('Erro ao salvar usuário.');
                }
            });
        }

        function loadUserData(userId) {
            $.ajax({
                url: '../../controllers/admin/carregar_usuario.php',
                type: 'GET',
                data: {
                    id: userId
                },
                success: function(response) {
                    const user = JSON.parse(response);
                    $('#userId').val(user.id);
                    $('#userEmail').val(user.email);
                    $('#userRole').val(user.role);
                    $('#userStatus').val(user.status);
                },
                error: function() {
                    alert('Erro ao carregar dados do usuário.');
                }
            });
        }

        function deleteUser(userId) {
            if (confirm('Tem certeza que deseja excluir este usuário?')) {
                $.ajax({
                    url: '../../controllers/admin/excluir_usuario.php',
                    type: 'POST',
                    data: {
                        id: userId
                    },
                    success: function(response) {
                        const result = JSON.parse(response);
                        if (result.success) {
                            loadUsers();
                            alert('Usuário excluído com sucesso!');
                        } else {
                            alert('Erro: ' + result.message);
                        }
                    },
                    error: function() {
                        alert('Erro ao excluir usuário.');
                    }
                });
            }
        }

        // Initialize dashboard
        $(document).ready(function() {
            loadUsers(); // Pre-load users for better UX
        });
    </script>
</body>

</html>