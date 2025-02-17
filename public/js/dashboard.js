$(document).ready(function() {
    if (toon) {
        toonBaanBezetting();
        toonGespeeld();
        toonSpelend();
        toonWachtend();
        toonGepland();
        $('[data-toggle="tooltip"]').tooltip({html:true})
    }
});

/**
 * globale variabelen voor de wedstrijd-arrays met verschillende statussen:
 */
var gespeeld = [];
var spelend  = [];
var wachtend = [];
var gepland  = [];

function toonBaanBezetting(){
    console.log(baanbezetting);
    var div = document.createElement("DIV");
    for (var i=1; i<=aantal_banen; i++){
        var baan = document.createElement("BUTTON");
        baan.innerHTML = "baan "+i;
        baan.id = "baan"+i;
        baan.type = "button";
        baan.classList.add("btn");
        baan.classList.add("btn-primary");
        baan.classList.add("hover");
        baan.addEventListener("click", function() { showUitslagForm(this); } );
        var tooltip = document.createElement("DIV");
        tooltip.classList.add("tooltip");
        tooltip.innerHTML = "geen wedstrijd";
        var wedstrijd_id = isBaanBezet(i);
        if (wedstrijd_id != false){
            baan.classList.add("bezet");
            baan.dataset.wedstrijd_id = wedstrijd_id;
            // default waarde voor als een wedstrijd over de daggrens gaat:
            tooltip.innerHTML = "wedstrijd nr: " + wedstrijd_id;
            for (j=0; j<wedstrijden.length; j++){
                if (wedstrijden[j]['echte_baan'] == i && wedstrijden[j]['wedstrijd_status'] == "spelend"){
                    var categorie = wedstrijden[j]['categorie'];
                    var naam1A = wedstrijden[j]['naam1A'];
                    var naam1B = wedstrijden[j]['naam1B'];
                    var naam2A = wedstrijden[j]['naam2A'];
                    var naam2B = wedstrijden[j]['naam2B'];
                    var title = "<div class = 'tt'>Categorie: " + categorie + "<br>" +
                    "Team 1     Team 2" + "<br>" +
                    naam1A + "   " + naam2A + "<br>" +
                    naam1B + "   " + naam2B + "</div>";
                    tooltip.innerHTML = title;
                    break;
                }
            }
        }
        baan.appendChild(tooltip);
        div.appendChild(baan);
    }
    var area = document.getElementById("presentation-area");
    area.appendChild(div);
}

// baanbezetting is een array met bezette banen + wedstrijd_id's
function isBaanBezet(baan){
    for (i=0; i<baanbezetting.length; i++){
        if (baanbezetting[i]['baan'] == baan){
            return baanbezetting[i]['wedstrijd_id'];
        }
    }
    return false
}

function toonVerberg(parameters){
    wedstrijd_status = parameters[0];
    button = parameters[1];
    var tbl_str = "#"+wedstrijd_status;
    $(tbl_str).toggleClass("verberg");
    button.innerHTML = (button.innerHTML == "Toon") ? "Verberg" : "Toon";
}

function toonGespeeld(){
    gespeeld = wedstrijden.filter(function(wedstrijd){
        return wedstrijd.wedstrijd_status == "gespeeld";
    })
    gespeeld.sort(compare_echte_start);
    console.log("array met gespeelde wedstrijden:");
    console.log(gespeeld);
    if (gespeeld.length >0){
        tblhdr("gespeeld", gespeeld.length, "Gespeelde wedstrijden: nog geen", "Gespeelde wedstrijden: ");
        toonDashboard(gespeeld, "gespeeld");
    }
}

function toonSpelend(){
    spelend = wedstrijden.filter(function(wedstrijd){
        return wedstrijd.wedstrijd_status == "spelend";
    })
    spelend.sort(compare_echte_start);
    console.log("array met spelende wedstrijden:");
    console.log(spelend);
    if (spelend.length >0){
        tblhdr("spelend", spelend.length, "Spelende wedstrijden: geen", "Spelende wedstrijden: ");
        toonDashboard(spelend, "spelend");
    }
}

function toonWachtend(){
    wachtend = wedstrijden.filter(function(wedstrijd){
        return wedstrijd.wedstrijd_status == "wachtend";
    })
    wachtend.sort(compare_wachtstarttijd);
    console.log("array met wachtende wedstrijden:");
    console.log(wachtend);
    if (wachtend.length>0){
        tblhdr("wachtend", wachtend.length, "Wachtende wedstrijden: geen", "Wachtende wedstrijden: ");
        toonDashboard(wachtend, "wachtend");
    }
}

function toonGepland(){
    gepland = wedstrijden.filter(function(wedstrijd){
        return (wedstrijd.wedstrijd_status == "gepland" || wedstrijd.wedstrijd_status == null);
    })
    // wedstrijden zijn al gesorteerd op plantijd
    console.log("array met geplande wedstrijden:");
    console.log(gepland);
    tblhdr("gepland", gepland.length, "Geplande wedstrijden voor vandaag: geen", "Geplande wedstrijden voor vandaag: ");
    toonDashboard(gepland, "gepland");
}

function compare_echte_start(a, b){
    if (a.echte_start == null || b.echte_start == null){
        return 0
    }
    if (a.echte_start<b.echte_start){
        return -1
    }
    if (a.echte_start>b.echte_start){
        return 1
    }
    return 0;
}

function compare_wachtstarttijd(a, b){
    if (a.wachtstarttijd == null || b.wachtstarttijd == null){
        return 0
    }
    if (a.wachtstarttijd<b.wachtstarttijd){
        return -1
    }
    if (a.wachtstarttijd>b.wachtstarttijd){
        return 1
    }
    return 0;
}

function tblhdr(wedstrijd_status, length, hdr_leeg, hdr_gevuld){
    var hdr = document.createElement("DIV");
    hdr.style = "margin-top: 30px; font-weight:bold; color:blue";
    if (length == 0) {
        hdr.innerHTML = hdr_leeg;
    } else {
        hdr.innerHTML = hdr_gevuld;
        var toon_verberg = document.createElement("BUTTON");
        toon_verberg.addEventListener("click", function() { toonVerberg([wedstrijd_status, this]); } );
        if (sessionStorage.getItem("verberg_"+wedstrijd_status) == "verberg") {
            toon_verberg.innerHTML = "Toon";                  
        } else {
            toon_verberg.innerHTML = "Verberg";
        }
        hdr.appendChild(toon_verberg);
    }
    var area = document.getElementById("presentation-area");
    area.appendChild(hdr);    
}

function toonDashboard(wedstrijden, wedstrijd_status){
    // first remove any table (with its child elements) that was previously created:
    var tbl_str = "#"+wedstrijd_status;
    $(tbl_str).remove();

    // Toon helemaal geen tabel als er geen wedstrijden zijn met deze status
    if (wedstrijden.length == 0){
        return;
    }

    var tbl = document.createElement("TABLE");
    tbl.id = wedstrijd_status
    if (sessionStorage.getItem("verberg_"+wedstrijd_status) == "verberg") {
        tbl.classList.add("verberg");
    }
    var th = document.createElement("TH");
    th.innerHTML = "categorie";
    tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "speler team 1";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "partner team 1";
    tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "speler team 2";
    tbl.appendChild(th);
    th = document.createElement("TH");
    th.innerHTML = "partner team 2";
    tbl.appendChild(th);

    th = document.createElement("TH");
    th.innerHTML = "plantijd";
    tbl.appendChild(th);
    if (wedstrijd_status == "wachtend") {
        th = document.createElement("TH");
        th.innerHTML = "wachtstarttijd";
        tbl.appendChild(th);
    }
    if (wedstrijd_status == "spelend" || wedstrijd_status == "gespeeld") {
        th = document.createElement("TH");
        th.innerHTML = "starttijd";
        tbl.appendChild(th);
        th = document.createElement("TH");
        th.innerHTML = "baan";
        tbl.appendChild(th);
    }
    if (wedstrijd_status == "gespeeld") {
        th = document.createElement("TH");
        th.innerHTML = "set 1";
        tbl.appendChild(th);
        th = document.createElement("TH");
        th.innerHTML = "set 2";
        tbl.appendChild(th);
        th = document.createElement("TH");
        th.innerHTML = "set 3";
        tbl.appendChild(th);
        th = document.createElement("TH");
        th.innerHTML = "winnaar";
        tbl.appendChild(th);
        th = document.createElement("TH");
        th.innerHTML = "opgave";
        tbl.appendChild(th);
    }
    for (var i = 0; i < wedstrijden.length; i++) {
        var row = tbl.insertRow(i);
        var cellnr = 0;
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].categorie;

        var cell = row.insertCell(cellnr);
        cellnr++;
        naamCell(cell, wedstrijden[i].wedstrijd_status, wedstrijden[i].naam1A, wedstrijden[i].aanwezig_1_a);

        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('naam1B' in wedstrijden[i]) {
            naamCell(cell, wedstrijden[i].wedstrijd_status, wedstrijden[i].naam1B, wedstrijden[i].aanwezig_1_b);
        }

        var cell = row.insertCell(cellnr);
        cellnr++;
        naamCell(cell, wedstrijden[i].wedstrijd_status, wedstrijden[i].naam2A, wedstrijden[i].aanwezig_2_a);
        var cell = row.insertCell(cellnr);
        cellnr++;
        if ('naam2B' in wedstrijden[i]) {
            naamCell(cell, wedstrijden[i].wedstrijd_status, wedstrijden[i].naam2B, wedstrijden[i].aanwezig_2_b);
        }
        var cell = row.insertCell(cellnr);
        cellnr++;
        cell.innerHTML = wedstrijden[i].starttijd;
        if (wedstrijden[i].wedstrijd_status == "wachtend") {
            var cell = row.insertCell(cellnr);
            cellnr++;
                cell.innerHTML = wedstrijden[i].wachtstarttijd;
            if (wedstrijden[i].wachtstarttijd) {
                cell.innerHTML = wedstrijden[i].wachtstarttijd.substr(11,5);
            }
        } else if (wedstrijden[i].wedstrijd_status == "spelend" || wedstrijden[i].wedstrijd_status == "gespeeld"){
            var cell = row.insertCell(cellnr);
            cellnr++;
                cell.innerHTML = wedstrijden[i].echte_start;
            if (wedstrijden[i].echte_start) {
                cell.innerHTML = wedstrijden[i].echte_start.substr(11,5);
                var cell = row.insertCell(cellnr);
                cellnr++;
                cell.innerHTML = wedstrijden[i].echte_baan;
            }
        }
        if (wedstrijden[i].wedstrijd_status == "gespeeld") {
            var cell = row.insertCell(cellnr);
            cellnr++;
            cell.innerHTML = pr(wedstrijden[i].set1_team1) + " - " + pr(wedstrijden[i].set1_team2);
            var cell = row.insertCell(cellnr);
            cellnr++;
            cell.innerHTML = pr(wedstrijden[i].set2_team1) + " - " + pr(wedstrijden[i].set2_team2);
            var cell = row.insertCell(cellnr);
            cellnr++;
            cell.innerHTML = pr(wedstrijden[i].set3_team1) + " - " + pr(wedstrijden[i].set3_team2);
            var cell = row.insertCell(cellnr);
            cellnr++;
            cell.innerHTML = "Team " + wedstrijden[i].winnaar;
            var cell = row.insertCell(cellnr);
            cellnr++;
            if (wedstrijden[i].opgave > 0) {
                cell.innerHTML = "opgave"
            }
        }
        var cell = row.insertCell(cellnr);
        cellnr++;
        if (wedstrijden[i].echte_start == null){
            select = document.createElement("SELECT");
            var option = document.createElement("option");
            option.setAttribute("value", "title");
            option.innerHTML = "Start op:";
            option.disabled = true;
            option.selected = true;
            select.appendChild(option);
            select.dataset.wedstrijd_id = wedstrijden[i].wedstrijd_id;
            select.addEventListener("change", function() { start_wedstrijd(this) } );
            for (var j = 1; j <= aantal_banen; j++) {
                var option = document.createElement("option");
                option.setAttribute("value", j);
                option.innerHTML = "baan " + j;
                if (isBaanBezet(j)){
                    option.disabled = true;
                }
                select.appendChild(option);
            }
            cell.appendChild(select);
        } else if (wedstrijden[i].wedstrijd_status == "spelend") {
            var button = document.createElement("BUTTON");
            button.innerHTML = "Uitslag invoeren";
            button.type = "button";
            button.dataset.wedstrijd_id = wedstrijden[i].wedstrijd_id;
            button.classList.add("btn");
            button.classList.add("btn-primary");
            button.classList.add("bezet");
            button.addEventListener("click", function() { showUitslagForm(this); } );
            cell.appendChild(button);
        }
    }
    var area = document.getElementById("presentation-area");
    area.appendChild(tbl);
}

/**
 * 
 * Hulpfunctie voor toonDashboard()
 * Voegt een cell met een deelnemer toe, met event handler voor aan/afwezig melden
 */
function naamCell(cell, wedstrijd_status, naam, aanwezig) {
    if (wedstrijd_status == "spelend" || wedstrijd_status == "gespeeld") {
        cell.innerHTML = naam;
        return;
    }
    var checkbox = document.createElement("INPUT");
    checkbox.type = "checkbox";
    var span = document.createElement("SPAN");
    span.innerHTML = naam;
    span.style.paddingLeft = "10px";
    cell.classList.add("hover");
    cell.addEventListener("click", function() { toggleAanwezig([this, wedstrijd_status]); } );
    var tooltip = document.createElement("DIV");
    tooltip.classList.add("tooltip");
    if (aanwezig == 1){
        checkbox.checked = true;
        cell.classList.add("aanwezig");
        tooltip.innerHTML = "aangemeld";
    } else {
        // aanwezig is null of 0:
        checkbox.checked = false;
        cell.classList.remove("aanwezig");
        tooltip.innerHTML = "niet aangemeld";
    }
    cell.appendChild(checkbox);
    cell.appendChild(span);
    cell.appendChild(tooltip);
}

function pr(nullable){
    return (nullable == null) ? " ": nullable;
}


/**
 * De eventhandlers:
 */

/**
 * start_wedstrijd voegt een nieuwe wedstrijd toe in de database, met baan en starttijd ingevuld
 * De post komt terug met een redirect naar path 'dashboard'
 */
function start_wedstrijd(select){
    var wedstrijd_id = select.dataset.wedstrijd_id;
    var baan = select.value;
    $.post("/start_wedstrijd/" + wedstrijd_id + "/" + baan, function(response){
        window.location.replace(response.redirecturl);
    }, 'json');
}

function showUitslagForm(button){
    // test eerst of de baan van deze button bezet is, zo niet, doe niets
    if (!button.classList.contains('bezet')){
        return;
    }
    // laat een nieuwe pagina met het wedstrijd uitslag formulier zien:
    window.location.replace("/wedstrijd_uitslag/"+button.dataset.wedstrijd_id);
}

function toggleAanwezig(parameters){
    var cell = parameters[0];
    var wedstrijd_status = parameters[1];
    // alleen voor de geplande wedstrijden wordt de aanwezigheid veranderd
    if (wedstrijd_status == "gepland" || wedstrijd_status == null) {
    var row = cell.parentNode.rowIndex;
        if (gepland[row]['echte_start'] != null){
            // de wedstrijd is al gestart of verder, dan wordt de aanwezigheid niet meer veranderd
            return;
        }
        if (cell.children[0].checked) {
            // speler is aangemeld, meld nu af:
            cell.children[0].checked = false;
            cell.children[2].innerHTML = "niet aangemeld";
            var aanwezig_nw = 0;
        } else {
            // speler is afgemeld, meld nu aan:
            cell.children[0].checked = true;
            cell.children[2].innerHTML = "aangemeld";
            var aanwezig_nw = 1;
        }
        cell.classList.toggle('aanwezig');
        var wedstrijd_id = gepland[row]['wedstrijd_id'];
        var cat_type = gepland[row]['cat_type'];
        var speler = "";
        switch (cell.cellIndex) {
            case 1: speler = "1A"; break;
            case 2: speler = "1B"; break;
            case 3: speler = "2A"; break;
            case 4: speler = "2B"; break;
        }
        $.post("/zet_aanwezig/" + wedstrijd_id + "/" + speler + "/" + cat_type + "/" + aanwezig_nw);
    }
}
