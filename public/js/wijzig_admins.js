function addAdmin() {
    admin_email = $("#nieuwe_admin").val();
    $.post("/add_admin/"+toernooi_id+"/"+admin_email,
        function(response){
            console.log("addAdmin(), response");
            if (response.status == 'OK') {
                // refresh de pagina om ook de nieuwe toernooi admin te laten zien
                window.location = "/wijzig_admins";
            } else {
                // laat foutboodschap zien:
                $("#error_message").html(response.message);
            }
        });
}

function deleteAdmin(button) {
    admin_email = button.getAttribute('data-admin');
    $.post("/delete_admin/"+toernooi_id+"/"+admin_email,
        function(response){
            console.log("deleteAdmin(), response");
            if (response.status == 'OK') {
                // refresh de pagina om de aangepaste lijst met toernooi admins te laten zien
                window.location = "/wijzig_admins";
            } else {
                // laat foutboodschap zien:
                $("#error_message").html(response.message);
            }
        });
}