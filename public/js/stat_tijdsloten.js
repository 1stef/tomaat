$(document).ready(function() {
    if (tijdsloten.length > 0) {
        overzichtTijdsloten();
    } else {
        $('#message_para').text("Start eerst de inschrijving.");
    }
});

function overzichtTijdsloten() {
    // De parameter tijdsloten is in de twig template tijdsloten.html.twig gezet
    // en bevat rijen met: dagnummer, aantal_tijdsloten, aantal_bezet
    console.log(tijdsloten);
    // maak een array met daglabels voor de chart:
    var daglabels = [];
    var aantal_tijdsloten = [];
    var aantal_bezet = [];
    for (i=1; i<=tijdsloten.length; i++){
        daglabels[i-1]="dag "+i;
        aantal_tijdsloten[i-1]=tijdsloten[i-1].aantal_tijdsloten - tijdsloten[i-1].aantal_bezet;
        aantal_bezet[i-1]=tijdsloten[i-1].aantal_bezet;
    }

    var ctx = document.getElementById('myChart').getContext('2d');
    var chart = new Chart(ctx, {
        // The type of chart we want to create
        type: 'bar',

        // The data for our dataset
        data: {
            labels: daglabels,
            datasets: [
                {
                    label: 'Aantal geplande wedstrijden',
                    backgroundColor: 'blue',
                    borderColor: 'black',
                    data: aantal_bezet
                },
                {
                    label: 'Maximaal nog te plannen wedstrijden',
                    backgroundColor: 'green',
                    borderColor: 'black',
                    data: aantal_tijdsloten
                },
            ]
        },

        // Configuration options go here
        options: {
            scales: {
                yAxes: [{
                    stacked: true,
                }],
                xAxes: [{
                    stacked: true,
                }],
            }
        }
    });

}

