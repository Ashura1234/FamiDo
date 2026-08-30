import { allRoutes, websiteName } from "./allRoutes.js";

const route404 = {
  title: "Page introuvable",
  pathHtml: "./pages/404.html",
  pathJS: "",
};
let activeScript = null;

const getRouteByUrl = (url) =>
  allRoutes.find((route) => route.url === url) || route404;

const loadSharedUi = (route) => {
  const isPublic = !route.requiresAuth;
  const user = JSON.parse(localStorage.getItem("user") || "{}");
  const taskCreationPath = user.roles?.includes("ROLE_CHEF")
    ? "/taches/nouvelle"
    : "/mes-taches/nouvelle";
  document.querySelector("#site-header").innerHTML = `
    <a class="brand" href="/dashboard" data-link aria-label="Retour au tableau de bord">
      <span class="brand-mark"><i class="bi bi-house-fill"></i></span><strong>FamiDo</strong>
    </a>
    <span class="version">v 1.0</span>`;
  document.querySelector("#bottom-nav").innerHTML = isPublic
    ? ""
    : `
    <a href="/dashboard" data-link aria-label="Tableau de bord"><i class="bi bi-house-fill"></i></a>
    <a href="/taches" data-link aria-label="Tâches familiales"><i class="bi bi-list-check"></i></a>
    <a class="nav-add" href="${taskCreationPath}" data-link aria-label="Ajouter une tâche"><i class="bi bi-plus-lg"></i></a>
    <a href="/famille" data-link aria-label="Famille"><i class="bi bi-people-fill"></i></a>
    <a href="/profil" data-link aria-label="Mon profil"><i class="bi bi-person-fill"></i></a>`;
};

const loadContentPage = async () => {
  const route = getRouteByUrl(window.location.pathname);
  if (window.location.pathname === "/" && localStorage.getItem("token")) {
    window.history.replaceState({}, "", "/dashboard");
    return loadContentPage();
  }
  if (route.requiresAuth && !localStorage.getItem("token")) {
    window.history.replaceState({}, "", "/connexion");
    return loadContentPage();
  }
  const html = await fetch(route.pathHtml).then((response) => response.text());
  document.querySelector("#main-page").innerHTML = html;
  loadSharedUi(route);
  document.title = `${route.title} - ${websiteName}`;
  document
    .querySelectorAll("[data-link]")
    .forEach((link) => link.addEventListener("click", routeEvent));

  if (activeScript) activeScript.remove();
  if (route.pathJS) {
    activeScript = document.createElement("script");
    activeScript.type = "module";
    activeScript.src = `${route.pathJS}?v=${Date.now()}`;
    document.body.appendChild(activeScript);
  }
};

const routeEvent = (event) => {
  event.preventDefault();
  const href = event.currentTarget.getAttribute("href");
  window.history.pushState({}, "", href);
  loadContentPage();
};

window.addEventListener("popstate", loadContentPage);
loadContentPage();
