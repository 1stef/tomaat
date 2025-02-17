var geselecteerde_wedstrijd = null;

function verplaatsen(){
    geselecteerde_wedstrijd = document.querySelector('input[name="kies_wedstrijd"]:checked');
    if (geselecteerde_wedstrijd != null){
        document.getElementById('msg').style.visibility = "hidden";
        window.location.replace("/wizard_wijzig_wedstrijd_2/"+geselecteerde_wedstrijd.value+"/verplaatsen");
    } else {
        // vertel de gebruiker een wedstrijd te selecteren
        document.getElementById('msg').style.visibility = "visible";
    }
}

function clearmsg(){
    document.getElementById('msg').style.visibility = "hidden";
}

function afzeggen(){
    geselecteerde_wedstrijd = document.querySelector('input[name="kies_wedstrijd"]:checked');
    if (geselecteerde_wedstrijd != null){
        document.getElementById('msg').style.visibility = "hidden";
        window.location.replace("/wizard_wijzig_wedstrijd_2/"+geselecteerde_wedstrijd.value+"/afzeggen");
    } else {
        // vertel de gebruiker een wedstrijd te selecteren
        document.getElementById('msg').style.visibility = "visible";
    }
}

function cancel(){
    window.location.replace("/wizard_wijzig_wedstrijd_2/"+geselecteerde_wedstrijd+"/niet verplaatsen");
}

