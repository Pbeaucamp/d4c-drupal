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
 
!function(e){"function"!=typeof e.matches&&(e.matches=e.msMatchesSelector||e.mozMatchesSelector||e.webkitMatchesSelector||function(e){for(var t=this,o=(t.document||t.ownerDocument).querySelectorAll(e),n=0;o[n]&&o[n]!==t;)++n;return Boolean(o[n])}),"function"!=typeof e.closest&&(e.closest=function(e){for(var t=this;t&&1===t.nodeType;){if(t.matches(e))return t;t=t.parentNode}return null})}(window.Element.prototype);

document.addEventListener('DOMContentLoaded', function() {



    //modalButtons = document.querySelectorAll('.js-open-modal'),
     var  overlay      = document.querySelector('.js-overlay-modal'),
       closeButtons = document.querySelectorAll('.js-modal-close');



   closeButtons.forEach(function(item){

      item.addEventListener('click', function(e) {
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


    overlay.addEventListener('click', function() {

        document.querySelector('.modal.active').classList.remove('active');
        this.classList.remove('active');
    });

}); // end ready
    
$('#formModal').after('<div class="modal" data-modal="1"><svg class="modal__cross js-modal-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="tablePlace" style="overflow:scroll; height:35em; "></div></div><div class="overlay js-overlay-modal"></div>');
/////////////modal/////////////


hide_param();
var validation_resurce = [];

$('#edit-chercher').after('&nbsp;<span id="chercher_b" onClick="controlSiteSearch();" style="cursor:pointer; border-style:solid!important; border-radius:10px; border:1px; border-color:#a6a6a6; background-color:#f0f0eb; ">&nbsp; Recherche &nbsp;</span>');

$('#edit-search').attr('onclick', 'checkDatasetResources(event);');

var locale = "";
if($("#domaine").length){
	locale = '<span> <input name="search_on_site" type="radio" checked="checked" value="locale" onclick="hide_param();"> '+ $("#domaine").text() +'</span> <br>'
}
$('#edit-chercher').attr("placeholder", "Mots clés de recherche");
$('#edit-chercher').before('<div id="site_search" style="margin-bottom:1em;""><p>'+locale+
'<span><input name="search_on_site"  type="radio" value="Data_Gouv_fr" onclick="hide_param();"> Data.Gouv.fr </span><br>'+
'<span> <input name="search_on_site" type="radio" value="Public_OpenDataSoft_com" onclick="hide_param();"> Public.OpenDataSoft.com</span><br>'+
'<span> <input name="search_on_site" type="radio" value="socrata" onclick="hide_param();"> Moissonneur Socrata</span> <br> '+
'<span> <input name="search_on_site" type="radio" value="dckan" onclick="hide_param();"> Ckan/Dkan</span><br> '+
'<span> <input name="search_on_site" type="radio" value="d4c" onclick="hide_param();"> D4C</span><br> '+
'<span> <input name="search_on_site" type="radio" value="odsall" onclick="hide_param();"> ODS</span><br> '+
'<span> <input name="search_on_site" type="radio" value="arcgis" onclick="hide_param();"> ArcGIS</span>'+
'</p></div>'+
'<div id="div_ckan"><br><label for="edit-ckan-search">Url Ckan/Dkan:</label><input data-drupal-selector="edit-ckan-search" type="search" id="edit-ckan-search" name="ckan_search" value="" size="60" maxlength="128" class="form-search"><br><br></div>'+
'<div id="div_d4c" style="display:none;"><br><label for="edit-d4c-search">Url Data4citizen:</label><input data-drupal-selector="edit-d4c-search" type="search" id="edit-d4c-search" name="d4c_search" value="" size="60" maxlength="128" class="form-search"><br><br></div>'+
'<div id="div_odsall" style="display:none;"><br><label for="edit-odsall-search">Url ODS:</label><input data-drupal-selector="edit-odsall-search" type="search" id="edit-odsall-search" name="odsall_search" value="" size="60" maxlength="128" class="form-search"><br><br></div>'+
'<div id="div_socrata" style="display:none;"><br><label for="edit-socrata-search">URL Socrata:</label><input data-drupal-selector="edit-socrata-search" type="search" id="edit-socrata-search" name="socrata_search" value="" size="60" maxlength="128" class="form-search"><br><br></div>'+
'<div id="div_arcgis" style="display:none;"><br><label for="edit-arcgis-search">Url ArcGIS:</label><input data-drupal-selector="edit-arcgis-search" type="search" id="edit-arcgis-search" name="arcgis_search" value="" size="60" maxlength="128" class="form-search"><br><br></div>');

$('#site_search').after('<div id="param_dataGouv"><hr><p><span><input name="search_by" type="radio" value="datasets"> Data Sets </span>&nbsp;<span> <input name="search_by" type="radio" checked="checked" value="organizations"> Organisations</span></p></div>');



$('#edit-ids').attr('style', '');
$('#org_div').attr('style', 'display:none;');
$('#div_ckan').attr('style', 'display:none;');
hide_param();

$('#chercher_b').after('<br><br><div><select style="width: 27%;"  id="edit-selected-org" name="selected_org" class="form-select" onchange="addDatasetCheckBox();"></div>');

window.addEventListener("keypress", function (e) {
    if (e.keyCode !== 13) return;

    controlSiteSearch();

    e.preventDefault();
    e.stopImmediatePropagation();
    if (!e.isDefaultPrevented()) {
        e.returnValue = false;
    }

});



$('#edit-selected-org').attr('onchange', 'addDatasetCheckBox();');
$('#edit-ids div').empty();




function controlSiteSearch() {
    //hide_param();
    
    let siteSearch = $('input[name=search_on_site]:checked').val();

    if (siteSearch == 'Data_Gouv_fr') {
        goSearch_Gouv_fr();
        $('#param_dataGouv').removeAttr('style');
        $('#edit-site-search').val('');
        $('#edit-site-search').val('Data_Gouv_fr');

    } else if (siteSearch == 'locale') {
        goSearch_InfoCom94();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('InfoCom94');


    } else if (siteSearch == 'Public_OpenDataSoft_com') {
       goSearch_Opendatasoft();

        $('#edit-site-search').val('');
        $('#edit-site-search').val('Public_OpenDataSoft_com');

    }
    else if (siteSearch == 'socrata') {
       goSearch_socrata();

        $('#edit-site-search').val('');
        $('#edit-site-search').val('socrata');
        $('#div_socrata').removeAttr('style');

    }
    
    else if(siteSearch == 'dckan'){
        //alert();
        search_ckan();
        
        $('#edit-site-search').val('');
        $('#edit-site-search').val('ckan'); 
        $('#div_ckan').removeAttr('style');
        
    }
    else if(siteSearch == 'd4c'){
        //alert();
        search_d4c();
        
        $('#edit-site-search').val('');
        $('#edit-site-search').val('d4c'); 
        $('#div_d4c').removeAttr('style');
        
    }
    else if(siteSearch == 'odsall'){
        //alert();
        goSearch_Opendatasoft_all_site();
        
        $('#edit-site-search').val('');
        $('#edit-site-search').val('odsall'); 
        $('#div_odsall').removeAttr('style');
        
    }
	else if(siteSearch == 'arcgis'){
        //alert();
        goSearch_ArcGIS();
        
        $('#edit-site-search').val('');
        $('#edit-site-search').val('arcgis'); 
        $('#div_arcgis').removeAttr('style');
		$('#edit-chercher').val('');
		$('#edit-chercher').hide('');
        
    }

}

function hide_param() {
    $('#param_dataGouv').attr('style', 'display:none;');
    $('#div_ckan').attr('style', 'display:none;');
    $('#div_d4c').attr('style', 'display:none;');
    $('#div_odsall').attr('style', 'display:none;');
    $('#div_arcgis').attr('style', 'display:none;');
    $('#div_socrata').attr('style', 'display:none;');
$('#edit-ids').empty();




    let siteSearch = $('input[name=search_on_site]:checked').val();
   

    if (siteSearch == 'Data_Gouv_fr') {

        $('#param_dataGouv').removeAttr('style');
        $('#edit-site-search').val('');
        $('#edit-site-search').val('Data_Gouv_fr');
		$('#edit-chercher').show('');

    } else if (siteSearch == 'InfoCom94') {
        $('#edit-site-search').val('');
        $('#edit-site-search').val('InfoCom94');
		$('#edit-chercher').show('');

    } else if (siteSearch == 'Public_OpenDataSoft_com') {
        $('#edit-site-search').val('');
        $('#edit-site-search').val('Public_OpenDataSoft_com');
		$('#edit-chercher').show('');

    }
    else if (siteSearch == 'socrata') {
        //alert();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('socrata');
        $('#div_socrata').removeAttr('style');
		$('#edit-chercher').show('');
    } 
    else if (siteSearch == 'dckan') {
        //alert();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('ckan'); 
        $('#div_ckan').removeAttr('style');
		$('#edit-chercher').show('');
    }
    else if (siteSearch == 'd4c') {
        //alert();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('d4c'); 
        $('#div_d4c').removeAttr('style');
		$('#edit-chercher').show('');
    }
    else if (siteSearch == 'odsall') {
        //alert();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('odsall'); 
        $('#div_odsall').removeAttr('style');
		$('#edit-chercher').show('');
    }
	else if (siteSearch == 'arcgis') {
        //alert();
        $('#edit-site-search').val('');
        $('#edit-site-search').val('arcgis'); 
        $('#div_arcgis').removeAttr('style');
		$('#edit-chercher').val('');
		$('#edit-chercher').hide('');
    }

}


////////////////////////Data_Gouv_fr///////////////////

//choize type search in gouv fr
function goSearch_Gouv_fr() {
    let typeSearch = $('input[name=search_by]:checked').val();
    //console.log(typeSearch);


    if (typeSearch == 'organizations') {
        $('#org_div').attr('style', 'display:none;');

        $('#edit-type-rech input').val('organizations');

        getOrganization();



    } else if (typeSearch == 'datasets') {

        $('#org_div').attr('style', 'width: 50%;');
        //$('#edit-org-def').attr('style','');
        $('#edit-type-rech input').val('datasets');
        getDataset();

    }


}
// org gouvfr 
function getOrganization() {

    $('html,body').attr('style', 'cursor:wait !important;');
    $('input[type="submit"]').attr('disabled', true);
    $('#edit-selected-org').attr('style', '');
    $('#edit-selected-org').attr('style', 'style="width: 27%;"');
    $('#edit-ids').empty();
    var org;

    $.getJSON('https://www.data.gouv.fr/api/1/organizations/?q=' + $('#edit-chercher').val(), function (result) {
        //$.getJSON('https://www.data.gouv.fr/api/1/datasets/?page_size=10000&q='+$('#edit-chercher').val(), function(result){
        //response data are now in the result variable
        //console.log(result);



        $('#edit-selected-org').find('option').remove();
        $('#edit-selected-org').append($('<option>', {
            value: "",
            text: "----"
        }));

        if (result.data.length != 0) {

            let option = [];



            for (let i = 0; i < result.data.length; i++) {


                option[i] = ({
                    value: result.data[i].id,
                    text: result.data[i].slug
                });


                //    $('#edit-selected-org').append($('<option>', {
                //    value: result.data[i].id,
                //    text: result.data[i].slug
                //}));    


                if (result.data.length == (i + 1)) {
                    $('html,body').removeAttr("style");
                    $('input[type="submit"]').attr('disabled', false);
                }

            }


            option.sort(function (a, b) {
                var textA = a.text.toLowerCase(),
                    textB = b.text.toLowerCase()
                if (textA < textB)
                    return -1
                if (textA > textB)
                    return 1
                return 0
            })

            //console.log(option);
            for (let f = 0; f < option.length; f++) {
                $('#edit-selected-org').append($('<option>', {
                    value: option[f].value,
                    text: option[f].text
                }));

            }


        } else {
            alert("Aucune information trouvée pour votre recherche.");
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
        }



    });








    //$('html,body').removeAttr("style");    
    //$('html,body').attr('style','cursor:default !important;');



}

// dataset gouvfr 
function getDataset() {

    let wh = window.innerHeight;
    wh = wh / 1.55;



    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());

    $('html,body').attr('style', 'cursor:wait !important;');
    $('input[type="submit"]').attr('disabled', true);

    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();
    // alert(org);



    console.log($('#edit-chercher').val());
    //$.getJSON('https://www.data.gouv.fr/api/1/organizations/?page_size=10000&q='+$('#edit-chercher').val(), function(result){
    $.getJSON('https://www.data.gouv.fr/api/1/datasets/?q=' + $('#edit-chercher').val(), function (result) {
		console.log(result);
        if (result.data.length != 0) {



            result.data.sort(function (a, b) {
                var textA = a.slug.toLowerCase(),
                    textB = b.slug.toLowerCase()
                if (textA < textB)
                    return -1
                if (textA > textB)
                    return 1
                return 0
            })





            for (let f = 0; f < result.data.length; f++) {
                //console.log(result.data[f]);
                let res_valid = false;
                console.log(result.data[f]);
                for (let g = 0; g < result.data[f].resources.length; g++) {


                    if (result.data[f].resources[g].format == 'CSV' || result.data[f].resources[g].format == 'XLS' || result.data[f].resources[g].format == 'XLSX' || result.data[f].resources[g].format == 'csv' || result.data[f].resources[g].format == 'xls' || result.data[f].resources[g].format == 'xlsx') {
                        res_valid = true;
                        
                        if(result.data[f].resources[g].format == 'CSV' || result.data[f].resources[g].format == 'csv'){
                            //console.log(result.data[f].resources[g]);
                            
                        let url_res=result.data[f].resources[g].url,
                            type_res='csv',
                            type_site='DataGouvfr'; 
               
                            
                            $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result.data[f].id + ' form-item-ids-' + result.data[f].id + '">&nbsp; <input data-drupal-selector="edit-ids-' + result.data[f].id + '" type="checkbox" id="edit-ids-' + result.data[f].id + '" name="ids[' + result.data[f].id + ']" value="' + result.data[f].id + '" class="form-checkbox"> &nbsp;<a href="' + result.data[f].page + '" target="_blank">' + result.data[f].slug + '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a> </div>');
                              //console.log(result.data[f].resources[g].url);
							  break;
                        }
						else {
							 //console.log(result.data[f].resources[g].url);
						}
                        

                    }

                }


                validation_resurce.push({
                    name: result.data[f].slug,
                    status_res: res_valid,
                    id: result.data[f].id
                });

               if(res_valid==false){
                   
              

                $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result.data[f].id + ' form-item-ids-' + result.data[f].id + '">&nbsp; <input data-drupal-selector="edit-ids-' + result.data[f].id + '" type="checkbox" id="edit-ids-' + result.data[f].id + '" name="ids[' + result.data[f].id + ']" value="' + result.data[f].id + '" class="form-checkbox"> &nbsp;<a href="' + result.data[f].page + '" target="_blank">' + result.data[f].slug + '</a> </div>');
}

                if (result.data.length == (f + 1)) {
                    $('html,body').removeAttr("style");
                    $('input[type="submit"]').attr('disabled', false);
                }

            }
        } else {
            alert("Aucune information trouvée pour votre recherche.");
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);

        }


    });

}

// search dataset&org dataset gouvfr 
function addDatasetCheckBox() {


    let wh = window.innerHeight;
    wh = wh / 1.8;

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());

    $('html,body').attr('style', 'cursor:wait !important;');
    $('input[type="submit"]').attr('disabled', true);

    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();
    // alert(org);



    $.getJSON('https://www.data.gouv.fr/api/1/datasets/?page_size=10000&organization=' + org, function (result) {

        //console.log(result);
        //console.log(result.data.length);

        if (result.data.length != 0) {

		

            result.data.sort(function (a, b) {
                var textA = a.slug.toLowerCase(),
                    textB = b.slug.toLowerCase()
                if (textA < textB)
                    return -1
                if (textA > textB)
                    return 1
                return 0
            })





            for (let f = 0; f < result.data.length; f++) {
				let added = false;
                //console.log(result.data[f]);
                let res_valid = false;
                for (let g = 0; g < result.data[f].resources.length; g++) {

                    if (result.data[f].resources[g].format == 'CSV' || result.data[f].resources[g].format == 'XLS' || result.data[f].resources[g].format == 'XLSX' || result.data[f].resources[g].format == 'csv' || result.data[f].resources[g].format == 'xls' || result.data[f].resources[g].format == 'xlsx') {
                       
                        
                        if(result.data[f].resources[g].format == 'CSV' || result.data[f].resources[g].format == 'csv'){
							 res_valid = true;
                            //console.log(result.data[f].resources[g]);
                            
                        let url_res=result.data[f].resources[g].url,
                            type_res='csv',
                            type_site='DataGouvfr'; 
                            
                            
                            
                            
							added = true;
                            $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result.data[f].id + ' form-item-ids-' + result.data[f].id + '">&nbsp; <input data-drupal-selector="edit-ids-' + result.data[f].id + '" type="checkbox" id="edit-ids-' + result.data[f].id + '" name="ids[' + result.data[f].id + ']" value="' + result.data[f].id + '" class="form-checkbox"> &nbsp;<a href="' + result.data[f].page + '" target="_blank">' + result.data[f].slug + '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a> </div>');
                             break;
                        }
                        

                    }

                }


                validation_resurce.push({
                    name: result.data[f].slug,
                    status_res: res_valid,
                    id: result.data[f].id
                });

               if(res_valid==false){
                   added = true;

                $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result.data[f].id + ' form-item-ids-' + result.data[f].id + '">&nbsp; <input data-drupal-selector="edit-ids-' + result.data[f].id + '" type="checkbox" id="edit-ids-' + result.data[f].id + '" name="ids[' + result.data[f].id + ']" value="' + result.data[f].id + '" class="form-checkbox"> &nbsp;<a href="' + result.data[f].page + '" target="_blank">' + result.data[f].slug + '</a> </div>');
}

                if (result.data.length == (f + 1)) {
                    $('html,body').removeAttr("style");
                    $('input[type="submit"]').attr('disabled', false);
                }

				if(!added) {
					console.log(result.data[f].id);
				}
            }
        } else {
            alert("Il n'y a aucun DataSet dans cette organisation.");
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);

        }

    });

    //$('html,body').css('cursor','default');
    // $('html,body').removeAttr("style");
    //$('html,body').attr('style','cursor:default !important;');

    //console.log('GO2');
}

function checkDatasetResources(event) {


    let allVals = [];

    $('#edit-ids :checked').each(function () {
        allVals.push($(this).val());
    });

    let nonRes = false;
    let massage = '';

    // console.log(allVals);

    for (let f = 0; f < allVals.length; f++) {


        let resNV = validation_resurce.find(x => x.id === allVals[f]);


        if (resNV.status_res == false) {
            massage = massage + '; ' + resNV.name;
            nonRes = true;
        }

    }



    if (nonRes == true) {

        if (massage.split(';').length > 1) {
            var conf = confirm("Les jeu de données suivants: " + massage.substring(1) + " comportent les types des fichiers différents parmi lesquels il n'y a pas de .csv ou .xls. Voulez-vous continuer?");
        } else {

            var conf = confirm("Le jeu de données " + massage.substring(1) + " comporte les types des fichiers différents parmi lesquels il n y a pas de .csv ou .xls. Voulez-vous continuer?");

        }





        if (!conf) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!event.isDefaultPrevented()) {
                event.returnValue = false;
            }
        }

    }



}

////////////////////////Data_Gouv_fr///////////////////



/////////////////InfoCom94///////////////////////////

function goSearch_InfoCom94() {



    let wh = window.innerHeight;
    wh = wh / 1.55;



    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());



    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();




    $.ajax('/datasets/update/callInfocom94/' + $('#edit-chercher').val(), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
            //gogo2();
        },
        success: function (result) {

            //result.data = result.result.results;

            if (result.length != 0) {


                result.sort(function (a, b) {
                    var textA = a.title.toLowerCase(),
                        textB = b.title.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })





                for (let f = 0; f < result.length; f++) {
                    let res_valid = false;

                    for (let g = 0; g < result[f].resources.length; g++) {

                        if (result[f].resources[g].format == 'CSV' || result[f].resources[g].format == 'XLS' || result[f].resources[g].format == 'XLSX' || result[f].resources[g].format == 'csv' || result[f].resources[g].format == 'xls' || result[f].resources[g].format == 'xlsx') {
                            res_valid = true;
                            
                            
                            if(result[f].resources[g].format == 'CSV' || result[f].resources[g].format == 'csv'){
                            //console.log(result.data[f].resources[g]);
                            
                         let url_res=result[f].resources[g].url,
                            
                            type_res='csv',
                            type_site='InfoCom94'; 

                            
                    $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + ' form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" type="checkbox" id="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" name="ids[' + result[f].id + '|' + result[f].siteOfDataset + ']" value="' + result[f].id + '|' + result[f].siteOfDataset + '" class="form-checkbox"> &nbsp;<a href="' + result[f].url + '" target="_blank">' + result[f].title + '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a></div>');
                        
                        
                        
                        
                        
                        
                        
                             
                        }


                        }

                    }

                    validation_resurce.push({
                        name: result[f].slug,
                        status_res: res_valid,
                        id: result[f].id
                    });
                    
                    if(res_valid == false){
                        
                   

                    $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + ' form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" type="checkbox" id="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" name="ids[' + result[f].id + '|' + result[f].siteOfDataset + ']" value="' + result[f].id + '|' + result[f].siteOfDataset + '" class="form-checkbox"> &nbsp;<a href="' + result[f].url + '" target="_blank">' + result[f].title + '</a></div>');
 }

                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }



            } else {
                alert("Aucune information trouvée pour votre recherche.");

            }


        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });




}

/////////////////InfoCom94///////////////////////////



////////////////Opendatasoft/////////////////////////

function goSearch_Opendatasoft() {

    let wh = window.innerHeight;
    wh = wh / 1.55;

    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());

    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

    $.ajax('/api/datasets/2.0/callSearchOpendatasoft/' + $('#edit-chercher').val(), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {
            //console.log(result);
            result = result.datasets;

            if (result.length != 0) {
                result.sort(function (a, b) {
                    var textA = a.metas.title.toLowerCase(),
                        textB = b.metas.title.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })

                for (let f = 0; f < result.length; f++) {

                    let url_res =result[f].datasetid;
                    let type_res ='csv';
                    let type_site ='Public.OpenDataSoft.com';
                    
                    $(`#edit-ids`).append(`
					<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-` + result[f].datasetid+` form-item-ids-` + result[f].datasetid+ `">
						<input data-drupal-selector="edit-ids-` + result[f].datasetid+ `" type="checkbox" id="edit-ids-` + result[f].datasetid+ `" name="ids[` + result[f].datasetid+ `]" value='{"id":"` + result[f].datasetid+ `"}' class="form-checkbox">
						<a href="https://public.opendatasoft.com/explore/dataset/` + result[f].datasetid+ `" target="_blank">` + result[f].metas.title+ `</a>
						<a href="#" class="js-open-modal" data-modal="1" data-url="https://public.opendatasoft.com/" data-id="`+url_res+`" data-type="ods" data-parameters="{}" onclick="openModalFilter($(this));">
							<span title="Filtrer" class="fa fa-filter" style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>
						</a>
					</div>`);

                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }
            } 
            else {
                alert("Aucune information trouvée pour votre recherche.");
            }
        },
        error: function (e) {
            console.log("ERROR: ", e);
        },
    });
}

////////////////Opendatasoft/////////////////////////

////////////////Opendatasoft_all_site/////////////////////////

function goSearch_Opendatasoft_all_site() {

	let pat = /^https?:\/\//i,
        pat2 = /^http?:\/\//i,
        parser = document.createElement('a'),
        url = $('#edit-odsall-search').val();
    if (pat.test(url)) {
        parser.href = url;
        url = parser.host;
    } else if (pat2.test(url)) {
        parser.href = url;
        url = parser.host;
    }

    let wh = window.innerHeight;
    wh = wh / 1.55;

    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());

    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

    $.ajax('/api/datasets/2.0/callSearchOpendatasoftAllSite/' +url+ ';' + $('#edit-chercher').val(), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {
            result = result.datasets;
            if (result.length != 0) {
                result.sort(function (a, b) {
                    var textA = a.metas.title.toLowerCase(),
                        textB = b.metas.title.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })


                for (let f = 0; f < result.length; f++) {
                    let url_res=result[f].datasetid,
                        type_res='csv',
                        type_site='odsall:'+url;
                    $(`#edit-ids`).append(`
					<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-` + result[f].datasetid+` form-item-ids-` + result[f].datasetid+ `">
						<input data-drupal-selector="edit-ids-` + result[f].datasetid+ `" type="checkbox" id="edit-ids-` + result[f].datasetid+ `" name="ids[` + result[f].datasetid+ `]" value='{"id":"` + result[f].datasetid+ `", "url":"https://` + url+ `"}' class="form-checkbox">
						<a href="https://` + url+ `/explore/dataset/` + result[f].datasetid+ `" target="_blank">` + result[f].metas.title+ `</a>
						<a href="#" class="js-open-modal" data-modal="1" data-url="https://` + url+ `/" data-id="`+url_res+`" data-type="ods" data-parameters="{}" onclick="openModalFilter($(this));">
							<span title="Filtrer" class="fa fa-filter" style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>
						</a>
					</div>`);
                  
                    /*$('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].datasetid+'|'+url+' form-item-ids-' + result[f].datasetid+'|'+url+ '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].datasetid+'|'+url+ '" type="checkbox" id="edit-ids-' + result[f].datasetid+'|'+url+ '" name="ids[' + result[f].datasetid+'|'+url+ ']" value="' + result[f].datasetid+'|'+url+ '" class="form-checkbox"> &nbsp;<a href="https://'+url+'/explore/dataset/' + result[f].datasetid+'" target="_blank">' + result[f].metas.title+ '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a></div>');*/

                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }
            } 
            else {
                alert("Aucune information trouvée pour votre recherche.");

            }
        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
}

////////////////Opendatasoft_all_site/////////////////////////


////////////////Socrata/////////////////////////

function goSearch_socrata(){
    
     let pat = /^https?:\/\//i,
        pat2 = /^http?:\/\//i,
        parser = document.createElement('a'),
        urlSocrata = $('#edit-socrata-search').val();
    
    if (pat.test(urlSocrata)) {

        parser.href = urlSocrata;
        urlSocrata = parser.host;

    } else if (pat2.test(urlSocrata)) {
        parser.href = urlSocrata;
        urlSocrata = parser.host;
    }
    
    
    let wh = window.innerHeight;
    wh = wh / 1.55;



    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());



    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

  
    //urlSocrata

    $.ajax('/datasets/update/socrataCall/' +urlSocrata+';' + $('#edit-chercher').val().replace(' ', '_'), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {
           

            result = result.results;

            if (result.length != 0) {


//                result.sort(function (a, b) {
//                    var textA = a.title.toLowerCase(),
//                        textB = b.title.toLowerCase()
//                    if (textA < textB)
//                        return -1
//                    if (textA > textB)
//                        return 1
//                    return 0
//                })

                    
                        



                for (let f = 0; f < result.length; f++) {
                    
                    let url_res= urlSocrata,
                        type_res='csv',
                        type_site='socrata:'+result[f].resource.id;
                    
                   //console.log(result);
                        
                     $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].resource.id + '|' + urlSocrata + ' form-item-ids-' + result[f].resource.id + '|' + urlSocrata + '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].resource.id + '|' + urlSocrata + '" type="checkbox" id="edit-ids-' + result[f].resource.id + '|' + urlSocrata + '" name="ids[' + result[f].resource.id + '|' + urlSocrata + ']" value="' + result[f].resource.id + '|' + urlSocrata + '" class="form-checkbox"> &nbsp;<a href="' + result[f].link + '" target="_blank">' + result[f].resource.name + '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a></div>');
                    

                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }


            } 
            else {
                alert("Aucune information trouvée pour votre recherche.");
            }


        },
        error: function (e) {
            
            alert("Aucune information trouvée pour votre recherche.");
            console.log("ERROR: ", e);
        },

    });


    
    
}

////////////////Socrata/////////////////////////

///////////////CKan////////////////////////////
function search_ckan(){
    //alert();
    
    let pat = /^https?:\/\//i,
        pat2 = /^http?:\/\//i,
        parser = document.createElement('a'),
        urlCkan = $('#edit-ckan-search').val();
    if (pat.test(urlCkan)) {

        parser.href = urlCkan;
        urlCkan = parser.host;

    } else if (pat2.test(urlCkan)) {
        parser.href = urlCkan;
        urlCkan = parser.host;
    }
    
    
   //console.log(urlCkan); 
    
     let wh = window.innerHeight;
    wh = wh / 1.55;



    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());



    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

   // let param = $('#edit-chercher').val().replace(' ', '_');


    $.ajax('/datasets/update/ckanSearchCall/' + urlCkan+';'+$('#edit-chercher').val().replace(' ', '_'), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {



//console.log(result);
           

            result = result.result.results;

            if (result.length != 0) {


                result.sort(function (a, b) {
                    var textA = a.title.toLowerCase(),
                        textB = b.title.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })





                for (let f = 0; f < result.length; f++) {
                    
                    //console.log(result[f]);
                    let res_valid = false;
                    
                    
                    for(let g = 0; g < result[f].resources.length; g++){
                        var check_csv = false;
                        if(result[f].resources[g].format=='CSV' || result[f].resources[g].format=='csv'){
                            
                            check_csv = true;
                            
                        let url_res=result[f].resources[g].url,
                            type_res='csv',
                            type_site='Ckan'; 
                            
                            
                            $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' +result[f].id+'|'+urlCkan+ ' form-item-ids-' +result[f].id+'|'+urlCkan+ '">&nbsp; <input data-drupal-selector="edit-ids-' +result[f].id+'|'+urlCkan+ '" type="checkbox" id="edit-ids-' +result[f].id+'|'+urlCkan+ '" name="ids[' +result[f].id+'|'+urlCkan+ ']" value="' +result[f].id+'|'+urlCkan+ '" class="form-checkbox"> &nbsp;<a href="http://'+urlCkan+'/dataset/' + result[f].id+ '" target="_blank">' +result[f].title+ '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a> </div>');
                            
                            break;
                           
                           }
                        
                    }
                    
                    if(check_csv == false){
                        
                       $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' +result[f].id+'|'+urlCkan+ ' form-item-ids-' +result[f].id+'|'+urlCkan+ '">&nbsp; <input data-drupal-selector="edit-ids-' +result[f].id+'|'+urlCkan+ '" type="checkbox" id="edit-ids-' +result[f].id+'|'+urlCkan+ '" name="ids[' +result[f].id+'|'+urlCkan+ ']" value="' +result[f].id+'|'+urlCkan+ '" class="form-checkbox"> &nbsp;<a href="http://'+urlCkan+'/dataset/' + result[f].id+ '" target="_blank">' +result[f].title+ '</a> </div>'); 
                        
                    }
                    

                    
                                    


                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }



            } 
            else {
                alert("Aucune information trouvée pour votre recherche.");
            }


        },
        error: function (e) {
            
            alert("Aucune information trouvée pour votre recherche.");
            console.log("ERROR: ", e);
        },

    });


    
    
    
}
///////////////CKan////////////////////////////


////////////D4C///////////////////////////////

function search_d4c(){

    let pat = /^https?:\/\//i,
        pat2 = /^http?:\/\//i,
        parser = document.createElement('a'),
        urlD4c = $('#edit-d4c-search').val(),
		url;
    if (pat.test(urlD4c)) {

        parser.href = urlD4c;
        urlD4c = parser.host;
		url = "https://" + urlD4c;
    } else if (pat2.test(urlD4c)) {
        parser.href = urlD4c;
        urlD4c = parser.host;
		url = "http://" + urlD4c;
    }
    
    let wh = window.innerHeight;
    wh = wh / 1.55;
    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());

    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

    $.ajax('/datasets/update/calld4c/' +urlD4c+ ';' + $('#edit-chercher').val(), {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {


            if (result.length != 0) {
                result.sort(function (a, b) {
                    var textA = a.title.toLowerCase(),
                        textB = b.title.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })
				
                for (let f = 0; f < result.length; f++) {
                    let res_valid = false;
					var datasetid = result[f].name;
                    for (let g = 0; g < result[f].resources.length; g++) {

                        if (result[f].resources[g].format == 'CSV' || result[f].resources[g].format == 'XLS' || result[f].resources[g].format == 'XLSX' || result[f].resources[g].format == 'csv' || result[f].resources[g].format == 'xls' || result[f].resources[g].format == 'xlsx') {
                            res_valid = true;
                            
							if(result[f].resources[g].format == 'CSV' || result[f].resources[g].format == 'csv'){
                            //console.log(result.data[f].resources[g]);
                            
								let url_res=result[f].resources[g].url,
								
								type_res='csv',
								type_site='D4C'; 
							 
								$(`#edit-ids`).append(`
								<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-` + result[f].id+` form-item-ids-` + result[f].id+ `">
									<input data-drupal-selector="edit-ids-` + result[f].id+ `" type="checkbox" id="edit-ids-` + result[f].id+ `" name="ids[` + result[f].id+ `]" value='{"id":"` + result[f].id+ `", "url":"` + url + `", "urlExplore":"` + result[f].siteOfDataset+ `"}' class="form-checkbox">
									<a href="` + result[f].url + `" target="_blank">` + result[f].title+ `</a>
									<a href="#" class="js-open-modal" data-modal="1" data-url="` + url+ `/" data-id="`+datasetid+`" data-type="d4c" data-parameters="{}" onclick="openModalFilter($(this));">
										<span title="Filtrer" class="fa fa-filter" style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>
									</a>
								</div>`);
								
								/*$('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + ' form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" type="checkbox" id="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" name="ids[' + result[f].id + '|' + result[f].siteOfDataset + ']" value="' + result[f].id + '|' + result[f].siteOfDataset + '" class="form-checkbox"> &nbsp;<a href="' + result[f].url + '" target="_blank">' + result[f].title + '</a>|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a></div>');*/
                        
							}
                        }
                    }

                    validation_resurce.push({
                        name: result[f].slug,
                        status_res: res_valid,
                        id: result[f].id
                    });

                    if(res_valid == false){
                       $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + ' form-item-ids-' + result[f].id + '|' + result[f].siteOfDataset + '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" type="checkbox" id="edit-ids-' + result[f].id + '|' + result[f].siteOfDataset + '" name="ids[' + result[f].id + '|' + result[f].siteOfDataset + ']" value="' + result[f].id + '|' + result[f].siteOfDataset + '" class="form-checkbox"> &nbsp;<a href="' + result[f].url + '" target="_blank">' + result[f].title + '</a></div>');
					}
                    
                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }
                }

            } else {
                alert("Aucune information trouvée pour votre recherche.");

            }
        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
    
    
    
}

////////////D4C///////////////////////////////

////////////////ArcGIS/////////////////////////

function goSearch_ArcGIS() {

	url = $('#edit-arcgis-search').val();
    
    let wh = window.innerHeight;
    wh = wh / 1.55;

    $('#edit-selected-org').attr('style', 'display:none;');

    $('#edit-ids').attr('style', 'overflow:scroll; height:auto; max-height: ' + wh + 'px; width: auto;  max-width: 50%;  overflow-x: hidden;');
    $('#edit-id-org').val('');
    $('#edit-id-org').val($('#edit-selected-org').val());



    let org = $('#edit-selected-org').val();
    $('#edit-ids').empty();

	//console.log(encodeURIComponent(url));
    $.ajax('/api/datasets/2.0/callSearchArcGIS/', {
		data: { url: url},
        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
        },
        success: function (result) {
            //console.log(result);

            result = result.layers;

            if (result.length != 0) {
				result.sort(function (a, b) {
                    var textA = a.name.toLowerCase(),
                        textB = b.name.toLowerCase()
                    if (textA < textB)
                        return -1
                    if (textA > textB)
                        return 1
                    return 0
                })

                for (let f = 0; f < result.length; f++) {
                    let url_res=result[f].id;
                    //    type_res='csv',
                    //   type_site='arcgis:'+url;
                    
                    $('#edit-ids').append('<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-ids-' + result[f].id+'|'+url+' form-item-ids-' + result[f].id+'|'+url+ '">&nbsp; <input data-drupal-selector="edit-ids-' + result[f].id+'|'+url+ '" type="checkbox" id="edit-ids-' + result[f].id+'|'+url+ '" name="ids[' + result[f].id+'|'+url+ ']" value="' + result[f].id+'|'+url+ '" class="form-checkbox"> &nbsp;<a href="'+ url+"/"+ result[f].id +'" target="_blank">' + result[f].name+ '</a>'
					/*+'|<a href="#" id="prew" class="js-open-modal" data-modal="1" onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);"><span style=" cursor: pointer; background-image: url(/sites/default/files/api/portail_d4c/img/preview.svg); display: inline-block; width: 20px; height: 20px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span></a></div>'*/);

                    if (result.length == (f + 1)) {
                        $('html,body').removeAttr("style");
                        $('input[type="submit"]').attr('disabled', false);
                    }

                }
            } 
            else {
                alert("Aucune information trouvée pour votre recherche.");
            }
        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
}

////////////////ArcGIS/////////////////////////


function createTablePrew(resUrl,type_file,type_site){

 resUrl = resUrl.replace(/\//g,'!');

//check if url contains any params
if(resUrl.includes("?")) {
      var res = resUrl.split("?");
      resUrl = res[0];
 }

$.ajax('/datasets/update/getCsvXls/' + resUrl+';'+type_file+';'+type_site , {

        type: 'POST',
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
            $('#tablePlace').contents().remove();
        },
        complete: function () {
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
           
            
        },
        success: function (result) {

            
            let delimeter=result.delimiter;
            if(delimeter =='\\t') delimeter='\t';
            
            result = result.data;
            
            
            let thead ='';
            let tbody ='';
            let count_prew;
            
            
            //console.log(result);
            
            if(result.length>=15){
                
               count_prew =15;
                
            }
            else{
               count_prew = result.length; 
            }
            
            for(let i = 0; i < count_prew; i++){
                
                if(i == 0){
                    let title = result[i].toString().split(delimeter);
                    
                    for(let j=0; j<title.length; j++){
                        
                      thead = thead+'<th>'+title[j]+'<th>'; 
                        
                    }
                    
                    thead = '<thead><tr>'+thead+'</tr></thead>';
                    
                }
                else{
                    let text = result[i].toString().split(delimeter);
                    let tbody_str='';
                    for(let j=0; j<text.length; j++){
                        
                      tbody_str = tbody_str+'<td>'+text[j]+'<td>'; 
                        
                    }
                    
                    tbody  = tbody+'<tr>'+tbody_str+'</tr>';  
                }
                   
            }
            
            tbody = '<tbody>'+tbody+'</tbody>';
            
            $('#tablePlace').append('<table data-drupal-selector="edit-table" id="edit-table" class="responsive-enabled" data-striping="1">'+thead+tbody+'</table>');

           


        },
        error: function (e) {
            console.log("ERROR: ", e);
            alert('Error');
            
        },

    });
 let overlay  = document.querySelector('.js-overlay-modal');
            let modalElem = document.querySelector('.modal[data-modal="1"]');
            modalElem.classList.add('active');
            overlay.classList.add('active');   
        
}

function openModalFilter(elem){

	var site_url = elem.data("url");
	var type_site = elem.data("type");
	var id = elem.data("id");
	var parameters = elem.attr("data-parameters");
	if(parameters != undefined /*&& parameters != ""*/){
		if(parameters != ""){
			parameters = JSON.parse(parameters);
		} else {
			parameters = {};
		}
	}

	//parameters.uuid = id;
	var nhits = elem.data("nhits");

	
	var scope = angular.element("#filterPlace .d4c-dataset-selection-list__records").scope();
	scope.externalcontext.type = type_site;
	scope.externalcontext.url = site_url;
	scope.externalcontext.datasetID = id;
	scope.externalcontext.parameters = parameters;
	scope.externalcontext.nhits = nhits;
	scope.cancel = function () {
		overlay = document.querySelector('.js-overlay-modal-filter');
		var parentModal = document.querySelector('.modal-filter');

		parentModal.classList.remove('active');
		overlay.classList.remove('active');
	};
	
	scope.selectDataset = function (dataset, parameters, nhits) {

		elem.attr("data-parameters", JSON.stringify(parameters));
		elem.data("nhits", nhits);
		var value = elem.parent().find("input").val();

       
		value = JSON.parse(value);
		value.params = parameters;
		value.url = site_url;
		elem.parent().find("input").val(JSON.stringify(value));
		scope.cancel();
		//console.log(parameters);
	};


	scope.reset();
	$("#edit-imgback").val("")
	
	let overlay  = document.querySelector('.js-overlay-modal-filter');
	let modalElem = document.querySelector('.modal[data-modal="2"]');
	modalElem.classList.add('active');
	overlay.classList.add('active');  

	//$('#filterPlace').append(template);	
        
}

;(function() {
    //angular.module('d4c.frontend', ['d4c', 'd4c-widgets']);
	//angular.module('d4c-widgets', ['d4c', 'd4c-widgets']);
	//angular.bootstrap(document.getElementById("app"), ['d4c-widgets']);
	var mod = angular.module('d4c.core.config', []);

	mod.factory("config", [function() {
		return {
			HOST: ""
		}
	}]);
        
	$("#filterPlace").css("overflow:scroll; height:35em; ");	
}());