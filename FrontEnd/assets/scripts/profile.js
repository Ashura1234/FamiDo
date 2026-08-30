import { clearSession } from "./api.js";

const user = JSON.parse(localStorage.getItem("user") || "{}");
const prenom = user.prenom || "Utilisateur";
const role = user.roles?.includes("ROLE_CHEF")
  ? "Chef de famille"
  : "Membre de famille";

document.querySelector("[data-profile-name]").textContent = prenom;
document.querySelector("[data-profile-prenom]").textContent = prenom;
document.querySelector("[data-profile-email]").textContent = user.email || "-";
document.querySelector("[data-profile-role]").textContent = role;
document.querySelector("[data-profile-role-detail]").textContent = role;
document.querySelector("[data-profile-initials]").textContent = prenom
  .slice(0, 2)
  .toUpperCase();

document.querySelector("[data-logout]")?.addEventListener("click", () => {
  clearSession();
  window.history.pushState({}, "", "/");
  window.dispatchEvent(new PopStateEvent("popstate"));
});
