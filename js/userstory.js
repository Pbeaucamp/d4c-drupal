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

/////////////modal/////////////
! function (e) {
    "function" != typeof e.matches && (e.matches = e.msMatchesSelector || e.mozMatchesSelector || e.webkitMatchesSelector || function (e) {
        for (var t = this, o = (t.document || t.ownerDocument).querySelectorAll(e), n = 0; o[n] && o[n] !== t;) ++n;
        return Boolean(o[n])
    }), "function" != typeof e.closest && (e.closest = function (e) {
        for (var t = this; t && 1 === t.nodeType;) {
            if (t.matches(e)) return t;
            t = t.parentNode
        }
        return null
    })
}(window.Element.prototype);

;(function() {
    //angular.module('d4c.frontend', ['d4c', 'd4c-widgets']);
	//angular.module('d4c-widgets', ['d4c', 'd4c-widgets']);
	//angular.bootstrap(document.getElementById("app"), ['d4c-widgets']);
	var mod = angular.module('d4c.core.config', []);
	
	mod.factory("config", [function() {
		return {
			HOST: ''
		}
	}]);
}());

document.addEventListener('DOMContentLoaded', function () {

$("#visibilityModalStory").css("display","none");

}); // end ready   
$('#formModal').after('<div style="width:62em; height:70%" class="modal" data-modal="1"><svg class="modal__cross js-modal-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="list_url_modal"><h2>Importer un rapport Vanilla</h2></div></br><div id="prew"></div></br><div><a href="#" id="prew" class="js-modal-close button js-form-submit form-submit">ok</a></div></div><div class="overlay js-overlay-modal"></div>');
/////////////modal/////////////
console.log("dgdgd");

$('#visibilityStories').before(`<p><input id="exportdataset" type="button" onclick="openModalStory()" class="button"  value="Ajouter une histoire" /></p>`);

/*$('#cartoTab').before(`<p><h3 onclick="anichange('#cartoTab')" style="cursor: pointer" >CARTOGRAPHIE</h3></p><hr size="2"/>`);
$('#resEtValidTab').before(`<p><h3 onclick="anichange('#resEtValidTab')" style="cursor: pointer" >RESSOURCES ET VALIDATION</h3></p><hr size="2"/>`);
$('#configurationTab').before(`<p><h3 onclick="anichange('#configurationTab')" style="cursor: pointer" >TABLEAU DE BORD DANS L'INFORMATION</h3></p><hr size="2"/>`);
$('#datasetLies').before(`<p><h3 onclick="anichange('#datasetLies')" style="cursor: pointer" >JEUX DE DONNEES LIES</h3></p> <hr size="2"/>`);
$('#filters').attr('style', ' background-color: #ddddda; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em ');
$('#infoTab').attr('style', ' background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em ');
$('#cartoTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#resEtValidTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#configurationTab').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px; padding:1em');
$('#datasetLies').attr('style', ' display: none; background-color: #fcfcfa; border: 1px solid #bfbfbf; border-radius: 3px;  overflow:scroll; height:20em; width: 30%; overflow-x: hidden; padding:1em');
$('#infoTab').after('<br>');
$('#cartoTab').after('<br>');
$('#resEtValidTab').after('<br>');
$('#configurationTab').after('<br>');
$('#datasetLies').after('<br>');
*/


function openModalStory() {
    $('#visibilityStories').after(`<div style="width: 70em; padding: 72px; box-shadow: 5px 10px 8px 10px #888888;" class="modal" data-modal="3">
         
              <div class="row">
                    <a href="#" id="cancel2" class="js-modal-close-export button" style="float: right;margin-top: -71px;
            margin-right: -67px; padding: 5px;border: none;
            background: transparent;">X</a>
              </div>
              <div class="modal-body" id="modal-body">
             
                <h2>Ajouter une nouvelle storie </h2>
               
                
          </div>
          <div class="overlay js-overlay-modal-export"></div>`);

         
          $("#myModal").css("display","block");
          $('#visibilityModalStory').appendTo('#modal-body');
          $("#visibilityModalStory").css("display","block");
      let overlay = document.querySelector('.js-overlay-modal-export');
      let modalElem = document.querySelector('.modal[data-modal="3"]');

      modalElem.classList.add('active');


      var overlay3 = document.querySelector('.js-overlay-modal-export');
      var closeButtons3 = document.querySelectorAll('.js-modal-close-export');

      // close button event  when end of export
      closeButtons3.forEach(function (item) {

            item.addEventListener('click', function (e) {
                var parentModal = this.closest('.modal');

                parentModal.classList.remove('active');
                overlay3.classList.remove('active');
                
                
            });

        }); // end foreach

}


