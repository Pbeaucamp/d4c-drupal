$ = jQuery;

function select_theme(){

    let them = $('#edit-selected-data').val().split('|')[1];
    $('#selected_theme option').removeAttr("selected");
    $('#selected_theme').val(them);
    $('#selected_theme option[value="'+them +'"]').attr("selected", "selected");  
    
}