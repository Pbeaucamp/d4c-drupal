$ = jQuery;

$("#edit-img-org-upload").after('<div id="org_img"></div>');
$('#edit-valider').attr('onclick', ' validUpload(event, "img_org");');

function addData(data) {

    console.log(data);
    
    data = data.result;
    let org_id = $('#edit-selected-org').val();

    if (org_id == 'new') {

        clear();

    } else {

        clear();

        for (let i = 0; i < data.length; i++) {

            if (data[i].id == org_id) {
                $('#edit-title ').val(data[i].display_name);
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
                /*if(data[i].extras[0].value == "true"){
                     $('#edit-selected-private').val('0');
                }
                else {
                      $('#edit-selected-private').val('1');
                }*/
                

                break;
            }

        }




    }

}

function clear() {
    
    $('#edit-title input').val('');
    $('#edit-description').val('');
    $('#org_img').empty();
    $('#count_package').remove();

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
