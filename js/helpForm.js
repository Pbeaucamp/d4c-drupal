$ = jQuery;

$(document).ready(function () {
	$("#help").attr("type","button");
});
 


 
/* (function($, Drupal, drupalSettings) {
    Drupal.behaviors.vShop = {
        attach: function (context, settings) {

            var loadVar = function(event, request, settings) {    
				alert(drupalSettings.bookmark);
                
			};

            $( document ).ready(loadVar);
            $( document ).ajaxComplete(loadVar);
        }
    };
})(jQuery, Drupal, drupalSettings);*/

function showHelp(e, bookmark){
	
	$.get(fetchPrefix() + "/sites/default/files/api/portail_d4c/files/Data4Citizen-Documentation-Administration.html", function(data, event){
		
		/*var el = document.createElement( 'html' );
		el.innerHTML = data;
		
		el.getElementsByTagName( 'a' )*/
		
		var el = $( '<div></div>' );
		el.html(data);

		arr = $('a[name]', el); 
		var endName = undefined;
		for(var i=0; i<arr.length; i++){
			if(arr[i].name == bookmark){
				if(arr.length > i){
					endName = arr[i+1].name;
				}
				break;
			}
		}
		var string; var els;
		if(endName != undefined){
			els = $("[name='"+bookmark+"']", el).parent().nextUntil("*:has([name='"+endName+"'])", el);
		} else {
			els = $("[name='"+bookmark+"']", el).parent().nextAll().html();
		}
		var container = $('<div/>');

		$.each(els, function(i,val) {
			container.append(val);
		});
		string = container.html();
		//console.log(string);
		
		$('.WordSection1', el).html();
		
		$('.WordSection1', el).html(string);
		
		$("#helpPlace").html(el);
		let overlay  = document.querySelector('.overlay-help');
		let modalElem = document.querySelector('.modal-help[data-modal="help"]');
		modalElem.classList.add('active');
		overlay.classList.add('active'); 
	});
	
	e.stopPropagation();
	e.preventDefault();
}


/////////////modal/////////////
 
!function(e){"function"!=typeof e.matches&&(e.matches=e.msMatchesSelector||e.mozMatchesSelector||e.webkitMatchesSelector||function(e){for(var t=this,o=(t.document||t.ownerDocument).querySelectorAll(e),n=0;o[n]&&o[n]!==t;)++n;return Boolean(o[n])}),"function"!=typeof e.closest&&(e.closest=function(e){for(var t=this;t&&1===t.nodeType;){if(t.matches(e))return t;t=t.parentNode}return null})}(window.Element.prototype);

document.addEventListener('DOMContentLoaded', function() {

    //modalButtons = document.querySelectorAll('.js-open-modal'),
     var  overlay      = document.querySelector('.overlay-help'),
       closeButtons = document.querySelectorAll('.js-modal-help-close');

   closeButtons.forEach(function(item){

      item.addEventListener('click', function(e) {
         var parentModal = this.closest('.modal-help');

         parentModal.classList.remove('active');
         overlay.classList.remove('active');
      });

   }); // end foreach


    document.body.addEventListener('keyup', function (e) {
        var key = e.keyCode;

        if (key == 27) {

            document.querySelector('.modal-help.active').classList.remove('active');
            document.querySelector('.overlay-help').classList.remove('active');
        };
    }, false);


    overlay.addEventListener('click', function() {
        document.querySelector('.modal-help.active').classList.remove('active');
        this.classList.remove('active');
    });

}); // end ready
    
$('#modalHelp').html('<div class="modal modal-help" data-modal="help"><svg class="modal__cross js-modal-help-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="helpPlace" style="height:100%;overflow:auto; "></div></div><div class="overlay overlay-help js-overlay-help-modal"></div>');
/////////////modal/////////////