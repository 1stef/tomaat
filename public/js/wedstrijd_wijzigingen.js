function toggleAkkoord(cell){
    if (cell.children[1].innerHTML == "Akkoord"){
        cell.children[0].checked = false;
        cell.children[1].innerHTML = "Niet akkoord"
        var akkoord = 0;
    } else {
        cell.children[0].checked = true;
        cell.children[1].innerHTML = "Akkoord";
        var akkoord = 1;
    }
    console.log("toggleAkkoord: " + cell.children[0].dataset.herplanoptie + ", " + cell.children[0].id + ", " + akkoord);
    // Sla het akkoord of niet akkoord op.
    // parameters: herplan_optie_id, speler1/partner1/speler2/partner2, boolean voor akkoord/niet akkoord
    $.post("/toggleAkkoord/" + cell.children[0].dataset.herplanoptie + "/" + cell.children[0].id + "/" + akkoord)
}

function verplaats_wedstrijd(wedstrijd_id){
    window.location.replace("/verplaats_wedstrijd/"+wedstrijd_id);
}


function afzeggen_wedstrijd(wedstrijd_id){
    window.location.replace("/afzeggen_wedstrijd/"+wedstrijd_id);
}

