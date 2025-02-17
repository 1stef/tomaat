//verwijder één categorie uit de select lijst voor delete categorie en uit de categorieen string:
function deleteOption(str) {
    $("#del_category option:selected").remove();
    $("#del_category").val($("#del_category option:first").val());
}

//voeg een categorie toe als optie in de select lijst voor delete categorie:
function addOption(str) {
    var added_option = document.createElement("OPTION");
    added_option.text = str;
    document.getElementById("del_category").appendChild(added_option);
    $("#del_category").val($("#del_category option:first").val());
}

var add_button = $("#add_button");
add_button.on('click', function(e) {
    e.preventDefault();
    var add_string = $("#add_category").val();
    console.log("cat_events, add_string= "+add_string);
    if (add_string.length == 0) {
        document.getElementById("action_result").innerHTML = "geen wijziging";
    } else {
        $.post('/addcat/'+add_string, 
                function(response){
                    if (response.status == 'OK') {
                        // de context is veranderd na de callback, dus add_string moet opnieuw bepaald worden, haal hem uit het response object:
                        var add_string = response.cat;
                        // update de lijst met categorieen
                        $("#categorieen").val(response.cat_list.join(', '));
                        // geef feedback dat verwijderen van categorie uit de database gelukt is
                        document.getElementById("action_result").innerHTML = "categorie " + add_string + " toegevoegd";
                        // voeg de categorie ook toe bij de selectielijst voor verwijder categorie
                        addOption(add_string);
                    } else {
                        // geef feedback dat het verwijderen niet gelukt is (kan eigenlijk niet voorkomen)
                        document.getElementById("action_result").innerHTML = "error, geen wijziging";
                    };
                    // clear the input field:
                    $("#add_category").val("");
                }, "json")
    }
});

var delete_button = $("#del_category");
delete_button.on('click', function(e){
    e.preventDefault();
    if (!this.selectedIndex == 0) {  // the first option is the dummy option Categorie
        var delete_string = this.value;
        console.log("cat_events, delete_string= "+delete_string);
        $.post('/delcat/'+delete_string, 
                function(response){
                    if (response.status == "OK") {
                        // de context is veranderd na callback, haal delete_string uit response object:
                        var delete_string = response.cat;
                        // update de lijst met categorieen
                        $("#categorieen").val(response.cat_list.join(', '));
                        // geef feedback dat verwijderen van categorie uit de database gelukt is
                        document.getElementById("action_result").innerHTML = "categorie " + delete_string + " verwijderd";
                        // haal de categorie ook weg bij de selectielijst voor verwijder categorie
                        deleteOption(delete_string);
                    } else {
                        // geef feedback dat het verwijderen niet gelukt is (kan eigenlijk niet voorkomen)
                        document.getElementById("action_result").innerHTML = "error, geen wijziging";
                    }
                }, "json")
    }
})

$(document).ready(function(){
    // haal de inhoud voor dynamisch aanpasbare velden op en vul deze velden in:
    $.post('toernooigegevens/dynamic',
            function(response){
                // vul de lijst met categorieen
                $("#categorieen").val( response.categorieen.join(', '));
                // genereer de selectielijst met categorieen voor verwijder categorie:
                for ($cat of response.categorieen){
                    addOption($cat)
                }
            }, "json")
})