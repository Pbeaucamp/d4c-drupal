$ = jQuery;

$("#edit-img-org-upload").after('<div id="org_img"></div>');
/*$('#edit-valider').attr('onclick', ' validUpload(event, "img_org");');*/

function addData(data) {

    console.log(data);
    
    data = data.result;
    let org_id = $('#edit-selected-org').val();

    if (org_id == 'new') {
        clear();
    }
    else {

        clear();

        for (let i = 0; i < data.length; i++) {

            if (data[i].id == org_id) {
                $('#edit-title ').val(data[i].display_name);
                $('#edit-id ').val(data[i].name);
                $('#edit-description').val(data[i].description);

                if(data[i].image_display_url!=''){
                    $('#org_img').append('<span id="img" style=" background-image: url(' + data[i].image_display_url + '); margin-top: 0px;  display: inline-block; width: 8em; height: 8em; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 20em; margin-top: -4em;"></span>');
                }
                
                $('#org_img').append('<div id="count_package"><label>Jeux de données:' + data[i].package_count + '</label></div>');
                
				var isPublic = true;
                $.each(data[i].extras, function(i,e){
					if(e.key == "private"){
						if(e.value == "true"){
							isPublic = false;
						} else {
							isPublic = true;
						}
					} 
				});
				if(isPublic){
					$('#edit-selected-private').val('1');
				} else {
					$('#edit-selected-private').val('0');
				}

                buildWidgetCode(data[i].name);

                break;
            }
        }
    }
}

function clear() {
    $('#edit-title input').val('');
    $('#edit-id input').val('');
    $('#edit-description').val('');
    $('#org_img').empty();
    $('#count_package').remove();

    buildWidgetCode(null);
}

function validUpload(event, name) {//backgr//resours
    if ($("[name='files["+name+"]']").val() != '' && $("[name='files["+name+"]']").val() != null && typeof($("[name='files["+name+"]']").val())!='undefined') {
        alert("Veuillez attendre la fin du chargement des ressources!");

        event.preventDefault();
        event.stopImmediatePropagation();
        if (!event.isDefaultPrevented()) {
            event.returnValue = false;
        }
    }
}

function buildWidgetCode(selectedOrganisation) {
    var content = '';
    if (selectedOrganisation != null) {
        content += '<style type="text/css">\n';
        content += '    header#navbar {\n';
        content += '        display: none!important;\n';
        content += '        visibility: hidden!important;\n';
        content += '    }\n';
        content += '    \n';
        content += '    .page-header {\n';
        content += '        visibility: hidden;\n';
        content += '        display: none;\n';
        content += '    }\n';
        content += '</style>\n';
        content += '\n';
        content += '<div class="ng-scope" ng-app="d4c-widgets">\n';
        content += '    <!-- Option Marque Blanche -->\n';
        content += '    <div class="hidden-filter" id="marque-blanche" type="hidden">\n';
        content += '    <div id ="main" class="widget-opendata">\n';
        content += '        <div id="filter" class="col-md-2 content-body" >\n';
        content += '            <h1> <span id="nb_jeux">0</span> Jeux de données</h1>\n';
        content += '    		<input id="input-tag" type="text" class="hidden-filter"/>\n';
        content += '    		<input id="selected-organization" type="text" class="hidden-filter" value="' + selectedOrganisation + '"/>\n';
        content += '            \n';
        content += '    		<div class="form-group">\n';
        content += '    			<label for="sel1">Trier par:</label>\n';
        content += '    			<select class="form-control" id="sel1">\n';
        content += '    				<option selected="selected" value="null"></option>\n';
        content += '    				<option value="alpha">Ordre alphabétique</option>\n';
        content += '    				<option value="alpha_reverse">Ordre anti alphabétique</option>\n';
        content += '    				<option value="date_recent">Récemment modifiés</option>\n';
        content += '    				<option value="date_old">Anciennement modifiés</option>\n';
        content += '    				<option value="imported_recent">Récemment importés</option>\n';
        content += '    				<option value="imported_old">Anciennement importés</option>\n';
        content += '    				<option value="enregistrement_plus">Le + d\'enregistrement</option>\n';
        content += '    				<option value="enregistrement_minus">Le - d\'enregistrement</option>\n';
        content += '    				<option value="telechargement_plus">Le + de téléchargements</option>\n';
        content += '    				<option value="telechargement_minus">Le - de téléchargements</option>\n';
        content += '    				<option value="populaire_plus">Les + populaires</option>\n';
        content += '    				<option value="populaire_minus">Les - populaires</option>\n';
        content += '    				<option value="producteur">Producteur</option>\n';
        content += '    			</select>\n';
        content += '    		</div>\n';
        content += '            \n';
        content += '            <div id="actif-filters">\n';
        content += '                <h2>Filtres actifs <span id="reset-filters">Tout effacer</span></h2>\n';
        content += '                <ul class="jetons"></ul>\n';
        content += '             </div>\n';
        content += '    		\n';
        content += '    		<h2> Filtres </h2>\n';
        content += '    		\n';
        content += '    		<form id="search-form">\n';
        content += '    			<div class="input-group" id="barreRecherche">\n';
        content += '    				<input id="search_bar" type="text"  class="form-control" aria-label="recherche" placeholder="Rechercher un jeu de données...">\n';
        content += '    				<div class="input-group-btn">\n';
        content += '    					<button class="btn btn-default" type="submit">\n';
        content += '    					<i class="glyphicon glyphicon-search"></i>\n';
        content += '    					</button>\n';
        content += '    				</div>\n';
        content += '    			</div>\n';
        content += '    			\n';
        content += '    		</form> \n';        
        content += '            <div id="div-producteur">\n';
        content += '                <h3>Producteurs</h3>\n';
        content += '                <ul class="list-group" id="list-producteur">\n';
        content += '                </ul>\n';
        content += '            </div>\n';
        content += '            <input class="hidden-filter" id="input-producteur" type="hidden" />\n';
        content += '            <input class="hidden-filter" id="input-map-coordinate" type="hidden" />\n';
        content += '            <input class="hidden-filter" id="input-format" type="hidden" />\n';
        content += '    		<ul id="list-visu" class="list-group">\n';
        content += '    		</ul>\n';
        content += '            \n';
        content += '    		<h3>Mots Clés</h3>\n';
        content += '    		<ul id="list-tag" class="list-group">\n';
        content += '    		</ul>\n';
        content += '    		\n';
        content += '    		<h3>Thèmes</h3>\n';
        content += '    		<ul id="list-theme" class="list-group">\n';
        content += '    		<input id="input-theme" type="hidden" class="hidden-filter">\n';
        content += '    		</ul>\n';
        content += '    		\n';
        content += '    		<h2>Télécharger le catalogue</h2>\n';
        content += '    		<ul id="list-cat" class="list-group">\n';
        content += '    			<li class="list-item" data-cat="csv"><i class="fa fa-file" aria-hidden="true"></i>CSV <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>\n';
        content += '    			<li class="list-item" data-cat="xls"><i class="fa fa-file" aria-hidden="true"></i>XLS <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>\n';
        content += '    			<li class="list-item" data-cat="json"><i class="fa fa-file" aria-hidden="true"></i>JSON <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>\n';
        content += '    		</ul>\n';
        content += '            \n';
        content += '        </div>\n';
        content += '        \n';
        content += '        <div class="col-md-10" style="display: flex;flex-direction: column;" >\n';
        content += '            <div id="datasets">\n';
        content += '            </div>\n';
        content += '            <div class="row-md-12">\n';
        content += '                <nav id="pagination">\n';
        content += '                    <ul class="pagination">\n';
        content += '                    </ul>\n';
        content += '                </nav>\n';
        content += '            </div>\n';
        content += '            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" version="1.1" class="d4cwidget-spinner d4cwidget-spinner--svg hidden">    <rect x="0" y="0" width="30" height="30" class="d4cwidget-spinner__cell-11"></rect>    <rect x="35" y="0" width="30" height="30" class="d4cwidget-spinner__cell-12"></rect>    <rect x="70" y="0" width="30" height="30" class="d4cwidget-spinner__cell-13"></rect>    <rect x="0" y="35" width="30" height="30" class="d4cwidget-spinner__cell-21"></rect>    <rect x="35" y="35" width="30" height="30" class="d4cwidget-spinner__cell-22"></rect>    <rect x="70" y="35" width="30" height="30" class="d4cwidget-spinner__cell-23"></rect>    <rect x="0" y="70" width="30" height="30" class="d4cwidget-spinner__cell-31"></rect>    <rect x="35" y="70" width="30" height="30" class="d4cwidget-spinner__cell-32"></rect>    <rect x="70" y="70" width="30" height="30" class="d4cwidget-spinner__cell-33"></rect></svg>\n';
        content += '        </div>\n';
        content += '    </div>\n';
        content += '</div>\n';
        content += '<p><script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>\n';
        content += '<script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>\n';
        content += '<script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>\n';
        content += '<script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/script_portail.js"></script>\n';
        content += '<script>\n';
        content += '    $(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );\n';
        content += '    $("head").append("<link href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\\\" rel=\\\"stylesheet\\\">");\n';
        content += '    $("head").append("<link href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/style.css\\\" rel=\\\"stylesheet\\\">");\n';
        content += '    $("head").append("<link href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/\'.$config->client->css_file.\'\\\" rel=\\\"stylesheet\\\">");\n';
        content += '    $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\\\">");\n';
        content += '    $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\\\">");\n';
        content += '    $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\\\">");\n';
        content += '</script></p>\n';
    }

    $('#edit-marque-blanche textarea').val(content);
}
