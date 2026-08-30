import Route from "./Route.js";

export const allRoutes = [
  new Route("/", "Accueil", "/pages/home.html"),
  new Route("/dashboard", "Tableau de bord", "/pages/dashboard.html", "/assets/scripts/dashboard.js", true),
  new Route("/connexion", "Connexion", "/pages/login.html", "/assets/scripts/forms.js"),
  new Route("/inscription", "Inscription", "/pages/register.html", "/assets/scripts/forms.js"),
  new Route("/profil", "Mon profil", "/pages/profile.html", "/assets/scripts/profile.js", true),
  new Route("/famille", "Ma famille", "/pages/family.html", "/assets/scripts/family.js", true),
  new Route("/taches", "Tâches familiales", "/pages/tasks.html", "/assets/scripts/tasks.js", true),
  new Route("/taches/nouvelle", "Nouvelle tâche", "/pages/task-form.html", "/assets/scripts/task-form.js", true),
  new Route("/mes-taches", "Mes tâches privées", "/pages/private-tasks.html", "/assets/scripts/tasks.js", true),
  new Route("/mes-taches/nouvelle", "Nouvelle tâche privée", "/pages/private-task-form.html", "/assets/scripts/private-task-form.js", true),
];

export const websiteName = "FamiDo";
