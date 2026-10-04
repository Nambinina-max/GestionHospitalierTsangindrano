// CSB1 Tsangindrano - scripts communs

function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
// Fermer une modale en cliquant en dehors
document.addEventListener('click', function (e) {
    document.querySelectorAll('.modal-overlay.show').forEach(function (overlay) {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});

// Filtrage rapide des tableaux via une barre de recherche (data-table-search)
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', function () {
        const term = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
}

// Confirmation avant suppression
function confirmDelete(message) {
    return confirm(message || 'Confirmez-vous la suppression ? Cette action est irréversible.');
}
