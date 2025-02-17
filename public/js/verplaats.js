$(document).ready(function() {
    console.log(wedstrijd_gegevens_json);
    console.log(speeltijden);
    console.log(vrije_tijdsloten);
    console.log(geplande_wedstrijden);
    console.log(verhinderingen);
    toon_vrije_tijdsloten();
});

var aantal_dagen = speeltijden.length;
var start_minuten = 8*60; // 08:00 's ochtends
var eind_minuten = 24*60; // 24:00 's nachts
var cel_duur = 15;
var aantal_deelnemers = 4;
if (wedstrijd_gegevens_json['cat_type'] == 'enkel'){
  aantal_deelnemers = 2;
}
var nieuwe_tijdslot = 0;


function toon_vrije_tijdsloten(){
    var aantal_cellen = Math.round( (eind_minuten - start_minuten) / cel_duur);

    console.log("aantal_cellen: "+aantal_cellen);
    var i, j, k, uren, minuten, timeStr;
  
    var div = document.createElement("DIV");
    div.classList.add("scrollx");
    var tbl = document.createElement("TABLE");
    div.appendChild(tbl);
    var th = document.createElement("TH");
    th.innerHTML = "dag";
    tbl.appendChild(th);
    var th2 = document.createElement("TH");
    var thdiv = document.createElement("DIV");
    thdiv.classList.add("maxh");
    thdiv.innerHTML = "datum of naam";
    th2.appendChild(thdiv);
    tbl.appendChild(th2);
  
    for (i = 0; i < aantal_cellen; i++) {
      uren = Math.floor((start_minuten + i*cel_duur)/60);
      minuten = (start_minuten + i*cel_duur)%60;
      var th = document.createElement("TH");
      if (minuten == 0){
        timeStr = uren.toLocaleString("nl-NL", { minimumIntegerDigits: 2 }) + ":" + 
                minuten.toLocaleString("nl-NL", { minimumIntegerDigits: 2 });
        var divrot = document.createElement("DIV");
        divrot.classList.add("vertical");
        divrot.innerHTML = timeStr;
        th.appendChild(divrot);
      }
      tbl.appendChild(th);
    }

    //var date = new Date(toernooi_obj.eerste_dag);
    for (k=0; k < aantal_dagen; k++){
      j = k*(aantal_deelnemers+1); // j is het rownr
      var row = tbl.insertRow(j);
      var cell = row.insertCell(0);
      var button = document.createElement("BUTTON");
      button.innerHTML = k+1;
      button.classList.add("btn-primary");
      button.addEventListener("click", function() { toon_verberg_details(this); } );
      cell.appendChild(button);
      var cell = row.insertCell(1);
      /*
      var dagStr = date.toLocaleString('nl-NL', {weekday: 'short'});
      var datumStr = date.toLocaleString('nl-NL', {dateStyle: 'medium'});
      cell.innerHTML = dagStr + " " + datumStr;
      date.setDate(date.getDate()+1);
      */
      // de cellen voor de vrije tijdsloten:
      for (i=0; i < aantal_cellen; i++) {
        var cell = row.insertCell(i+2);
      }
      // Voor alle 2 of 4 deelnemers:
      for (speler=1; speler<=aantal_deelnemers; speler++){
        var row = tbl.insertRow(j+1);
        row.classList.add("verberg");
        j++;
        var cell = row.insertCell(0);
        var cell = row.insertCell(1);
        var celldiv = document.createElement("DIV");
        celldiv.classList.add("maxh");
        // vul de naam van de deelnemer in:
        switch (speler){
          case 1: 
            celldiv.innerHTML = wedstrijd_gegevens_json['naam_speler1'];
            break;
          case 2: 
            if (aantal_deelnemers == 2){
              celldiv.innerHTML = wedstrijd_gegevens_json['naam_speler2'];
            } else {
              celldiv.innerHTML = wedstrijd_gegevens_json['naam_partner1'];
            }
            break;
          case 3:
            celldiv.innerHTML = wedstrijd_gegevens_json['naam_speler2'];
            break;
          case 4:
            celldiv.innerHTML = wedstrijd_gegevens_json['naam_partner2'];
            break;
          default:
            break;
          }
        cell.appendChild(celldiv);
        // voeg de cellen voor de geplande wedstrijden of verhinderingen per deelnemer toe
        for (i=0; i< aantal_cellen; i++){
          var cell = row.insertCell(i+2);
        }
      }
    }
    
    var element = document.getElementById("vrije_tijdsloten");
    element.appendChild(div);
    
    // Markeer de vrije tijdsloten:
    for (i=0; i<vrije_tijdsloten.length; i++){
        var dagnr = vrije_tijdsloten[i]['dagnummer']-1;  // dagnummers in vrije_tijdsloten tellen vanaf 1
        var rownr = dagnr*(aantal_deelnemers+1);
        var start_tijdslot = vrije_tijdsloten[i]['starttijd'].split(":");
        var start_tijdslot_minuten = Number(start_tijdslot[0])*60 + Number(start_tijdslot[1]);
        var wedstrijd_duur = Number(vrije_tijdsloten[i]['wedstrijd_duur']);
        var cell_nr = Math.ceil((start_tijdslot_minuten - start_minuten)/ cel_duur) + 2;
        var starttijd_cell = start_minuten + (cell_nr-2) * cel_duur;
        tbl.rows[rownr].cells[cell_nr].classList.add("start_wedstrijd");
        while ((starttijd_cell + cel_duur) <= (start_tijdslot_minuten + wedstrijd_duur)){
          var cell = tbl.rows[rownr].cells[cell_nr];
            cell.classList.add("vrij");
            cell.dataset.tijdslot = vrije_tijdsloten[i]['eerste_tijdslot_id'];
            cell.addEventListener("click", function() { reserveer(this); } );
            cell_nr++;
            starttijd_cell += cel_duur;
        }
        tbl.rows[rownr].cells[cell_nr-1].classList.add("einde_wedstrijd");
    }

    // Markeer de geplande Wedstrijden:
    for (i=0; i<geplande_wedstrijden.length; i++){
        var dagnr = geplande_wedstrijden[i]['dagnummer']-1;  // dagnummers in geplande_wedstrijden tellen vanaf 1
        for (j=1; j<=4; j++){
          if (wedstrijd_gegevens_json[j] == geplande_wedstrijden[i]['user_id']){
            var deelnemer_index = j;
            var rownr = dagnr * (aantal_deelnemers+1) + deelnemer_index;
            
            var start_tijdslot = geplande_wedstrijden[i]['starttijd'].split(":");
            var start_tijdslot_minuten = Number(start_tijdslot[0])*60 + Number(start_tijdslot[1]);
            var wedstrijd_duur = Number(geplande_wedstrijden[i]['wedstrijd_duur']);
            var cell_nr = Math.ceil((start_tijdslot_minuten - start_minuten)/ cel_duur) + 2;
            var starttijd_cell = start_minuten + (cell_nr-2) * cel_duur;
            var cell = tbl.rows[rownr].cells[cell_nr];
            while ((starttijd_cell + cel_duur) <= (start_tijdslot_minuten + wedstrijd_duur)){
                tbl.rows[rownr].cells[cell_nr].classList.add("ingepland");
                combineer_markering(tbl.rows[rownr-deelnemer_index].cells[cell_nr], "ingepland");
                cell_nr++;
                starttijd_cell += cel_duur;
            }
          }
        }
    }

    // Markeer de verhinderingen:
    for (i=0; i<verhinderingen.length; i++){
      var dagnr = verhinderingen[i]['dagnummer']-1;  // dagnummers in tabel verhindering tellen vanaf 1
      for (j=1; j<=4; j++){
        if (wedstrijd_gegevens_json[j] == verhinderingen[i]['user_id']){
          var deelnemer_index = j;
          var rownr = dagnr * (aantal_deelnemers+1) + deelnemer_index;
      
          var start_tijdslot = verhinderingen[i]['begintijd'].split(":");
          var start_tijdslot_minuten = Number(start_tijdslot[0])*60 + Number(start_tijdslot[1]);
          var eind_tijdslot = verhinderingen[i]['eindtijd'].split(":");
          var eind_tijdslot_minuten = Number(eind_tijdslot[0])*60 + Number(eind_tijdslot[1]);
          var cell_nr = Math.ceil((start_tijdslot_minuten - start_minuten)/ cel_duur) + 2;
          var starttijd_cell = start_minuten + (cell_nr-2) * cel_duur;
          var cell = tbl.rows[rownr].cells[cell_nr];
          while ((starttijd_cell + cel_duur) <= (eind_tijdslot_minuten)){
              tbl.rows[rownr].cells[cell_nr].classList.add("verhinderd");
              combineer_markering(tbl.rows[rownr-deelnemer_index].cells[cell_nr], "verhinderd");
              cell_nr++;
              starttijd_cell += cel_duur;
          }
        }
      }
  }
}

function combineer_markering(cell, status){
  if (cell.classList.contains("vrij")) {
    if (status == "ingepland") {
      cell.classList.add("vrij_met_ingepland")
    } if (status == "verhinderd") {
      cell.classList.add("vrij_met_verhinderd")
    }
  }
}

function toon_verberg_details(button){
  var cell = button.parentNode;
  var rownr = cell.parentNode.rowIndex;
  var rows = cell.parentNode.parentNode.rows;
  for (var i=1; i<=aantal_deelnemers; i++){
    rows[rownr+i].classList.toggle("verberg");
  }
}

function reserveer(cell){
  // window.location.replace("/reserveer_tijdslot/"+wedstrijd_gegevens_json['wedstrijd_id']+"/"+wedstrijd_gegevens_json['tijdslot']+"/"+cell.dataset.tijdslot);
  nieuwe_tijdslot = cell.dataset.tijdslot;
  $('#modalVerplaats').modal('show');
}

function maakWijziging(){
  console.log("MaakWijziging()");
  var message = document.getElementById("message");
  if (!message.classList.contains("verberg")){
    message.classList.add("verberg");
  }
  var choice = document.querySelector("[name=kies_aanvrager]:checked");
  if (choice){
    var indiener = choice.value;
    console.log("MaakWijziging(), value indiener: "+indiener+" nieuwe_tijdslot: "+nieuwe_tijdslot);
    window.location.replace("/maak_verplaatsing/"+wedstrijd_gegevens_json['wedstrijd_id']+"/"+nieuwe_tijdslot+"/"+indiener);
  } else {
    message.classList.remove("verberg");
  }
}