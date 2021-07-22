$ = jQuery;

$(document).ready(function () {
	$("#repositorybtn").attr("type","button");

});

$('#repositoryTab').before(`<p><h3>Référentiel Vanilla</h3></p>`);
$('#repositoryTab').attr('style', ' background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em ');
$('#datasetTab').attr('style', 'height: 400px; overflow:auto; ');


function loadRepository(e) {
	var login = document.getElementById('txtlogin').value;
	var pass = document.getElementById('txtpass').value;
	var group = document.getElementById('txtgroup').value;
	var repo = document.getElementById('txtrepo').value;
	$.getJSON('https://mla-vanilla.data4citizen.com/VanillaRuntime/externalRepositoryServlet?login=' + login + '&pass=' + pass + '&group=' + group + '&repository=' + repo, function(data) {
    	var element = document.getElementById('repositoryDiv');
		  var tree = '<ul id="myUL">';
		  $.each( data, function( key, val ) {
			tree = tree + createElementHtml(val);
		  });

		tree = tree + '</ul>';
		element.innerHTML = tree;
		
		var toggler = document.getElementsByClassName("caret");
		var i;
		
		var ul = $('#myUL');
		var lis = ul.find('li');
		lis.each(function( index ) {
		    $( this ).on("click", function(event) {
				event.stopPropagation();
				var ul2 = $('#myUL');
				var lis2 = ul2.find('li');
				lis2.each(function( index ) {
					$( this ).attr("class",'notselected');
				});
				$( this ).attr("class",'selectedItem');
				document.getElementById('txtitemid').value = event.target.id;
				//$('txtitemid').val(event.target.id);
		    });
		});

		for (i = 0; i < toggler.length; i++) {
		  toggler[i].addEventListener("click", function() {
			this.parentElement.querySelector(".nested").classList.toggle("active");
			this.classList.toggle("caret-down");
		  });
		}
	}); 

	
	e.stopPropagation();
	e.preventDefault();
	
}

function createElementHtml(object) {
	var html = '<li class="notselected"><span class="caret" id="dir:' + object.id + '">' + object.name + '</span><ul class="nested">';
	$.each( object.childs, function( key, val ) {
		if(val.itemName != undefined) {
			html = html + '<li class="notselected" id="item:' + val.id + '">' + val.itemName + '</li>';
		}
		else html = html + createElementHtml(val);
	});
	
	html = html + '</ul></li>';
	return html;
}

function changeSelection(length) {
	var dimensions = '';
	var measures = '';
	for(let i =1; i<length; i++) {
		var colname = $('#colname' + i).val();
		var type = $('#coltype' + i + ' option:selected').text();
		var parentc = $('#colparent' + i + ' option:selected').text();
		if(type == 'Mesure') {
			measures += colname + ';';
		}
		if(type == 'Dimension') {
			if(parentc == 'Aucun') {
				dimensions += colname + ';';
			}
		}
	}
	for(let i =1; i<length; i++) {
		var colname = $('#colname' + i).val();
		var type = $('#coltype' + i + ' option:selected').text();
		var parentc = $('#colparent' + i + ' option:selected').text();
		if(type == 'Dimension') {
			if(parentc != 'Aucun') {
				dimensions = dimensions.replace(parentc, parentc + ',' + colname);
			}
		}
	}
	
	document.getElementById('dimensions').value = dimensions;
	document.getElementById('measures').value = measures;
}

function fillTable(data) {
	$('#edit-table tbody').remove();
	var fieldOptions = '<option>Aucun</option>';
	for(let i =1; i<data.length; i++) {
		fieldOptions += '<option>' + data[i].label + '</option>';
	}
	var typeOptions = '<option>Aucun</option><option>Dimension</option><option>Mesure</option>';
	
	var tableHtml = '';
	
	for(let i =1; i<data.length; i++) {
		tableHtml += '<tr>';
		var label = '<td><input id="colname' + i + '" type="text" value="'+data[i].label+'" size="15" maxlength="128" class="form-text"></td>';
		var type = '<td><select id="coltype' + i + '" onchange="changeSelection(' + data.length + ');">' + typeOptions + '</select></td>';
		var parentCol = '<td><select id="colparent' + i + '" onchange="changeSelection(' + data.length + ');">' + fieldOptions + '</select></td>';
		tableHtml += label + type + parentCol;
		tableHtml += '</tr>';
	}
	
	 $('#edit-table').append('<tbody>' + tableHtml + '</tbody>');
}

function loadFields(urlCkan) {
  let datasetId = $("#edit-selected-dataset").val();
  if (datasetId != "" && datasetId != "----") {
    $.ajax(fetchPrefix() + "/d4c/api/datasets/1.0/DATASETID/DATASETID=" + datasetId,
      {
        type: "POST",
        dataType: "json",
        cache: true,
        success: function (result) {
          let data = extractFields(result);
          fillTable(data);
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
