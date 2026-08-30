import { apiFetch, saveSession } from "./api.js";

const form = document.querySelector("[data-form]");

form?.addEventListener("submit", async (event) => {
  event.preventDefault();
  const message = form.querySelector("[data-message]");
  const values = Object.fromEntries(new FormData(form));
  try {
    if (
      form.dataset.form === "register" &&
      values.password !== values.confirm
    ) {
      throw new Error("Les mots de passe ne correspondent pas.");
    }
    const endpoint = form.dataset.form === "login" ? "/login" : "/register";
    const data = await apiFetch(endpoint, {
      method: "POST",
      body: JSON.stringify(values),
    });
    saveSession(data);
    window.history.pushState(
      {},
      "",
      form.dataset.form === "register" ? "/famille" : "/dashboard",
    );
    window.dispatchEvent(new PopStateEvent("popstate"));
  } catch (error) {
    message.textContent = error.message;
    message.className = "form-message";
  }
});
