$(document).ready(function () {
    $('#myTableCategorie').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/categorieQuery.php",
            "type": "POST",
            "data": { 'getAllCategorie': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 },
            { "data": 2 },
            { "data": 3 },
            { "data": 4 }
        ],

    });

});

function ajouter_categorie (){
    $.ajax({
        url: 'ajaxfiles/categorieQuery.php',
        type: 'POST',
        data: {
            ajouterModalCategorie: true
        },
        success: function (data) {
            $('#update').html(data);
            $('#ajouterCategorieModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}

function modifierCategorie(id){
    $.ajax({
        url: 'ajaxfiles/categorieQuery.php',
        type: 'POST',
        data: {
            modifierModalCategorie: true,
            id:id
        },
        success: function (data) {
            $('#update').html(data);
            $('#modifierCategorieModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    }); 
}
$(document).on('submit', '#AjouterCategorie', function (event) {
    event.preventDefault();
    var productDetails = [];
    var formData = new FormData(this);
    
    formData.append('ajouterCategorie', true);
    $.ajax({
        url: 'ajaxfiles/categorieQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function (data) {
            if (data.status === 'success') {

                Swal.fire({
                    icon: 'success',
                    title: 'categorie ajoutée ',
                    text: data.message,
                    confirmButtonText: 'OK',
                }).then(() => {
                    $('#ajouterCategorieModal').modal('hide');
                    $('.modal-backdrop').remove();
                });
                $('#myTableCategorie').DataTable().ajax.reload(null, false);
            } else {

                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: data.message,
                    confirmButtonText: 'Réessayer'
                });
            }
        },
        error: function (xhr, status, error) {
            console.log(error);
        }
    });
});

function supprimerCategorie (id){
     $.ajax({
        url: 'ajaxfiles/categorieQuery.php',
        type: 'POST',
        dataType: 'json', 
        data: {
            supprimerCategorie: true,
            id: id
        },
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'categorie supprimé',
                    text: data.message,
                    confirmButtonText: 'OK'
                });
                $('#myTableCategorie').DataTable().ajax.reload(null, false);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: data.message,
                    confirmButtonText: 'Réessayer'
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
            console.log("Réponse brute :", xhr.responseText);
        }
    });
}