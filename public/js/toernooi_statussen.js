$(document).ready(function() {
});

function selectToernooienMetStatus(status) {
    console.log("selectToernooienMetStatus, status= "+status);
    //$.post("/toernooi_statussen/"+status);
    window.location.replace("/toernooi_statussen/"+status);
}

function setToernooiStatus(select_object) {
    status = select_object.value;
    toernooi_id = select_object.getAttribute('data-toernooi-id');
    console.log("setToernooiStatus, toernooi_id = "+toernooi_id+", status= "+status);
    $.post("/setToernooiStatus/"+toernooi_id+"/"+status);
}

function setBlokkeerMails(checkbox){
    val = (checkbox.checked ? 1 : 0);
    toernooi_id = checkbox.getAttribute('data-toernooi-id');
    console.log("setBlokkeerMails, toernooi_id = "+toernooi_id+", waarde = "+val);
    $.post("/setBlokkeerMails/"+toernooi_id+"/"+val);
}

function toonAanvraag(button) {
    toernooi_id = button.getAttribute('data-toernooi-id');
    console.log("toonAanvraag, toernooi_id = "+toernooi_id);
    $.post("/toonAanvraag/"+toernooi_id, function(response){
        console.log("toonAanvraag, response: "+response);
        $("#modalPlaceHolder").html(response);
        $('#modalAanvraag').modal('show');
    });
}



