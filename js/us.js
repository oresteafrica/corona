$(function() {
	
	
	var req = $.ajax({
		url: 'json/us_pretty.json',
		method: 'GET',
		dataType: 'json',
		cache: false
	});
 
	req.progress(function() {
		$('body').pleaseWait();
	});
 
 	req.done(function( resp ) {
		$('#resp').jsonView( resp );
	});
 
	req.fail(function( jqXHR, textStatus ) {
		alert( 'Request failed: ' + textStatus );
	});
 
	req.always(function() {
		$('body').pleaseWait('stop');
	});







});

