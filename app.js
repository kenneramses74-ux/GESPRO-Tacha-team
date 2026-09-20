/**
 * Comportement commun à l'espace connecté (sidebar, recherche).
 *
 * Avant : deux scripts redondants coexistaient dans ce fichier, l'un
 * ciblant #sidebarToggle et les classes .mini/.mobile-open (celles
 * réellement définies dans style.css), l'autre ciblant un bouton
 * #menuToggle qui n'existait sur aucune page et une classe .active
 * qui n'existait dans aucune feuille de style : ce second bloc ne
 * faisait donc jamais rien. Il a été retiré et ses parties utiles
 * (recherche, fermeture au clic sur un lien, touche Échap) ont été
 * fusionnées avec le script qui fonctionne réellement.
 */
document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('sidebarToggle');
    var main = document.getElementById('mainContent');

    function fermerMenuMobile() {
        if (sidebar) sidebar.classList.remove('mobile-open');
    }

    if (sidebar && toggle) {
        toggle.addEventListener('click', function () {
            if (window.innerWidth <= 700) {
                sidebar.classList.toggle('mobile-open');
            } else {
                sidebar.classList.toggle('mini');
                if (main) main.classList.toggle('sidebar-mini');
            }
        });

        // Referme le menu mobile après un clic sur un lien du menu.
        sidebar.querySelectorAll('a').forEach(function (lien) {
            lien.addEventListener('click', fermerMenuMobile);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') fermerMenuMobile();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 700) fermerMenuMobile();
    });

    // Recherche instantanée (utilisée sur la page des tâches, etc.)
    var searchInput = document.getElementById('searchInput');
    var searchItems = document.querySelectorAll('.searchable');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var texte = this.value.toLowerCase().trim();
            searchItems.forEach(function (item) {
                var contenu = item.textContent.toLowerCase();
                item.style.display = (texte === '' || contenu.includes(texte)) ? '' : 'none';
            });
        });
    }
});
