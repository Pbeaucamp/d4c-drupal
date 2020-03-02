/*
This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

$ = jQuery;
//preview();
getTableById(false);

$(".btn-preview").click(function(ev){
	preview();
});
var resId;
var datasetName;
var angularLoaded = false;



document.addEventListener('DOMContentLoaded', function () {
	baba();
}); // end ready  

function getTableById(refresh=true){
//$('#edit-table > tbody').val("");
    $('#edit-table tbody tr').remove();
    
	let param = $('#selected_data select').val();    
	$('#selected_data select').attr("data-drupal-selector", "edit-selected-data");
	$('#selected_data select').attr("id", "edit-selected-data");
	$('#selected_data select').attr("name", "selected_data");
	$('#selected_data select').attr("class", "form-select");
	
    if(param != null){
		param = param.split('%');
		datasetId = param[0];   
		resourceId = param[1];   
		resId = resourceId;
		
		if(param != 'no_data_csv'){
			$.ajax('/api/table/'+resourceId,
			{
				type: 'POST',
				dataType: 'json',
				cache : true,
				success: function (result) {
					if(result.success){
					    
						uncheckAllHeader();
						addDataInTable(result);
						//var names = result.result.fields.map(function(c){ return c.id;});
						//$("#tooltip-standard").attr("data-cols", names.join(','));
						loadTooltip(datasetId, result);
					}
					else{
						if(refresh) {
							alert("pas de csv!");
							console.log("ERROR: ");
						}
					}            
				
							
				},
				error: function (e) {
					console.log("ERROR: ", e);
				}
			}); 
		}
		else{
			if(refresh) {
				alert("pas de csv!");
			}
		}
    }
 
}

function addDataInTable(data){
	
	dataSize = data.result.fields.length;
    for(let i =1; i<data.result.fields.length; i++){

        if(!data.result.fields[i].info){
           data.result.fields[i].info={
               
           };
           }

        if(!data.result.fields[i].info.label){
           
         data.result.fields[i].info.label="";
           
           }
        if(!data.result.fields[i].info.notes){
           
         data.result.fields[i].info.notes="";
           
           }
        if(!data.result.fields[i].info.type_override){
           
         data.result.fields[i].info.type_override="";
           
           }
        
        
        
        
        if (data.result.fields[i].info.label!=''){
            var init='<input data-drupal-selector="edit-table-'+i+'-intitul" type="text" id="edit-table-'+i+'-intitul" name="table['+i+'][Intitulé]" value="'+data.result.fields[i].info.label+'" size="15" maxlength="128" class="form-text">';
        }
        else{
            var init='<input data-drupal-selector="edit-table-'+i+'-intitul" type="text" id="edit-table-'+i+'-intitul" name="table['+i+'][Intitulé]" value="" size="15" maxlength="128" class="form-text">';
        }
        

        let facet='<input data-drupal-selector="edit-table-'+i+'-facet" type="checkbox" id="edit-table-'+i+'-facet" name="table['+i+'][facet]" value="1" class="form-checkbox">';
        
        let table='<input data-drupal-selector="edit-table-'+i+'-table" type="checkbox" id="edit-table-'+i+'-table" name="table['+i+'][table]" value="1" class="form-checkbox">';
        
      //  let tooltip='<input data-drupal-selector="edit-table-'+i+'-tooltip" type="checkbox" id="edit-table-'+i+'-tooltip" name="table['+i+'][tooltip]" value="1" class="form-checkbox">';
        
        let sortable='<input data-drupal-selector="edit-table-'+i+'-sortable" type="checkbox" id="edit-table-'+i+'-sortable" name="table['+i+'][sortable]" value="1" class="form-checkbox">';
        
        let disjunctive='<input data-drupal-selector="edit-table-'+i+'-disjunctive" type="checkbox" id="edit-table-'+i+'-disjunctive" name="table['+i+'][disjunctive]" value="1" class="form-checkbox">';
        
        let date='<input data-drupal-selector="edit-table-'+i+'-date" type="checkbox" id="edit-table-'+i+'-date" name="table['+i+'][date]" value="1" class="form-checkbox">';
        
        let startdate='<input data-drupal-selector="edit-table-'+i+'-startdate" type="checkbox" id="edit-table-'+i+'-startdate" name="table['+i+'][startDate]" value="1" class="form-checkbox">';
        
        let enddate='<input data-drupal-selector="edit-table-'+i+'-enddate" type="checkbox" id="edit-table-'+i+'-enddate" name="table['+i+'][endDate]" value="1" class="form-checkbox">';
        
        let images='<input data-drupal-selector="edit-table-'+i+'-images" type="checkbox" id="edit-table-'+i+'-images" name="table['+i+'][images]" value="1" class="form-checkbox" onclick="cleare_column(`images`, '+data.result.fields.length+', '+i+');">';
        
        let wordcount='<input data-drupal-selector="edit-table-'+i+'-wordcount" type="checkbox" id="edit-table-'+i+'-wordcount" name="table['+i+'][wordCount]" value="1" class="form-checkbox">';
        let wordcountnumber='<input data-drupal-selector="edit-table-'+i+'-wordcountnumber" type="checkbox" id="edit-table-'+i+'-wordcountnumber" name="table['+i+'][wordCountNumber]" value="1" class="form-checkbox">';
       
        let dateTime='<input data-drupal-selector="edit-table-'+i+'-dateTime" type="checkbox" id="edit-table-'+i+'-dateTime" name="table['+i+'][dateTime]" value="1" class="form-checkbox">';
        //
        let title_for_timeLine='<input data-drupal-selector="edit-table-'+i+'-title_for_timeLine" type="checkbox" id="edit-table-'+i+'-title_for_timeLine" name="table['+i+'][title_for_timeLine]" value="1" class="form-checkbox" onclick="cleare_column(`title_for_timeLine`, '+data.result.fields.length+', '+i+');">';
        
        let descr_for_timeLine='<input data-drupal-selector="edit-table-'+i+'-descr_for_timeLine" type="checkbox" id="edit-table-'+i+'-descr_for_timeLine" name="table['+i+'][descr_for_timeLine]" value="1" class="form-checkbox" >';
        
         let date_timeLine='<input data-drupal-selector="edit-table-'+i+'-date_timeline" type="checkbox" id="edit-table-'+i+'-date_timeline" name="table['+i+'][date_timeline]" value="1" class="form-checkbox">';
        
        //let image_url='<input data-drupal-selector="edit-table-'+i+'-image_url" type="checkbox" id="edit-table-'+i+'-image_url" name="table['+i+'][image_url]" value="1" class="form-checkbox" onclick="cleare_column(`image_url`, '+data.result.fields.length+', '+i+');">';
        
        

        let init_div='<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-'+i+'-intitulé form-item-table-'+i+'-intitulé form-no-label">'+init+'</div>';

        let facet_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-facet form-item-table-'+i+'-facet form-no-label">'+facet+'</div>';
        
        let table_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-table form-item-table-'+i+'-table form-no-label">'+table+'</div>';
        
        //let tooltip_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-tooltip form-item-table-'+i+'-tooltip form-no-label">'+tooltip+'</div>';
        
        let sortable_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-sortable form-item-table-'+i+'-sortable form-no-label">'+sortable+'</div>';
        
        let disjunctive_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-disjunctive form-item-table-'+i+'-disjunctive form-no-label">'+disjunctive+'</div>';
        
        let date_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-date form-item-table-'+i+'-date form-no-label">'+date+'</div>';
        
        let startdate_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-startdate form-item-table-'+i+'-startdate form-no-label">'+startdate+'</div>';
        
        let enddate_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-enddate form-item-table-'+i+'-enddate form-no-label">'+enddate+'</div>';
        
        let images_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-images form-item-table-'+i+'-images form-no-label">'+images+'</div>';
        
        let wordcount_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-wordcount form-item-table-'+i+'-wordcount form-no-label">'+wordcount+'</div>';
        
        let wordcountNumber_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-wordcountnumber form-item-table-'+i+'-wordcountnumber form-no-label">'+wordcountnumber+'</div>';
        
        let dateTime_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-wordcountnumber form-item-table-'+i+'-dateTime form-no-label">'+dateTime+'</div>';
        
    
        let intit_facet='<input data-drupal-selector="edit-table-'+i+'-intitule_facette" type="text" id="edit-table-'+i+'-intitule_facette" name="table['+i+'][intitule_facette]" value="" size="15" maxlength="128" class="form-text">';
        
         let description='<input data-drupal-selector="edit-table-'+i+'-description" type="text" id="edit-table-'+i+'-description" name="table['+i+'][description]" value="" size="15" maxlength="128" class="form-text">';
        //
        let title_for_timeLine_div = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-'+i+'-title_for_timeLine form-item-table-'+i+'-title_for_timeLine form-no-label">'+title_for_timeLine+'</div>';
        
        let descr_for_timeLine_div = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-'+i+'-descr_for_timeLine form-item-table-'+i+'-descr_for_timeLine form-no-label">'+descr_for_timeLine+'</div>';
        
       // let image_url_div = '<div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-'+i+'-image_url form-item-table-'+i+'-image_url form-no-label">'+image_url+'</div>';
        
        let date_timeLine_div='<div class="js-form-item form-item js-form-type-checkbox form-type-checkbox js-form-item-table-'+i+'-date_timeline form-item-table-'+i+'-date_timeline form-no-label">'+date_timeLine+'</div>';
        
        
		$('#edit-table > thead > tr > th').first().css('position','sticky');
		$('#edit-table > thead > tr > th').first().css('position','-webkit-sticky');
		$('#edit-table > thead > tr > th').first().css('width','100px');
		$('#edit-table > thead > tr > th').first().css('min-width','100px');
		$('#edit-table > thead > tr > th').first().css('max-width','100px');
		$('#edit-table > thead > tr > th').first().css('left','0px');
		$('#edit-table > thead > tr > th').first().css('background-color','white');
        $('#edit-table > tbody:last-child').append('<tr data-drupal-selector="edit-table-'+i+'" class="odd">'+'<td style="background-color: white;position: sticky;position: -webkit-sticky;width: 100px;min-width: 100px;max-width: 100px;left: 0px;">'+data.result.fields[i].id+'</td>'+'<td>'+init_div+'</td>'+'<td>'+intit_facet+'</td>'+'<td>'+facet_div+'</td>'+'<td>'+disjunctive_div+'</td>'+'<td>'+table_div+'</td>'+/*'<td>'+tooltip_div+'</td>'+*/'<td>'+sortable_div+'</td>'+'<td>'+date_div+'</td>'+'<td>'+startdate_div+'</td>'+'<td>'+enddate_div+'</td>'+'<td>'+images_div+'</td>'+'<td>'+wordcount_div+'</td>'+'<td>'+wordcountNumber_div+'</td>'+'<td>'+dateTime_div+'</td>'+'<td>'+description+'</td>'+'<td>'+title_for_timeLine_div+'</td>'+'<td>'+descr_for_timeLine_div+'</td>'+'<td>'+date_timeLine_div+'</td>'+'</tr>');
        //'<td>'+image_url_div+'</td>'+
        
        if(data.result.fields[i].info.notes){
            
            let notes = data.result.fields[i].info.notes;
            notes=notes.replace(/\s+/g, '');
            
            notes=notes.replace(';', ',');
            notes=notes.replace('><', '>,<');
            notes=notes.split(',');
          
            for(let j = 0; j<notes.length; j++){
                
                
                if(notes[j]=='<!--facet-->'){
                   $('#edit-table-'+i+'-facet').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--table-->'){
                   
                   $('#edit-table-'+i+'-table').attr('checked', 'checked');
                   }
                
                /*if(notes[j]=='<!--tooltip-->'){
                   $('#edit-table-'+i+'-tooltip').attr('checked', 'checked');
                   }
                */
                if(notes[j]=='<!--sortable-->'){
                   $('#edit-table-'+i+'-sortable').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--disjunctive-->'){
                   $('#edit-table-'+i+'-disjunctive').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--date-->'){
                   $('#edit-table-'+i+'-date').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--startDate-->'){
                   $('#edit-table-'+i+'-startdate').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--endDate-->'){
                   $('#edit-table-'+i+'-enddate').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--images-->'){
                   $('#edit-table-'+i+'-images').attr('checked', 'checked');
                   }
                
                 if(notes[j]=='<!--wordcount-->'){
                   $('#edit-table-'+i+'-wordcount').attr('checked', 'checked');
                   }
                if(notes[j]=='<!--wordcountNumber-->'){                
                   $('#edit-table-'+i+'-wordcountnumber').attr('checked', 'checked');
                   }
                if(notes[j]=='<!--timeserie_precision-->'){                
                   $('#edit-table-'+i+'-dateTime').attr('checked', 'checked');
                   }
                
                if((notes[j].substring(0,15))=='<!--facet_name?'){
                    
                    $('#edit-table-'+i+'-intitule_facette').val(((notes[j].split('?'))[1].slice(0, -3)).replace(/_/g, ' '));
                }
                
                if((notes[j].substring(0,16))=='<!--description?'){
                    
                    $('#edit-table-'+i+'-description').val(((notes[j].split('?'))[1].slice(0, -3)).replace(/_/g, ' '));
                }
                
                if(notes[j]=='<!--title_for_timeLine-->'){                
                   $('#edit-table-'+i+'-title_for_timeLine').attr('checked', 'checked');
                   }
                
                if(notes[j]=='<!--descr_for_timeLine-->'){                
                   $('#edit-table-'+i+'-descr_for_timeLine').attr('checked', 'checked');
                   }
                if(notes[j]=='<!--date_timeLine-->'){                
                   $('#edit-table-'+i+'-date_timeline').attr('checked', 'checked');
                   }
                
//                if(notes[j]=='<!--image_url-->'){                
//                   $('#edit-table-'+i+'-image_url').attr('checked', 'checked');
//                   }
                

            }

        }
        
  
    }
}

function cleare_column(name_col, col_row, row_selected){
    for(let i = 0; i < col_row; i++){
      if(row_selected !=i){
          $('#edit-table-'+i+'-'+name_col).prop('checked',false);
      }
    }
}

function loadTooltip(idDataset, dataFields){
	$.ajax('/api/datasets/2.0/DATASETID/id='+idDataset,
	{
		type: 'POST',
		dataType: 'json',
		cache : true,
		success: function (result) {
			if(result.success){
				var dataset = result.result;
				var edit = false;
				var config; 
				datasetName = dataset.name;
				$.each(dataset.extras, function(i, e){
					if(e.key == "tooltip"){
						edit = true;
						config = JSON.parse(e.value);
						return false;
					}
				});
				
				var cols = dataFields.result.fields.map(function(c){ return c.id;});
				
				//var cols = $("#tooltip-standard").attr("data-cols").split(',');
				//cols.sort();
				$('#edit-title').html("");
				$.each(cols, function (i, item) {
					$('#edit-title').append($('<option>', { 
						value: item,
						text : item 
					}));
				});
				if(edit && config.type == "standard"){
					$("#edit-title").val(config.value.title);
					//$('#edit-title option[value="'+$config.value.title+'"]').prop('selected', true);
				}
				
				var sel = [];
				if(edit && config.type == "standard"){
					if (config.value.fields) {
						sel = config.value.fields.split(',');
					}
				}
				var l1 = "";
				var l2 = "";
				// for(var i=0; i<sel.length; i++){
				// 	l1 += '<div class="list-group-item">'+sel[i]+'</div>';
				// }
				// for(var i=0; i<cols.length; i++){
				// 	l2 += '<div class="list-group-item tinted">'+cols[i]+'</div>';
				// }

				if (sel.length == 0) {
					for(var i=0; i<cols.length; i++){
						l1 += '<div class="list-group-item">'+cols[i]+'</div>';
					}
				}
				else {
					for(var i=0; i<sel.length; i++){
						l1 += '<div class="list-group-item">'+sel[i]+'</div>';
					}
					
					for(var i=0; i<cols.length; i++){
						let found = false;

						for(var j=0; j<sel.length; j++){
							if (sel[j] == cols[i]) {
								found = true;
								break;
							}
						}

						if (!found) {
							l2 += '<div class="list-group-item tinted">'+cols[i]+'</div>';
						}
					}
				}

				$("#tooltip-standard").html(`<div style="width:60%">
						<div id="shared-lists" class="row">
							<div class="col-md-6 area-label">Champs affichés</div>
							<div class="col-md-6 area-label">Champs disponibles</div>
							<div id="list-left" class="list-group col-md-6">
								`+l1+`
							</div>
							<div id="list-right" class="list-group col-md-6">
								`+l2+`
							</div>
						</div>
					</div>`);
					
				var left = $("#list-left")[0],
					right = $("#list-right")[0];
				var list1 = new Sortable(left, {
					group: 'shared', // set both lists to same group
					animation: 150,
					getData: function (/**Event*/evt) {
						text = $("#list-left")[0].innerText;
						if(text == ""){
							return [];
						} else {
							return text.split("\n");
						}
						
					},
					onRemove: function (/**Event*/evt) {
						if(list1.options.getData().length == 0){
							$("#shared-lists").append("<div id='empty-col' class='col-md-6'>Veuillez déposer les items ici</div>");
						}
						//$("#edit-tooltip").attr("value",list1.options.getData().join(','));
						$("#edit-fields").val(list1.options.getData().join(','));
					},
					onAdd: function (/**Event*/evt) {
						if(list1.options.getData().length == 1){
							//$('#empty-col').outerHTML="";
							$('#empty-col').remove();
						}
						//$("#edit-tooltip").attr("value",list1.options.getData().join(','));
						$("#edit-fields").val(list1.options.getData().join(','));
					}
				});
				list1.options.onRemove(null);
				new Sortable(right, {
					group: 'shared',
					animation: 150,
					sort: false
				});
				
				if(edit && config.type == "html"){
					$("#edit-template").val(config.value);
				}
				
				if(edit){
					$("#edit-type").val(config.type);
					$("#edit-type").change();
				} else {
					$("#edit-type").val("standard");
					$("#edit-type").change();
					$("#edit-title").val("");
					//$("#edit-template").val("");
										$("#edit-template").val('<h2 class="d4cwidget-map-tooltip__header" ng-show="!!getTitle(record)">\n'+
			'<span ng-bind="getTitle(record)">\n'+
			'</span>\n'+
			'</h2>\n'+
			'<ul style="display: block; list-style-type: none; color: #2c3f56; padding:0; margin:0;">\n'+
			'<li  ng-repeat="field in context.dataset.extra_metas.visualization.map_tooltip_fields">\n'+
			'<strong>{{field}}</strong> : {{record.fields[field]}}</li>\n'+
			'</ul>');
				}
			}
			else{
				alert(JSON.stringify(result.error));
				console.log("ERROR: ");
			}            
		
					
		},
		error: function (e) {
			console.log("ERROR: ", e);
		}
	});
	
	
}

function preview(){
	var html = $("#edit-template").val();//.replace(/\{/gi, "[").replace(/\}/gi, "]");
	//'basemap':'`+new Date().getTime()+`'
	var inner = `<d4c-dataset-context id="dataset" context="test" test-dataset="`+datasetName+`" test-parameters="{}">
			<div class="leaflet-popup  leaflet-zoom-animated" style="opacity: 1;">
				<a class="leaflet-popup-close-button" href="#close">×</a>
				<div class="leaflet-popup-content-wrapper">
					<div class="leaflet-popup-content" style="width: 251px;">
						<d4c-map-tooltip id="tooltip" tooltip-sort="" shape="" recordid="1" context="test" map="" template='`+html+`' grid-data="null" geo-digest="" resourceid="`+resId+`"  datasetid="`+datasetName+`"></d4c-map-tooltip>
						<!--<div class="d4cwidget-map-tooltip ng-scope ng-isolate-scope" tooltip-sort="" shape="shape" recordid="recordid" context="context" map="map" template="`+html+`" grid-data="gridData" geo-digest="">   
							
						</div>-->
					</div>
				</div>
				<div class="leaflet-popup-tip-container">
					<div class="leaflet-popup-tip"></div>
				</div>
			</div>
		</d4c-dataset-context>`
	
	
	
	if(!angularLoaded){
		$("#preview").html("");
		$("#preview").html(
		`<div class="ng-scope" ng-app="d4c-widgets" id="app"> 
			`+inner+`
		</div>`);
		try{
			angular.bootstrap(document.getElementById("preview"), ['d4c-widgets']);
			angularLoaded = true;
		} catch(e){
			console.log(e);
		}
	} else {
		//$("#app").html("");
		var scope = angular.element(document.getElementById("tooltip")).scope();
		scope.$$childTail.template = html;
		scope.$$childTail.updateTemplate();
		scope.$$childTail.refresh();
		
		//$("#dataset").attr("test-dataset", datasetName);
		//$("#tooltip").attr("template", html);
		//$("#tooltip").attr("resourceid", resId);
		//$("#tooltip").attr("datasetid", datasetName);
	}
	
}

function baba(){
	//$('#selected_data select').html(""); 
	getTableById(false);
	var myVar = setInterval(function(){ 
		if($('#selected_data select option').length > 0){
			
			getTableById(false);
			clearInterval(myVar);
		}
	}, 500);
}

function uncheckAllHeader() {
	var headers = ['checkboxFacet','checkboxFacetM','checkboxTableau','checkboxTri','checkboxDatePonctuel','checkboxDateDebut','checkboxDateFin','checkboxImages','checkboxNuageDeMot','checkboxNuageDeMotNombre','checkboxDateEtHeure','checkboxLibelleFriseChrono','checkboxDescriptionFriseChrono','checkboxDateFriseChrono'];

	for (let j = 0; j < headers.length; j++) {
		document.getElementById(headers[j]).checked = false;
	}
}

function checkAll(columnName, checkbox) {
	var check = document.getElementById(checkbox).checked;

	if (!(typeof dataSize === 'undefined')) {
		for(let j = 0; j < dataSize; j++) {
			$('#edit-table-' + j + columnName).prop('checked', check);
		}
	}
}