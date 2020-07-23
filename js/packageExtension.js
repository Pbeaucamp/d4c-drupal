$ = jQuery;

//open popup export dataset 
function openExportPopup(event){

	createPopupExport($("#visibilityModalExport"));

	// get id of current dataset
	let id = event.target.dataset.id;

	//get zip file from packageManger.php
	$.ajax('/package/exportdataset/'+id, {
        type: 'GET',
        dataType: "json",
        cache: true,
        success: function (data) {
        	// get url of filename 
            let fileName = data.filename;
            window.location.href = fileName; //set your file url which want to download

            updateProgressBarValue();

        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
}
