$(document).ready(function () {
    $('#myTableCommandes').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/commandeProc.php",
            "type": "POST",
            "data": { 'getAllCommandes': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 },
            { "data": 2 },
            { "data": 3 },
            { "data": 4 },
            { "data": 5 },
            { "data": 6 },
            { "data": 7 },
            { "data": 8 },
            { "data": 9 }
        ],

    });
});

function ValiderCommande(){
    console.log("clickkk");
    $.ajax({
        url: 'ajaxfiles/commandeProc.php',
        type: 'POST',
        data: {
            ValiderCommandeModal: true
        },
        success: function (data) {
            $('#update').html(data);
            $('#ValiderCommandeModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}

$(document).on('submit', '#ValiderCommande', function (event) {
    event.preventDefault();

    var formData = new FormData(this);
    formData.append('ValiderCommande', true);

    // Créer le spinner et l'ajouter au modal
    var spinnerOverlay = $('<div class="spinner-overlay"></div>');
    var spinner = $('<div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div>');

    // Centrer le spinner dans le modal
    spinnerOverlay.css({
        'position': 'absolute',
        'top': '50%',
        'left': '50%',
        'transform': 'translate(-50%, -50%)',
        'z-index': '1050', 
        'display': 'flex',
        'justify-content': 'center',
        'align-items': 'center',
        'width': '100%',
        'height': '100%'
    });
    spinnerOverlay.append(spinner);

    // Ajouter le spinner au-dessus du modal body
    $('#ValiderCommandeModal .modal-content').append(spinnerOverlay);

    // Appliquer l'effet flou au body du modal
    $('#ValiderCommandeModal .modal-body').css('filter', 'blur(5px)');

    // Afficher le modal
    $('#ValiderCommandeModal').modal('show');

    $.ajax({
        url: 'ajaxfiles/commandeProc.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function (data) {
            // Attendre 2 secondes avant de masquer le spinner et afficher le résultat
            setTimeout(function() {
                // Masquer le spinner
                spinnerOverlay.remove();

                // Retirer le flou du modal body
                $('#ValiderCommandeModal .modal-body').css('filter', 'none');

                // Afficher le résultat dans un Swal
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Commande Validée !',
                        text: `Votre commande a été validée avec succès. ${data.message}`,
                        confirmButtonText: 'OK',
                    }).then(() => {
                        $('#ValiderCommandeModal').modal('hide');
                        $('.modal-backdrop').remove();
                    });
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur de Validation',
                        text: `Une erreur est survenue lors de la validation de la commande. ${data.message}`,
                        confirmButtonText: 'Réessayer'
                    });
                }
            }, 2000); // 2 secondes
        },
        error: function (xhr, status, error) {
            // Attendre 2 secondes avant d'afficher l'erreur
            setTimeout(function() {
                console.error('Erreur AJAX:', error);

                // Masquer le spinner
                spinnerOverlay.remove();

                // Retirer le flou du modal body
                $('#ValiderCommandeModal .modal-body').css('filter', 'none');

                Swal.fire({
                    icon: 'error',
                    title: 'Erreur de Connexion',
                    text: 'Une erreur est survenue lors de la tentative de connexion au serveur. Veuillez vérifier votre connexion et réessayer.',
                    confirmButtonText: 'OK'
                });
                $('#ValiderCommandeModal').modal('hide');
                $('.modal-backdrop').remove();
            }, 2000); // 2 secondes
        }
    });
});



