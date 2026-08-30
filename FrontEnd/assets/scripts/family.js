import { apiFetch } from "./api.js";

const currentUser = JSON.parse(localStorage.getItem("user") || "null");
const isChef = currentUser?.roles?.includes("ROLE_CHEF");
const createFamilyForm = document.querySelector('[data-form="create-family"]');
const joinFamilyForm = document.querySelector('[data-form="join-family"]');
if (isChef) joinFamilyForm?.remove();
else createFamilyForm?.remove();

const showFamily = (family) => {
  document.querySelector("[data-family-name]").textContent = family.nom;
  document.querySelector("[data-family-setup]").hidden = true;
  document.querySelectorAll("[data-family-content]").forEach((element) => {
    element.hidden = false;
  });
  document.querySelector("[data-code]").textContent = family.codeInvitation;
  document.querySelector("[data-member-count]").textContent =
    `${family.membres.length} personne(s)`;
  document.querySelector("[data-members]").innerHTML = family.membres
    .map(
      (member) => `
    <div class="member"><span class="avatar small">${member.prenom.slice(0, 2).toUpperCase()}</span><div><strong>${member.prenom}</strong><small>${member.email}</small></div><span class="role-tag">${member.roles.includes("ROLE_CHEF") ? "Chef" : "Membre"}</span></div>`,
    )
    .join("");
};

const showError = (form, error) => {
  form.querySelector("[data-message]").textContent = error.message;
};

apiFetch("/famille")
  .then(showFamily)
  .catch(() => {});

document
  .querySelector('[data-form="create-family"]')
  ?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      showFamily(
        await apiFetch("/famille", {
          method: "POST",
          body: JSON.stringify(
            Object.fromEntries(new FormData(event.currentTarget)),
          ),
        }),
      );
    } catch (error) {
      showError(event.currentTarget, error);
    }
  });

document
  .querySelector('[data-form="join-family"]')
  ?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      showFamily(
        await apiFetch("/famille/rejoindre", {
          method: "POST",
          body: JSON.stringify(
            Object.fromEntries(new FormData(event.currentTarget)),
          ),
        }),
      );
    } catch (error) {
      showError(event.currentTarget, error);
    }
  });

document
  .querySelector("[data-copy]")
  ?.addEventListener("click", async (event) => {
    await navigator.clipboard?.writeText(
      document.querySelector("[data-code]").textContent,
    );
    event.currentTarget.textContent = "Copié";
  });
