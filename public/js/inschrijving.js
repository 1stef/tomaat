$(document).ready(function() {
  // maak_cellen();
  voeg_handlers_toe();
});

function voeg_handlers_toe(){
  var categorie_1 = document.getElementById("inschrijving_categorie_1");
  var categorie_2 = document.getElementById("inschrijving_categorie_2");
  var categorie_3 = document.getElementById("inschrijving_categorie_3");
  categorie_1.addEventListener("change", function() {
    setDubbelVeld(inschrijving_deelnemerB_1, inschrijving_aantal_1, inschrijving_cat_type_1, this);
  } );
  setDubbelVeld(inschrijving_deelnemerB_1, inschrijving_aantal_1, inschrijving_cat_type_1, categorie_1);
  categorie_2.addEventListener("change", function() {
    setDubbelVeld(inschrijving_deelnemerB_2, inschrijving_aantal_2, inschrijving_cat_type_2, this);
  } );
  setDubbelVeld(inschrijving_deelnemerB_2, inschrijving_aantal_2, inschrijving_cat_type_2, categorie_2);
  categorie_3.addEventListener("change", function() { 
    setDubbelVeld(inschrijving_deelnemerB_3, inschrijving_aantal_3, inschrijving_cat_type_3, this);
  } );
  setDubbelVeld(inschrijving_deelnemerB_3, inschrijving_aantal_3, inschrijving_cat_type_3, categorie_3);
}

function setDubbelVeld(dubbelVeld, aantalVeld, catTypeVeld, select){
  if (select.selectedIndex == 0) {
    // gebruiker heeft "Kies categorie" geselecteerd om de categorie te verwijderen
    dubbelVeld.disabled = false;
    dubbelVeld.value = "";
    dubbelVeld.required = false;
    aantalVeld.value = "";
    catTypeVeld.value = "";
    return;
  }
  if (select.options[select.selectedIndex].dataset.catType == "enkel") {
    dubbelVeld.disabled = true;
    dubbelVeld.value = "";
    dubbelVeld.required = false;
    catTypeVeld.value = "enkel";
  } else {
    dubbelVeld.disabled = false;
    dubbelVeld.required = true;
    catTypeVeld.value = "dubbel";
  }
}

function wijzigVerhindering(button) {
  var id = button.getAttribute('data-id');
  console.log("wijzigVerhindering "+id);
  window.location.replace("/verhindering/"+id);
}


