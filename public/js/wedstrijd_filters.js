function catFilter(){
    var cat = $('input[type=radio][name="cat_filter"]:checked').val();
    tbl = document.getElementById("wedstrijden");
    if (cat == "alle"){
        for (var i = 0; i < wedstrijden.length; i++) {
            tbl.rows[i].classList.remove("verberg_cat");
        }    
    } else {
        for (var i = 0; i < wedstrijden.length; i++) {
            if (wedstrijden[i].categorie == cat){
                tbl.rows[i].classList.remove("verberg_cat");
            } else {
                tbl.rows[i].classList.add("verberg_cat");
            }
        }
    }
}

function dagFilter(){
    var dag = $('input[type=radio][name="dag_filter"]:checked').val();
    tbl = document.getElementById("wedstrijden");
    if (dag == "alle"){
        for (var i = 0; i < wedstrijden.length; i++) {
            tbl.rows[i].classList.remove("verberg_dag");
        }    
    } else {
        for (var i = 0; i < wedstrijden.length; i++) {
            if (wedstrijden[i].dagnummer == dag){
                tbl.rows[i].classList.remove("verberg_dag");
            } else {
                tbl.rows[i].classList.add("verberg_dag");
            }
        }
    }
}

function bondsnrFilter(){
    var bondsnr = $("#bondsnummer").val();
    tbl = document.getElementById("wedstrijden");
    if (bondsnr == 0){
        for (var i = 0; i < wedstrijden.length; i++) {
            tbl.rows[i].classList.remove("verberg_bondsnr");
        }    
    } else {
        for (var i = 0; i < wedstrijden.length; i++) {
            if ((wedstrijden[i].bondsnr1A == bondsnr)||
                (wedstrijden[i].bondsnr1B == bondsnr)||
                (wedstrijden[i].bondsnr2A == bondsnr)||
                (wedstrijden[i].bondsnr1B == bondsnr))
            {
                tbl.rows[i].classList.remove("verberg_bondsnr");
            } else {
                tbl.rows[i].classList.add("verberg_bondsnr");
            }
        }
    }
}

function naamFilter(){
    var naam = $("#naam").val();
    tbl = document.getElementById("wedstrijden");
    if (naam == ""){
        for (var i = 0; i < wedstrijden.length; i++) {
            tbl.rows[i].classList.remove("verberg_naam");
        }    
    } else {
        for (var i = 0; i < wedstrijden.length; i++) {
            if ((wedstrijden[i].naam1A.indexOf(naam) > -1)||
                ((wedstrijden[i].cat_type == "dubbel") && (wedstrijden[i].naam1B.indexOf(naam) > -1))||
                (wedstrijden[i].naam2A.indexOf(naam) > -1)||
                ((wedstrijden[i].cat_type == "dubbel") && (wedstrijden[i].naam2B.indexOf(naam) > -1)))
            {
                tbl.rows[i].classList.remove("verberg_naam");
            } else {
                tbl.rows[i].classList.add("verberg_naam");
            }
        }
    }
}