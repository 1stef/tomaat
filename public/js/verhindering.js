var hele_dag = document.getElementById("verhindering_form_hele_dag");
var dagnummer = document.getElementById("verhindering_form_dagnummer");
var begintijd_hour = document.getElementById("verhindering_form_begintijd_hour");
var begintijd_minute = document.getElementById("verhindering_form_begintijd_minute");
var eindtijd_hour = document.getElementById("verhindering_form_eindtijd_hour");
var eindtijd_minute = document.getElementById("verhindering_form_eindtijd_minute");

$(document).ready(function() {
    hele_dag.addEventListener("change", function() {
      zetVanTot(this);
    } );
    dagnummer.addEventListener("change", function() { maakVeldenGeldig(); });
    begintijd_hour.addEventListener("change", function() { maakVeldenGeldig(); });
    begintijd_minute.addEventListener("change", function() { maakVeldenGeldig(); });
    eindtijd_hour.addEventListener("change", function() { maakVeldenGeldig(); });
    eindtijd_minute.addEventListener("change", function() { maakVeldenGeldig(); });
});

function wijzigVerhindering(button) {
    var id = button.getAttribute('data-id');
    console.log("wijzigVerhindering "+id);
    window.location.replace("/verhindering/"+id);
}

function zetVanTot(checkbox){
    if (checkbox.checked) {
        // zet begintijd en eindtijd velden op start en eind speeltijden voor de gekozen dag:
        var start = speeltijden[dagnummer.value-1]['starttijd'].split(":");
        var eind = speeltijden[dagnummer.value-1]['eindtijd'].split(":");
        begintijd_hour.value = Number(start[0]);
        begintijd_minute.value = Number(start[1]);
        eindtijd_hour.value = Number(eind[0]);
        eindtijd_minute.value = Number(eind[1]);
    }
}

function maakVeldenGeldig(){
    hele_dag.checked = false;
    var start = speeltijden[dagnummer.value-1]['starttijd'].split(":");
    var eind = speeltijden[dagnummer.value-1]['eindtijd'].split(":");
    var sp_start = Number(start[0])*60 + Number(start[1]);
    var sp_eind = Number(eind[0])*60 + Number(eind[1]);
    var verh_start = Number(begintijd_hour.value)*60 + Number(begintijd_minute.value);
    var verh_eind = Number(eindtijd_hour.value)*60 + Number(eindtijd_minute.value)
    if(verh_start < sp_start){
        begintijd_hour.value = Number(start[0]);
        begintijd_minute.value = Number(start[1]);
    }
    if(verh_start > sp_eind){
        begintijd_hour.value = Number(eind[0]);
        begintijd_minute.value = Number(eind[1]);
    }
    if (verh_eind > sp_eind) {
        eindtijd_hour.value = Number(eind[0]);
        eindtijd_minute.value = Number(eind[1]);
    }
    if (verh_eind < sp_start) {
        eindtijd_hour.value = Number(start[0]);
        eindtijd_minute.value = Number(start[1]);
    }
    if (verh_start > verh_eind) {
        eindtijd_hour.value = Number(begintijd_hour.value);
        eindtijd_minute.value = Number(begintijd_minute.value);
    }

}