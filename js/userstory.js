$ = jQuery;

$('#formModal').after('<div style="width:62em; height:70%" class="modal" data-modal="1"><svg class="modal__cross js-modal-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.954 21.03l-9.184-9.095 9.092-9.174-2.832-2.807-9.09 9.179-9.176-9.088-2.81 2.81 9.186 9.105-9.095 9.184 2.81 2.81 9.112-9.192 9.18 9.1z"/></svg><div id="list_url_modal"><h2>Importer un rapport Vanilla</h2></div></br><div id="prew"></div></br><div><a href="#" id="prew" class="js-modal-close button js-form-submit form-submit">ok</a></div></div><div class="overlay js-overlay-modal"></div>');
$("#visibilityModalStory").css("display", "none");
$('#edit-button-del-story').attr('onclick', 'delStory(event);');

$("#edit-img-widget-upload").after('<div id="img_widget"></div>');
$('#visibilityStories').before(`<p><input id="exportdataset" type="button" onclick="openModalStory()" class="button"  value="Ajouter une histoire" /></p>`);
$("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(1)">');

$(document).ready(function () {
  var timer = null;
  /*timer = setInterval(function() {
    $("#next").trigger("click");
  }, 2500);
    */

});

function deleteRowWidget(btn) {
  var row = btn.parentNode.parentNode;
  row.parentNode.removeChild(row);
}

function uploadImg(num) {
  const selectedFile = document.getElementById('edit-table-widgets-' + num + '-img-widget-upload').files[0];
  console.log(" key up");
  console.log(selectedFile);
}

function addWidgetRow(num) {

  num = num + 1;


  let label_widget = '<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-widgets-' + num + '-label_widget form-item-table-widgets-' + num + '-label_widget form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-name" type="text" id="edit-table-widgets-' + num + '-label_widget" name="table_widgets[' + num + '][label_widget]" value="" size="30" class="form-text"></div></td>';
  let img_widget = '<td><div id="ajax-wrapper-' + num + '"><div class="js-form-item form-item js-form-type-managed-file form-type-managed-file js-form-item-table-widgets-' + num + '-img-widget form-item-table-widgets-' + num + '-img-widget"><label for="edit-table-widgets-' + num + '-img-widget-upload" id="edit-table-widgets-' + num + '-img-widget--label">Image de l\'histoire  :</label><div id="edit-table-widgets-' + num + '-img-widget" class="js-form-managed-file form-managed-file"><input onclick="uploadImg(' + num + ')"  data-drupal-selector="edit-table-widgets-' + num + '-img-widget-upload" type="file" id="edit-table-widgets-' + num + '-img-widget-upload" name="files[table_widgets_' + num + '_img_widget]" size="22" class="js-form-file form-file"><input onsubmit="uploadImg(' + num + ')" onkeyup="uploadImg(' + num + ')" class="js-hide button js-form-submit form-submit" data-drupal-selector="edit-table-widgets-' + num + '-img-widget-upload-button" formnovalidate="formnovalidate" type="submit" id="edit-table-widgets-' + num + '-img-widget-upload-button" name="table_widgets_' + num + '_img_widget_upload_button" value="Transférer"><input data-drupal-selector="edit-table-widgets-' + num + '-img-widget-fids" type="hidden" name="table_widgets[' + num + '][img_widget][0]"></div></div></div></td>';
  let widget_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-widget form-item-table-widgets-' + num + '-widget form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-widget" id="edit-table-widgets-' + num + '-widget" name="table_widgets[' + num + '][widget]" rows="5" cols="60" class="form-textarea resize-vertical"></textarea> </div></div></td>';
  let del_widget = ' <td><input type="button" class="button js-form-submit form-submit" value="Supprimer" onclick="deleteRowWidget(this)"/></td>';

  $('#edit-table-widgets > tbody:last-child').append('<tr data-drupal-selector="edit-table-widgets-' + num + '" class="odd">' + label_widget + img_widget + widget_widget + del_widget + '</tr>');
  $('#addRowBtnWidget').remove();
  $("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(' + num + ')">');
}

function delStory(event) {

  var conf = confirm("Etes-vous sûr de vouloir supprimer cette connaissance?");
  if (conf) {
    $('#edit-del-story').selected(true);
    $('#edit-del-story').val('1');
  }
  else {
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!event.isDefaultPrevented()) {
      event.returnValue = false;
    }
  }
}

function loadStory($stories, $widgets) {
  var x = document.getElementById("selected_data").value;
  var story = getStoryByID($stories, x);
  var widgets = getWidgetsByStory($widgets, x);
  console.log(x);
  console.log(widgets);
  console.log(story);
  openModalStory(story, widgets);
}

function getWidgetsByStory(widgets, idstory) {
  const data = [];
  console.log(widgets.length);
  for (var i = 0; i < widgets.length; i++) {
    if (widgets[i]["story_id"] == idstory) {
      data.push(widgets[i]);
    }
  }

  return data;
}

function getStoryByID(stories, id) {
  data = stories[stories.findIndex(x => x.story_id === id)];
  return data;
}


function showSlides(n) {
  console.log("show slides");
  var i;
  var slides = document.getElementsByClassName("mySlides");
  console.log(" attributes ");

  if (n == 1) {
    for (var j = 0; j < slides.length; j++) {
      console.log(slides[j].getAttribute("data-key"));
      if (slides[j].getAttribute("data-key") == 0) {
        slides[j].style.display = "block";
      }
    }
  }
  else {
    var dots = document.getElementsByClassName("dot");

    if (n > slides.length) { slideIndex = 1 }
    if (n <= 1) { slideIndex = slides.length }
    for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";
    }
    for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " active";
  }
}

var slideIndex = 1;

var slideIndexArray = [];
$(document).ready(function () {

  $(".slidescontent").each(function () {
    slideIndexArray[$(this).attr("data-id")] = 1;
    showSlides2(null, slideIndexArray[$(this).attr("data-id")]);
    var timer = null;
    var next = $(this).find("#next");
    var scroll = next.attr("data-scrolltime");
    let number = scroll.toString().length;
    let mathpow = Math.pow(10, number - 1);
    let scrolltime = (scroll * 1000) / mathpow;

    if (scrolltime > 0) {
      timer = setInterval(function () {
        next.trigger("click");
      }, scrolltime);
    }
  });

  console.log(" slide array ");
  console.log(slideIndexArray);
});

function showSlides2(storyindex, n) {
  if (storyindex == null) {
    if (n == 1) {
      var slides = document.getElementsByClassName("mySlides");
      for (var j = 0; j < slides.length; j++) {
        if (slides[j].getAttribute("data-key") == 0) {
          slides[j].style.display = "block";
        }
      }
    }
  }
  else {
    var i;

    var slides = document.getElementById("slides-" + storyindex).querySelectorAll(".mySlides");
    var dots = document.getElementById("slidesContent-" + storyindex).querySelectorAll(".dot");

    if (n > slides.length) { slideIndexArray[storyindex] = 1 }
    if (n <= 1) { slideIndexArray[storyindex] = slides.length }
    for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";
    }
    for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndexArray[storyindex] - 1].style.display = "block";
    dots[slideIndexArray[storyindex] - 1].className += " active";
  }
}

function plusSlides(storyindex, n) {
  showSlides2(storyindex, slideIndexArray[storyindex] += n);
}

function currentSlide(storyindex, n) {
  showSlides2(storyindex, slideIndexArray[storyindex] = n);
}

var urlimag = "";
window.addEventListener('load', function () {
  var img = document.querySelector('img');
  console.log($('input[type="file"]'));
  document.querySelector('input[type="file"]').addEventListener('change', function () {
    if (this.files && this.files[0]) {
      img.src = URL.createObjectURL(this.files[0]); // set src to blob url
      img.onload = imageIsLoaded;
    }
  });
});

function imageIsLoaded() {
}

function openModalStory(story = null, widgets = null) {
  $('#visibilityStories').after(`<div style="width: 100em; padding: 72px; box-shadow: 5px 10px 8px 10px #888888;" class="modal" data-modal="3">
         
              <div class="row">
                    <a href="#" id="cancel2" class="js-modal-close-export button" style="float: right;margin-top: -71px;
            margin-right: -67px; padding: 5px;border: none;
            background: transparent;">X</a>
              </div>
              <div class="modal-body" id="modal-body">
             
                <h2 id="title-modal-story">Ajouter une nouvelle storie </h2>
               
                
          </div>
          <div class="overlay js-overlay-modal-export"></div>`);


  $("#myModal").css("display", "block");
  $('#visibilityModalStory').appendTo('#modal-body');
  $("#visibilityModalStory").css("display", "block");
  $('#edit-label-widget').before('<img id="img-widget-modal" style="width:80px !important; height:80px !important" src="https://kmo.data4citizen.com/sites/default/files/gris.jpg" width:80 height: 80 />');

  if (story != null && widgets != null) {
    $("table[id='edit-table-widgets'] tr:first-child").remove();
    $('#title-modal-story').text("Modifier la story " + story["title_story"]);
    $('input[name=button_del_story_name]').css("display", "block");
    $('input[name=id_story]').val(story["story_id"]);
    $('input[name=story_title]').val(story["title_story"]);
    $('input[name=scroll_tps]').val(story["scroll_time"]);
    $('input[name=label_widget]').val(story["widget_label"]);
    $('textarea[name=widget]').val(story["widget"]);
    $('#img-widget-modal').attr('src', story["image"]);
    let num = 0;
    for (let i = 0; i < widgets.length; i++) {
      $('input[name=id_story]').val(story["story_id"]);

      num = i + 1;
      console.log(widgets[i]);
      let id_widget_content = widgets[i]["widget_id"];
      let label_widget_content = widgets[i]["widget_label"];
      let widget_widget_content = widgets[i]["widget"];
      let img_widget_content = "";
      let label_widget = '<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-table-widgets-' + num + '-label_widget form-item-table-widgets-' + num + '-label_widget form-no-label"><input data-drupal-selector="edit-table-widgets-' + num + '-name" type="text" id="edit-table-widgets-' + num + '-label_widget" name="table_widgets[' + num + '][label_widget]" value="' + label_widget_content + '" size="30" class="form-text"></div></td>';
      let img_widget = '<td><div id="ajax-wrapper"><div class="js-form-item form-item js-form-type-managed-file form-type-managed-file js-form-item-table-widgets-' + num + '-img-widget form-item-table-widgets-' + num + '-img-widget"><label for="edit-table-widgets-' + num + '-img-widget-upload" id="edit-table-widgets-' + num + '-img-widget--label">Image de l\'histoire  :</label><div id="edit-table-widgets-' + num + '-img-widget" class="js-form-managed-file form-managed-file"><input data-drupal-selector="edit-table-widgets-' + num + '-img-widget-upload" type="file" id="edit-table-widgets-' + num + '-img-widget-upload" name="files[table_widgets_' + num + '_img_widget]" size="22" class="js-form-file form-file"><input class="js-hide button js-form-submit form-submit" data-drupal-selector="edit-table-widgets-' + num + '-img-widget-upload-button" formnovalidate="formnovalidate" type="submit" id="edit-table-widgets-' + num + '-img-widget-upload-button" name="table_widgets_' + num + '_img_widget_upload_button" value="Transférer"><input data-drupal-selector="edit-table-widgets-' + num + '-img-widget-fids" type="hidden" name="table_widgets[' + num + '][img_widget][0]"></div></div></div></td>';
      let widget_widget = '<td><div class="js-form-item form-item js-form-type-textarea form-type-textarea js-form-item-table-widgets-' + num + '-widget form-item-table-widgets-' + num + '-widget form-no-label"><div class="form-textarea-wrapper"><textarea style="height: 5em;width: 25em;" data-drupal-selector="edit-table-widgets-' + num + '-widget" id="edit-table-widgets-' + num + '-widget" name="table_widgets[' + num + '][widget]" rows="5" cols="60" class="form-textarea resize-vertical" value="' + widget_widget_content + '">' + widget_widget_content + '</textarea> </div></div></td>';
      let del_widget = ' <td><input type="button" class="button js-form-submit form-submit" value="Supprimer" onclick="deleteRowWidget(this)"/></td>';

      $('#edit-table-widgets > tbody:last-child').append('<tr data-drupal-selector="edit-table-widgets-' + num + '" class="odd">' + label_widget + img_widget + widget_widget + del_widget + '</tr>');
      $('#addRowBtnWidget').remove();
      $("#edit-table-widgets").after('<input id="addRowBtnWidget" class="button js-form-submit form-submit" value="Ajouter un widget" type="button" onclick="addWidgetRow(' + num + ')">');
    }
  }
  else {
    $('input[name=button_del_story_name]').css("display", "none");
    clear();
  }

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
      document.getElementById('selected_data').value = 'new';
      clear();
    });
  }); // end foreach
}

function clear() {
  $('input[name=id_story]').val('');
  $('input[name=scroll_tps]').val('');
  $('input[name=label_widget]').val('');
  $('textarea[name=widget]').val('');
  $('#img-widget-modal').attr('src', "https://kmo.data4citizen.com/sites/default/files/gris.jpg");

  $('input[name=id_story]').val('');
  $('input[name=story_title]').val('');
  $("#edit-table-widgets tbody tr:not(:first)").remove();
  $('input[id=edit-table-widgets-1-label_widget]').val('');
  $('input[id=edit-table-widgets-1-img-widget-upload]').val('');
  $('textarea[id=edit-table-widgets-1-widget]').val('');
}

function validUpload(event, name) {//backgr//resours
  if ($("[name='files[" + name + "]']").val() != '' && $("[name='files[" + name + "]']").val() != null && typeof ($("[name='files[" + name + "]']").val()) != 'undefined') {
    alert("Veuillez attendre la fin du chargement des ressources!");
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!event.isDefaultPrevented()) {
      event.returnValue = false;
    }
  }
}
