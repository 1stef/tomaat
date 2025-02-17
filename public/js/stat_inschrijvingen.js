$(document).ready(function() {
    statInschrijvingenPerCategorie();
});

function statInschrijvingenPerCategorie(){
    console.log("statInschrijvingenPerCategorie...", 0);
    // Toon de aantallen inschrijvingen per categorie:
    // De parameter inschrijvingen_per_cat is in de twig template inschrijvingen.html.twig gezet

    // first remove any table (with its child elements) that was previously created:
    $("TABLE").remove();
    // build and fill a new table:
    var tbl = document.createElement("TABLE");
    var th = document.createElement("TH");
    th.innerHTML = "categorie";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "inschrijvingen";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "gevraagd aantal wedstrijden";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "gepland aantal wedstrijden";
    tbl.appendChild(th);
    for (var i = 0; i < inschrijvingen_per_cat.length; i++) {
        var row = tbl.insertRow(i);
        var cell = row.insertCell(0);
        cell.innerHTML = inschrijvingen_per_cat[i].cat;
        var cell = row.insertCell(1);
        cell.innerHTML = inschrijvingen_per_cat[i].aantal_inschrijvingen;
        var cell = row.insertCell(2);
        cell.innerHTML = inschrijvingen_per_cat[i].gevraagd_aantal/2;
        var cell = row.insertCell(3);
        cell.innerHTML = inschrijvingen_per_cat[i].gepland_aantal;
    }
    //document.body.appendChild(tbl);
    var area = document.getElementById("presentation-area");
    area.appendChild(tbl);
}

