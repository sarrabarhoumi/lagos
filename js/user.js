$(document).ready(function () {
    $('#myTableUsers').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/userQuery.php",
            "type": "POST",
            "data": { 'getAllUsers': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 },
            { "data": 2 },
            { "data": 3 },
            { "data": 4 },
            { "data": 5 }

        ],

    });
    $('#myTableRole').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/userQuery.php",
            "type": "POST",
            "data": { 'getAllRoles': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 }

        ],

    });

});



function addUser (){
    $.ajax({
        url: 'ajaxfiles/userQuery.php',
        type: 'POST',
        data: {
            ajouterModalUser: true
        },
        success: function (data) {
            $('#update').html(data);
            $('#ajouterUserModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}

function addRole (){
    $.ajax({
        url: 'ajaxfiles/userQuery.php',
        type: 'POST',
        data: {
            ajouterRole: true
        },
        success: function (data) {
            $('#update').html(data);
            $('#ajouterRoleModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}
$(document).on('submit', '#AddRole', function (event) {
    event.preventDefault();
    var productDetails = [];
    var formData = new FormData(this);
    
    formData.append('addRole', true);
    $.ajax({
        url: 'ajaxfiles/userQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    html: `<strong>Role ajouté :</strong> ${data.message}`,
                    confirmButtonText: 'OK',
                    timer: 2500,
                    timerProgressBar: true,
                    
                });
            } else if (data.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oups...',
                    html: `<strong>Erreur :</strong> ${data.message}`,
                    confirmButtonText: 'Réessayer',
                    footer: data.error ? `<small>${data.error}</small>` : ''
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Une réponse inattendue a été reçue du serveur.',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur serveur',
                html: `<strong>Statut :</strong> ${status}<br><strong>Détail :</strong> ${error}`,
                confirmButtonText: 'OK'
            });
            console.error('AJAX Error:', error);
        }
    });
    
});
function modifierUtilisateur(id){
    $.ajax({
        url: 'ajaxfiles/userQuery.php',
        type: 'POST',
        data: {
            modifierModalUser: true,
            id:id
        },
        success: function (data) {
            $('#update').html(data);
            $('#modifierUserModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    }); 
}
$(document).on('submit', '#AddUser', function (event) {
    event.preventDefault();
    var productDetails = [];
    var formData = new FormData(this);
    
    formData.append('addUser', true);
    $.ajax({
        url: 'ajaxfiles/userQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès !',
                    html: `<strong>Utilisateur ajouté :</strong> ${data.message}`,
                    confirmButtonText: 'OK',
                    timer: 2500,
                    timerProgressBar: true,
                    didClose: () => {
                        $('#ajouterUserModal').modal('hide');
                        $('.modal-backdrop').remove();
                        // Optionnel : recharger la table des utilisateurs
                        if (typeof refreshUserTable === "function") {
                            refreshUserTable();
                        }
                    }
                });
            } else if (data.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oups...',
                    html: `<strong>Erreur :</strong> ${data.message}`,
                    confirmButtonText: 'Réessayer',
                    footer: data.error ? `<small>${data.error}</small>` : ''
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Une réponse inattendue a été reçue du serveur.',
                    confirmButtonText: 'OK'
                });
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur serveur',
                html: `<strong>Statut :</strong> ${status}<br><strong>Détail :</strong> ${error}`,
                confirmButtonText: 'OK'
            });
            console.error('AJAX Error:', error);
        }
    });
    
});