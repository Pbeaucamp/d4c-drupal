$ = jQuery;


let wh = window.innerHeight;
     wh = wh/1.8;
    
    $('#idsDiv').attr('style', 'overflow:scroll; height:auto; max-height: '+wh+'px; width: auto;  max-width: 50%;  overflow-x: hidden;');


function fillData(data) {


    if ($('#edit-selected-site').val() == 'new') {

        $('#edit-url input').val('');
        $("input:checkbox").prop('checked', false);
        
    } else {

        $('#edit-url input').val('');
        $("input:checkbox").prop('checked', true);
        
        //$("input:checkbox").attr("checked","checked");

        for (let i = 0; i < data.length; i++) {

            if ($('#edit-selected-site').val() == data[i].url) {

                $('#edit-url input').val(data[i].url);

                for (let j = 0; j < data[i].orgs.length; j++) {

                    $('#edit-ids-org-' + data[i].orgs[j]).prop('checked', false);

                }

                break;
            }

        }

    }

    console.log(data);

}
