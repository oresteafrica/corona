$(function() {

corona_lan();
var lan = rc('language') ? rc('language') : $('#corona_lan').val();

var jqxhr = $.getJSON('mobile.php?lan='+lan+'&opt=7')
.done(function(a) { // success
	var cc = rc('country');
	$.each( a, function( key, value ) {
		if (value==cc) {
			$('#corona_countries').append('<option value="'+value+'" selected>'+value+'</option>');
		} else {
			$('#corona_countries').append('<option value="'+value+'">'+value+'</option>');
		}
	});
	var country = rc('country') ? rc('country') : $('#corona_countries').val();
	corona_data(country,lan);
})
.fail(function(a,b,c) { // error
		alert('a comunicação com o servidor foi interrompida'+'\n'+a+'\n'+b+'\n'+c+'\nInit');
	})
.always(function() { // complete

	});
	
$('#corona_source').html(vars[lan][9]+' <a target="_blank" href="https://github.com/CSSEGISandData/COVID-19/tree/master/csse_covid_19_data">Johns Hopkins University</a>');

$('#corona_countries').change(function() {
	var c = this.value;
	document.cookie = 'country='+c+'; max-age=31536000';
	var l = $('#corona_lan').val();
	corona_data(c,l);
});

$('#corona_lan').change(function() {
	var l = this.value;
	var c = $('#corona_countries').val();
	document.cookie = 'language='+l+'; max-age=31536000';
	corona_data(c,l);
});

//----------------------------------------------------------------------------------------------------------------------
function corona_data(country,lan) {
	$('#corona_graph').pleaseWait();
	var jqxhr = $.getJSON('mobile.php?countries='+country+'&lan='+lan+'&opt=8')
	.done(function(a) { // success
		corona_text(a,lan,country);
		corona_graph(a,lan,country);
	})
	.fail(function(a,b,c) { // error
		alert('a comunicação com o servidor foi interrompida'+'\n'+a+'\n'+b+'\n'+c+'\ncorona_data('+country+','+lan+')');
	})
	.always(function() { // complete
		$('#corona_graph').pleaseWait('stop');
	});
$('#corona_source').html(vars[lan][9]+' <a target="_blank" href="https://github.com/CSSEGISandData/COVID-19/tree/master/csse_covid_19_data">Johns Hopkins University</a>');
}
//----------------------------------------------------------------------------------------------------------------------
function corona_lan() {
	var lans = {en:'English', pt:'Português', fr:'Français', it:'Italiano'};
	var cl = rc('language');
	$.each( lans, function( key, value ) {
		if (key==cl) {
			$('#corona_lan').append('<option value="'+key+'" selected>'+value+'</option>');
		} else {
			$('#corona_lan').append('<option value="'+key+'">'+value+'</option>');
		}
	});

}
//----------------------------------------------------------------------------------------------------------------------
function corona_countries(lan) {
var jqxhr = $.getJSON('mobile.php?lan='+lan+'&opt=7')
.done(function(a) { // success
	var cc = rc('country');
	$.each( a, function( key, value ) {
		if (value==cc) {
			$('#corona_countries').append('<option value="'+value+'" selected>'+value+'</option>');
		} else {
			$('#corona_countries').append('<option value="'+value+'">'+value+'</option>');
		}
	});
})
.fail(function(a,b,c) { // error
		alert('a comunicação com o servidor foi interrompida'+'\n'+a+'\n'+b+'\n'+c);
	})
.always(function() { // complete

	});
}
//----------------------------------------------------------------------------------------------------------------------
function corona_text(json_data,lan,country) {
	const dateoptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
	const NumberFormatter = new Intl.NumberFormat(lan);
	var last_date = new Date(json_data[1][json_data[1].length-1]);
	var last_confirmed = json_data[2][json_data[2].length-1];
	var last_deaths = json_data[3][json_data[3].length-1];
	var last_recovered = json_data[4][json_data[4].length-1];


	var html = '';
	var html = html + '<p><b>'+ last_date.toLocaleDateString(lan, dateoptions) + '</b><br />';
	var html = html + vars[lan][1]+': <b>'+ NumberFormatter.format(last_confirmed) + '</b><br />';
	var html = html + vars[lan][2]+': <b>'+ NumberFormatter.format(last_deaths) + '</b><br />';
	var html = html + vars[lan][3] + ': <b>' + NumberFormatter.format(last_recovered) + '</b></p>';

	var jap = JSON.stringify(json_data, null, 2);

	$('#corona_text').html(html);

}
//----------------------------------------------------------------------------------------------------------------------
function corona_graph(json_data,lan,country) {

	var dates = json_data[1].map(function (x) { return x.slice(x.length - 5); });
	var confirmed = json_data[2].map(function (x) { return parseInt(x, 10); });
	var deaths = json_data[3].map(function (x) { return parseInt(x, 10); });
	var recovered = json_data[4].map(function (x) { return parseInt(x, 10); });

	var diff_confirmed = json_data[2].map(function (data,index) {
							if (index==0) {
								return data;
							} else {
								return (data - json_data[2][index-1]);
							}
	});
	var diff_deaths = json_data[3].map(function (data,index) {
							if (index==0) {
								return data;
							} else {
								return (data - json_data[3][index-1]);
							}
	});
	var diff_recovered = json_data[4].map(function (data,index) {
							if (index==0) {
								return data;
							} else {
								return (data - json_data[4][index-1]);
							}
	});
	
	
	
	$('#corona_graph').highcharts({
		title: { text: 'Covid-19', x: -20 },
		subtitle: { text: vars[lan][4]+' - ' + country, x: -20 },
		xAxis: { 
			categories: dates 
		},
		yAxis: {
			title: { text: vars[lan][5]},
			plotLines: [ { value: 0, width: 1, color: '#808080' } ]
		},
		tooltip: { valueSuffix: ' ' + vars[lan][5] },
		legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
		series: 
		[ 
			{ name: vars[lan][1], data: confirmed },
			{ name: vars[lan][2], data: deaths },
			{ name: vars[lan][3], data: recovered }
		]
	});

/*
	$('#corona_graph2').highcharts({
        chart: { 
            renderTo: 'lineChart',
            type: 'line'
        },
		title: { text: 'Covid-19', x: -20 },
		subtitle: { text: vars[lan][6]+' - ' + country, x: -20 },
		xAxis: { 
			categories: dates 
		},
		yAxis: {
			title: { text: vars[lan][7]},
			plotLines: [ { value: 0, width: 1, color: '#808080' } ]
		},
		tooltip: { valueSuffix: ' ' + vars[lan][5] },
		legend: { layout: 'vertical', align: 'right', verticalAlign: 'middle', borderWidth: 0 },
		series: 
		[ 
			{ name: vars[lan][1], data: diff_confirmed },
			{ name: vars[lan][2], data: diff_deaths }	//,
//			{ name: vars[lan][3], data: diff_recovered }
		]
	});

*/

	if (dates.length == confirmed.length) {
		const NumberFormatter = new Intl.NumberFormat(lan);
		var corona_htmltable = '<table><thead><tr><th>'+vars[lan][8]+'</th><th>'+vars[lan][1]+'</th><th>'+vars[lan][2]+'</th><th>'+vars[lan][3]+'</th><tr></thead><tbody>';
		$.each(dates, function( index, value ) {
			corona_htmltable += '<tr><td>'+value+'</td><td>'+NumberFormatter.format(confirmed[index])
				+'</td><td>'+NumberFormatter.format(deaths[index])+
				'</td><td>'+NumberFormatter.format(recovered[index])+'</td><tr>';
		});
		corona_htmltable += '</tbody></table>';
		$('#corona_table').html(corona_htmltable);
	}

}
//----------------------------------------------------------------------------------------------------------------------
function cc(name,value,days) { // create Cookie
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}
//----------------------------------------------------------------------------------------------------------------------
function rc(name) { // read Cookie
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}
//----------------------------------------------------------------------------------------------------------------------
function ec(name) { // erase Cookie
	cc(name,"",-1);
}
//----------------------------------------------------------------------------------------------------------------------
});
