$(document).ready(function() {

});

function selectToernooi(button) {
    toernooi = button.value;
    toernooi_id = button.getAttribute('data-toernooi-id');
    console.log("selectToernooi, toernooi= "+toernooi_id+", "+toernooi);
    // sla geselecteerde toernooi op in php session variabele voor opbouw twig templates
    $.post("/selectToernooi/"+toernooi_id+"/"+toernooi, function(response){
        window.location.replace(response.redirecturl);
    }, 'json');
}

function schrijfIn() {
    $.post("/deelnemer/account/"+"schrijfIn", function(response){
        // try no action
    });
}

function selecteerEnSchrijfIn(button) {
    toernooi_id = button.getAttribute('data-toernooi-id');
    toernooi_naam = button.getAttribute('data-toernooi-naam');
    window.location.replace("/selecteerEnSchrijfIn/"+toernooi_id);
}

function loginMetRol(rol){
    console.log("loginMetRol, rol= "+rol);
    $.post("/login_met_rol/" + rol, function(response){
        window.location.replace(response.redirecturl);
    }, 'json');
}

function zetStatusTerug(){
    console.log("zetStatusTerug");
    if (confirm("Weet u zeker dat u de status wil terug zetten?")) {
        $.post("/zet_status_terug", function(response){
            window.location.replace('/home');
        }, 'json');
    } else {
        alert("status ongewijzigd.")
    }

}

function toggleSidebar(){
    var sidebars = document.getElementsByClassName("my-sidebar");
    if (sidebars[0].style.display == "block") {
        sidebars[0].style.display = "none";
    } else {
        sidebars[0].style.display = "block";
    }
}
