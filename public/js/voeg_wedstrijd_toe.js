var inschrijving_1 = -1;
var inschrijving_2 = -1;

function toggleSelect (checkbox){
    var array_index = checkbox.getAttribute('data-arrayindex');
    console.log("toggleSelect, categorie: "+incompl_inschr[array_index]['categorie']);
    if (inschrijving_1 == array_index){
        inschrijving_1 = -1;
        console.log("toggleSelect, inschrijving_1: "+inschrijving_1);
        console.log("toggleSelect, inschrijving_2: "+inschrijving_2);
        return;
        }
    if (inschrijving_2 == array_index){
        inschrijving_2 = -1;
        console.log("toggleSelect, inschrijving_1: "+inschrijving_1);
        console.log("toggleSelect, inschrijving_2: "+inschrijving_2);
        return;
    }
    if (inschrijving_1 == -1 && inschrijving_2 == -1){
        inschrijving_1 = array_index;
        console.log("toggleSelect, inschrijving_1: "+inschrijving_1);
        console.log("toggleSelect, inschrijving_2: "+inschrijving_2);
        return;
    }
    if (inschrijving_1 == -1 && inschrijving_2 != -1) { 
        if (incompl_inschr[array_index]['categorie'] == incompl_inschr[inschrijving_2]['categorie']){
            inschrijving_1 = array_index;
            console.log("toggleSelect, inschrijving_1: "+inschrijving_1);
            console.log("toggleSelect, inschrijving_2: "+inschrijving_2);
            return;
        }
    }
    if (inschrijving_2 == -1 && inschrijving_1 != -1) {
        if (incompl_inschr[array_index]['categorie'] == incompl_inschr[inschrijving_1]['categorie']){
            inschrijving_2 = array_index;
            console.log("toggleSelect, inschrijving_1: "+inschrijving_1);
            console.log("toggleSelect, inschrijving_2: "+inschrijving_2);
            return;
        }
    }
    // In all other cases do not allow the checkbox to remain checked
    checkbox.checked = 0;
}

function voegCombinatieToe(){
    if (inschrijving_1 >= 0 && inschrijving_2 >= 0){
        var categorie = incompl_inschr[inschrijving_1]['categorie'];
        var inschrijving_id_1 = incompl_inschr[inschrijving_1]['id'];
        var inschrijving_id_2 = incompl_inschr[inschrijving_2]['id'];
        window.location.replace("/voegCombinatieToeEnZoekTijdslot/"+categorie+"/"+inschrijving_id_1+"/"+inschrijving_id_2);
    }
}