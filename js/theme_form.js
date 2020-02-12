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

$= jQuery

;(function() {
    //angular.module('d4c.frontend', ['d4c', 'd4c-widgets']);
	//angular.module('d4c-widgets', ['d4c', 'd4c-widgets']);
	//angular.bootstrap(document.getElementById("app"), ['d4c-widgets']);
	var mod = angular.module('d4c.core.config', []);

	mod.factory("config", [function() {
		return {
			HOST: ""
		}
	}]);
        
		
}());

//$('#edit-selected').append($('<option>', { value: 'new_theme', text : 'new theme' }));

$('#edit-valider').attr('onclick', ' validUpload(event, "img_theme");');

$('#edit-selected option[value="new_theme"]').attr("selected", "selected");
$('#pickImg').attr("style", "overflow:scroll; height:15em; overflow-x: hidden; display: none;  width: 30%;");
$("#btnImgHide").append(`<input class="button js-form-submit form-submit" value="Ouvrir les pictogrammes" type="button" onclick="togglePicker('#pickImg')">`);

getDataForUpt();

//addPicto();


function getDataForUpt(){
    $("#edit-theme").removeAttr("disabled");
    if($("#edit-selected option:selected").val()=='new_theme'){
        $("input[name='theme']").val('');
        $("#img").remove();
        
    }
   else{
       
       let url = $("#edit-selected option:selected").val();
       url=url.split('%')[1];
       
       
       
       $("#img").remove();
       
       if($("#edit-selected option:selected").text()=='Default'){
           $("#edit-theme").val($("#edit-selected option:selected").text());
           $("#edit-theme").attr("disabled", "disabled");

       }
       else{
           $("#edit-theme").val($("#edit-selected option:selected").text());
           $("#edit-theme").removeAttr("disabled");
       }
       
       
       $("#edit-theme").after('<span id="img" style=" background-image: url('+url+'); margin-top: 0px;  display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 8px; "></span>');
   }
   setTimeout(function() { 
		var scope = angular.element("#app button").scope();
		scope.validate = function () {
			if (!scope.isOldIcon) {
				if (scope.selectedPicto === '') {
					scope.currentPicto = scope.selectedPicto;
				} else {
					scope.currentPicto = 'd4c-' + scope.selectedPicto;
				}
				scope.currentPictoName = scope.selectedPictoName;
				if(typeof ngModel !== 'undefined') ngModel.$setViewValue(scope.currentPicto);
				$('#edit-imgback').val(scope.currentPicto.replace("d4c-",""));
			}
			scope.togglePicker(null);
		};
		
		//scope.selectedPicto = undefined;
		//scope.selectedPictoName = "";
		//scope.previewNoPicto();
		scope.reset();
		$("#edit-imgback").val("")
   }, 500);
    
}

function anichange(objName) {

    //objName = '#datasetLies';
    if ($(objName).css('display') == 'none') {
        $(objName).animate({
            height: 'show'
        }, 400);
    } else {
        $(objName).animate({
            height: 'hide'
        }, 200);
    }
}

function addPicto() {

    let imgs = $('#edit-imgimg').val();
    imgs = imgs.split(';');
    imgs = imgs.slice(1);

    $('#edit-imgimg').remove();


    for (let i = 0; i < imgs.length; i++) {

        let name = imgs[i].split('/');

        name = name[(name.length - 1)];
        name = name.split('.');



        $("#pickImg").append(`<span  id="img_` + i + `"  style=" cursor: pointer; background-image: url(/` + imgs[i] + `); display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;" onclick="fillImgBakc('` + name[0] + `'); anichange('#pickImg'); addSelectedImg('` + imgs[i] + `') "></span>`);

    }

}

function fillImgBakc(name) {
    $('#edit-imgback').val('');
    $('#edit-imgback').val(name);
}

function addSelectedImg(url) {

    $('#img').attr('style', '');
    $('#img_selected').remove();

    $("#btnImgHide").append('<span  id="img_selected"  style=" background-image: url(/' + url + '); display: inline-block; width: 30px; height: 30px; background-repeat: no-repeat; background-size: contain; vertical-align: middle; margin-left: 1em;"></span>');
}

function validUpload(event, name) {

    if ($("[name='files["+name+"]']").val() != '' && $("[name='files["+name+"]']").val() != null && typeof($("[name='files["+name+"]']").val())!='undefined') {
        
         alert("Veuillez attendre la fin du chargement des ressources!");
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!event.isDefaultPrevented()) {
                event.returnValue = false;
            }

 
        
    }


}