$(function() {
	
	$('#menu-bar a').click(function() {
		var id = $(this).attr('id');
		swi(id);
	});

//----------------------------------------------------------------------------------------------------------------------
function swi(id) {
	var qry = new Object();
	switch(id) {
		case 'misau_uss_matr':					// Unidades de saúde lista matriz (array)
			qry = {pty : 0,opt : 0,upt : 0};
			aja(qry);
			break;
		case 'misau_uss_json':					// Unidades de saúde lista json
			qry = {pty : 1,opt : 11,upt : 0};
			aja(qry);
			break;
		case 'misau_uss_table':					// Unidades de saúde tabela html
			qry = {pty : 0,opt : 12,upt : 0};
			aja(qry);
			break;
		case 'misau_uss_list':					// Unidades de saúde lista html css menu 
			qry = {pty : 0,opt : 13,upt : 0};
			aja(qry);
			break;
		case 'misau_mod_obit_json':				// Óbitos modelo json
			qry = {pty : 0,opt : 14,upt : 0};
			aja(qry);
			break;
		case 'misau_mod_obit_table':			// Óbitos modelo tabela
			qry = {pty : 0,opt : 5,upt : 0};
			aja(qry);
			break;
		case 'misau_teste_obit':				// Óbitos teste filtro
			qry = {pty : 1,opt : 16,upt : 0};
			aja(qry);
			break;
		case 'misau_doc_esp':					// Especificação
			loaddoc('specAPI.html');
			break;
		case 'misau_doc_rel':					// Relatório
			loaddoc('relatorio_API_SISMA.html');
			break;
		case 'misau_doc_gui':
		break;
		default:

	}
}
//----------------------------------------------------------------------------------------------------------------------
function loading(onoff) {
	var gif = '<div style="display:table-cell;height:300px;text-align:center;width:300px;vertical-align:middle;backgroud-color:red;"><img alt="loading" src="gif/loading.gif" /></div>'
	if (onoff) {
		$('#resp').html(gif);
	} else {
		$('#resp').html('');
	}
}
//----------------------------------------------------------------------------------------------------------------------
function loaddoc(doc) {
	loading(true);
	$('#resp').load(doc);
}
//----------------------------------------------------------------------------------------------------------------------
function aja(qry) {

	loading(true);

	var req = $.ajax({
		url: 'php/index.php',
		method: 'GET',
		dataType: 'html',
		data: qry,
		cache: false
	});
  
 	req.done(function(resp) {
		$('#resp').html(resp);
	});
 
	req.fail(function(jqXHR, textStatus) {
		$('#resp').html('Problemas de conexão: ' + textStatus);
	});

	req.when(function() {

	});
}
//----------------------------------------------------------------------------------------------------------------------
function naodisp(msg = 'opção não disponível') {	
	$('#resp').html(msg);
}
//----------------------------------------------------------------------------------------------------------------------
});
