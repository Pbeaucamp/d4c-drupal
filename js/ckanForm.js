$ = jQuery;

$("#edit-selected-org").attr('onchange','$("#info_upd").remove();');
hideTimiPick();
controlTimeUpdate();

function select(data) {
    
     $('#edit-a-p option[value="A"]').removeAttr('selected', 'selected');
     $('#edit-a-p option[value="P"]').removeAttr('selected', 'selected');

    $('#edit-a-p option[value="0"]').attr('selected', 'selected')
    $('#edit-time-up option[value="0"]').attr('selected', 'selected')
    $('#edit-time-up-value').val("1");

    $('#edit-id-dataset-selected').val($('#selected_dataset').val());


    for (let i = 0; i < data.length; i++) {

        if ($('#edit-selected-org').val() == data[i].id_org) {

            for (let j = 0; j < data[i].datasets.length; j++) {

                if (data[i].datasets[j].id_data == $('#selected_dataset').val()) {
                   $("#info_upd").remove();
                    
                       
                    let t = data[i].datasets[j].periodic_update.split(';');
                    if(t[1]==''){
                        t[1]=1;
                    }
                    // $('#edit-a-p').val(t[0]);
                    $('#edit-a-p option[value="' + t[2] + '"]').attr('selected', 'selected');
                    $('#edit-time-up option[value="' + t[0] + '"]').attr('selected', 'selected');
                    //$('#edit-time-up').val(t[1]);
                    $('#edit-time-up-value').val(t[1]);

                    
                    let date;
                  
                    
                    switch (t[0]) {
                        case 'Mi':
                            date = t[1]* 60;
                            break;
                        case 'H':
                             date = t[1]* 3600;
                            break;
                        case 'D':
                             date = t[1]* 86400;
                            break;
                        case 'W':
                             date = t[1]* 604800;
                            break;
                        case 'M':
                             date = t[1]* 2592000;
                            break;
                        case 'Y':
                             date = t[1]* 31536000;
                            break;
                            
                        default: 
                            
                            date=0;
                            break;

                    }



                            let dateLastUp = parseInt(Date.parse(data[i].datasets[j].last_update) / 1000);
                          
                            
                    
                    if(parseInt(Date.parse(dateLastUp))/ 1000>date){
                        
                       date = (parseInt(Date.parse(dateLastUp) / 1000) + date )*1000; 
                        
                    }
                    else{
                         date = (date + dateLastUp)*1000;
                    }
                      
                    
                 
                   date =  new Date(date);
                    
                    var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute:'numeric', second:'numeric' };
                    dateLastUp =  new Date(dateLastUp*1000);
                    
                  //date = date.toString(options); 
                  date = date.toLocaleDateString("fr-FR", options);
                  dateLastUp = dateLastUp.toLocaleDateString("fr-FR", options);
                
                    if(data[i].datasets[j].periodic_update==''|| data[i].datasets[j].periodic_update==null){
                       
                    $('#edit-a-p option[value="A"]').attr('selected', 'selected');
                        
                  date = 'tous les jours à 5h';
               
                        
                       }
                    
                    $('#selected_dataset').after('<div id="info_upd"><br><div><label>La date de dernière réplication:&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:green;">' + dateLastUp + '</span></label></div> <div><label id="date_next_up">La date prévue de la prochaine réplication:&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:green;">'+date+'</span></label></div></div>');



                            break;
                    }
                }
                break;
            }
        }

hideTimiPick();

    }


function controlTimeUpdate() {
    hideTimiPick();
    $('#edit-time-up-value').removeAttr('min');
    $('#edit-time-up-value').attr('min', '1');
    
    if($('#edit-time-up').val()=='Y'){
        $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '1');
    }
    
   else if($('#edit-time-up').val()=='M'){
       $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '12');
   }
    else if($('#edit-time-up').val()=='W'){
       $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '4');
   } 
    else if($('#edit-time-up').val()=='D'){
       $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '31');
   } 
    else if($('#edit-time-up').val()=='H'){
       $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '24');
   } 
    else if($('#edit-time-up').val()=='Mi'){
       $('#edit-time-up-value').removeAttr('max');
        $('#edit-time-up-value').attr('max', '60');
   } 
   
    
}


function hideTimiPick(){
    
    if($('#edit-a-p').val()=='P' || $('#edit-a-p').val()=='0'){
        
        $('#edit-time-up').attr('style', 'display:none');
        $('#edit-time-up-value').attr('style', 'display:none');
        $('#date_next_up').attr('style', 'display:none');
    
       }

else {
     $('#edit-time-up').attr('style', 'display:block; width: 50%;');
     $('#edit-time-up-value').attr('style', 'display:block; width: 50%;');
     $('#date_next_up').attr('style', 'display:block; width: 50%;');
     
}
    
   
   
    
    
}




