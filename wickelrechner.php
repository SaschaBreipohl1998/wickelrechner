<?php
defined( 'ABSPATH' ) or die( 'Kein direkter Zugriff erlaubt.' );
 
/**
 * Plugin Name:       Wickelrechner
 * Description:       Ein Wickelrechner welcher mit dem Shortcode [wickelrechner] eingebunden werden kann. Exklusiv für Schäper Steuerungsservice programmiert - Eine Weitergabe an Dritte ist untersagt!
 * Version:           1.0.0
 * Author:            Sascha Breipohl
 * Author URI:        mailto:sascha@breipohl.net
 * Text Domain:       wickelrechner
 */

function wickelrechner_shortcode() {
ob_start();
?>
<!-- START WICKELRECHNER PLUGIN -->
<!-- Programmiert von Sascha Breipohl EXKLUSIV für Schäper Steuerungsservice GmbH & Co. KG - Weitergabe untersagt -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js"></script>
<?php if (!isset($_POST['wickelrechner']) or $_POST['wickelrechner'] == "show_form") { ?>
<form method="post">
	<fieldset>
		<p class="form_element form_element_half" id="element_dmin">
			<label for="dmin">d<sub>min</sub> <small>(in mm)</small></label> 
			<input name="dmin" class="text_input is_empty" type="number" id="dmin" placeholder="50" min="0" required="required" />
		</p>
		<p class="form_element form_element_half" id="element_dmax">
			<label for="dmax">d<sub>max</sub> <small>(in mm)</small></label> 
			<input name="dmax" class="text_input is_empty" type="number" id="dmax" placeholder="1500" min="0" required="required" />
		</p>
		<p class="form_element form_element_half" id="element_charakteristik">
			<label for="charakteristik">Charakteristik <small>(in Prozent)</small></label> 
			<input name="charakteristik" class="text_input is_empty" type="number" id="charakteristik" min="0" max="100" placeholder="15" required="required" />
		</p>
		<p class="form_element form_element_half" id="element_anfangszugkraft">
			<label for="anfangszugkraft">Anfangszugkraft bei d<sub>min</sub> <small>(in Prozent)</small></label> 
			<input name="anfangszugkraft" class="text_input is_empty" type="number" id="anfangszugkraft" min="0" max="100" placeholder="90" required="required" />
		</p>
		<p class="form_element">
			<input type="hidden" name="wickelrechner" value="show_chart" />
			<input type="submit" value="Berechnen" class="button" />
		</p>
	</fieldset>
</form>
<?php
} elseif (isset($_POST['dmin']) and isset($_POST['dmax']) and isset($_POST['charakteristik']) and isset($_POST['anfangszugkraft'])) { 
	// Eingabevariabeln
	$dmin = (int) htmlspecialchars($_POST['dmin']);
	$dmax = (int) htmlspecialchars($_POST['dmax']);
	$charakteristik = (int) htmlspecialchars($_POST['charakteristik']);
	$anfangszugkraft = (int) htmlspecialchars($_POST['anfangszugkraft']);

	// Anzahl der Zwischenwerte
	$steps = 15;

	// Start der Berechnungen
	$wickelverhaeltnis = $dmax / $dmin;

	$ist_durchmesser = array();
	$ist_durchmesser[] = $dmin;
	for($count = 1; $count < $steps - 1; $count++) {
		$ist_durchmesser[] = (($dmax - $dmin)/14) + $ist_durchmesser[$count - 1];
	}
	$ist_durchmesser[] = $dmax;

	$motormoment = array();
	$motormoment[] = round($anfangszugkraft / $wickelverhaeltnis, 2);
	for($count = 1; $count < $steps; $count++) {
		$motormoment[] = $anfangszugkraft / ($dmax / $ist_durchmesser[$count]) - (($anfangszugkraft/($dmax / $ist_durchmesser[$count]) - ($anfangszugkraft / $wickelverhaeltnis)) / 100 * ( 100 - $charakteristik));
	}

	$bahnzugkraft = array();
	for($count = 0; $count < $steps; $count++) {
		$bahnzugkraft[] = $motormoment[$count] * ($dmax / $ist_durchmesser[$count]);
	}

	// Alle Werte in den Arrays auf zwei Nachkommastellen runden
	array_walk($ist_durchmesser, function (&$el) {
		$el = number_format($el, 2, '.', '');
	});
	array_walk($motormoment, function (&$el) {
		$el = number_format($el, 2, '.', '');
	});
	array_walk($bahnzugkraft, function (&$el) {
		$el = number_format($el, 2, '.', '');
	});
?>
<fieldset>
	<p class="form_element form_element_half" id="element_dmin">
		<b>d<sub>min</sub></b> 
		<?php echo $dmin." mm"; ?>
	</p>
	<p class="form_element form_element_half" id="element_dmax">
		<b>d<sub>max</sub></b> 
		<?php echo $dmax." mm"; ?>
	</p>
	<p class="form_element form_element_half" id="element_charakteristik">
		<b>Charakteristik</b> 
		<?php echo $charakteristik." %"; ?>
	</p>
	<p class="form_element form_element_half" id="element_anfangszugkraft">
		<b>Anfangszugkraft bei d<sub>min</sub></b> 
		<?php echo $anfangszugkraft." %"; ?>
	</p>
</fieldset>
<canvas id="wickelrechner" width="100" height="50"></canvas>
<form method="post">
	<fieldset>
		<p class="form_element">
			<input type="hidden" name="wickelrechner" value="show_form" />
			<input type="submit" value="Neu berechnen" class="button" />
		</p>
	</fieldset>
</form>
<script>
var ctx = document.getElementById("wickelrechner").getContext('2d');
var wickelrechner = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(", ", $ist_durchmesser); ?>],
        datasets: [{
            label: 'Motormoment in %',
            data: [<?php echo implode(", ", $motormoment); ?>],
			backgroundColor: 'blue',
			borderColor: 'blue',
			fill: false,
        }, {
            label: 'Bahnzugkraft in %',
            data: [<?php echo implode(", ", $bahnzugkraft); ?>],
			backgroundColor: 'orange',
			borderColor: 'orange',
			fill: false,
        }]
    },
    options: {
		responsive: true,
		tooltips: {
			mode: 'index',
			intersect: false,
		},
		hover: {
			mode: 'nearest',
			intersect: true
		},
        scales: {
            xAxes: [{
                ticks: {
                    beginAtZero:true
                },
				scaleLabel: {
					display: true,
					labelString: 'Durchmesser in mm'
				}
            }],
            yAxes: [{
                ticks: {
					min: 0,
					max: 100,
                    beginAtZero:true
                },
				scaleLabel: {
					display: true,
					labelString: 'Zugkraft in Prozent'
				}
            }]
        }
    }
});
</script>
<?php } else { ?>
<p>Bei der Berechnung ist ein Fehler aufgetreten!</p>
<?php } ?>
<!-- ENDE WICKELRECHNER PLUGIN -->
<?php
return ob_get_clean();
}
	
add_shortcode('wickelrechner', 'wickelrechner_shortcode' );
?>