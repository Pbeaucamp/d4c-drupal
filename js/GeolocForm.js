$ = jQuery;

document.addEventListener('DOMContentLoaded', function () {
	updateUI();
});

function getDatasets(urlCkan, iduser) {
	updateUI();
	
	// $("textfield[name='title']").val() == 'Test';
	let datasetId = $("#edit-selected-org").val();
	if (datasetId != "" && datasetId != "----") {
		/*$.ajax("/api/orga/2.0/show/include_datasets=true&id=" + datasetId,
		  {
			type: "POST",
			dataType: "json",
			cache: true,
			success: function (result) {
			  let data = extractPackages(result);
			  fillDataset(data);
			},
			error: function (e) {
			  console.log("ERROR: ", e);
			}
		  }
		);*/
		$.ajax(fetchPrefix() + '/d4c/api/datasets/2.0/searchPublicPrivate/include_private=true&rows=1000&q=organization:"' + datasetId + '"&fq=-(-edition_security:**'+iduser+'** OR edition_security:*)',
		{
			type: "POST",
			dataType: "json",
			cache: true,
			success: function (result) {
				let data = extractPackages(result);
				fillDataset(data);
			},
			error: function (e) {
				console.log("ERROR: ", e);
			}
		});
	}
}

function extractPackages(data) {
  data = data.result.results;
  return data;
}

function fillDataset(data) {
  // Datasets
  $('#edit-selected-dataset').empty();
  $('#edit-selected-dataset').append($('<option>').text("----"))
  for (let i = 0; i < data.length; i++) {
    $('#edit-selected-dataset').append($('<option>').text(data[i].title).attr('value', data[i].name));
  }
}

function getResources(urlCkan) {
  updateUI();

  // $("textfield[name='title']").val() == 'Test';
  let datasetId = $("#edit-selected-dataset").val();
  if (datasetId != "" && datasetId != "----") {
    $.ajax("/api/datasets/1.0/" + datasetId,
      {
        type: "POST",
        dataType: "json",
        cache: true,
        success: function (result) {
          let data = extractResources(result);
          fillResources(data);
        },
        error: function (e) {
          console.log("ERROR: ", e);
        }
      }
    );
  }
}

function extractResources(data) {
  data = data.metas.resources;
  return data;
}

function fillResources(data) {
  updateUI();

  // Resources
  $('#edit-selected-resource').empty();
  $('#edit-selected-resource').append($('<option>').text("----"))
  for (let i = 0; i < data.length; i++) {
    $('#edit-selected-resource').append($('<option>').text(data[i].name + " (" + data[i].format + ")").attr('value', data[i].id));
  }
}

function getFields(urlCkan) {
  updateUI();

  // $("textfield[name='title']").val() == 'Test';
  let datasetId = $("#edit-selected-dataset").val();
  if (datasetId != "" && datasetId != "----") {
    $.ajax("/api/datasets/1.0/DATASETID/DATASETID=" + datasetId,
      {
        type: "POST",
        dataType: "json",
        cache: true,
        success: function (result) {
          let data = extractFields(result);
          fillFields(data);
        },
        error: function (e) {
          console.log("ERROR: ", e);
        }
      }
    );
  }
}

function extractFields(data) {
  data = data.fields;
  return data;
}

function fillFields(data) {
  // Resources
  $('#edit-selected-address').empty();
  $('#edit-selected-address').append($('<option>').text("----"));
  
  $('#edit-selected-postalcode').empty();
  $('#edit-selected-postalcode').append($('<option>').text("----"));
  
  $('#edit-selected-numero').empty();
  $('#edit-selected-numero').append($('<option>').text("----"));
  
  $('#edit-selected-rue').empty();
  $('#edit-selected-rue').append($('<option>').text("----"));
  
  $('#edit-selected-ville').empty();
  $('#edit-selected-ville').append($('<option>').text("----"));
  
  $('#edit-selected-lat').empty();
  $('#edit-selected-lat').append($('<option>').text("----"));
  
  $('#edit-selected-long').empty();
  $('#edit-selected-long').append($('<option>').text("----"));
  
  $('#edit-selected-geoloc').empty();
  $('#edit-selected-geoloc').append($('<option>').text("----"));
  for (let i = 0; i < data.length; i++) {
    $('#edit-selected-address').append($('<option>').text(data[i].label).attr('value', data[i].name));
    $('#edit-selected-postalcode').append($('<option>').text(data[i].label).attr('value', data[i].name));
	  $('#edit-selected-numero').append($('<option>').text(data[i].label).attr('value', data[i].name));
	  $('#edit-selected-rue').append($('<option>').text(data[i].label).attr('value', data[i].name));
	  $('#edit-selected-ville').append($('<option>').text(data[i].label).attr('value', data[i].name));
  	$('#edit-selected-lat').append($('<option>').text(data[i].label).attr('value', data[i].name));
	  $('#edit-selected-long').append($('<option>').text(data[i].label).attr('value', data[i].name));
	  $('#edit-selected-geoloc').append($('<option>').text(data[i].label).attr('value', data[i].name));
  }
}

function updateUI() {
  let resourceId = $("#edit-selected-resource").val();
  let valueGeoloc = $('input[name=type_geoloc]:checked', '#edit-type-geoloc').val();
  if(valueGeoloc == 'address') {
	  $('#div_adress').css('display', 'block');
	  $('#div_latlong').css('display', 'none');
	  $('#div_one_geoloc_column').css('display', 'none');
  }
  else if(valueGeoloc == 'latlong') {
	  $('#div_adress').css('display', 'none');
	  $('#div_latlong').css('display', 'block');
	  $('#div_one_geoloc_column').css('display', 'none');
  }
  else {
	  $('#div_adress').css('display', 'none');
	  $('#div_latlong').css('display', 'none');
	  $('#div_one_geoloc_column').css('display', 'block');
  }

  // let showFields = resourceId != "" && value == 'address';
  // $("#edit-selected-address").prop("disabled", !showFields);
  // $("#edit-selected-postalcode").prop("disabled", !showFields);
}
