import { apiFetch } from "./api.js";

const form = document.querySelector('[data-form="private-task"]');

form?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const message = form.querySelector("[data-message]");
  try {
    await apiFetch("/mes-taches", {
      method: "POST",
      body: JSON.stringify(Object.fromEntries(new FormData(form))),
    });
    window.history.pushState({}, "", "/mes-taches");
    window.dispatchEvent(new PopStateEvent("popstate"));
  } catch (error) {
    message.textContent = error.message;
  }
});
