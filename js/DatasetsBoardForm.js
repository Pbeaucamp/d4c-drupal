$ = jQuery;
var users;
var currentuser;

$(document).ready(function(){
	$("#edit-roles-list-administrator").attr("disabled", "disabled");
	for (let [key, user] of Object.entries(users)) {
		// if(user.roles.includes('administrator')) {
			// $("#edit-users-list-" + user.id).attr("disabled", "disabled");
		// }
		// console.log(currentuser);
		if(currentuser == user.id) {
			$("#edit-users-list-" + user.id).attr("disabled", "disabled");
		}
	}
});

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
    var overlay1 = document.querySelector('.js-overlay-modal'),
    overlay2 = document.querySelector('.js-overlay-modal-security'),
    closeButtons1 = document.querySelectorAll('.js-modal-close');
    closeButtons2 = document.querySelectorAll('.js-modal-close-security');

    closeButtons1.forEach(function (item) {

        item.addEventListener('click', function (e) {
            var parentModal = this.closest('.modal');

            parentModal.classList.remove('active');
            overlay1.classList.remove('active');
			
			$("select[data-id="+$("#edit-selected-id").val()+"]").val($("#edit-selected-type").val() == "private" ? "public" : "private");
			
			$("#edit-selected-type").val("");
			$("#edit-selected-id").val("");
			$("#edit-selected-users").val("");
			
        });

    }); // end foreach

	closeButtons2.forEach(function (item) {

        item.addEventListener('click', function (e) {
            var parentModal = this.closest('.modal');

            parentModal.classList.remove('active');
            overlay2.classList.remove('active');
			
			$("#edit-selected-type").val("");
			$("#edit-selected-id").val("");
			$("#edit-selected-users").val("");
			
        });

    }); // end foreach
	
	$("#edit-roles-list").css("column-count", 2);
	$("#edit-roles-list .form-type-checkbox").css("display", "inline-block");
	
	$("#edit-users-list").css("overflow", "auto").css("max-height", "200px");
	
	$("#edit-roles-list .form-type-checkbox input").click(function(event){
		console.log(event);
		var checked = event.target.checked;
		var role = event.target.value;
	
		for (let [key, user] of Object.entries(users)) {
			if(user.roles.indexOf(role) != -1 || (role == "administrator" && user.id == "1")){
				$("#edit-users-list-"+parseInt(user.id)).prop("checked", checked);
			}
		}
	});

}); // end ready   
$('#visibilityModal').after(`<div style="width:25em;" class="modal" data-modal="1">
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
						
$('#securityModal').after(`<div style="width:35em;" class="modal" data-modal="2">
							<div id="title2" style="color: cornflowerblue;">
								<h2>Gestion</h2>
							</div>
							<div id="question2" style="text-align:left;"></div>
							<div class="row">
								<a href="#" id="cancel2" class="js-modal-close-security button" style="width:8em">Annuler</a>
								<input type="button" id="apply2" class="js-modal-close-security button" style="width:8em" value="Confirmer" onclick="saveSecurity()">
							</div>
						</div>
						<div class="overlay js-overlay-modal-security"></div>`);
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

function openecurityPopup(event){
	$("#edit-security").css("display","block");
	$("#edit-security .form-type-checkbox input").each(function( index ) {
		$( this ).prop("checked", false);
	});
	
	console.log(event);
	var id = event.srcElement.attributes["data-id"].value;
	var sec = atob(event.srcElement.attributes["data-security"].value);
		
	$("#question2").append($("#edit-security"));
	
	/*cbs = document.querySelectorAll('#edit-security .form-type-checkbox');
	cbs.forEach(function (item) {
		item.checked = false;
    });*/

	$("#edit-selected-id").val(id);
	
	if(sec != ""){
		sec = JSON.parse(sec);
		for(var role of sec.roles){
			$("#edit-roles-list-"+role).prop("checked", true);
		}
		for(var user of sec.users){
			$("#edit-users-list-"+user).prop("checked", true);
		}
	}
	
	let overlay = document.querySelector('.js-overlay-modal-security');
	let modalElem = document.querySelector('.modal[data-modal="2"]');
	modalElem.classList.add('active');
	overlay.classList.add('active');
}

function end(){
	$("#edit-search").click();
}

function saveSecurity(){
	var res = {"roles":[], "users":[]};
	cbRoles = document.querySelectorAll('#edit-roles-list .form-type-checkbox input');
    cbUsers = document.querySelectorAll('#edit-users-list .form-type-checkbox input');
	cbRoles.forEach(function (item) {
		if(item.checked == true){
			res.roles.push(item.value);
		}
    });
	cbUsers.forEach(function (item) {
		if(item.checked == true){
			res.users.push("*"+parseInt(item.value)+"*");
		}
    });
	$("#edit-selected-users").val(JSON.stringify(res));
	
	$("#edit-search").click();
}

(function($, Drupal, drupalSettings) {
    users = JSON.parse(drupalSettings.users);
	currentuser = drupalSettings.currentuser;
	// console.log(currentuser);
})(jQuery, Drupal, drupalSettings);