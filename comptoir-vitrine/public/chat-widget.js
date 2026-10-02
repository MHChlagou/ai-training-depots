/* Widget de chat (script du prestataire, version autonome pour la démonstration). */
(function () {
  // Initialisation du prestataire : simulée par une attente active de 300 ms.
  var debut = Date.now();
  while (Date.now() - debut < 300) {
    /* attente */
  }

  function afficherBulle() {
    var bouton = document.createElement("button");
    bouton.type = "button";
    bouton.textContent = "Une question ? Discutons !";
    bouton.setAttribute(
      "style",
      "position:fixed;right:16px;bottom:16px;z-index:50;padding:12px 18px;" +
        "border:0;border-radius:24px;background:#8a4b2a;color:#fff;font:600 14px sans-serif;" +
        "box-shadow:0 2px 8px rgba(0,0,0,.25);cursor:pointer"
    );
    bouton.addEventListener("click", function () {
      alert("Notre service client vous répond du lundi au vendredi.");
    });
    document.body.appendChild(bouton);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", afficherBulle);
  } else {
    afficherBulle();
  }
})();
