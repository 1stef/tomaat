function verplaatsOptie(wedstrijd_wijziging_id){
    herplan_selectie = document.querySelector('input[name="radio_herplan_opties"]:checked');
    if (herplan_selectie != null){
        document.getElementById('msg').style.visibility = "hidden";
        window.location.replace("/wizard_wijzig_wedstrijd_3a/"+wedstrijd_wijziging_id+"/"+herplan_selectie.value);
    } else {
        // vertel de gebruiker een wedstrijd te selecteren
        document.getElementById('msg').style.visibility = "visible";
    }
}
