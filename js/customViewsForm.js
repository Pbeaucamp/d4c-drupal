$ = jQuery;



$(document).ready(function () {
    hide_temp();
    $("#edit-selected-data").append("<option value='' selected>---</option>");
    $('#edit-selected-templ').val('1');
    getTemplate(); 
    
//    $("#edit-selected-templ option[value='1']").attr("data-imagesrc","http://i.imgur.com/XkuTj3B.png");
//    $("#edit-selected-templ option[value='1']").attr("data-description","Description with Facebook");

});

function hide_temp() {
    $("textarea[name='template_1']").val("");
    $("textarea[name='template_2']").val("");
    $("textarea[name='template_3']").val("");
    $("textarea[name='template_4']").val("");
    
    $("#edit-template-1").attr('style', 'display: none;');
    $("#edit-template-2").attr('style', 'display: none;');
    $("#edit-template-3").attr('style', 'display: none;');
    $("#edit-template-4").attr('style', 'display: none;'); 
}


function hide_temp_2() {
  
    $("#edit-template-1").attr('style', 'display: none;');
    $("#edit-template-2").attr('style', 'display: none;');
    $("#edit-template-3").attr('style', 'display: none;');
    $("#edit-template-4").attr('style', 'display: none;');

   
}

function getData() {
    
    

    hide_temp();

    if ($("select[name='selected_Data']").val() == '') {
       
        
        $("input[name='name']").val("");
        $("input[name='title']").val("");
        $("#edit-selected-templ").val("1");
        $("#edit-template-1").attr('style', '');
        
        getTemplate();

    } 
    else {
       
 
//        $.post("/admin/config/data4citizen/custom_views/", {
//            getD: $("select[name='selected_Data']").val()
//        }, function (data) {
//
//            data = jQuery.parseJSON(data.split("<!DOCTYPE html>")[0]);
//           
//
//            if (data == null) {
//                
//        $("input[name='name']").val("");
//        $("input[name='title']").val("");
//        $("#edit-selected-templ").val("1");
//        $("#edit-template-1").attr('style', '');
//        
//        getTemplate();
//                
//
//            } 
//            else {
//                
//               
//
//                $("#edit-name").val(data.cv_name);
//                $("#edit-title").val(data.cv_title);
//               // $("#edit-icon").append($("<option value='" + data.cv_id + "'><i class='icon-" + data.cv_icon + "'></i></option>"));
//                $('#edit-selected-templ').val(data.cv_template);
//
//                // add value for templates 
//                for (let i = 1; i <= data.html.length; i++) {
//                    $("#edit-template-" + i).attr('style', '');
//                    $("textarea[name='template_" + i + "']").empty();
//                    $("textarea[name='template_" + i + "']").val(data.html[i - 1].cvh_html);
//
//                }
//            }
//
//        });
         //console.log('/api/datasets/2.0/callCustomView/'+$("select[name='selected_Data']").val());
        
        $.ajax(fetchPrefix() + '/d4c/api/datasets/2.0/callCustomView/'+$("select[name='selected_Data']").val(), {
        type: 'POST',
      
        dataType: 'json',
        cache: true,
        beforeSend: function () {
            $('html,body').attr('style', 'cursor:wait !important;');
            $('input[type="submit"]').attr('disabled', true);
            
        $("input[name='name']").attr('style', 'display:none;');
        $("input[name='title']").attr('style', 'display:none;');
        $("#edit-selected-templ").attr('style', 'display:none;');
        $("#edit-template-1").attr('style', 'display:none;');
            
        },
        complete: function () {
            
            
        $("input[name='name']").removeAttr('style');
        $("input[name='title']").removeAttr('style');
        $("#edit-selected-templ").removeAttr('style');
        $("#edit-template-1").removeAttr('style');
            
            $('html,body').removeAttr("style");
            $('input[type="submit"]').attr('disabled', false);
            $('#org_div').attr('style', 'width: 50%;');
            
            
            
            
        },
        success: function (data) {
           
            console.log(data);
            //data = jQuery.parseJSON(data.split("<!DOCTYPE html>")[0]);
            //data = data.split("<!DOCTYPE html>")[0];
           

            if (data == null) {
                
        $("input[name='name']").val("");
        $("input[name='title']").val("");
        $("#edit-selected-templ").val("1");
        $("#edit-template-1").attr('style', '');
        
        getTemplate();
                

            } 
            else {
                
               

                $("#edit-name").val(data.cv_name);
                $("#edit-title").val(data.cv_title);
               // $("#edit-icon").append($("<option value='" + data.cv_id + "'><i class='icon-" + data.cv_icon + "'></i></option>"));
                $('#edit-selected-templ').val(data.cv_template);

                // add value for templates 
                for (let i = 1; i <= data.html.length; i++) {
                    $("#edit-template-" + i).attr('style', '');
                    $("textarea[name='template_" + i + "']").empty();
                    $("textarea[name='template_" + i + "']").val(data.html[i - 1].cvh_html);

                }
            }


        },
        error: function (e) {
            
            
        $("input[name='name']").val("");
        $("input[name='title']").val("");
        $("#edit-selected-templ").val("1");
        $("#edit-template-1").attr('style', '');
            console.log("ERROR: ", e);
        },

    });
        
        
    }

}

function getTemplate() {
    
    if($("select[name='selected_Data']").val() == ''){
       
       
    
    let countTempl = $("select[name='selected_templ']").val();

    hide_temp();



    switch (countTempl) {
        case '2':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');

            break;

        case '3':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');
            $("#edit-template-3").attr('style', '');

            break;
        case '4':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');
            $("#edit-template-3").attr('style', '');
            $("#edit-template-4").attr('style', '');
        
            break;

        default:
            $("#edit-template-1").attr('style', '');

            break;
    }
    
    }

else{
    
    
    
     let countTempl = $("select[name='selected_templ']").val();

   // hide_temp();

hide_temp_2();

    switch (countTempl) {
        case '2':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');

            break;

        case '3':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');
            $("#edit-template-3").attr('style', '');

            break;
        case '4':
            $("#edit-template-1").attr('style', '');
            $("#edit-template-2").attr('style', '');
            $("#edit-template-3").attr('style', '');
            $("#edit-template-4").attr('style', '');
        
            break;

        default:
            $("#edit-template-1").attr('style', '');

            break;
    }
    
    
    
    
    
    
    
    
}
    
    
    

}
