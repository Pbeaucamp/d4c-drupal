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
			
			$("select[data-id="+$("#edit-selected-id").val()+"]").val($("#edit-selected-type").val() == "private" ? "public" : "private");
			
			$("#edit-selected-type").val("");
			$("#edit-selected-id").val("");
			
        });

    }); // end foreach


    /*document.body.addEventListener('keyup', function (e) {
        var key = e.keyCode;

        if (key == 27) {

            document.querySelector('.modal.active').classList.remove('active');
            //document.querySelector('.overlay').classList.remove('active');
        };
    }, false);*/


    /*overlay.addEventListener('click', function () {
        document.querySelector('.modal.active').classList.remove('active');
        this.classList.remove('active');
    });*/

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
	var selectedType = event.srcElement.value;
	var labelOld = selectedType == "private" ? "Public" : "Privé";
	var labelNew = selectedType == "private" ? "Privé" : "Public";
	/*var position = event.path.reduce(function (acc, cur){
		var res = {}; 
		res.x = (acc.x || (acc.clientLeft + acc.offsetLeft)) + ((cur.clientLeft + cur.offsetLeft) || 0);
		res.y = (acc.y || (acc.clientLeft + acc.offsetLeft)) + ((cur.clientTop + cur.offsetTop) || 0);
		return res;
	});*/
	//var position = event.srcElement.getBoundingClientRect();
	//$(".modal").css({top: position.y, left: position.x});
	$("#question").html("<p>Êtes-vous sûrs de passer le statut du jeu de données <strong>" + id + "</strong> de " + labelOld + " à <strong>" + labelNew + "</strong> ?</p>" );
	//$("#cancel").attr("root", id);
	$("#edit-selected-type").val(selectedType);
	$("#edit-selected-id").val(id);
	
	let overlay = document.querySelector('.js-overlay-modal');
	let modalElem = document.querySelector('.modal[data-modal="1"]');
	modalElem.classList.add('active');
	overlay.classList.add('active');
}

function end(){
	$("#edit-search").click();
}