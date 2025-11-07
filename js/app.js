$(document).ready(function () {
    $('#myTableTopBar').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/appQuery.php",
            "type": "POST",
            "data": { 'getAllMessages': true },
        },
        "columns": [
            { "data": 0 },
            { "data": 1 },
            { "data": 2 }
        ],

    });

    $('#myTableContactMessage').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ajaxfiles/appQuery.php",
            "type": "POST",
            "data": { 'getAllContactMsg': true },
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


function newTopBar() {


    $.ajax({
        url: 'ajaxfiles/appQuery.php',
        type: 'POST',
        data: {
            ModalTopBar: true
        },
        success: function (data) {
            $('#update').html(data);
            $('#ajouterTopBarModal').modal('show');

        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}


$(document).on('submit', '#AddMessageTopBar', function (event) {
    event.preventDefault();

    var formData = new FormData(this);

    formData.append('ajouterMessageBar', true);
    $.ajax({
        url: 'ajaxfiles/appQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function (data) {
            if (data.status === 'success') {

                Swal.fire({
                    icon: 'success',
                    title: 'message ajoutée ',
                    text: data.message,
                    confirmButtonText: 'OK',
                }).then(() => {
                    $('#ajouterTopBarModal').modal('hide');
                    $('.modal-backdrop').remove();
                });
                $('#myTableTopBar').DataTable().ajax.reload(null, false);
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

function supprimerMessageBar(id) {
    $.ajax({
        url: 'ajaxfiles/appQuery.php',
        type: 'POST',
        dataType: 'json', // ✅ Ajouté
        data: {
            deleteMessageBar: true,
            id: id
        },
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Message supprimé',
                    text: data.message,
                    confirmButtonText: 'OK'
                });
                // ✅ Rafraîchir la DataTable sans recharger toute la page
                $('#myTableTopBar').DataTable().ajax.reload(null, false);
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


function modifierMessageBar(id) {


    $.ajax({
        url: 'ajaxfiles/appQuery.php',
        type: 'POST',
        data: {
            modalUpdateMessage: true,
            id: id
        },
        success: function (data) {
            $('#update').html(data);
            $('#modifierTopBarModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX:", error);
        }
    });
}

$(document).on('submit', '#UpdateMessageTopBar', function (event) {
    event.preventDefault();
    var formData = new FormData(this);
    formData.append('updateMessage', true);

    $.ajax({
        url: 'ajaxfiles/appQuery.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Message ajouté',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#modifierTopBarModal').modal('hide');
                    $('.modal-backdrop').remove();
                });
                $('#myTableTopBar').DataTable().ajax.reload(null, false);
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
            console.log("Erreur :", error);
            console.log("Réponse brute :", xhr.responseText);
        }
    });
});


// Formulaire de contact
$('#contactForm').on('submit', function (e) {
    e.preventDefault();

    const name = $('#name').val().trim();
    const email = $('#email').val().trim();
    const phone = $('#phone').val().trim();
    const message = $('#message').val().trim();

    if (!name || !email || !phone || !message) {
        showToast('Erreur');
        return;
    }

    $.ajax({
        url: 'ajaxfiles/send_contact.php',
        type: 'POST',
        dataType: 'json',
        data: {
            send_mail: true,
            name: name,
            email: email,
            phone: phone,
            message: message
        },
        success: function (response) {
            if (response.status == 'success') {
                showToast('Message envoyé');
                /* Swal.fire('Message envoyé', response.message, 'success'); */
                $('#contactForm')[0].reset();
            } else {
                showToast('Erreur');
                /* Swal.fire('Erreur', response.message, 'error'); */
            }
        },
        error: function (xhr, status, error) {
            console.error("Erreur AJAX :", error);
            /*  Swal.fire('Erreur', "Une erreur s'est produite lors de l'envoi.", 'error'); */
            showToast('Erreur');
        }
    });
});

/* function showToast(message, bgColor = 'black') {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: bgColor,
        close: true
    }).showToast();
}
 */

function showToast(message, bgColor = 'black') {
    Toastify({
        text: message,
        duration: 3000,
        gravity: "top",
        position: "right",
        backgroundColor: bgColor,
        close: true
    }).showToast();
}
