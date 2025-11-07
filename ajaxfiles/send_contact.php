<?php
include '../connection.php';


if(isset($_POST['send_mail'])){

    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $message = isset($_POST['message']) ? $_POST['message'] : '';

    // Insertion sécurisée
    $sql = "INSERT INTO contact_message (message, email, nom, num_tel, date_ajout) 
            VALUES ('$message', '$email', '$name', '$phone', NOW())";

    if (mysqli_query($con, $sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Message envoyé avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'envoi.", 'error' => mysqli_error($con)]);
    }

}
?>