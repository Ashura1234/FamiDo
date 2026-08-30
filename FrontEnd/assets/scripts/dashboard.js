import { apiFetch } from "./api.js";

const list = document.querySelector("[data-dashboard-tasks]");
const count = document.querySelector("[data-dashboard-count]");
const user = JSON.parse(localStorage.getItem("user") || "{}");
const userId = Number(user.id);
document.querySelector("[data-dashboard-name]").textContent =
  user.prenom || "Utilisateur";
document.querySelector("[data-dashboard-initials]").textContent = (
  user.prenom || "U"
)
  .slice(0, 2)
  .toUpperCase();
document.querySelector("[data-dashboard-date]").textContent =
  new Intl.DateTimeFormat("fr-FR", { dateStyle: "full" }).format(new Date());

const renderTasks = ([tasks, family]) => {
  const members = Object.fromEntries(
    family.membres.map((member) => [member.id, member.prenom]),
  );
  const assignedTasks = tasks.filter((task) =>
    task.assignations?.includes(userId),
  );
  const todoTasks = assignedTasks.filter((task) => task.statut === "a_faire");
  const doneTasks = assignedTasks.filter((task) => task.statut === "terminee");
  count.textContent = `${assignedTasks.length} tâche(s)`;
  document.querySelector("[data-todo-count]").textContent = todoTasks.length;
  document.querySelector("[data-done-count]").textContent = doneTasks.length;
  if (!assignedTasks.length) {
    list.innerHTML =
      '<p class="form-message">Aucune tâche ne t\'est assignée.</p>';
    return;
  }
  list.innerHTML = assignedTasks
    .slice(0, 3)
    .map((task) => {
      const assignedNames = (task.assignations || [])
        .map((id) => members[id])
        .filter(Boolean)
        .join(", ");
      return `<article class="task-row"><div><h3>${task.titre}</h3><p>${task.description || "Aucune description"}</p><small class="priority ${task.priorite === "haute" ? "high" : task.priorite === "basse" ? "low" : "medium"}">${task.priorite}</small><small>${task.dateEcheance ? `échéance : ${task.dateEcheance}` : "Sans échéance"}</small><small>Assigné à : ${assignedNames || "personne"}</small></div><span class="assignee"><i class="bi bi-people-fill"></i></span></article>`;
    })
    .join("");
};

Promise.all([apiFetch("/taches"), apiFetch("/famille")])
  .then(renderTasks)
  .catch((error) => {
    count.textContent = "Indisponible";
    list.innerHTML = `<p class="form-message">${error.message}</p>`;
  });
