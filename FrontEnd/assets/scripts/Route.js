export default class Route {
  constructor(url, title, pathHtml, pathJS = "", requiresAuth = false) {
    this.url = url;
    this.title = title;
    this.pathHtml = pathHtml;
    this.pathJS = pathJS;
    this.requiresAuth = requiresAuth;
  }
}
