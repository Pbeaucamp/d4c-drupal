$ = jQuery;
$('#edit-selected-org option[value=""]').attr('selected', 'selected');

$('#edit-table thead tr').remove();
$('#edit-table thead').append('<th data-type="string">nom</th><th data-type="string">Organisation</th><th data-type="string">origine</th><th data-type="string">site</th><th>La date de dernière réplication</th><th>La date prévue de la prochaine réplication</th><th>État</th><th>Fréquence de moissonnage</th>');

$('#edit-table').before('</br><div><input class="form-search" type="text"  id="search" placeholder="Recherche"></div></br>');


clear();

function fillTable(data) {





    clear();

    if ($('#edit-selected-org').val() == '') {} else {

        for (let j = 0; j < data.length; j++) {

            if ($('#edit-selected-org').val() == data[j].id_org) {
                let datasets = data[j].datasets;

                   for(let i= 0 ; i<datasets.length; i++){
                    
                    if(datasets[i].title_data==null){
                        delete datasets[i];
                       }
                    
                    
                    
                }
                
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
                
                if(datasets!=null || datasets!=''){
                   
                    
                  for (let i = 0; i < datasets.length; i++) {



                   

                   

                    let name_id = '<td><a target="_blank"  href ="/visualisation/table/?id=' + datasets[i].id_data + '">' + datasets[i].title_data + '</a><div style="display:none" class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-' + i + '-name-2 form-item-table-' + i + '-name-2 form-no-label"><input data-drupal-selector="edit-table-' + i + '-name-2" type="text" id="edit-table-0-name-2" name="table[' + i + '][name][2]" value="' + datasets[i].id_data + '" size="60" maxlength="128" class="form-text"></div></td>';

                    let org = '<td>' + data[j].name_org + '</td>';


                    let orgine = '<td>Moissonnage</td>';
                    let site = '<td>' + datasets[i].site + '</td>';

                    if (datasets[i].site == 'joinDataset') {
                        orgine = '<td>Jointure</td>';
                        site = '<td></td>';
                    }
                      let dateLastUp='';
                      let date='';
                      //console.log(typeof(datasets[i].periodic_update));
                      let t=[];
                      
                    if(datasets[i].periodic_update==null || datasets[i].periodic_update=='' || datasets[i].periodic_update== 0 || datasets[i].periodic_update=='0' || typeof(datasets[i].periodic_update)=='number'){
                        
                       t[0]='';
                       t[1]='';
                       t[2]='A';
                       
                        date = '<td>tous les jours à 5h</td>'
                       dateLastUp = parseInt(Date.parse(datasets[i].last_update) / 1000);
                         var options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric'
                    };
                    dateLastUp = new Date(dateLastUp * 1000);
                    dateLastUp = dateLastUp.toLocaleDateString("fr-FR", options);
                    dateLastUp = '<td>'+dateLastUp+'</td>';
                        
                    }
                      else{
                        
                        
                   
                     t = datasets[i].periodic_update.split(';');

                    if (t[1] == '') {
                        t[1] = 1;
                    }

                    


                    switch (t[0]) {
                        case 'Mi':
                            date = t[1] * 60;
                            break;
                        case 'H':
                            date = t[1] * 3600;
                            break;
                        case 'D':
                            date = t[1] * 86400;
                            break;
                        case 'W':
                            date = t[1] * 604800;
                            break;
                        case 'M':
                            date = t[1] * 2592000;
                            break;
                        case 'Y':
                            date = t[1] * 31536000;
                            break;

                        default:

                            date = 0;
                            break;

                    }

                    dateLastUp = parseInt(Date.parse(datasets[i].last_update) / 1000);
                    if(parseInt(Date.parse(Date()))/ 1000>date){
                        
                       date = (parseInt(Date.parse(Date()) / 1000) + date )*1000; 
                        
                    }
                    else{
                         date = (date + dateLastUp)*1000;
                    }

                    date = new Date(date);

                    var options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        second: 'numeric'
                    };
                    dateLastUp = new Date(dateLastUp * 1000);

                    //date = date.toString(options); 
                    date = date.toLocaleDateString("fr-FR", options);
                    dateLastUp = dateLastUp.toLocaleDateString("fr-FR", options);

                   
                         
                   

                    dateLastUp = '<td>' + dateLastUp + '</td>';
                    date = '<td>' + date + '</td>';
                    }

                    let status = '<td><div class="js-form-item form-item js-form-type-select form-type-select js-form-item-table-' + i + '-status form-item-table-' + i + '-status form-no-label"><select data-drupal-selector="edit-table-' + i + '-status" id="edit-table-' + i + '-status" name="table[' + i + '][status]" class="form-select"><option value="A">Actif</option><option value="P">Passif</option></select></div></td>';

                    let period = '<td><div class="js-form-item form-item js-form-type-select form-type-select js-form-item-table-' + i + '-period-1 form-item-table-' + i + '-period-1 form-no-label"><select data-drupal-selector="edit-table-' + i + '-period-1" id="edit-table-' + i + '-period-1" name="table[' + i + '][period][1]" class="form-select"><option value="Mi">Minute</option><option value="H">Heure</option><option value="D">Jour</option><option value="W">Semaine</option><option value="M">Mois</option><option value="Y">Année</option></select></div><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-table-' + i + '-period-2 form-item-table-' + i + '-period-2 form-no-label"><input data-drupal-selector="edit-table-' + i + '-period-2" type="number" id="edit-table-' + i + '-period-2" name="table[' + i + '][period][2]" value="" step="1" class="form-number"></div></td>';


                    $('#edit-table > tbody:last-child').append('<tr data-drupal-selector="edit-table-' + i + '" class="odd">' + name_id + '' + org + '' + orgine + '' + site + '' + dateLastUp + '' + date + '' + status + '' + period + '</tr>');



                    //console.log(t[0]);


                    $('#edit-table-' + i + '-status option[value="' + t[2] + '"]').attr('selected', 'selected');
                    $('#edit-table-' + i + '-period-1 option[value="' + t[0] + '"]').attr('selected', 'selected');

                    $('#edit-table-' + i + '-period-2').val(t[1]);



                    // 


                }  
                }


                

            }


        }

    }

   // goSort();

}

function clear() {
    $('#edit-table tbody tr').remove();
    $('#search').val('');
    
}




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
