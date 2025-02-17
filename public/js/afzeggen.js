function maakAfzegging(){
    console.log("maakAfzegging()");
    var message = document.getElementById("message");
    if (!message.classList.contains("verberg")){
      message.classList.add("verberg");
    }
    var choice = document.querySelector("[name=kies_aanvrager]:checked");
    if (choice){
      var indiener = choice.value;
      console.log("maakAfzegging(), value indiener: "+indiener);
      window.location.replace("/maak_afzegging/"+wedstrijd_id+"/"+indiener);
    } else {
      message.classList.remove("verberg");
    }
  }

  function cancel(){
    window.location.replace("/zoek_wedstrijd");
  }