<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Flight Distance Calculator | Partial Project</title>
	<meta name="description" content="A small project that shows the distance of a flight">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="apple-touch-icon" href="apple-touch-icon.png">

	<meta charset="UTF-8">


	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">


	<style>
			.flight-calculator
			{
				height: 100vh;
			}
			.searchView {
				display: none;
				position: absolute;
				width: inherit;
				z-index: 10;
				min-height: 60px;
				max-height: 300px;
				overflow-y: scroll;
				background-color: #fff;
				padding: 2px;
			}

			.airport-link {
				padding: 5px;
			}

			.airport-code {
				margin-right: -10px;
			}
			
			#map {
				height: 400px;
			}


	</style>

</head>



<body>
	

<h1 class="text-center">Flight Distance Calculator</h1>
<hr>

<div class="alert alert-success" role="alert">
	This is a small project that shows the distance between airports. 
	The data is pulled via ajax request from <a href="https://www.developer.aero/Airport-API/API-Overview">SITA</a>.
	Simply type into the boxes below and the distance will be calculated.
</div>

<div class="container">
	<div class="flight-calculator">

		<div class="col-md-5">
			<div class="form-group">
				<div class="col-md-11">
					<input type="text" placeholder="departure" id="departure" class="form-control">
					<div id="departureSearchView" class="searchView"></div>
				</div>
				<div class="col-md-1">
					<div id="departureAirportCode" class="airport-code pull-right"></div>
				</div>
			</div>

			<div class="form-group">
				<div class="col-md-11">
					<input type="text" placeholder="arrival" id="arrival" class="form-control">
					<div id="arrivalSearchView" class="searchView"></div>
				</div>
				<div class="col-md-1">
					<div id="arrivalAirportCode" class="airport-code pull-right"></div>
				</div>
			</div>

			<div class="col-md-11">
				<h4 id="distance"></h4>
			</div>
		</div>


		<div class="col-md-7 pull-right">
			<div class="map" id="map"></div>
		</div>
	
	</div>
</div>


<script src="https://maps.googleapis.com/maps/api/js?v=3.exp"></script>
<script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>


<script>

// 
// Map
// 

var mapOptions = {
zoom: 3,
center: new google.maps.LatLng(43, -79),
mapTypeId: google.maps.MapTypeId.TERRAIN
};

var map = new google.maps.Map(document.getElementById('map'),
  mapOptions);

var markers = [];

function addMarker(direction, name, lat, lng)
{
	if ( markers[direction] !== undefined )
	{
		markers[direction].setMap(null);	
	}

	var myLatlng = new google.maps.LatLng(lat,lng);
	markers[direction] = new google.maps.Marker({
	position: myLatlng,
	map: map,
	title: name

  });

	map.panTo(markers[direction].position);

	if ( markers.departure !== undefined && markers.arrival !== undefined)
	{
		var departureCoordinates = new google.maps.LatLng(markers.departure.position.k, markers.departure.position.D);
		var arrivalCoordinates = new google.maps.LatLng(markers.arrival.position.k, markers.arrival.position.D);
		addLine(map, departureCoordinates, arrivalCoordinates);
	}
}

var flightPath; 

function addLine(map, departureCoordinates, arrivalCoordinates)
{
	if (flightPath !== undefined)
	{
		flightPath.setMap(null);
	}

	console.log(departureCoordinates + ' ' + arrivalCoordinates);
	
	var flightPlanCoordinates = [
		departureCoordinates,
		arrivalCoordinates
	];

	flightPath = new google.maps.Polyline({
	path: flightPlanCoordinates,
	geodesic: false,
	strokeColor: '#FF0000',
	strokeOpacity: 1.0,
	strokeWeight: 2,
	});


	console.log(flightPath);
	flightPath.setMap(map);

	var bounds = new google.maps.LatLngBounds();
    bounds.extend(flightPath.getPath().getAt(0));
    bounds.extend(flightPath.getPath().getAt(1));
    map.fitBounds(bounds);
}

// 
// Flight distance Calculator
// 

$("#departure").keyup(function(){
	displayResults('departure');
});

$('#arrival').keyup(function() 
{
	displayResults('arrival');
});

// 
// on focus out of each input it will calculate the distance between the two airports and display 
// it on the page

$('#departureSearchView, #arrivalSearchView').focusout(function()
{
	var $arrivalAirportCode = $('#arrivalAirportCode').html();
	var $departureAirportCode = $('#departureAirportCode').html();

	if ($arrivalAirportCode !== '' && $departureAirportCode !== '')
	{
		getAirportDistance($departureAirportCode, $arrivalAirportCode, function(data){
			displayDistance(data);
		});
	}
});

// 
// on click of a link in the search view box it will place the code, city name and country name
// into the input box - will also add code to code div

$('#departureSearchView, #arrivalSearchView').on('click', '.airport-link', function(event){

	event.preventDefault();
	var $this = $(this);
	var $direction = $this.attr('data-airport-direction');
	var $city = $this.attr('data-airport-city');
	var $country = $this.attr('data-airport-country');
	var $code = $this.attr('data-airport-code');
	var $lat = $this.attr('data-airport-lat');
	var $lng = $this.attr('data-airport-lng');

	$('#' + $direction).val($code + ', ' + $city + ' ' + $country);
	$('#' + $direction + 'AirportCode').html($code);
	$('#' + $direction + 'SearchView').hide();

	addMarker($direction, $city, $lat, $lng);



});


// 
// will display the given distance data on the page
// 

function displayDistance(data)
{
	var distance = data.distance;
	var units = data.units;

	$('#distance').html(distance + ' ' + units);
}

// 
// displays results in dropdown box 
//

function displayResults(direction) 
{
	var $searchView  = $('#' + direction + 'SearchView');

	$searchView.slideDown();

	var $searchString = $('#' + direction).val();

	searchAirports($searchString, function(data){

		$searchView.html('');

		var airports = data.airports;

		if (airports.length > 0 )
		{
			for (var i = airports.length - 1; i <= airports.length; i--) {

				var airport = airports[i]

				if  (airport === undefined)
				{
					return;
				}
				var airportName = "<h3 class=airport-name>" + airport.name + "</h3>";
				var airportLocation = "<h4>" + airport.city + " " + airport.country +"</h4>"
				var linkToAirport = "<a class=airport-link href=# data-airport-code=" + airport.code + 
										" data-airport-city=" + airport.city + 
										" data-airport-country=" + airport.country + 
										" data-airport-direction=" + direction + 
										" data-airport-lat=" + airport.lat +
										" data-airport-lng=" + airport.lng +
										">" + airportName + airportLocation + "</a>"
				$searchView.append(linkToAirport);
			};
		} else {
			$searchView.html('No results found...');
		}
	});
}

// 
// fetches airport data for a given string
// 

function searchAirports(cityString, fn) {

    $.ajax({
		url: "https://airport.api.aero/airport/match/" + cityString + "?user_key=0fe9922118fb2f06ae6c0cb48d119918",
		type: "get",
		dataType: "jsonp",
		cache: false,
        error: function (err) {
            console.log(err);
        },
        success: function (data) {
            fn(data);
        }
    });

}

// 
// fetches the distance between two given airport codes
// 

function getAirportDistance(departure, arrival, fn) {

    $.ajax({
		url: "https://airport.api.aero/airport/distance/" + departure + "/" + arrival + "?user_key=0fe9922118fb2f06ae6c0cb48d119918",
		type: "get",
		dataType: "jsonp",
		cache: false,
        error: function (err) {
            displayDialogBox("Error", err.toString());
        },
        success: function (data) {
            fn(data);
        }
    });

}


</script>

</body>
</html>