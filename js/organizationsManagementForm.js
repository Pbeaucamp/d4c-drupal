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
                
                $('#org_img').append('<div id="count_package"><label>Connaissances:' + data[i].package_count + '</label></div>');
                
				var isPublic = true;
                var coordinates = '';

                $.each(data[i].extras, function(i,e){
					if (e.key == "private") {
						if(e.value == "true"){
							isPublic = false;
						} else {
							isPublic = true;
						}
					}
                    else if (e.key == "coord") {
                        coordinates = e.value;
					}
				});
				if(isPublic){
					$('#edit-selected-private').val('1');
				} else {
					$('#edit-selected-private').val('0');
				}
                $('#edit-coord ').val(coordinates);

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
    $('#edit-coord').val('');

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
        content += '\n';
        content += '    .page-header {\n';
        content += '        visibility: hidden;\n';
        content += '        display: none;\n';
        content += '    }\n';
        content += '</style>\n';
        content += '\n';
        content += '<div class="ng-scope" ng-app="d4c-widgets" role="main">\n';
        content += '    <!-- Option Marque Blanche -->\n';
        content += '    <input id="selected-organization" type="text" class="hidden-filter" value="' + selectedOrganisation + '"/>\n';
        content += '    <input id="input-producteur" class="hidden-filter" type="hidden" />\n';
        content += '    <input id="input-map-coordinate" class="hidden-filter" type="hidden" />\n';
        content += '    <input id="input-format" class="hidden-filter" type="hidden" />\n';
        content += '    <input id="input-theme" class="hidden-filter" type="hidden" />\n';
        content += '    <input id="marque-blanche" class="hidden-filter" type="hidden" />\n';
        content += '\n';
        content += '    <div id="main" class="widget-opendata">\n';
        content += '        <div id="filter" class="col-md-2 content-body">\n';
        content += '            <h1><span id="nb_jeux">0</span> Connaissances</h1>\n';
        content += '            <!-- Keep input for CSS -->\n';
        content += '            <input id="input-tag" type="text" class="hidden-filter">\n';
        content += '            <div class="form-group">\n';
        content += '                <label for="sel1">Trier par:</label>\n';
        content += '                <select class="form-control" id="sel1">\n';
        content += '                    <option selected="selected" value="null"></option>\n';
        content += '                    <option value="alpha">Ordre alphabétique</option>\n';
        content += '                    <option value="alpha_reverse">Ordre anti alphabétique</option>\n';
        content += '                    <option value="date_recent">Récemment modifiés</option>\n';
        content += '                    <option value="date_old">Anciennement modifiés</option>\n';
        content += '                    <option value="imported_recent">Récemment importés</option>\n';
        content += '                    <option value="imported_old">Anciennement importés</option>\n';
        content += '                    <option value="enregistrement_plus">Le + d\'enregistrement</option>\n';
        content += '                    <option value="enregistrement_minus">Le - d\'enregistrement</option>\n';
        content += '                    <option value="telechargement_plus">Le + de téléchargements</option>\n';
        content += '                    <option value="telechargement_minus">Le - de téléchargements</option>\n';
        content += '                    <option value="populaire_plus">Les + populaires</option>\n';
        content += '                    <option value="populaire_minus">Les - populaires</option>\n';
        content += '                    <option value="producteur">Producteur</option>\n';
        content += '                </select>\n';
        content += '            </div>\n';
        content += '\n';
        content += '            <div id="actif-filters">\n';
        content += '                <h2>Filtres actifs <span id="reset-filters">Tout effacer</span></h2>\n';
        content += '                <ul class="jetons"></ul>\n';
        content += '            </div>\n';
        content += '\n';
        content += '            <form id="search-form">\n';
        content += '                <div class="input-group" id="barreRecherche">\n';
        content += '                    <input aria-label="recherche" class="form-control" id="search_bar" placeholder="Rechercher une connaissance..." type="text" />\n';
        content += '                    <div class="input-group-btn">\n';
        content += '                        <button class="btn btn-default" type="submit"></button>\n';
        content += '                    </div>\n';
        content += '                </div>\n';
        content += '            </form>\n';
        content += '\n';
        content += '            <h3 id="title-visualisations"> Visualisations</h3>\n';
        content += '            <ul id="list-visu" class="list-group"></ul>\n';
        content += '\n';
        content += '            <h3>Thèmes</h3>\n';
        content += '            <ul class="list-group" id="list-theme"></ul>\n';
        content += '\n';
        content += '            <h2>Télécharger le catalogue</h2>\n';
        content += '\n';
        content += '            <ul class="list-group" id="list-cat">\n';
        content += '                <li class="list-item" data-cat="csv">CSV</li>\n';
        content += '                <li class="list-item" data-cat="xls">XLS</li>\n';
        content += '                <li class="list-item" data-cat="json">JSON</li>\n';
        content += '            </ul>\n';
        content += '        </div>\n';
        content += '\n';
        content += '        <div class="col-md-10" style="display: flex;flex-direction: column;">\n';
        content += '            <div id="datasets">&nbsp;</div>\n';
        content += '\n';
        content += '            <div class="row-md-12">\n';
        content += '                <nav id="pagination">\n';
        content += '                    <ul class="pagination">\n';
        content += '                    </ul>\n';
        content += '                </nav>\n';
        content += '            </div>\n';
        content += '            <svg class="d4cwidget-spinner d4cwidget-spinner--svg hidden" version="1.1" viewbox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">\n';
        content += '                <rect class="d4cwidget-spinner__cell-11" height="30" width="30" x="0" y="0"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-12" height="30" width="30" x="35" y="0"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-13" height="30" width="30" x="70" y="0"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-21" height="30" width="30" x="0" y="35"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-22" height="30" width="30" x="35" y="35"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-23" height="30" width="30" x="70" y="35"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-31" height="30" width="30" x="0" y="70"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-32" height="30" width="30" x="35" y="70"></rect>\n';
        content += '                <rect class="d4cwidget-spinner__cell-33" height="30" width="30" x="70" y="70"></rect>\n';
        content += '            </svg>\n';
        content += '        </div>\n';
        content += '    </div>\n';
        content += '\n';
        content += '    <p><script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>\n';
        content += '    <script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>\n';
        content += '    <script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>\n';
        content += '    <script src="' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/script_portail.js"></script>\n';
        content += '    <script>\n';
        content += '        $(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );\n';
        content += '        $("head").append("<link href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\\\" rel=\\\"stylesheet\\\">");\n';
        content += '        $("head").append("<link href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/style.css\\\" rel=\\\"stylesheet\\\">");\n';
        content += '        $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\\\">");\n';
        content += '        $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\\\">");\n';
        content += '        $("head").append("<link rel=\\\"stylesheet\\\" type=\\\"text/css\\\" href=\\\"' + fetchPrefix() + '/sites/default/files/api/portail_d4c/css/font-awesome.min.css\\\">");\n';
        content += '    </script></p>\n';
        content += '</div>\n';
    }

    $('#edit-marque-blanche textarea').val(content);
}
