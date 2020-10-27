$ = jQuery;
var variableglobale = [];
var variableglobalekey = [];
var nhitsvalue = [];
var datasetvalues=[];
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

hide_param();
var validation_resurce = [];

/////////////modal/////////////
$('#edit-selected-org option[value=""]').attr('selected', 'selected');

$('#edit-table thead tr').remove();
$('#edit-table thead').append('<th data-type="string">nom</th><th data-type="string">Organisation hgh</th><th data-type="string">origine</th><th data-type="string">site</th><th>La date de dernière réplication</th><th>La date prévue de la prochaine réplication</th><th>État</th><th>Fréquence de moissonnage</th><th>Détails</th> <th>Supprimer</th>');

$('#edit-table').before('</br><div><input class="form-search" type="text"  id="search" placeholder="Recherche"></div></br>');

$('#edit-chercher').after('&nbsp;<span id="chercher_b" onClick="controlSiteSearch();" style="cursor:pointer; border-style:solid!important; border-radius:10px; border:1px; border-color:#a6a6a6; background-color:#f0f0eb; ">&nbsp; Recherche &nbsp;</span>');

$('#edit-search').attr('onclick', 'checkDatasetResources(event);');
clear();




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
////////////////ArcGIS/////////////////////////


function createTablePrew(resUrl,type_file,type_site){
// console.log(resUrl);
 resUrl = resUrl.replace(/\//g,'!');

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


function clearDatasetNull(datasets) {
    console.log(" clear ");
    for(let i= 0 ; i<datasets.length; i++){
                        // if dataset is undefined, remove it from array
                        if(datasets[i] == undefined) {
                              datasets.splice(i, 1);
                          
                        }

                        if(datasets[i] && datasets[i].title_data==null){
                            delete datasets[i];
                        }
                    }
    return datasets;
}
function fillTable(data) {
    if(datasetvalues && datasetvalues.length > 0) {
        data = datasetvalues;
    }
    var dataString = JSON.stringify(data);
    datasetvalues = data;
    clear();
    if ($('#edit-selected-org').val() == '') {}
    else {

        for (let j = 0; j < data.length; j++) {

            if ($('#edit-selected-org').val() == data[j].id_org) {
                // let keydataset = data2[data[j].id_org];
                
               /*console.log(data[j]);*/
                let datasets = data[j].datasets;

                datasets = clearDatasetNull(datasets);

                Object.values(datasets);
                datasets = datasets.sort(function (a, b) {
                    var nameA = a.title_data.toLowerCase(),
                        nameB = b.title_data.toLowerCase()
                    if (nameA < nameB) 
                        return -1
                    if (nameA > nameB)
                        return 1
                    return 0 
                });
                
                if(datasets!=null || datasets!='') {
                    for (let i = 0; i < datasets.length; i++) {

                        var datasetvalueparams = null;
                        if(datasets[i] && datasets[i].parameters && JSON.stringify(datasets[i].parameters) != undefined){
                            datasetvalueparams = encodeURIComponent(JSON.stringify(datasets[i].parameters));
                        }
   
                        let name_id = '<td><a target="_blank"  href ="/visualisation/table/?id=' + datasets[i].id_data + '">' + datasets[i].title_data + '</a><div style="display:none" class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-' + i + '-name-2 form-item-table-' + i + '-name-2 form-no-label"><input data-drupal-selector="edit-table-' + i + '-name-2" type="text" id="edit-table-0-name-2" name="table[' + i + '][name][2]" value="' + datasets[i].id_data + '" size="60" maxlength="128" class="form-text"></div></td>';
                        let org = '<td>' + data[j].name_org + '</td>';
                        let orgine = '<td>Moissonnage</td>';
                        /*let site = '<td>' + datasets[i].site + '</td>';*/
                        // let site = '<td><a target="_blank"  href ="' + keydataset[datasets[i].id_data] + '">'  + keydataset[datasets[i].id_data] + '</a></td>';
                        let site = '<td><a target="_blank"  href ="' + datasets[i].siteUrl + '">'  + datasets[i].siteUrl + '</a></td>';

                        if (datasets[i].site == 'joinDataset') {
                            orgine = '<td>Jointure</td>';
                            site = '<td></td>';
                        }

                        var options = {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: 'numeric',
                            second: 'numeric'
                        };

                        let dateLastUpdate = new Date(Date.parse(datasets[i].last_update));
                        dateLastUpdate = dateLastUpdate.toLocaleDateString("fr-FR", options);
                        let dateLastUp = '<td>' + dateLastUpdate + '</td>';
                        
                        let dateNextUpdate = new Date(Date.parse(datasets[i].next_update));
                        dateNextUpdate = dateNextUpdate.toLocaleDateString("fr-FR", options);
                        let dateNextUp = '<td>' + dateNextUpdate + '</td>';

                        let t=[];
                        if (datasets[i].periodic_update == null || datasets[i].periodic_update == '' 
                                || datasets[i].periodic_update == 0 || datasets[i].periodic_update == '0' 
                                || typeof(datasets[i].periodic_update) == 'number'){
                            t[0]='';
                            t[1]='';
                            t[2]='A';
                        }
                        else {
                            t = datasets[i].periodic_update.split(';');

                            if (t[1] == '') {
                                t[1] = 1;
                            }
                        }

                        let status = '<td><div class="js-form-item form-item js-form-type-select form-type-select js-form-item-table-' + i + '-status form-item-table-' + i + '-status form-no-label"><select data-drupal-selector="edit-table-' + i + '-status" id="edit-table-' + i + '-status" name="table[' + i + '][status]" class="form-select"><option value="A">Actif</option><option value="P">Passif</option></select></div></td>';

                        let period = '<td><div class="js-form-item form-item js-form-type-select form-type-select js-form-item-table-' + i + '-period-1 form-item-table-' + i + '-period-1 form-no-label"><select data-drupal-selector="edit-table-' + i + '-period-1" id="edit-table-' + i + '-period-1" name="table[' + i + '][period][1]" class="form-select"><option value="Mi">Minute</option><option value="H">Heure</option><option value="D">Jour</option><option value="W">Semaine</option><option value="M">Mois</option><option value="Y">Année</option></select></div><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-table-' + i + '-period-2 form-item-table-' + i + '-period-2 form-no-label"><input data-drupal-selector="edit-table-' + i + '-period-2" type="number" id="edit-table-' + i + '-period-2" name="table[' + i + '][period][2]" value="" step="1" class="form-number"></div></td>';

                        let details = "";
                        let del_moissonage ="";
                        let url_res="";
                        if(datasets[i].site == "Data_Gouv_fr") {
                            $.getJSON('https://www.data.gouv.fr/api/1/datasets/?q='+ datasets[i].title_data, function (result) {

                                if (result) {
                                    result.data.sort(function (a, b) {
                                        var textA = a.slug.toLowerCase(),
                                        textB = b.slug.toLowerCase()
                                        if (textA < textB)
                                            return -1
                                        if (textA > textB)
                                            return 1
                                        return 0
                                    })
    
                                    let firstResource = getFirstRessource(getdatasetbyTitle(result,datasets[i].title_data));
                                    url_res = firstResource ? firstResource.url : '', type_res='csv', type_site='DataGouvfr';
    
                                    details ='<td><input type="hidden" id="valuedetails_span_'+datasets[i].id_data+'" name="table[' + i + '][valuedetails_span]" value="" data-nhits="" /><span id="details-moisonnage-span_'+datasets[i].id_data_site+'" class=" span_'+i+' hello details-moisonnage-span_'+datasets[i].id_data_site+' btn btn-info js-open-modal" role="button" data-modal="1" data-url="https://public.opendatasoft.com/" data-id="'+datasets[i].id_data_site+'" data-id-dataset="'+datasets[i].id_data+'" data-param-values-set="'+datasetvalueparams+'" data-type="ods" data-parameters="{}"'+
                                        ' onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);" style="cursor:pointer; border-style:solid!important; border-radius:10px; border:1px; border-color:#a6a6a6; background-color:#f0f0eb; padding: 9px;"> Détails</span></td>';

                                    del_moissonage =`<td> <a href="#"    role="button" data-id="`+url_res+`" data-type="ods" onclick="deleteMoissonnage($(this));" data-id-moisonnage =`+datasets[i].id_data+` data-datset =`+dataString+`>
                        <span  title="Supprimer" class="fa fa-trash-o " style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>

                        </a></td>`;
                                   
                                    $('#edit-table > tbody:last-child').append('<tr data-drupal-selector="edit-table-' + i + '" class="odd">' + name_id + '' + org + '' + orgine + '' + site + '' + dateLastUp + '' + dateNextUp + '' + status + '' + period + '' + details + '' + del_moissonage+ '</tr>');
                                
                                    $('#edit-table-' + i + '-status option[value="' + t[2] + '"]').attr('selected', 'selected');
                                    $('#edit-table-' + i + '-period-1 option[value="' + t[0] + '"]').attr('selected', 'selected');
            
                                    $('#edit-table-' + i + '-period-2').val(t[1]);
            
            
            
                                    if(JSON.stringify(datasets[i].parameters)  && JSON.stringify(datasets[i].parameters) != null && JSON.stringify(datasets[i].parameters) != "null" && JSON.stringify(datasets[i].parameters) != undefined) {
                                        $(".span_"+i).addClass("buttonDetailsActif");
                                    }
                                }
                            });
                        }
                        else if(datasets[i].site == "locale") {

                            $.getJSON('/datasets/update/callInfocom94/'+ datasets[i].title_data, function (result) {

                                if (result) {
                                    result.data.sort(function (a, b) {
                                        var textA = a.slug.toLowerCase(),
                                            textB = b.slug.toLowerCase()
                                        if (textA < textB)
                                            return -1
                                        if (textA > textB)
                                            return 1
                                        return 0
                                    })

                                    let firstResource = getFirstRessource(getdatasetbyTitle(result,datasets[i].title_data));
                                    url_res = firstResource ? firstResource.url : '', type_res='csv', type_site='InfoCom94';

                                    details ='<td><input type="hidden" id="valuedetails_span_'+datasets[i].id_data+'" name="table[' + i + '][valuedetails_span]"  value="" data-nhits="" /><span id="details-moisonnage-span_'+datasets[i].id_data_site+'" class=" span_'+i+' hello details-moisonnage-span_'+datasets[i].id_data_site+' btn btn-info js-open-modal" role="button" data-modal="1" data-url="https://public.opendatasoft.com/" data-id="'+datasets[i].id_data_site+'" data-id-dataset="'+datasets[i].id_data+'" data-param-values-set="'+datasetvalueparams+'" data-type="ods" data-parameters="{}"'+
                                        ' onclick="createTablePrew(`'+url_res+'`,`'+type_res+'`,`'+type_site+'`);" style="cursor:pointer; border-style:solid!important; border-radius:10px; border:1px; border-color:#a6a6a6; background-color:#f0f0eb; padding: 9px;"> Détails</span></td>'
                                    del_moissonage =`<td> <a href="#"    role="button" data-id="`+url_res+`" data-type="ods" onclick="deleteMoissonnage($(this));" data-id-moisonnage =`+datasets[i].id_data+` data-datset =`+dataString+`>
                        <span  title="Supprimer" class="fa fa-trash-o " style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>

                        </a></td>`;
                                    $('#edit-table > tbody:last-child').append('<tr data-drupal-selector="edit-table-' + i + '" class="odd">' + name_id + '' + org + '' + orgine + '' + site + '' + dateLastUp + '' + dateNextUp + '' + status + '' + period + '' + details + '' +del_moissonage+ '</tr>');
                                
                                    $('#edit-table-' + i + '-status option[value="' + t[2] + '"]').attr('selected', 'selected');
                                    $('#edit-table-' + i + '-period-1 option[value="' + t[0] + '"]').attr('selected', 'selected');
            
                                    $('#edit-table-' + i + '-period-2').val(t[1]);
            
            
            
                                    if(JSON.stringify(datasets[i].parameters)  && JSON.stringify(datasets[i].parameters) != null && JSON.stringify(datasets[i].parameters) != "null" && JSON.stringify(datasets[i].parameters) != undefined) {
                                        $(".span_"+i).addClass("buttonDetailsActif");
                                    }
                                }
                            });
                        }
                        else {
                            details ='<td><input type="hidden" id="valuedetails_span_'+datasets[i].id_data+'" name="table[' + i + '][valuedetails_span]"  value="" data-nhits="" /><span id="details-moisonnage-span_'+datasets[i].id_data_site+'" class=" span_'+i+' hello details-moisonnage-span_'+datasets[i].id_data_site+' btn btn-info js-open-modal" role="button" data-modal="1" data-url="https://public.opendatasoft.com/" data-id="'+datasets[i].id_data_site+'" data-id-dataset="'+datasets[i].id_data+'" data-param-values-set="'+datasetvalueparams+'" data-type="ods" data-parameters="{}" onclick="openModalFilter($(this) );" style="cursor:pointer; border-style:solid!important; border-radius:10px; border:1px; border-color:#a6a6a6; background-color:#f0f0eb; padding: 9px;"> Détails</span> <input type ="hidden" name ="savedata-moio" id = "savedata-moi-"'+datasets[i].id_data_site+' value ="" /></td>'
                            del_moissonage =`<td> <a href="#"    role="button" data-id="`+url_res+`" data-type="ods" onclick="deleteMoissonnage($(this));" data-id-moisonnage =`+datasets[i].id_data+` data-datset =`+dataString+`>
                        <span  title="Supprimer" class="fa fa-trash-o " style="cursor:pointer;vertical-align:middle;margin-left:1em;color:black;font-size:20px;"></span>

                        </a></td>`;

                            $('#edit-table > tbody:last-child').append('<tr data-drupal-selector="edit-table-' + i + '" class="odd">' + name_id + '' + org + '' + orgine + '' + site + '' + dateLastUp + '' + dateNextUp + '' + status + '' + period + '' + details + '' +del_moissonage+ '</tr>');
                        
                            $('#edit-table-' + i + '-status option[value="' + t[2] + '"]').attr('selected', 'selected');
                            $('#edit-table-' + i + '-period-1 option[value="' + t[0] + '"]').attr('selected', 'selected');
    
                            $('#edit-table-' + i + '-period-2').val(t[1]);
    
    
    
                            if(JSON.stringify(datasets[i].parameters)  && JSON.stringify(datasets[i].parameters) != null && JSON.stringify(datasets[i].parameters) != "null" && JSON.stringify(datasets[i].parameters) != undefined) {
                                $(".span_"+i).addClass("buttonDetailsActif");
                            }
                        }
                    }  
                }
            }
        }
    }
}


function removeDatasetFromarray(data, id) {
    for (let j = 0; j < data.length; j++) {
                if ($('#edit-selected-org').val() == data[j].id_org) {
                    let datasets = data[j].datasets;
                    var ind = datasets.findIndex(x => x.id_data === id);
                     if (ind > -1) {
                      datasets.splice(ind, 1);
                    }
                }
                
    }
    return data;
}

function deleteMoissonnage(event) {
    var conf = confirm("Etes-vous sûr de vouloir supprimer ce moissonnage?");
    if (conf) {
        var datasetId=event.attr("data-id-moisonnage");
        $.ajax('/api/dataset/remove/'+datasetId, {
        type: 'POST',
        dataType: 'json',
        cache: true,
        success: function (data) {
            clear();
            var dataresult = removeDatasetFromarray(datasetvalues,datasetId);
            datasetvalues = dataresult;
            fillTable(dataresult);
        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
      

    } else {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!event.isDefaultPrevented()) {
            event.returnValue = false;
        }

    }
}

 function getdatasetbyTitle(result,title) {


    for (let f = 0; f < result.data.length; f++) {
                            let res_valid = false;
                            if(result.data[f].title == title){
                                

                            return result.data[f];
                            }
                            
                        }

}


function getFirstRessource(result) {
    if (result) {
        for (let g = 0; g < result.resources.length; g++) {
            if(result.resources[g].format == 'CSV' || result.resources[g].format == 'csv'){

                url_res=result.resources[g].url,
                type_res='csv',
                type_site='DataGouvfr'; 

                return result.resources[g];
            }
        }
    }
    return null;
}

////////////////ArcGIS/////////////////////////


function createTablePrew(resUrl,type_file,type_site){

 resUrl = resUrl.replace(/\//g,'!');

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


    var data_str = elem.attr("data-param-values-set");
    var my_object = JSON.parse(decodeURIComponent(data_str));
    /*data-id-dataset="'+datasets[i].id_data+'"*/
    var site_url = elem.data("url");
    var type_site = elem.data("type");
    var id = elem.data("id");
    var idDataset = elem.data("id-dataset");
    var parameters = elem.attr("data-parameters");

    if(parameters != undefined /*&& parameters != ""*/){
        if(parameters != "" && parameters != "{}"){
            
            parameters = JSON.parse(parameters);
        } else if(my_object != null) {
            parameters = JSON.parse(decodeURIComponent(data_str));
        }
        else {
           
            parameters = {};
        }
    }


/*    if(my_object != null ) {
        parameters = JSON.parse(decodeURIComponent(data_str));
    }   */


    var elem2 = elem;
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
       
        elem.parent().find("span").addClass("buttonDetailsActif");

        var value = document.getElementById("valuedetails_span_"+idDataset).value;

        value.params = parameters;
        value.url = site_url;
      
        document.getElementById("valuedetails_span_"+idDataset).value=JSON.stringify(parameters);

        scope.cancel();

    };
    
    
    scope.reset();

 
    $("#edit-imgback").val("");
    
    let overlay  = document.querySelector('.js-overlay-modal-filter');
    let modalElem = document.querySelector('.modal[data-modal="2"]');
    modalElem.classList.add('active');
    overlay.classList.add('active');  
  


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




    //$.getJSON('https://www.data.gouv.fr/api/1/organizations/?page_size=10000&q='+$('#edit-chercher').val(), function(result){
    $.getJSON('https://www.data.gouv.fr/api/1/datasets/?q=' + $('#edit-chercher').val(), function (result) {
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
                //console.log(result.data[f]);
                let res_valid = false;

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


function clear() {
    $('#edit-table tbody tr').remove();
    $('#search').val('');
    
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

$(document).ready(function(){

            $("#search").keyup(function(){
                _this = this;
                $.each($("#edit-table tbody tr"), function() {
                    if($(this).text().toLowerCase().indexOf($(_this).val().toLowerCase()) === -1)
                       $(this).hide();
                    else
                       $(this).show();                
                });
            });
        });
