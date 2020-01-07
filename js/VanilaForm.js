$ = jQuery;

$('#edit-valider').attr('onclick', ' validUpload(event, "img_url");');

create_table();
clear();

function create_table(){

let data = jQuery.parseJSON($('#edit-json').val());
let table='';

for(let i = 0; i<data.length; i++ ){
  
  table = table+'<tr style="height: 25px;" ><td style="text-align: left; width: 105px;">&nbsp;</td><td style="width: 835px;">&nbsp;</td></tr><tr style="cursor:pointer;" onclick="return window.open(`'+data[i].url_1+'`,`_blank`); "><td style="text-align: left; width: 105px;"><a href="'+data[i].url_1+'" style="text-decoration: none; color: black;" target="_blank"><img alt="data4citizen_logo" data-entity-type="file"  src="'+data[i].img_url+'" width="94" height="82"></a></td><td style="width: 835px;"><h2 style="margin-top: 0px; margin-bottom: 0px;"><a href="'+data[i].url_1+'" style="text-decoration: none; color: black;" target="_blank">'+data[i].title+'</a></h2></td></tr>';    
    
}
 
    
   $('#content_place').append('<table style="border: 0px solid transparent; width: 100%;" cellspacing="0" cellpadding="0" border="0"><tbody>'+table+'</tbody></table>');  
    
}

function fillData(data) {
clear();
    
    if ($('#edit-datas').val() == 'new') {
        
    clear();


    } else {

        for (let i = 0; i < data.length; i++) {

            if (data[i].name == $('#edit-datas').val()) {

                $('#edit-title input').val(data[i].title);
                $('#edit-url-1').val(data[i].url_1);
                $('#edit-url-2').val(data[i].url_2);
                $('#img_form').append('<span id="img" style=" background-image: url('+data[i].img_url+'); margin-top: 0px;  display: inline-block; width: 10em; height: 10em; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 8px; "></span>');
                break;
            }


        }

    }

}

function clear(){
    
    $('#edit-title input').val('');
    $('#edit-url-1').val('');
    $('#edit-url-2').val('');
    $('#img_form').empty();
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