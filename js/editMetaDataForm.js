/*
This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

$ = jQuery;

/////////////modal/////////////
! function (e) {
    "function" != typeof e.matches && (e.matches = e.msMatchesSelector || e.mozMatchesSelector || e.webkitMatchesSelector || function (e) {
        for (var t = this, o = (t.document || t.ownerDocument).querySelectorAll(e), n = 0; o[n] && o[n] !== t;) ++n;
        return Boolean(o[n])
    }), "function" != typeof e.closest && (e.closest = function (e) {
        for (var t = this; t && 1 === t.nodeType;) {
            if (t.matches(e)) return t;
            t = t.parentNode
        }
        return null
    })
}(window.Element.prototype);

;(function() {
    //angular.module('d4c.frontend', ['d4c', 'd4c-widgets']);
	//angular.module('d4c-widgets', ['d4c', 'd4c-widgets']);
	//angular.bootstrap(document.getElementById("app"), ['d4c-widgets']);
	var mod = angular.module('d4c.core.config', []);
	
	mod.factory("config", [function() {
		return {
			HOST: ''
		}
	}]);
}());

document.addEventListener('DOMContentLoaded', function () {

    //modalButtons = document.querySelectorAll('.js-open-modal'),
    var overlay = document.querySelector('.js-overlay-modal'),
        closeButtons = document.querySelectorAll('.js-modal-close');

    closeButtons.forEach(function (item) {

        item.addEventListener('click', function (e) {
            var parentModal = this.closest('.modal');

            parentModal.classList.remove('active');
            overlay.classList.remove('active');
        });

    }); // end foreach


    document.body.addEventListener('keyup', function (e) {
        var key = e.keyCode;

        if (key == 27) {

            document.querySelector('.modal.active').classList.remove('active');
            document.querySelector('.overlay').classList.remove('active');
        };
    }, false);


    overlay.addEventListener('click', function () {
        document.querySelector('.modal.active').classList.remove('active');
        this.classList.remove('active');
    });

}); // end ready   
$('#formModal').after('<div style="width:62em; height:70%" class="modal" data-modal="1"><svg class="modal__cross js-modal-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="list_url_modal"><h2>Importer un rapport Vanilla</h2></div></br><div id="prew"></div></br><div><a href="#" id="prew" class="js-modal-close button js-form-submit form-submit">ok</a></div></div><div class="overlay js-overlay-modal"></div>');
/////////////modal/////////////

$('#filters').before(`<div style="height:37px;display:block"></div>`);
$('#infoTab').before(`<p><h3>INFORMATION GENERALE</h3></p>`);
$('#cartoTab').before(`<p><h3 onclick="anichange('#cartoTab')" style="cursor: pointer" >CARTOGRAPHIE</h3></p><hr size="2"/>`);
$('#resEtValidTab').before(`<p><h3 onclick="anichange('#resEtValidTab')" style="cursor: pointer" >RESSOURCES ET VALIDATION</h3></p><hr size="2"/>`);
$('#configurationTab').before(`<p><h3 onclick="anichange('#configurationTab')" style="cursor: pointer" >TABLEAU DE BORD DANS L'INFORMATION</h3></p><hr size="2"/>`);
$('#datasetLies').before(`<p><h3 onclick="anichange('#datasetLies')" style="cursor: pointer" >JEUX DE DONNEES LIES</h3></p> <hr size="2"/>`);
$('#filters').attr('style', ' background-color: #ddddda; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em ');
$('#infoTab').attr('style', ' background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em ');
$('#cartoTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#resEtValidTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#configurationTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#datasetLies').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px;  overflow:scroll; height:20em; width: 30%; overflow-x: hidden; padding:1em');
//$('#filters').after('<br>');
$('#infoTab').after('<br>');
$('#cartoTab').after('<br>');
$('#resEtValidTab').after('<br>');
$('#configurationTab').after('<br>');
$('#datasetLies').after('<br>');
//$("#edit-img-picto-upload").after('<div id= "old_img"></div><div id="btnImgHide"><br></div><div style=" overflow:scroll; height:15em; overflow-x: hidden; display: none;  width: 30%;" id="pickImg"></div><br>');
$('#edit-selected-type-map').val("");
$('#edit-table').after('<div id="up-stock" style="display:none"></div>');
for(var i=1; i<=20; i++){
	$('#up-stock').append($("#edit-table-"+i+"-file").parent().parent());
}
if ($('#selected_data').val() == 'new') {
    $('#edit-table tbody tr').remove();
}
$('#edit-valider').attr('onclick', 'validUpload(event, "resours"); validUpload(event, "img_backgr"); generateTaskUniqueId(); checkProgress()');
$('#edit-del-button-dataset').attr('onclick', 'delDataset(event);');
$('#edit-del-button-dataset').attr("style", "display: none;");
$('#edit-filtr-org').change(function () {
    clear();
});
let element = document.getElementById("edit-tags");
element.classList.add("onlyDigits");
document.querySelector(".onlyDigits").onkeyup = onlyDigits;
clear();
$('#pickImg').attr("style", "overflow:scroll; height:15em; overflow-x: hidden; display: none;  width: 30%;");
$("#btnImgHide").append(`<input class="button js-form-submit form-submit" value="Ouvrir les pictogrammes" type="button" onclick="togglePicker('#pickImg')">`);
$("#selected_data select").val($('#edit-selected-data-id').val());
$("#edit-selected-data-id").val($('#selected_data').val());
//addPicto();

$("#edit-tags input").autocomplete({
    serviceUrl: '/api/thesaurus',
    delimiter: ', ',
    minChars: 3,
    onSelect: function (suggestion) {
        
    }
});

function anichange(objName) {

    //objName = '#datasetLies';
    if ($(objName).css('display') == 'none') {
        $(objName).animate({
            height: 'show'
        }, 400);
    } else {
        $(objName).animate({
            height: 'hide'
        }, 200);
    }
}

function addData(result) {

    $('#addRowBtnWidget').remove();
    $('#edit-selected-data-id').val('');

    $('#edit-selected-data-id').val($('#selected_data').val());

    result = jQuery.parseJSON(result);

    clear();
    let data_id = $('#selected_data').val();

    if (data_id == '') {
        clear();

    } else if (data_id == 'new') {
        clear();


        $('#analyse_def_div').removeAttr("style");
        $('#analyse_def_div').attr("style", "display: none;");
        $('#edit-del-button-dataset').attr("style", "display: none;");

        //addResource(-1);
        //del(-1);


    } else {

        let data = getDataByID(result, data_id);
        fillData(data);

        $('#analyse_def_div').removeAttr("style");
        $('#analyse_def_div').attr("style", "");
        $('#edit-del-button-dataset').attr("style", "");
        $('#edit-del-button-dataset').attr("style", "color: #fcfcfa; background:#e1070799;");

    }


}

function clear() {
    $('#addRowBtnWidget').remove();
    $('#edit-description').val("");
    $('#edit-tags').val("");
    $('#edit-dataset-lies').val("");
    $('#edit-selected-theme').val("default%Default");
    $('#edit-selected-type-map').val("");
    //$("#edit-title").attr('style', 'display: none;');
    $("#edit-title input").val("");

    $('#edit-selected-lic').val("");
    $('#edit-selected-org').val("");
	for(var i=1; i<=20; i++){
		$('#up-stock').append($('[id^=edit-table-'+i+'-file]').first().parent().parent());
	}
    $('#edit-table tbody tr').remove();
    $('#edit-imgback').val('');
    $('#img_selected').remove();
    $("td>div").removeAttr("style");
    $("#edit-analize-false").removeAttr("checked");
    $("#edit-api-false").removeAttr("checked");
    $("#edit-display-versionning").removeAttr("checked");
    $('#disable_fields_empty').prop("checked", true);
    $('#edit-imgback').val('');
    // clear producer value
    $('#edit-producteur input').val('');
    // clear frequence value
    $('#edit-frequence input').val('');
    // clear source value
    $('#edit-source input').val('');
    // clear donnees source value
    $('#edit-donnes-source input').val('');
    // clear mentions legales value
    $('#edit-mention-legales textarea').val('');
 

    //$("#edit-table-widgets ").remove();


    $("#img").remove();


    $('#analyse_def_div').removeAttr("style");
    $('#analyse_def_div').attr("style", "width: 50%; height: 2em; display: none;");
    $('#edit-analyse-default').val('');

    // clear dataset lies
    var tableChecks = $('#edit-dataset-lies-table').find('input');

    //var options = $('#selected_data option');

    $("#edit-table-widgets tbody tr").remove();
    $("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(1)"">');

    var values = $.map(tableChecks, function (tableChecks) {
        return tableChecks.id;
    });


    for (let i = 0; i < values.length; i++) {

        if (values[i] != '' || values[i] != 'new') {

            $('#edit-dataset-lies-table-' + values[i] + '-dt').removeAttr("checked")

        }
    }




}

function getDataByID(data, id) {

    data = data.result.results[data.result.results.findIndex(x => x.id === id)];
    //console.log(data);

    //console.log(data);

    return data;
}


function fillData(data) {

    $('#id_row_' + data.id).attr('style', 'display: none;');
	
    //title
    $("#edit-title input").val(data.title);

    // descr
    $('#edit-description').val(data.notes);
    //keywords
    let keywords = "";

    if (data.tags.length != 0) {
        for (i = 0; i < data.tags.length; i++) {
            keywords = keywords + ',' + data.tags[i].display_name;
        }
        keywords = keywords.substring(1);
    }

    $('#edit-tags').val(keywords);
    // licene   
    $('#edit-selected-lic').val(data.license_id);

    // organization
    $('#edit-selected-org').val(data.organization.id);

    // pub_priv 
    if (data.private == true) {
        $('#edit-selected-private').val('1');
    } else {
        $('#edit-selected-private').val('0');
    }
	
    // table resources
	var upload_template = `<div id="edit-table-$$$-file" class="js-form-managed-file form-managed-file" style="margin-bottom: 5px;">
							<input data-drupal-selector="edit-table-$$$-file-upload" type="file" id="edit-table-$$$-file-upload" name="files[table_$$$_file]" size="1" class="js-form-file form-file">
							<input class="js-hide button js-form-submit form-submit" data-drupal-selector="edit-table-$$$-file-upload-button" formnovalidate="formnovalidate" type="submit" id="edit-table-$$$-file-upload-button" name="table_$$$_file_upload_button" value="Transférer">
							<input data-drupal-selector="edit-table-$$$-file-fids" type="hidden" name="table[$$$][file][fids]">
						</div>`;

	
    $('#edit-table tbody tr').remove();
    let num = 0;

    for (let i = 0; i < data.resources.length; i++) {
        //console.log(data.resources[i]);
        num = i + 1;

		//var upload = upload_template.replace(/\$\$\$/gi, num);
		var upload = '<div id="up-'+num+'"></div>';
		
        let titre = `<span class="label" id="label-table-` + num + `-name">` + data.resources[i].name + `</span>
					<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-` + num + `-name form-item-table-` + num + `-name form-no-label edit">
						<input data-drupal-selector="edit-table-` + num + `-name" type="text" id="edit-table-` + num + `-name" name="table[` + num + `][name]" value="` + data.resources[i].name + `" size="30" maxlength="128" class="form-text">
					</div>`;
        let description = `<span class="label" id="label-table-` + num + `-description">` + data.resources[i].description + `</span>
							<div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-` + num + `-description form-item-table-` + num + `-description form-no-label edit">
								<div class="form-textarea-wrapper">
									<textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-` + num + `-description" id="edit-table-` + num + `-description" name="table[` + num + `][description]" rows="5" cols="60" class="form-textarea resize-vertical">` + data.resources[i].description + `</textarea>
								</div>
							</div>`;
        let donnes = `<a class="label" id="label-table-` + num + `-donnees" href="`+ data.resources[i].url +`">` + data.resources[i].url + `</a>
						<div class="form-textarea-wrapper edit"> ` + upload + `
							<textarea data-drupal-selector="edit-table-` + num + `-donnees" id="edit-table-` + num + `-donnees" name="table[` + num + `][donnees]" rows="5" cols="60" class="form-textarea resize-vertical" style="height: 2em;width: 19em;">` + data.resources[i].url + `</textarea>
						</div>`;
        let encoding = `<span class="label" id="label-table-` + num + `-encoding"> / </span>
                    <div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-` + num + `-encoding form-item-table-` + num + `-encoding form-no-label edit">
                        <input data-drupal-selector="edit-table-` + num + `-encoding" type="text" id="edit-table-` + num + `-encoding" name="table[` + num + `][encoding]" value="UTF-8" size="30" maxlength="128" class="form-text">
                    </div>`;
						
		let donnees_old = `<a class="label" id="label-table-` + num + `-donnees_old" href="`+ data.resources[i].url +`" style="display:none;">` + data.resources[i].url + `</a>
						<div class="form-textarea-wrapper edit" style="display:none;"> ` + upload + `
							<textarea data-drupal-selector="edit-table-` + num + `-donnees_old" id="edit-table-` + num + `-donnees_old" name="table[` + num + `][donnees_old]" rows="5" cols="60" class="form-textarea resize-vertical" style="height: 2em;width: 19em;display:none;">` + data.resources[i].url + `</textarea>
						</div>`;

		let editer = `<input class="button js-form-submit form-submit label" value="Editer" type="button" onclick="editRow(` + (num) + `);">
						<input class="button js-form-submit form-submit edit" value="Valider" type="button" onclick="validRow(` + (num) + `);" style="margin-bottom: 5px;">
						<input class="button js-form-submit form-submit edit" value="Annuler" type="button" onclick="cancelRow(` + (num) + `);" style="color: #fcfcfa; background:#e1070799;">`;				
						
        let supprimer = `<input class="button js-form-submit form-submit label" value="Supprimer" type="button" onclick="hideRow(` + (num) + `);" style="color: #fcfcfa; background:#e1070799;">`;
		
		let status = `<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-` + num + `-status-1 form-item-table-` + num + `-status-1 form-no-label">
							<input style=" display: none;" data-drupal-selector="edit-table-` + num + `-status-1" type="checkbox" id="edit-table-` + num + `-status-1" name="table[` + num + `][status][1]" value="" class="form-checkbox">
						</div>
						<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-` + num + `-status-2 form-item-table-` + num + `-status-2 form-no-label">
							<input style=" display: none;" data-drupal-selector="edit-table-` + num + `-status-2" type="checkbox" id="edit-table-` + num + `-status-2" name="table[` + num + `][status][2]" value="1" class="form-checkbox">
						</div>
						<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-` + num + `-status-3 form-item-table-` + num + `-status-3 form-no-label">
							<input style="display: none;" data-drupal-selector="edit-table-` + num + `-status-3" type="text" id="edit-table-` + num + `-status-3" name="table[` + num + `][status][3]" value="" size="60" class="form-text">
						</div>`;

        $('#edit-table > tbody:last-child').append(`<tr id="row_` + num + `" data-drupal-selector="edit-table-` + num + `" class="odd resource-row noedit">
														<td>` + titre + `</td>
														<td>` + description + `</td>
														<td>` + donnes + `</td>
														<td>` + encoding + `</td>
														<td>` + editer + `</td>
														<td>` + supprimer + status + `</td>
														<td>` + donnees_old + `</td>
														
													</tr>`);

        //$('#edit-table-' + num + '-status-2').attr('checked', 'checked');
        $('#edit-table-' + num + '-status-3').val(data.resources[i].id);
		
		$('#up-' + num).append($('[id^=edit-table-'+num+'-file]').first().parent().parent());
    }

    let hasHideFieldsProp = false;
    let hasDisplayVersionning = false;
    for (let g = 0; g < data.extras.length; g++) {
        // get donnees source and source value from extra data and assign it to new source and donnees_source
        if (data.extras[g].key == 'FTP_API') {
            var value = data.extras[g].value;
            if(value != "FTP") {
                $("#edit-donnes-source input").val(value);
                const url = new URL(value);
                $("#edit-source input").val(url.hostname);
            }
            else {
                $("#edit-source input").val("FTP/SFTP");
            }
            
        }
        // get producer value
        if (data.extras[g].key == 'producer') {
            var producer = data.extras[g].value;
                $("#edit-producteur input").val(producer);
            
        }

        // get frequence value
        if (data.extras[g].key == 'frequence') {
            var frequence = data.extras[g].value;
                $("#edit-frequence input").val(frequence);
            
        }

        // get source value
        if (data.extras[g].key == 'source') {
            var source = data.extras[g].value;
            if(source != null) {
                $("#edit-source input").val(source);
            }
            
        }

        // get donnees source value
        if (data.extras[g].key == 'donnees_source') {
            var donnees_source = data.extras[g].value;
            if(donnees_source != null){
                $("#edit-donnes-source input").val(donnees_source);
            }
            
        }

        //get mention legales value
        if (data.extras[g].key == 'mention_legales') {
            var mention_legales = data.extras[g].value;
                $("#edit-mention-legales textarea").val(mention_legales);
            
        }

        if (data.extras[g].key == 'Picto') {
			var path = data.extras[g].value;
			if(!path.startsWith('/')){
				path = '/sites/default/files/api/portail_d4c/img/set-v3/pictos/' + path.replace("d4c-", "") + ".svg";
			}
            $("#old_img").append('<span id="img" style=" background-image: url('+path+'); margin-top: 0px;  display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 20em; margin-top: -4em;"></span>');
        }

        if (data.extras[g].key == 'LinkedDataSet') {
            //$("#edit-dataset-lies").val(data.extras[g].value);
            let links = data.extras[g].value;
            links = links.split(';');

            for (let f = 0; f < links.length; f++) {
                $('#edit-dataset-lies-table-' + (links[f].replace(/:/, '')) + '-dt').attr('checked', 'checked');
                $('#edit-dataset-lies-table-' + links[f] + '-dt').val(1);
            }
        }
		
		if (data.extras[g].key == 'default_visu') {
			$('#edit-selected-visu').val(data.extras[g].value);
		}

        if (data.extras[g].key == 'theme') {
            for (let f = 0; f < data.extras.length; f++) {
                if (data.extras[f].key == 'label_theme') {
                    $('#edit-selected-theme').val(data.extras[g].value + '%' + data.extras[f].value);
                }
            }
        }

        // Analyse par défaut
        if (data.extras[g].key == 'analyse_default') {
            $('#edit-analyse-default').val(data.extras[g].value);
        }

        if (data.extras[g].key == 'type_map') {
            $('#edit-selected-type-map').val(data.extras[g].value);
        }

        if (data.extras[g].key == 'overlays') {
            var selected = data.extras[g].value.split(",");
            $('#edit-authorized-overlays-map input').each(function (index, cb) {
                if (selected.indexOf(cb.defaultValue) != -1) {
                    cb.checked = true;
                }
            });
        }

        if (data.extras[g].key == 'dont_visualize_tab') {
            let dnt_vis = data.extras[g].value.split(";");

            for (let f = 0; f < dnt_vis.length; f++) {
                if (dnt_vis[f] == 'api') {
                    $("#edit-api-false").attr("checked", "checked");
                } else if (dnt_vis[f] == 'analize') {
                    $("#edit-analize-false").attr("checked", "checked");
                }
                
            }
        }

        if (data.extras[g].key == 'widgets') {
            let widgets = data.extras[g].value.split('<.explode.>');

            for (let i = 0; i < widgets.length; i++) {
                let title_w = widgets[i].split('<.info.>')[0];
                let decription_w = widgets[i].split('<.info.>')[1];
                let code_w = widgets[i].split('<.info.>')[2];

                num = i + 1;

                let offWidget = '<td><div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-widgets-' + num + '-offwidjet form-item-table-widgets-' + num + '-offwidjet form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-offwidjet" type="checkbox" id="edit-table-widgets-' + num + '-offwidjet" name="table_widgets[' + num + '][offWidjet]" value="1" class="form-checkbox"></div></td>';

                if (widgets[i].slice(-7) == '<.off.>') {
                    offWidget = '<td><div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-widgets-' + num + '-offwidjet form-item-table-widgets-' + num + '-offwidjet form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-offwidjet" type="checkbox" id="edit-table-widgets-' + num + '-offwidjet" name="table_widgets[' + num + '][offWidjet]" value="1" checked="checked" class="form-checkbox"></div></td>';
                }

                let title_widget = '<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-widgets-' + num + '-name form-item-table-widgets-' + num + '-name form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-name" type="text" id="edit-table-widgets-' + num + '-name" name="table_widgets[' + num + '][name]" value="' + title_w + '" size="30" class="form-text"></div></td>';

                let description_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-description form-item-table-widgets-' + num + '-description form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-description" id="edit-table-widgets-' + num + '-description" name="table_widgets[' + num + '][description]" rows="5" cols="60" class="form-textarea resize-vertical">' + decription_w + '</textarea></div></div></td>';

                let widget_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-widget form-item-table-widgets-' + num + '-widget form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-widget" id="edit-table-widgets-' + num + '-widget" name="table_widgets[' + num + '][widget]" rows="5" cols="60" class="form-textarea resize-vertical">' + code_w + '</textarea><a href="#" id="prew" class="js-open-modal button js-form-submit form-submit" data-modal="1" onclick="getUrlWidgets(`' + num + '`);">Importer un rapport Vanilla</a></div></div></td>';

                let del_widget = ' <td><input type="button" class="button js-form-submit form-submit" value="Supprimer" onclick="deleteRowWidget(this)"/></td>';

                $('#edit-table-widgets > tbody:last-child').append('<tr data-drupal-selector="edit-table-widgets-' + num + '" class="odd">' + title_widget + description_widget + widget_widget + offWidget + del_widget + '</tr>');

                $('#addRowBtnWidget').remove();
                $("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(' + num + ')">');
            }
        }

        
        // dataset date
        if (data.extras[g].key == 'date_dataset') {
            $('#edit-date-dataset').val(data.extras[g].value);
        }

        if (data.extras[g].key == 'disable_fields_empty') {
            hasHideFieldsProp = true;
            if(data.extras[g].value == 1 ) {
                $('#disable_fields_empty').prop("checked", true);
            }
            else {
                $('#disable_fields_empty').prop("checked", false);
            }
        }

        if (data.extras[g].key == 'display_versionning') {
            hasDisplayVersionning = true;
            if(data.extras[g].value == 1 ) {
                $('#edit-display-versionning').prop("checked", true);
            }
            else {
                $('#edit-display-versionning').prop("checked", false);
            }
        }
    }

    if (!hasHideFieldsProp) {
        $('#disable_fields_empty').prop("checked", false);
    }

    if (!hasDisplayVersionning) {
        $('#edit-display-versionning').prop("checked", false);
    }

    //    
    //    $('#edit-table-'+num+'-donnees-1-upload > input > type="file"').on('change', function triggerUploadButton(event) {
    //  $(event.target).closest('.js-form-managed-file').find('.js-form-submit').trigger('mousedown');
    //});
    //    $('#edit-table-'+num+'-donnees-1-upload > input > type="file"').on('change', function(b) {
    //  return "undefined" != typeof r && r.event.triggered !== b.type ? r.event.dispatch.apply(a, arguments) : void 0
    //});

	setTimeout(function() { 
		var scope = angular.element("#app button").scope();
		scope.validate = function () {
			if (!scope.isOldIcon) {
				if (scope.selectedPicto === '') {
					scope.currentPicto = scope.selectedPicto;
				} else {
					scope.currentPicto = 'd4c-' + scope.selectedPicto;
				}
				scope.currentPictoName = scope.selectedPictoName;
				if(typeof ngModel !== 'undefined') ngModel.$setViewValue(scope.currentPicto);
				$('#edit-imgback').val(scope.currentPicto.replace("d4c-",""));
			}
			scope.togglePicker(null);
		};
		
		//scope.selectedPicto = undefined;
		//scope.selectedPictoName = "";
		//scope.previewNoPicto();
		scope.reset();
		$("#edit-imgback").val("")
   }, 500);
}

function addResource(num) {

    num = num + 2;


    let description = '<div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-' + num + '-description form-item-table-' + num + '-description form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-' + num + '-description" id="edit-table-' + num + '-description" name="table[' + num + '][description]" rows="5" cols="60" class="form-textarea resize-vertical"></textarea></div></div>';




    let donnes = '<div class="form-textarea-wrapper"><textarea data-drupal-selector="edit-table-' + num + '-donnees-2" id="edit-table-' + num + '-donnees-2" name="table[' + num + '][donnees][2]" rows="5" cols="60" class="form-textarea resize-vertical" style="height: 2em;width: 19em;"></textarea></div>';


    //style=" display: none;"

    let supprimer = '<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-' + num + '-status-1 form-item-table-' + num + '-status-1 form-no-label"><input style=" display: none;" data-drupal-selector="edit-table-' + num + '-status-1" type="checkbox" id="edit-table-' + num + '-status-1" name="table[' + num + '][supprimer][1]" value="" class="form-checkbox"></div><div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-' + num + '-status-2 form-item-table-' + num + '-status-2 form-no-label"><input style=" display: none;" data-drupal-selector="edit-table-' + num + '-status-2" type="checkbox" id="edit-table-' + num + '-status-2" name="table[' + num + '][supprimer][2]" value="" class="form-checkbox"></div><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-' + num + '-status-3 form-item-table-' + num + '-status-3 form-no-label"><input style="display: none;" data-drupal-selector="edit-table-' + num + '-status-3" type="text" id="edit-table-' + num + '-status-3" name="table[' + num + '][supprimer][3]" value="" size="60" class="form-text"></div>' +


        '<input class="button js-form-submit form-submit" value="Supprimer" type="button" onclick="hideRow(' + (num) + ');">';

    $('#edit-table-' + num + '-status-2').selected(true);
    $('#edit-table-' + num + '-status-2').val('1');


    $('#edit-table > tbody:last-child').append('<tr id="row_' + num + '" data-drupal-selector="edit-table-' + num + '" class="odd"><td>' + titre + '</td><td>' + description + '</td><td>' + donnes + '</td><td>' + supprimer + '</td></tr>');

}

function hideRow(num) {
    var conf = confirm("Etes-vous sûr de vouloir supprimer cette ressource?");


    if (conf) {

        $('#edit-table-' + num + '-status-1').selected(true);
        $('#edit-table-' + num + '-status-1').val('1');
        $('#row_' + num).attr('style', 'display:none;');
    }
}

function editRow(num) {
	$('#edit-table-' + num + '-name').val($('#label-table-' + num + '-name').text());
	$('#edit-table-' + num + '-description').val($('#label-table-' + num + '-description').text());
	$('#edit-table-' + num + '-donnees').val($('#label-table-' + num + '-donnees').text());
	$('#edit-table-' + num + '-donnees_old').val($('#label-table-' + num + '-donnees_old').text());
	$('#row_' + num).addClass('edit').removeClass("noedit");
}

function validRow(num) {
    var conf = confirm("Etes-vous sûr de valider les modifications ?");

    if (conf) {
		$('#row_' + num).addClass('noedit').removeClass("edit");
        $('#edit-table-' + num + '-status-2').selected(true);
        $('#edit-table-' + num + '-status-2').val('1');
		
		$('#label-table-' + num + '-name').text($('#edit-table-' + num + '-name').val());
		$('#label-table-' + num + '-description').text($('#edit-table-' + num + '-description').val());
		
		if($('[id^=edit-table-'+num+'-file] a').length > 0){
			$('#label-table-' + num + '-donnees').text($('[id^=edit-table-'+num+'-file] a')[0].href);
			$('#label-table-' + num + '-donnees').attr("href",$('[id^=edit-table-'+num+'-file] a')[0].href);
			$('#edit-table-' + num + '-donnees').val($('[id^=edit-table-'+num+'-file] a')[0].href);
			$('#edit-table-' + num + '-donnees_old').val($('#label-table-' + num + '-donnees_old').text());
		} else {
			$('#label-table-' + num + '-donnees').text($('#edit-table-' + num + '-donnees').val());
		}
		
    }
}

function cancelRow(num) {
	$('#row_' + num).addClass('noedit').removeClass("edit");
	$('#edit-file_' + num + '-upload').val("");
}


function onlyDigits() {
    this.value = this.value.replace(/[\s]/g, "");

}

/*function addPicto() {

    let imgs = $('#edit-imgimg').val();
    imgs = imgs.split(';');
    imgs = imgs.slice(1);

    $('#edit-imgimg').remove();


    for (let i = 0; i < imgs.length; i++) {

        let name = imgs[i].split('/');

        name = name[(name.length - 1)];
        name = name.split('.');



        $("#pickImg").append(`<span  id="img_` + i + `"  style=" cursor: pointer; background-image: url(/` + imgs[i] + `); display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;" onclick="fillImgBakc('` + name[0] + `'); anichange('#pickImg'); addSelectedImg('` + imgs[i] + `') "></span>`);

    }

}*/

function fillImgBakc(name) {
    $('#edit-imgback').val('');
    $('#edit-imgback').val(name);
}

function addSelectedImg(url) {

    $('#img').attr('style', '');
    $('#img_selected').remove();

    $("#btnImgHide").append('<span  id="img_selected"  style=" background-image: url(/' + url + '); display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span>');
}

function validUpload(event, name) { //backgr//resours

    if ($("[name='files[" + name + "]']").val() != '' && $("[name='files[" + name + "]']").val() != null && typeof ($("[name='files[" + name + "]']").val()) != 'undefined') {

        alert("Veuillez attendre la fin du chargement des ressources");
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!event.isDefaultPrevented()) {
            event.returnValue = false;
        }

    }


}

function delDataset(event) {

    var conf = confirm("Etes-vous sûr de vouloir supprimer ce jeu de données?");


    if (conf) {

        $('#edit-del-dataset').selected(true);
        $('#edit-del-dataset').val('1');

    } else {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!event.isDefaultPrevented()) {
            event.returnValue = false;
        }

    }
}

function addWidgetRow(num) {

    num = num + 1;


    let title_widget = '<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-widgets-' + num + '-name form-item-table-widgets-' + num + '-name form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-name" type="text" id="edit-table-widgets-' + num + '-name" name="table_widgets[' + num + '][name]" value="" size="30" class="form-text"></div></td>';

    let description_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-description form-item-table-widgets-' + num + '-description form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-description" id="edit-table-widgets-' + num + '-description" name="table_widgets[' + num + '][description]" rows="5" cols="60" class="form-textarea resize-vertical"></textarea></div></div></td>';

    let widget_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-widget form-item-table-widgets-' + num + '-widget form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-widget" id="edit-table-widgets-' + num + '-widget" name="table_widgets[' + num + '][widget]" rows="5" cols="60" class="form-textarea resize-vertical"></textarea> <a href="#" id="prew" class="js-open-modal button js-form-submit form-submit" data-modal="1" onclick="getUrlWidgets(`' + num + '`);">Importer un rapport Vanilla</a></div></div></td>';

    let offWidget = '<td><div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-widgets-' + num + '-offwidjet form-item-table-widgets-' + num + '-offwidjet form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-offwidjet" type="checkbox" id="edit-table-widgets-' + num + '-offwidjet" name="table_widgets[' + num + '][offWidjet]" value="1" class="form-checkbox"></div></td>'

    let del_widget = ' <td><input type="button" class="button js-form-submit form-submit" value="Supprimer" onclick="deleteRowWidget(this)"/></td>';

    $('#edit-table-widgets > tbody:last-child').append('<tr data-drupal-selector="edit-table-widgets-' + num + '" class="odd">' + title_widget + description_widget + widget_widget + offWidget + del_widget + '</tr>');


    $('#addRowBtnWidget').remove();
    $("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(' + num + ')">');


}

function addWidgetUrl(num) {

    $('#edit-table-widgets-' + num + '-widget').val('');
    $('#edit-table-widgets-' + num + '-widget').val($('#select_widget').val());
}

function deleteRowWidget(btn) {
    var row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
}

function getUrlWidgets(num) {

    $.ajax('/api/records/2.0/callVanillaUrlReports', {
        type: 'POST',

        dataType: 'json',
        cache: true,
        success: function (data) {
            let list_url = data;



            let res = '<select  id="select_widget" name="filtr_org" class="form-select" onchange="visual_widget(); addWidgetUrl(`' + num + '`) "><option value="" selected="selected">----</option>';

            for (let i = 0; i < list_url.length; i++) {

                res = res + '<option value="' + list_url[i].url + '">' + list_url[i].name + '</option>';
            }

            res = res + '</select>';

            $('#list_url_modal').contents().remove();
            $('#list_url_modal').append(res);


            let overlay = document.querySelector('.js-overlay-modal');
            let modalElem = document.querySelector('.modal[data-modal="1"]');
            modalElem.classList.add('active');
            overlay.classList.add('active');


        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
}

function visual_widget() {
    $('#prew').contents().remove();
    let url = $('#select_widget').val();
    $('#prew').append('<iframe style="width:100%; height:25em; border:none" src="' + url + '"></iframe>');
}

function findGetParameter(parameterName) {
    var result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          tmp = item.split("=");
          if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}

if(findGetParameter("id") != null){
	$('#selected_data').val(findGetParameter("id"));
	var event = new Event('change');
	$('#selected_data')[0].dispatchEvent(event);
}