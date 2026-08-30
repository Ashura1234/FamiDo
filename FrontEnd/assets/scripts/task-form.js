import { apiFetch } from "./api.js";

const form = document.querySelector("[data-form=task]");
const membersContainer = document.querySelector("[data-family-members]");

apiFetch("/famille").then((family) => {
  membersContainer.innerHTML = family.membres.map((member) => `
    <label class="check"><input type="checkbox" name="userIds" value="${member.id}"> ${member.prenom}</label>`).join("");
}).catch((error) => { membersContainer.innerHTML = `<p class="form-message">${error.message}</p>`; });

form?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const message = form.querySelector("[data-message]");
  try {
    const formData = new FormData(form);
    const task = await apiFetch("/taches", {
      method: "POST",
      body: JSON.stringify({
        titre: formData.get("titre"),
        description: formData.get("description"),
        priorite: formData.get("priorite"),
        dateEcheance: formData.get("dateEcheance"),
      }),
    });
    const userIds = formData.getAll("userIds").map(Number);
    await apiFetch(`/taches/${task.id}/assignations`, {
      method: "PUT",
      body: JSON.stringify({ userIds: [...new Set(userIds)] }),
    });
    window.history.pushState({}, "", "/taches");
    window.dispatchEvent(new PopStateEvent("popstate"));
  } catch (error) {
    message.textContent = error.message;
    message.className = "form-message";
  }
});
