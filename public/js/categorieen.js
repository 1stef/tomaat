function addCat() {
    cat = $("#nieuwe_cat").val();
    type = $("#nieuw_type").val();
    $.post("/add_categorie/"+cat+"/"+type,
        function(response){
            console.log("addCat(), response");
            if (response.status == 'OK') {
                // refresh de pagina om ook de nieuwe toernooi admin te laten zien
                //window.location = "/categorieen";
                $('#modalCategorieen').modal('hide');
                bewerkCategorieen();
            } else {
                // laat foutboodschap zien:
                $("#error_message").html(response.message);
            }
        });
}

function deleteCat(button) {
    cat = button.getAttribute('data-cat');
    $.post("/delete_categorie/"+cat,
        function(response){
            console.log("deleteCat(), response");
            if (response.status == 'OK') {
                // refresh de pagina om de aangepaste lijst met toernooi admins te laten zien
                // window.location = "/categorieen";
                $('#modalCategorieen').modal('hide');
                bewerkCategorieen();
            } else {
                // laat foutboodschap zien:
                $("#error_message").html(response.message);
            }
        });
}

function bewerkCategorieen() {
    console.log("bewerkCategorieen");
    $.post("/categorieen", function(response){
        console.log("bewerkCategorieen, response: "+response);
        $("#modalPlaceHolder").html(response);
        $('#modalCategorieen').modal('show');
    });

}