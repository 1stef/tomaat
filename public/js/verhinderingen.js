function wijzigVerhindering(button) {
    var id = button.getAttribute('data-id');
    var wedstrijd_wijziging_id = button.getAttribute('data-wedstrijd_wijziging_id');
    console.log("wijzigVerhindering "+id);
    window.location.replace("/verhindering/"+id+"/"+wedstrijd_wijziging_id);
}
