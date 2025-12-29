$(document).ready(function () {
  // DataTable commandes (admin / historique)
  if ($('#myTableCommandes').length) {
    $('#myTableCommandes').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "ajaxfiles/commandeProc.php",
        type: "POST",
        data: function (d) {
          d.getAllCommandes = true;
          d.csrf_token = window.CSRF_TOKEN || "";
        }
      },
      columns: [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 }
      ]
    });
  }
});

// Ouvrir modal validation commande (checkout modal)
function ValiderCommande() {
  $.ajax({
    url: 'ajaxfiles/commandeProc.php',
    type: 'POST',
    data: {
      ValiderCommandeModal: true,
      csrf_token: window.CSRF_TOKEN || ""
    },
    success: function (html) {
      $('#update').html(html);
      $('#ValiderCommandeModal').modal('show');
    },
    error: function () {
      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: "Impossible d'ouvrir la fenêtre de validation."
      });
    }
  });
}

$(document).on('submit', '#ValiderCommande', function (event) {
  event.preventDefault();

  const $form = $(this);
  const $btn = $form.find('button[type="submit"], #btnSubmitCommande').first();

  let formData = new FormData(this);
  formData.append('ValiderCommande', true);
  formData.append('csrf_token', window.CSRF_TOKEN || "");

  // UI loading
  const spinnerOverlay = $('<div class="spinner-overlay"></div>');
  const spinner = $('<div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div>');

  spinnerOverlay.css({
    position: 'absolute',
    top: '50%',
    left: '50%',
    transform: 'translate(-50%, -50%)',
    zIndex: '1050',
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    width: '100%',
    height: '100%'
  });
  spinnerOverlay.append(spinner);

  $('#ValiderCommandeModal .modal-content').css('position', 'relative').append(spinnerOverlay);
  $('#ValiderCommandeModal .modal-body').css('filter', 'blur(3px)');

  if ($btn.length) $btn.prop("disabled", true);

  $.ajax({
    url: 'ajaxfiles/commandeProc.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function (data) {
      spinnerOverlay.remove();
      $('#ValiderCommandeModal .modal-body').css('filter', 'none');
      if ($btn.length) $btn.prop("disabled", false);

      if (data && data.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'Commande validée ✅',
          text: data.message || "Votre commande a été confirmée.",
          confirmButtonText: 'OK',
        }).then(() => {
          $('#ValiderCommandeModal').modal('hide');
          $('.modal-backdrop').remove();

          // optionnel: vider / recharger panier
          window.location.href = "success.php?order=" + encodeURIComponent(data.order_id || "");
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Erreur',
          text: (data && data.message) ? data.message : "Erreur lors de la validation.",
          confirmButtonText: 'Réessayer'
        });
      }
    },
    error: function (xhr) {
      spinnerOverlay.remove();
      $('#ValiderCommandeModal .modal-body').css('filter', 'none');
      if ($btn.length) $btn.prop("disabled", false);

      let msg = "Erreur serveur. Réessayez.";
      try {
        const r = JSON.parse(xhr.responseText);
        if (r && r.message) msg = r.message;
      } catch (e) {}

      Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: msg,
        confirmButtonText: 'OK'
      });
    }
  });
});
