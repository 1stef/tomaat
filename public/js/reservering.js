$(document).ready(function() {

})

function toggleBevestigd(cell){
    if (cell.children[1].innerHTML == "Bevestigd"){
        cell.children[0].checked = false;
        cell.children[1].innerHTML = "Niet bevestigd"
        var bevestigd = 0;
    } else {
        cell.children[0].checked = true;
        cell.children[1].innerHTML = "Bevestigd";
        var bevestigd = 1;
    }
    console.log("toggleBevestigd: " + cell.children[0].dataset.reservering + ", " + cell.children[0].id + ", " + bevestigd);
    // Sla de bevestiging of afwijzing op.
    // parameters: reservering_id, speler1/partner1/speler2/partner2, boolean voor bevestiging/afwijzing
    $.post("/zet_verplaatsing_ok_nok/" + cell.children[0].dataset.reservering + "/" + cell.children[0].id + "/" + bevestigd)
}

function anderTijdstip(reservering_id){
    window.location.replace("/ander_tijdstip/"+reservering_id);
}

function verplaatsDefinitief(reservering_id){
    window.location.replace("/verplaats_definitief/"+reservering_id);
}

function verwijderReservering(reservering_id){
    window.location.replace("/verwijder_reservering/"+reservering_id);
}