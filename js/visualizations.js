// $ = jQuery;
$j = jQuery;

function updateVisualization(visualizationId, data) {
    $j.ajax('/d4c/api/v1/visualization/' + visualizationId, {
        type: 'PUT',
        dataType: 'json',
        contentType: "application/json; charset=utf-8",
        data: JSON.stringify(data),

        success: function(data) {
            location.reload();
        }
    });
}

function deleteVisualization(visualizationId) {
    var data = {
        'visualizationId': visualizationId
    };

    //TODO: Not working add confirm button
    // Confirm box
    // bootbox.confirm("Do you really want to delete record?", function(result) {
    //     if (result) {

    $j.ajax('/d4c/api/v1/visualization', {
        type: 'DELETE',
        dataType: 'json',
        contentType: "application/json; charset=utf-8",
        data: JSON.stringify(data),

        success: function(data) {
            location.reload();
        }
    });
    //     }
    // });
}