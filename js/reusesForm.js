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
document.addEventListener('DOMContentLoaded', function () {

    //modalButtons = document.querySelectorAll('.js-open-modal'),
    var overlay = document.querySelector('.js-overlay-modal'),
    closeButtons = document.querySelectorAll('.js-modal-close');

    closeButtons.forEach(function (item) {

        item.addEventListener('click', function (e) {
            var parentModal = this.closest('.modal');

            parentModal.classList.remove('active');
            overlay.classList.remove('active');
			
			$("select[data-id="+$("#edit-selected-id").val()+"]").val($("select[data-id="+$("#edit-selected-id").val()+"]").data("old-status"));
			
			$("#edit-selected-type").val("");
			$("#edit-selected-id").val("");
			
        });

    }); // end foreach


}); // end ready   
$('#formModal').after(`<div style="width:25em;" class="modal" data-modal="1">
							<div id="title" style="color: cornflowerblue;">
								<h2>Confirmation</h2>
							</div>
							<div id="question"></div>
							<div class="row">
								<a href="#" id="cancel" class="js-modal-close button" style="width:8em">Annuler</a>
								<input type="button" id="apply" class="js-modal-close button" style="width:8em" value="Confirmer" onclick="end()">
							</div>
						</div>
						<div class="overlay js-overlay-modal"></div>`);
/////////////modal/////////////


function confirm(event){
	console.log(event);
	var id = event.srcElement.attributes["data-id"].value;
	var selectedAction = event.srcElement.value;
	var question;
	if(selectedAction == "online"){
		question = "Vous êtes sur le point de valider cette réutilisation. Elle sera visible publiquement sur la palteforme."; 
	} else if (selectedAction == "offline"){
		question = "Vous êtes sur le point de refuser cette réutilisation. Elle ne sera pas visible sur la plateforme, mais restera accessible pour les administrateurs."; 
	} else if (selectedAction == "delete"){
		question = "Vous êtes sur le point de supprimer cette réutilisation. Elle ne sera plus visible sur la plateforme et ne sera plus accessible pour les administrateurs."; 
	}

	//var position = event.srcElement.getBoundingClientRect();
	//$(".modal").css({top: position.y, left: position.x});
	$("#question").html("<p>" + question + "</p>" );
	//$("#cancel").attr("root", id);
	$("#edit-selected-action").val(selectedAction);
	$("#edit-selected-id").val(id);
	
	let overlay = document.querySelector('.js-overlay-modal');
	let modalElem = document.querySelector('.modal[data-modal="1"]');
	modalElem.classList.add('active');
	overlay.classList.add('active');
}

function end(){
	$("#edit-search").click();
}