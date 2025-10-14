// JavaScript pour la gestion du formulaire locataire
$(document).ready(function() {

    // --- Autocomplétion commune ---
    $('#commune').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/ajax_commune.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    term: request.term
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.nom_commune,
                            value: item.nom_commune,
                            id: item.id_commune
                        };
                    }));
                }
            });
        },
        minLength: 2,
        select: function(_, ui) {
            // Sauvegarde l'id_commune dans un champ caché
            $('#id_commune').val(ui.item.id);
        }
    });

    // --- Toggle champs entreprise ---
    $('#isEntreprise').on('change', function() {
        if ($(this).is(':checked')) {
            $('#entrepriseFields').removeClass('d-none');
        } else {
            $('#entrepriseFields').addClass('d-none');
        }
    });

    $('#prestations').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '../ajax/ajax_prestations.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'search',
                    search: request.term
                },
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.libelle_prestation,
                            value: item.libelle_prestation,
                            id: item.id_prestation
                        };
                    }));
                }
            });
        },
        minLength: 2,
    select: function(_, ui) {
        // Ajoute la prestation sélectionnée à la liste
        var selectedPrestation = $('<li class="list-group-item d-flex justify-content-between align-items-center"></li>');
        selectedPrestation.text(ui.item.label);
        var removeBtn = $('<button type="button" class="btn btn-sm btn-danger">&times;</button>');
        removeBtn.on('click', function() {
            $(this).parent().remove();
        });
        selectedPrestation.append(removeBtn);
        $('#selectedPrestations').append(selectedPrestation);
    }
});
});