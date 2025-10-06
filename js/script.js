// JavaScript pour la gestion du formulaire locataire
$(document).ready(function() {

    // --- Autocomplétion commune ---
    $('#commune').autocomplete({
        source: function(request, response) {
            $.ajax({
                url: 'ajax_commune.php',
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
        select: function(event, ui) {
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

});
