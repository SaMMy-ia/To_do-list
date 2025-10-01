// Simulação de dados (em um sistema real, isso viria de um backend)
let tasks = JSON.parse(localStorage.getItem("userTasks")) || [];
let currentFilter = "all";

// Elementos DOM
const taskForm = document.getElementById("task-form");
const todoList = document.getElementById("todo-list");
const filterButtons = document.querySelectorAll(".filter-btn");

// Estatísticas
const totalTasksEl = document.getElementById("total-tasks");
const pendingTasksEl = document.getElementById("pending-tasks");
const completedTasksEl = document.getElementById("completed-tasks");
const highPriorityTasksEl = document.getElementById("high-priority-tasks");

// Inicialização
document.addEventListener("DOMContentLoaded", function () {
  updateStats();
  renderTasks();

  // Configurar filtros
  filterButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Remover classe active de todos os botões
      filterButtons.forEach((btn) => btn.classList.remove("active"));
      // Adicionar classe active ao botão clicado
      this.classList.add("active");
      // Atualizar filtro
      currentFilter = this.getAttribute("data-filter");
      renderTasks();
    });
  });
});

// Adicionar nova tarefa
taskForm.addEventListener("submit", function (e) {
  e.preventDefault();

  const title = document.getElementById("task-title").value;
  const description = document.getElementById("task-description").value;
  const priority = document.getElementById("task-priority").value;
  const dueDate = document.getElementById("task-due-date").value;

  const newTask = {
    id: Date.now(),
    title: title,
    description: description,
    priority: priority,
    dueDate: dueDate,
    completed: false,
    createdAt: new Date().toISOString(),
  };

  tasks.push(newTask);
  saveTasks();
  renderTasks();
  updateStats();

  // Limpar formulário
  taskForm.reset();
});

// Renderizar tarefas
function renderTasks() {
  // Limpar lista
  todoList.innerHTML = "";

  // Filtrar tarefas
  let filteredTasks = tasks;
  if (currentFilter === "pending") {
    filteredTasks = tasks.filter((task) => !task.completed);
  } else if (currentFilter === "completed") {
    filteredTasks = tasks.filter((task) => task.completed);
  }

  // Se não houver tarefas, mostrar estado vazio
  if (filteredTasks.length === 0) {
    const emptyState = document.createElement("li");
    emptyState.className = "empty-state";
    emptyState.innerHTML = `
                    <i>📝</i>
                    <p>Nenhuma tarefa ${
                      currentFilter === "all"
                        ? "encontrada"
                        : currentFilter === "pending"
                        ? "pendente"
                        : "concluída"
                    }. Adicione uma nova tarefa!</p>
                `;
    todoList.appendChild(emptyState);
    return;
  }

  // Ordenar tarefas (não concluídas primeiro, depois por prioridade)
  filteredTasks.sort((a, b) => {
    if (a.completed !== b.completed) {
      return a.completed ? 1 : -1;
    }

    const priorityOrder = { high: 3, medium: 2, low: 1 };
    return priorityOrder[b.priority] - priorityOrder[a.priority];
  });

  // Adicionar tarefas à lista
  filteredTasks.forEach((task) => {
    const taskItem = document.createElement("li");
    taskItem.className = `todo-item ${task.completed ? "completed" : ""}`;

    const priorityText = {
      high: "Alta",
      medium: "Média",
      low: "Baixa",
    };

    taskItem.innerHTML = `
                    <input type="checkbox" class="todo-checkbox" ${
                      task.completed ? "checked" : ""
                    } data-id="${task.id}">
                    <div class="todo-content">
                        <div class="todo-title">${task.title}</div>
                        ${
                          task.description
                            ? `<div class="todo-description">${task.description}</div>`
                            : ""
                        }
                        <div>
                            <span class="todo-priority priority-${
                              task.priority
                            }">${priorityText[task.priority]}</span>
                            ${
                              task.dueDate
                                ? `<span class="todo-date">Vence: ${formatDate(
                                    task.dueDate
                                  )}</span>`
                                : ""
                            }
                        </div>
                    </div>
                    <div class="todo-actions">
                        <button class="action-btn edit-btn" data-id="${
                          task.id
                        }">✏️</button>
                        <button class="action-btn delete-btn" data-id="${
                          task.id
                        }">🗑️</button>
                    </div>
                `;

    todoList.appendChild(taskItem);
  });

  // Adicionar event listeners para as ações
  document.querySelectorAll(".todo-checkbox").forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      toggleTaskCompletion(parseInt(this.getAttribute("data-id")));
    });
  });

  document.querySelectorAll(".edit-btn").forEach((button) => {
    button.addEventListener("click", function () {
      editTask(parseInt(this.getAttribute("data-id")));
    });
  });

  document.querySelectorAll(".delete-btn").forEach((button) => {
    button.addEventListener("click", function () {
      deleteTask(parseInt(this.getAttribute("data-id")));
    });
  });
}

// Alternar conclusão da tarefa
function toggleTaskCompletion(taskId) {
  const taskIndex = tasks.findIndex((task) => task.id === taskId);
  if (taskIndex !== -1) {
    tasks[taskIndex].completed = !tasks[taskIndex].completed;
    saveTasks();
    renderTasks();
    updateStats();
  }
}

// Editar tarefa (simulação simples)
function editTask(taskId) {
  const task = tasks.find((task) => task.id === taskId);
  if (task) {
    // Em um sistema real, abriria um modal ou formulário de edição
    const newTitle = prompt("Editar título da tarefa:", task.title);
    if (newTitle !== null && newTitle.trim() !== "") {
      task.title = newTitle.trim();
      saveTasks();
      renderTasks();
    }
  }
}

// Excluir tarefa
function deleteTask(taskId) {
  if (confirm("Tem certeza que deseja excluir esta tarefa?")) {
    tasks = tasks.filter((task) => task.id !== taskId);
    saveTasks();
    renderTasks();
    updateStats();
  }
}

// Atualizar estatísticas
function updateStats() {
  const total = tasks.length;
  const pending = tasks.filter((task) => !task.completed).length;
  const completed = tasks.filter((task) => task.completed).length;
  const highPriority = tasks.filter((task) => task.priority === "high").length;

  totalTasksEl.textContent = total;
  pendingTasksEl.textContent = pending;
  completedTasksEl.textContent = completed;
  highPriorityTasksEl.textContent = highPriority;
}

// Salvar tarefas no localStorage (simulação de banco de dados)
function saveTasks() {
  localStorage.setItem("userTasks", JSON.stringify(tasks));
}

// Formatar data para exibição
function formatDate(dateString) {
  const options = { day: "2-digit", month: "2-digit", year: "numeric" };
  return new Date(dateString).toLocaleDateString("pt-BR", options);
}
