$ = jQuery;


$('#formModal').after('<div style="width:62em; height:70%" class="modal" data-modal="1"><svg class="modal__cross js-modal-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="list_url_modal"><h2>Importer un rapport Vanilla</h2></div></br><div id="prew"></div></br><div><a href="#" id="prew" class="js-modal-close button js-form-submit form-submit">ok</a></div></div><div class="overlay js-overlay-modal"></div>');
 $("#visibilityModalStory").css("display","none");
$('#edit-button-del-story').attr('onclick', 'delStory(event);');

$("#edit-img-widget-upload").after('<div id="img_widget"></div>');
/*$('#edit-valider').attr('onclick', ' validUpload(event, "img_widget");');*/
$('#visibilityStories').before(`<p><input id="exportdataset" type="button" onclick="openModalStory()" class="button"  value="Ajouter une histoire" /></p>`);
$("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(1)">');




$(document).ready(function() {
var timer = null;
timer = setInterval(function() {
  $("#next").trigger("click");
}, 2500);
  

  });

function delStory(event) {

    var conf = confirm("Etes-vous sûr de vouloir supprimer ce jeu de données?");


    if (conf) {

        $('#edit-del-story').selected(true);
        $('#edit-del-story').val('1');
      

    } else {
        event.preventDefault();
        event.stopImmediatePropagation();
        if (!event.isDefaultPrevented()) {
            event.returnValue = false;
        }

    }
}

function loadStory($stories) {
  var x = document.getElementById("selected_data").value;
  var story = getStoryByID($stories, x);
console.log(x);
  console.log($stories);
  console.log(story);
  /*console.log($stories[x-1]);*/
  openModalStory(story);

}

function getStoryByID(stories, id) {

    data = stories[stories.findIndex(x => x.story_id === id)];


    return data;
}
var slideIndex = 1;
showSlides(slideIndex);

function showSlides(n) {
  var i;
  var slides = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("dot");


  if (n > slides.length) {slideIndex = 1}    
  if (n <= 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block"; 
  dots[slideIndex-1].className += " active";
}

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  console.log(" hh ");
  showSlides(slideIndex = n);
}

var urlimag = "";
window.addEventListener('load', function() {
  var img = document.querySelector('img');
  console.log($('input[type="file"]'));
  document.querySelector('input[type="file"]').addEventListener('change', function() {
      if (this.files && this.files[0]) {
            // $('img')[0]
          img.src = URL.createObjectURL(this.files[0]); // set src to blob url
          img.onload = imageIsLoaded;
          
      }

  });
  
});



function imageIsLoaded() { 
 
/*
  $("#img-widget-modal").attr("src",this.src);*/
}

/*$("#edit-img-widget-upload").on('click', function(){
  alert("hhh");
})*/

function openModalStory(story=null) {
    $('#visibilityStories').after(`<div style="width: 70em; padding: 72px; box-shadow: 5px 10px 8px 10px #888888;" class="modal" data-modal="3">
         
              <div class="row">
                    <a href="#" id="cancel2" class="js-modal-close-export button" style="float: right;margin-top: -71px;
            margin-right: -67px; padding: 5px;border: none;
            background: transparent;">X</a>
              </div>
              <div class="modal-body" id="modal-body">
             
                <h2 id="title-modal-story">Ajouter une nouvelle storie </h2>
               
                
          </div>
          <div class="overlay js-overlay-modal-export"></div>`);

         
          $("#myModal").css("display","block");
          $('#visibilityModalStory').appendTo('#modal-body');
          $("#visibilityModalStory").css("display","block");
          $('#edit-label-widget').before('<img id="img-widget-modal" style="width:80px !important; height:80px !important" src="https://kmo.data4citizen.com/sites/default/files/gris.jpg" width:80 height: 80 />');

          if(story != null ) {
             $('#title-modal-story').text("Modifier la story "+story["widget_label"]);
             $('input[name=button_del_story_name]').css("display","block");
             $('input[name=id_story]').val(story["story_id"]);
             $('input[name=scroll_tps]').val(story["scroll_time"]);
             $('input[name=label_widget]').val(story["widget_label"]);
             $('textarea[name=widget]').val(story["widget"]);
             $('#img-widget-modal').attr('src', story["image"]);



          }
          else {
            $('input[name=button_del_story_name]').css("display","none");
            clear();
          }
          
     /*     $('#edit-valider').attr('onclick', ' validUpload(event, "img_widget");');*/
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

function clear() {
              $('input[name=id_story]').val('');
             $('input[name=scroll_tps]').val('');
             $('input[name=label_widget]').val('');
             $('textarea[name=widget]').val('');
             $('#img-widget-modal').attr('src', "https://kmo.data4citizen.com/sites/default/files/gris.jpg");

}

function validUpload(event, name) {//backgr//resours

    if ($("[name='files["+name+"]']").val() != '' && $("[name='files["+name+"]']").val() != null && typeof($("[name='files["+name+"]']").val())!='undefined') {
        
         alert("Veuillez attendre la fin du chargement des ressources!");
            event.preventDefault();
            event.stopImmediatePropagation();
            if (!event.isDefaultPrevented()) {
                event.returnValue = false;
            }

 
        
    }



}
