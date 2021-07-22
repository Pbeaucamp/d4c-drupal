$ = jQuery;
var users;
var currentuser;
var datapusher;

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
    var overlay = document.querySelector('.js-overlay-modal-log'),
    closeButtons = document.querySelectorAll('.js-modal-close-log');
	closeButtons.forEach(function (item) {

        item.addEventListener('click', function (e) {
            var parentModal = this.closest('.modal');

            parentModal.classList.remove('active');
            overlay.classList.remove('active');
        });

    }); // end foreach
}); // end ready  				
$('#logModal').after(`<div style="width:45em;" class="modal" data-modal="2">
						<div id="title2" style="color: cornflowerblue;">
							<h2>Journal de chargement</h2>
						</div>
						<div id="question2" style="text-align:left;overflow: auto;max-height: 300px;"></div>
						<div class="row">
							<a href="#" id="cancel2" class="js-modal-close-log button" style="width:8em">Close</a>
						</div>
					</div>
					<div class="overlay js-overlay-modal-log"></div>`);
/////////////modal/////////////


function openLogPopup(event){
	// $("#edit-log").css("display","block");
	
	console.log(event);
	var id = event.srcElement.attributes["data-id"].value;

	var html = "<div>";
	if (datapusher != ""){
		var logs = JSON.parse(datapusher[id]);
		if (logs.status == 'error') {
			html += "<div class='errorDatapusher'>" + logs.error.Response + "</div>";
		}
		for(var log of logs.logs){
			html += "<p>" + log.message + "</p>";
		}
	}
	html += "</div>";	
		
	$("#question2").append($("<div>", {
		id: "#edit-log",
		className: 'foobar',
		html: html
	}));

	let overlay = document.querySelector('.js-overlay-modal-log');
	let modalElem = document.querySelector('.modal[data-modal="2"]');
	modalElem.classList.add('active');
	overlay.classList.add('active');
}

function pushToDatastore(event) {
	console.log(event);
	var id = event.srcElement.attributes["data-id"].value;
	$("#edit-selected-resource").val(id);

	$("#edit-datapusher").click();
}

(function($, Drupal, drupalSettings) {
	currentuser = drupalSettings.currentuser;
	datapusher = drupalSettings.ckan;
	// console.log("Datapusher " + datapusher);
    users = JSON.parse(drupalSettings.user);
})(jQuery, Drupal, drupalSettings);