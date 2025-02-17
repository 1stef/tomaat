$(document).ready(function() {
    if (toon) {
        toonInschrijvingen();
    }
});

function toonInschrijvingen(){
    // first remove any table (with its child elements) that was previously created:
    $("TABLE").remove();
    // toon de inschrijvingen voor deze categorie.
    // de variabele inschrijvingen is gevuld door twig in de template toon_inschrijvingen.html.twig:
    // array met categorie, bondsnr1, naam1, bondsnr2, naam2, aantal
    console.log (inschrijvingen);

    var tbl = document.createElement("TABLE");
    var th = document.createElement("TH");
    th.innerHTML = "categorie";
    tbl.appendChild(th);

    var th = document.createElement("TH");
    th.innerHTML = "bondsnrA";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "naamA";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "bondsnrB";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "naamB";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "gevraagde wedstrijden";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "geplande wedstrijden";
    tbl.appendChild(th);
    for (var i = 0; i < inschrijvingen.length; i++) {
        var row = tbl.insertRow(i);
        var cell = row.insertCell(0);
        cell.innerHTML = inschrijvingen[i].categorie;

        var cell = row.insertCell(1);
        cell.innerHTML = inschrijvingen[i].deelnemerA;
        var cell = row.insertCell(2);
        cell.innerHTML = inschrijvingen[i].naamA;
        var cell = row.insertCell(3);
        if ('deelnemerB' in inschrijvingen[i]) {
            cell.innerHTML = inschrijvingen[i].deelnemerB;
        }
        var cell = row.insertCell(4);
        if ('naamB' in inschrijvingen[i]) {
            cell.innerHTML = inschrijvingen[i].naamB;
        }
        var cell = row.insertCell(5);
        cell.innerHTML = inschrijvingen[i].aantal_gevraagd;
        var cell = row.insertCell(6);
        cell.innerHTML = inschrijvingen[i].aantal_gepland;
    }
    var area = document.getElementById("presentation-area");
    area.appendChild(tbl);
}

