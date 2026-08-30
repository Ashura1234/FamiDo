import { apiFetch } from "./api.js";

const list = document.querySelector("[data-tasks-list]");
const privateList = window.location.pathname === "/mes-taches";
const isChef = JSON.parse(localStorage.getItem("user") || "{}").roles?.includes(
  "ROLE_CHEF",
);
const statusFilter = document.querySelector("[data-filter-status]");
const priorityFilter = document.querySelector("[data-filter-priority]");
let allTasks = [];
document.querySelectorAll("[data-chef-only]").forEach((element) => {
  if (!isChef) element.remove();
});

const renderTasks = (tasks) => {
  allTasks = tasks;
  const filteredTasks = tasks.filter((task) => {
    const statusMatches =
      !statusFilter ||
      statusFilter.value === "all" ||
      task.statut === statusFilter.value;
    const priorityMatches =
      !priorityFilter ||
      priorityFilter.value === "all" ||
      task.priorite === priorityFilter.value;
    return statusMatches && priorityMatches;
  });
  if (!filteredTasks.length) {
    list.innerHTML =
      '<section class="empty-state"><span class="empty-icon"><i class="bi bi-check2"></i></span><h2>Aucune tâche</h2><p>Tout est calme pour le moment.</p></section>';
    return;
  }
  list.innerHTML = filteredTasks
    .map(
      (task) => `
    <article class="task-card" data-task-id="${task.id}">
      <div class="task-card-top"><h2>${task.titre}</h2>${(isChef && !privateList) || privateList ? `<button class="status-button" data-status="${task.statut}" data-status-id="${task.id}" data-private-status="${privateList}">${task.statut === "terminee" ? "✓ Fait" : "◉ À faire"}</button>` : `<span class="status-button">${task.statut === "terminee" ? "✓ Fait" : "◉ À faire"}</span>`}</div>
      <p>${task.description || "Aucune description"}</p>
      <div class="task-meta"><span class="priority ${task.priorite === "haute" ? "high" : task.priorite === "basse" ? "low" : "medium"}">${task.priorite}</span><span>${task.dateEcheance ? `échéance : ${task.dateEcheance}` : "Sans échéance"}</span></div>
      ${isChef && !privateList ? `<div class="card-actions"><button class="button button-outline" data-edit-id="${task.id}" data-edit-title="${task.titre}"><i class="bi bi-pencil"></i> Modifier</button><button class="button button-dark" data-delete-id="${task.id}"><i class="bi bi-trash"></i> Supprimer</button></div>` : ""}
    </article>`,
    )
    .join("");
  list.querySelectorAll("[data-status-id]").forEach((button) =>
    button.addEventListener("click", async () => {
      const status =
        button.dataset.status === "terminee" ? "a_faire" : "terminee";
      const endpoint =
        button.dataset.privateStatus === "true"
          ? `/mes-taches/${button.dataset.statusId}`
          : `/taches/${button.dataset.statusId}/statut`;
      try {
        await apiFetch(endpoint, {
          method: button.dataset.privateStatus === "true" ? "PUT" : "PATCH",
          body: JSON.stringify({ statut: status }),
        });
        button.dataset.status = status;
        button.textContent = status === "terminee" ? "✓ Fait" : "◉ À faire";
        const task = allTasks.find(
          (item) => item.id === Number(button.dataset.statusId),
        );
        if (task) task.statut = status;
      } catch (error) {
        window.alert(error.message);
      }
    }),
  );
  list.querySelectorAll("[data-edit-id]").forEach((button) =>
    button.addEventListener("click", async () => {
      const title = window.prompt("Nouveau titre", button.dataset.editTitle);
      if (!title?.trim()) return;
      try {
        await apiFetch(`/taches/${button.dataset.editId}`, {
          method: "PUT",
          body: JSON.stringify({ titre: title.trim() }),
        });
        button.closest(".task-card").querySelector("h2").textContent =
          title.trim();
        button.dataset.editTitle = title.trim();
      } catch (error) {
        window.alert(error.message);
      }
    }),
  );
  list.querySelectorAll("[data-delete-id]").forEach((button) =>
    button.addEventListener("click", async () => {
      if (!window.confirm("Supprimer cette tâche ?")) return;
      try {
        await apiFetch(`/taches/${button.dataset.deleteId}`, {
          method: "DELETE",
        });
        button.closest(".task-card").remove();
      } catch (error) {
        window.alert(error.message);
      }
    }),
  );
};

if (list) {
  apiFetch(privateList ? "/mes-taches" : "/taches")
    .then(renderTasks)
    .catch((error) => {
      list.innerHTML = `<p class="form-message">${error.message}</p>`;
    });
  statusFilter?.addEventListener("change", () => renderTasks(allTasks));
  priorityFilter?.addEventListener("change", () => renderTasks(allTasks));
}
