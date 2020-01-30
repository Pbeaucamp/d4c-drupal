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

