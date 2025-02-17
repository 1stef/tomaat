$(document).ready(function() {
    if (toon) {
        toonWedstrijden();
    }
});

function toonWedstrijden(){
    // console.log(wedstrijden);
    // first remove any table (with its child elements) that was previously created:
    console.log(wedstrijden);
    $("TABLE").remove();

    var tbl = document.createElement("TABLE");
    tbl.id = "wedstrijden";
    var th = document.createElement("TH");
    th.innerHTML = "categorie";
    tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "speler team 1";
    tbl.appendChild(th);
    var th = document.createElement("TH");
    th.innerHTML = "bondsnr";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "partner team 1";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "bondsnr partner";
    tbl.appendChild(th);
    //th = document.createElement("TH");
    //th.innerHTML = "ranking team 1";
    //tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "speler team 2";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "bondsnr";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "partner team 2";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "bondsnr partner";
    tbl.appendChild(th);
    //th = document.createElement("TH");
    //th.innerHTML = "ranking team 2";
    //tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "dagnummer";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "baannummer";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "starttijd";
    tbl.appendChild(th);
    for (var i = 0; i < wedstrijden.length; i++) {
        var row = tbl.insertRow(i);
        var cellnr = 0;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].categorie;

        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].naam1A;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].bondsnr1A;
        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('naam1B' in wedstrijden[i]) {
            cell.innerHTML = wedstrijden[i].naam1B;
        }
        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('bondsnr1B' in wedstrijden[i]) {
            cell.innerHTML = wedstrijden[i].bondsnr1B;
        }
        //var cell = row.insertCell(cellnr);
        //cellnr++;
        //cell.innerHTML = wedstrijden[i].ranking1;

        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].naam2A;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].bondsnr2A;
        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('naam2B' in wedstrijden[i]) {
            cell.innerHTML = wedstrijden[i].naam2B;
        }
        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('bondsnr2B' in wedstrijden[i]) {
            cell.innerHTML = wedstrijden[i].bondsnr2B;
        }
        //var cell = row.insertCell(cellnr);
        //cellnr++;
        //cell.innerHTML = wedstrijden[i].ranking2;

        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].dagnummer;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].baannummer;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].starttijd;
        var cell = row.insertCell(cellnr);
        cellnr++;
        var verplaats = document.createElement("BUTTON");
        verplaats.innerHTML = "Verplaatsen";
        verplaats.classList.add("newbutton");
        verplaats.dataset.wedstrijd_id = wedstrijden[i].wedstrijd_id;
        verplaats.addEventListener("click", function() { verplaats_wedstrijd(this); } );
        cell.appendChild(verplaats);
        var cell = row.insertCell(cellnr);
        cellnr++;
        var afzeggen = document.createElement("BUTTON");
        afzeggen.innerHTML = "Afzeggen";
        afzeggen.classList.add("newbutton");
        afzeggen.dataset.wedstrijd_id = wedstrijden[i].wedstrijd_id;
        afzeggen.addEventListener("click", function() { afzeggen_wedstrijd(this); } );
        cell.appendChild(afzeggen);
    }
    var area = document.getElementById("presentation-area");
    area.appendChild(tbl);
}

function verplaats_wedstrijd(button){
    window.location.replace("/verplaats_wedstrijd/"+button.dataset.wedstrijd_id);
}


function afzeggen_wedstrijd(button){
    window.location.replace("/afzeggen_wedstrijd/"+button.dataset.wedstrijd_id);
}
