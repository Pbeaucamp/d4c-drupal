<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class userStoryController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function myPage() {
  
    //$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');
    $config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
    $element = array(
        'example one' => [
          '#type' => 'inline_template',
          '#template' => ' <style>
* {box-sizing: border-box}
body {font-family: Verdana, sans-serif; margin:0}
.mySlides {display: none}
img {vertical-align: middle;}
iframe {vertical-align: middle;}

/* Slideshow container */
.slideshow-container {
  max-width: 1000px;
  position: relative;
  margin: auto;
}

/* Next & previous buttons */
.prev, .next {
  cursor: pointer;
  position: relative;
  top: 50%;
  width: auto;
  padding: 16px;
  margin-top: 22px;
  color: white !important;
  font-weight: bold;
  font-size: 18px;
  transition: 0.6s ease;
  border-radius: 0 3px 3px 0;
  user-select: none;
  background-color: rgba(0,0,0,0.8);
}

/* Position the "next button" to the right */
.next {
  right: 0;
  border-radius: 3px 0 0 3px;
}

/* On hover, add a black background color with a little bit see-through */
.prev:hover, .next:hover {
  background-color: #007bff;
}

/* Caption text */
.text {
position:relative;
margin-top: 20px;
  color: #000;
  font-weight: bold;
  font-size: 15px;
  padding: 8px 12px;
  bottom: 8px;
  width: 100%;
  text-align: center;
}

/* Number text (1/3 etc) */
.numbertext {
  color: #f2f2f2;
  font-size: 12px;
  padding: 8px 12px;
  position: absolute;
  top: 0;
}

/* The dots/bullets/indicators */
.dot {
  cursor: pointer;
  height: 15px;
  width: 15px;
  margin: 0 2px;
  background-color: #bbb;
  border-radius: 50%;
  display: inline-block;
  transition: background-color 0.6s ease;
}

.active, .dot:hover {
  background-color: #007bff;
}
a {
  text-decoration: none !important;
}
.text:hover {
  color: #007bff;
  text-decoration: none;
}

/* Fading animation */
.fade {
  -webkit-animation-name: fade;
  -webkit-animation-duration: 1.5s;
  animation-name: fade;
  animation-duration: 1.5s;
}

@-webkit-keyframes fade {
  from {opacity: .4} 
  to {opacity: 1}
}

@keyframes fade {
  from {opacity: .4} 
  to {opacity: 1}
}

iframe {
  width: 100% !important;
}
.text-center {
  text-align: center !important;
}

.section-heading {
  font-size: 2.5rem;
  margin-top: 0;
  margin-bottom: 1rem;
  color: #000;
  font-weight: bold;
}

.text-uppercase {
  text-transform: uppercase !important;
}

/* On smaller screens, decrease text size */
@media only screen and (max-width: 300px) {
  .prev, .next,.text {font-size: 11px}
}

.slidesjs-play,.slidesjs-stop {
  display: none !important;
  margin-top: -100px !important;
}
#slides {
  margin-top: 20px !important;
  padding-top: 50px !important;
}
</style><div id ="main" class="widget-opendata">
            
         <div class="text-center">
                            <h2 class="section-heading text-uppercase" >Visualiseur de données</h2>
          </div>

          <div class="slideshow-container" id="slides">

<div class="mySlides " data-index = "1">
<a href ="https://grandest.data4citizen.com/visualisation/table/?id=4b096697-bc50-40cd-a9ee-42277c1424c3&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImNyZWF0aW9uc19kX2VudHJlcHJpc2VzX3Bhcl9zZWN0ZXVyX2RfYWN0aXZpdGVfZW5fMjAxOCIsIm9wdGlvbnMiOnsiaWQiOiI0YjA5NjY5Ny1iYzUwLTQwY2QtYTllZS00MjI3N2MxNDI0YzMifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImFyZGVubmVzIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzY2YzJhNSJ9LHsiYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImF1YmUiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjZmM4ZDYyIn1dLCJ4QXhpcyI6InR5cGVfZW50ciIsIm1heHBvaW50cyI6NTAsInNvcnQiOiJzZXJpZTEtMSJ9XSwidGltZXNjYWxlIjoiIiwiZGlzcGxheUxlZ2VuZCI6dHJ1ZSwiYWxpZ25Nb250aCI6dHJ1ZX0%3D" target="_blank">
<iframe id="iframejeu" src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=4b096697-bc50-40cd-a9ee-42277c1424c3&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImNyZWF0aW9uc19kX2VudHJlcHJpc2VzX3Bhcl9zZWN0ZXVyX2RfYWN0aXZpdGVfZW5fMjAxOCIsIm9wdGlvbnMiOnsiaWQiOiI0YjA5NjY5Ny1iYzUwLTQwY2QtYTllZS00MjI3N2MxNDI0YzMifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImFyZGVubmVzIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzBCNzJCNSJ9LHsiYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImF1YmUiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjNjE5RkM4In0seyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoic3BpZGVyd2ViIiwiZnVuYyI6IlNVTSIsInlBeGlzIjoibWFybmUiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjOEVCQUQ4In0seyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoic3BpZGVyd2ViIiwiZnVuYyI6IlNVTSIsInlBeGlzIjoiaGF1dGVfbWFybmUiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjQkJENUU3In0seyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoic3BpZGVyd2ViIiwiZnVuYyI6IlNVTSIsInlBeGlzIjoibWV1cnRoZV9ldF9tb3NlbGxlIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzhkYTBjYiJ9LHsiYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6Im1ldXNlIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzkzOUVDNiJ9LHsiYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImJhc19yaGluIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzY2YzJhNSJ9LHsiYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwaWRlcndlYiIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6ImhhdXRfcmhpbiIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6IiM4RkFGODkifSx7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJzcGlkZXJ3ZWIiLCJmdW5jIjoiU1VNIiwieUF4aXMiOiJ2b3NnZXMiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjNjZjMmE1In1dLCJ4QXhpcyI6InR5cGVfZW50ciIsIm1heHBvaW50cyI6NTAsInNvcnQiOiJzZXJpZTEtMSJ9XSwidGltZXNjYWxlIjoiIiwiZGlzcGxheUxlZ2VuZCI6dHJ1ZSwiYWxpZ25Nb250aCI6dHJ1ZSwic2luZ2xlQXhpcyI6ZmFsc2V9" frameBorder="0" width = 100% height =645></iframe> 

                  

  <div class="text">Créations d\'entreprises par secteur d\'activité en 2018</div></a>
</div>

<div class="mySlides " data-index = "2">
                <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=1cf064f5-0f20-4437-932f-78d3ded92af8&disjunctive.insee_com&disjunctive.nom_com&disjunctive.statut&disjunctive.nom_dept&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImFycy1hdXZlcmduZS1yaG9uZS1hbHBlcy1hbmNpZW5zLXNlY3RldXJzLXBzeWNoaWF0cmllLWluZmFudG8tanV2ZW5pbGUiLCJvcHRpb25zIjp7ImlkIjoiMWNmMDY0ZjUtMGYyMC00NDM3LTkzMmYtNzhkM2RlZDkyYWY4IiwiZGlzanVuY3RpdmUuaW5zZWVfY29tIjp0cnVlLCJkaXNqdW5jdGl2ZS5ub21fY29tIjp0cnVlLCJkaXNqdW5jdGl2ZS5zdGF0dXQiOnRydWUsImRpc2p1bmN0aXZlLm5vbV9kZXB0Ijp0cnVlfX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InBpZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6InBvcHVsYXRpb24iLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiJyYW5nZS1CbHVlcyIsInBvc2l0aW9uIjoiY2VudGVyIn1dLCJ4QXhpcyI6Im5vbV9jb20iLCJtYXhwb2ludHMiOjUwLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJ0aW1lc2NhbGUiOiIiLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlfQ%3D%3D" target="_blank" >

                  <iframe id="iframejeu" src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=1cf064f5-0f20-4437-932f-78d3ded92af8&disjunctive.insee_com&disjunctive.nom_com&disjunctive.statut&disjunctive.nom_dept&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImFycy1hdXZlcmduZS1yaG9uZS1hbHBlcy1hbmNpZW5zLXNlY3RldXJzLXBzeWNoaWF0cmllLWluZmFudG8tanV2ZW5pbGUiLCJvcHRpb25zIjp7ImlkIjoiMWNmMDY0ZjUtMGYyMC00NDM3LTkzMmYtNzhkM2RlZDkyYWY4IiwiZGlzanVuY3RpdmUuaW5zZWVfY29tIjp0cnVlLCJkaXNqdW5jdGl2ZS5ub21fY29tIjp0cnVlLCJkaXNqdW5jdGl2ZS5zdGF0dXQiOnRydWUsImRpc2p1bmN0aXZlLm5vbV9kZXB0Ijp0cnVlfX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InBpZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6InBvcHVsYXRpb24iLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiJyYW5nZS1CbHVlcyIsInBvc2l0aW9uIjoiY2VudGVyIn1dLCJ4QXhpcyI6Im5vbV9jb20iLCJtYXhwb2ludHMiOjUwLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJ0aW1lc2NhbGUiOiIiLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlfQ%3D%3D" frameBorder="0"  width = 100% height =645></iframe>    <Carousel.Caption>

  <div class="text">ARS Auvergne-Rhône-Alpes - Anciens secteurs psychiatrie infanto-juvénile [antérieurs à la loi du 26 janvier 2016 ]</div></a>
</div>

<div class="mySlides " data-index = "3">
                <a href="https://grandest.data4citizen.com/visualisation/information/?id=7bda264d-edcb-4553-ba4d-cd82c6ff8d7e&disjunctive.nature_pg&disjunctive.nature&disjunctive.numero&disjunctive.importance&disjunctive.sens" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=7bda264d-edcb-4553-ba4d-cd82c6ff8d7e&disjunctive.nature_pg&disjunctive.nature&disjunctive.numero&disjunctive.importance&disjunctive.sens&refine.numero=D913&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6InRlc3RfYXphX19fY291Y2hlX2Rlc19yb3V0ZXNfdGVfX18xMjBfdG9ubmVzIiwib3B0aW9ucyI6eyJpZCI6IjdiZGEyNjRkLWVkY2ItNDU1My1iYTRkLWNkODJjNmZmOGQ3ZSIsImRpc2p1bmN0aXZlLm5hdHVyZV9wZyI6dHJ1ZSwiZGlzanVuY3RpdmUubmF0dXJlIjp0cnVlLCJkaXNqdW5jdGl2ZS5udW1lcm8iOnRydWUsImRpc2p1bmN0aXZlLmltcG9ydGFuY2UiOnRydWUsImRpc2p1bmN0aXZlLnNlbnMiOnRydWUsInJlZmluZS5udW1lcm8iOiJEOTEzIn19LCJjaGFydHMiOlt7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJzcGlkZXJ3ZWIiLCJmdW5jIjoiU1VNIiwieUF4aXMiOiJsYXJnZXVyIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoiIzAwNTM5NSIsInBvc2l0aW9uIjoiY2VudGVyIn1dLCJ4QXhpcyI6InNlbnMiLCJtYXhwb2ludHMiOjUwLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJ0aW1lc2NhbGUiOiIiLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlLCJzaW5nbGVBeGlzIjpmYWxzZX0%3D&location=14,0,6.25496" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Couche des routes TE - 120 Tonnes (54)</div></a>
</div>

<div class="mySlides " data-index = "4">
                <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=8bf60376-841d-4207-99b7-d74602eb9e19&disjunctive.denomination&disjunctive.forme_juridique&disjunctive.ville&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6InNvY2lldGVzX3JhZGllZXNfZW5fMjAyMCIsIm9wdGlvbnMiOnsiaWQiOiI4YmY2MDM3Ni04NDFkLTQyMDctOTliNy1kNzQ2MDJlYjllMTkiLCJkaXNqdW5jdGl2ZS5kZW5vbWluYXRpb24iOnRydWUsImRpc2p1bmN0aXZlLmZvcm1lX2p1cmlkaXF1ZSI6dHJ1ZSwiZGlzanVuY3RpdmUudmlsbGUiOnRydWV9fSwiY2hhcnRzIjpbeyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoicGllIiwiZnVuYyI6IkNPVU5UIiwieUF4aXMiOiJzaXJlbiIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6InJhbmdlLVB1QnUiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJmb3JtZV9qdXJpZGlxdWUiLCJtYXhwb2ludHMiOiIiLCJ0aW1lc2NhbGUiOiIiLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlLCJ0aW1lc2NhbGUiOiIifQ%3D%3D" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=8bf60376-841d-4207-99b7-d74602eb9e19&disjunctive.forme_juridique&disjunctive.greffe&disjunctive.secteur_d_activite&disjunctive.denomination&disjunctive.code_postal&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6InNvY2lldGVzX3JhZGllZXNfZW5fMjAyMCIsIm9wdGlvbnMiOnsiaWQiOiI4YmY2MDM3Ni04NDFkLTQyMDctOTliNy1kNzQ2MDJlYjllMTkiLCJkaXNqdW5jdGl2ZS5mb3JtZV9qdXJpZGlxdWUiOnRydWUsImRpc2p1bmN0aXZlLmdyZWZmZSI6dHJ1ZSwiZGlzanVuY3RpdmUuc2VjdGV1cl9kX2FjdGl2aXRlIjp0cnVlLCJkaXNqdW5jdGl2ZS5kZW5vbWluYXRpb24iOnRydWUsImRpc2p1bmN0aXZlLmNvZGVfcG9zdGFsIjp0cnVlLCJsb2NhdGlvbiI6IjYsNDYuMzkxMTksMy45OTA2NSJ9fSwiY2hhcnRzIjpbeyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoicG9sYXIiLCJmdW5jIjoiQ09VTlQiLCJ5QXhpcyI6InNpcmVuIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoicmFuZ2UtQmx1ZXMiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJncmVmZmUiLCJtYXhwb2ludHMiOiIiLCJ0aW1lc2NhbGUiOiIiLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlLCJ0aW1lc2NhbGUiOiIifQ%3D%3D&location=6,46.39119,3.99065" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Sociétés radiées en 2020 par ville</div></a>
</div>
<div class="mySlides " data-index = "5">
                <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=8bf60376-841d-4207-99b7-d74602eb9e19&disjunctive.forme_juridique&disjunctive.greffe&disjunctive.secteur_d_activite&disjunctive.denomination&disjunctive.code_postal&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6InNvY2lldGVzX3JhZGllZXNfZW5fMjAyMCIsIm9wdGlvbnMiOnsiaWQiOiI4YmY2MDM3Ni04NDFkLTQyMDctOTliNy1kNzQ2MDJlYjllMTkiLCJkaXNqdW5jdGl2ZS5mb3JtZV9qdXJpZGlxdWUiOnRydWUsImRpc2p1bmN0aXZlLmdyZWZmZSI6dHJ1ZSwiZGlzanVuY3RpdmUuc2VjdGV1cl9kX2FjdGl2aXRlIjp0cnVlLCJkaXNqdW5jdGl2ZS5kZW5vbWluYXRpb24iOnRydWUsImRpc2p1bmN0aXZlLmNvZGVfcG9zdGFsIjp0cnVlLCJsb2NhdGlvbiI6IjYsNDYuMzkxMTksMy45OTA2NSJ9fSwiY2hhcnRzIjpbeyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoicG9sYXIiLCJmdW5jIjoiQ09VTlQiLCJ5QXhpcyI6InNpcmVuIiwic2NpZW50aWZpY0Rpc3BsYXkiOnRydWUsImNvbG9yIjoicmFuZ2UtQmx1ZXMiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJncmVmZmUiLCJtYXhwb2ludHMiOiIiLCJ0aW1lc2NhbGUiOiIiLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlLCJ0aW1lc2NhbGUiOiIifQ%3D%3D&location=6,46.39119,3.99065" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=8bf60376-841d-4207-99b7-d74602eb9e19&disjunctive.denomination&disjunctive.forme_juridique&disjunctive.ville&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6InNvY2lldGVzX3JhZGllZXNfZW5fMjAyMCIsIm9wdGlvbnMiOnsiaWQiOiI4YmY2MDM3Ni04NDFkLTQyMDctOTliNy1kNzQ2MDJlYjllMTkiLCJkaXNqdW5jdGl2ZS5kZW5vbWluYXRpb24iOnRydWUsImRpc2p1bmN0aXZlLmZvcm1lX2p1cmlkaXF1ZSI6dHJ1ZSwiZGlzanVuY3RpdmUudmlsbGUiOnRydWV9fSwiY2hhcnRzIjpbeyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoicGllIiwiZnVuYyI6IkNPVU5UIiwieUF4aXMiOiJzaXJlbiIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6InJhbmdlLVB1QnUiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJmb3JtZV9qdXJpZGlxdWUiLCJtYXhwb2ludHMiOiIiLCJ0aW1lc2NhbGUiOiIiLCJzb3J0IjoiIiwic2VyaWVzQnJlYWtkb3duIjoiIiwic2VyaWVzQnJlYWtkb3duVGltZXNjYWxlIjoiIn1dLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlLCJ0aW1lc2NhbGUiOiIifQ%3D%3D" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Sociétés radiées en 2020 par forme juridique</div></a>
</div>

<div class="mySlides " data-index = "6">
                <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=e6def3f2-1434-4a72-bd4a-42aef092f2f9&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6Im5vbWJyZV9kZV9uYWlzc2FuY2VzX2V0X2RlY2VzX2dyYW5kX2VzdCIsIm9wdGlvbnMiOnsiaWQiOiJlNmRlZjNmMi0xNDM0LTRhNzItYmQ0YS00MmFlZjA5MmYyZjkifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwbGluZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6Im5haXNzYW5jZXMiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjMjZBRDI2In0seyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoic3BsaW5lIiwiZnVuYyI6IlNVTSIsInlBeGlzIjoiZGVjZXMiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjMEI3MkI1In1dLCJ4QXhpcyI6ImFubmVlIiwibWF4cG9pbnRzIjo1MCwic29ydCI6IiJ9XSwidGltZXNjYWxlIjoiIiwiZGlzcGxheUxlZ2VuZCI6dHJ1ZSwiYWxpZ25Nb250aCI6dHJ1ZSwic2luZ2xlQXhpcyI6dHJ1ZX0%3D" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=e6def3f2-1434-4a72-bd4a-42aef092f2f9&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6Im5vbWJyZV9kZV9uYWlzc2FuY2VzX2V0X2RlY2VzX2dyYW5kX2VzdCIsIm9wdGlvbnMiOnsiaWQiOiJlNmRlZjNmMi0xNDM0LTRhNzItYmQ0YS00MmFlZjA5MmYyZjkifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InNwbGluZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6Im5haXNzYW5jZXMiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjMjZBRDI2In0seyJhbGlnbk1vbnRoIjp0cnVlLCJ0eXBlIjoic3BsaW5lIiwiZnVuYyI6IlNVTSIsInlBeGlzIjoiZGVjZXMiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjMEI3MkI1In1dLCJ4QXhpcyI6ImFubmVlIiwibWF4cG9pbnRzIjo1MCwic29ydCI6IiJ9XSwidGltZXNjYWxlIjoiIiwiZGlzcGxheUxlZ2VuZCI6dHJ1ZSwiYWxpZ25Nb250aCI6dHJ1ZSwic2luZ2xlQXhpcyI6dHJ1ZX0%3D" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Nombre de naissances et décès Grand Est</div></a>
</div>

<div class="mySlides " data-index = "7">
              <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=bb1211e0-45d1-447b-a75e-6b86d586d018&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6Im5vbWJyZV90b3RhbF9kZV9tYXJpYWdlc19lbnJlZ2lzdHJlcyIsIm9wdGlvbnMiOnsiaWQiOiJiYjEyMTFlMC00NWQxLTQ0N2ItYTc1ZS02Yjg2ZDU4NmQwMTgifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InBpZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6InZhbGV1ciIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6InJhbmdlLVB1QnUiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJhbm5lZSIsIm1heHBvaW50cyI6NTAsInNvcnQiOiIiLCJzZXJpZXNCcmVha2Rvd24iOiIiLCJzZXJpZXNCcmVha2Rvd25UaW1lc2NhbGUiOiIifV0sInRpbWVzY2FsZSI6IiIsImRpc3BsYXlMZWdlbmQiOnRydWUsImFsaWduTW9udGgiOnRydWV9" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=bb1211e0-45d1-447b-a75e-6b86d586d018&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6Im5vbWJyZV90b3RhbF9kZV9tYXJpYWdlc19lbnJlZ2lzdHJlcyIsIm9wdGlvbnMiOnsiaWQiOiJiYjEyMTFlMC00NWQxLTQ0N2ItYTc1ZS02Yjg2ZDU4NmQwMTgifX0sImNoYXJ0cyI6W3siYWxpZ25Nb250aCI6dHJ1ZSwidHlwZSI6InBpZSIsImZ1bmMiOiJTVU0iLCJ5QXhpcyI6InZhbGV1ciIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6InJhbmdlLVB1QnUiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJhbm5lZSIsIm1heHBvaW50cyI6NTAsInNvcnQiOiIiLCJzZXJpZXNCcmVha2Rvd24iOiIiLCJzZXJpZXNCcmVha2Rvd25UaW1lc2NhbGUiOiIifV0sInRpbWVzY2FsZSI6IiIsImRpc3BsYXlMZWdlbmQiOnRydWUsImFsaWduTW9udGgiOnRydWV9" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Nombre total de mariages enregistrés par région Grand Est&nbsp;</div></a>
</div>

<div class="mySlides " data-index = "8">
              <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=1d9882c1-a15d-45f6-8990-3992e97fde4f&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImRlbWFuZGV1cnNfZF9lbXBsb2lfaW5zY3JpdHNfYV9wb2xlX2VtcGxvaV9mZW1tZXMiLCJvcHRpb25zIjp7ImlkIjoiMWQ5ODgyYzEtYTE1ZC00NWY2LTg5OTAtMzk5MmU5N2ZkZTRmIn19LCJjaGFydHMiOlt7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJhcmVhc3BsaW5lcmFuZ2UiLCJmdW5jIjoiQ09VTlQiLCJ5QXhpcyI6InZhbGV1ciIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6IiMwQjcyQjUiLCJjaGFydHMiOlt7ImZ1bmMiOiJNSU4iLCJ5QXhpcyI6InZhbGV1ciJ9LHsiZnVuYyI6Ik1BWCIsInlBeGlzIjoidmFsZXVyIn1dfV0sInhBeGlzIjoiYW5uZWUiLCJtYXhwb2ludHMiOjUwLCJzb3J0IjoiIn1dLCJ0aW1lc2NhbGUiOiIiLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlfQ%3D%3D" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=1d9882c1-a15d-45f6-8990-3992e97fde4f&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImRlbWFuZGV1cnNfZF9lbXBsb2lfaW5zY3JpdHNfYV9wb2xlX2VtcGxvaV9mZW1tZXMiLCJvcHRpb25zIjp7ImlkIjoiMWQ5ODgyYzEtYTE1ZC00NWY2LTg5OTAtMzk5MmU5N2ZkZTRmIn19LCJjaGFydHMiOlt7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJhcmVhc3BsaW5lcmFuZ2UiLCJmdW5jIjoiQ09VTlQiLCJ5QXhpcyI6InZhbGV1ciIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6IiMwQjcyQjUiLCJjaGFydHMiOlt7ImZ1bmMiOiJNSU4iLCJ5QXhpcyI6InZhbGV1ciJ9LHsiZnVuYyI6Ik1BWCIsInlBeGlzIjoidmFsZXVyIn1dfV0sInhBeGlzIjoiYW5uZWUiLCJtYXhwb2ludHMiOjUwLCJzb3J0IjoiIn1dLCJ0aW1lc2NhbGUiOiIiLCJkaXNwbGF5TGVnZW5kIjp0cnVlLCJhbGlnbk1vbnRoIjp0cnVlfQ%3D%3D&datasetcard=false" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Demandeurs d\'emploi inscrits à Pôle emploi Femmes</div></a>
</div>

<div class="mySlides " data-index = "9">
              <a href="https://grandest.data4citizen.com/visualisation/analyze/?id=a6d16219-5c3d-427c-8b52-14ce5442b33a&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImRlbWFuZGV1cnNfZF9lbXBsb2lfaW5zY3JpdHNfYV9wb2xlX2VtcGxvaV9ob21tZXMiLCJvcHRpb25zIjp7ImlkIjoiYTZkMTYyMTktNWMzZC00MjdjLThiNTItMTRjZTU0NDJiMzNhIn19LCJjaGFydHMiOlt7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJjb2x1bW4iLCJmdW5jIjoiU1VNIiwieUF4aXMiOiJ2YWxldXIiLCJzY2llbnRpZmljRGlzcGxheSI6dHJ1ZSwiY29sb3IiOiIjMDA1Mzk1In1dLCJ4QXhpcyI6ImFubmVlIiwibWF4cG9pbnRzIjo1MCwic29ydCI6IiJ9XSwidGltZXNjYWxlIjoiIiwiZGlzcGxheUxlZ2VuZCI6dHJ1ZSwiYWxpZ25Nb250aCI6dHJ1ZX0%3D" target="_blank" >

                  <iframe src="https://grandest.data4citizen.com/visualisation/frame/analyze/?id=a6d16219-5c3d-427c-8b52-14ce5442b33a&dataChart=eyJxdWVyaWVzIjpbeyJjb25maWciOnsiZGF0YXNldCI6ImRlbWFuZGV1cnNfZF9lbXBsb2lfaW5zY3JpdHNfYV9wb2xlX2VtcGxvaV9ob21tZXMiLCJvcHRpb25zIjp7ImlkIjoiYTZkMTYyMTktNWMzZC00MjdjLThiNTItMTRjZTU0NDJiMzNhIn19LCJjaGFydHMiOlt7ImFsaWduTW9udGgiOnRydWUsInR5cGUiOiJwaWUiLCJmdW5jIjoiU1VNIiwieUF4aXMiOiJhbm5lZSIsInNjaWVudGlmaWNEaXNwbGF5Ijp0cnVlLCJjb2xvciI6InJhbmdlLVB1QnUiLCJwb3NpdGlvbiI6ImNlbnRlciJ9XSwieEF4aXMiOiJhbm5lZSIsIm1heHBvaW50cyI6NTAsInNvcnQiOiIiLCJzZXJpZXNCcmVha2Rvd24iOiIiLCJzZXJpZXNCcmVha2Rvd25UaW1lc2NhbGUiOiIifV0sInRpbWVzY2FsZSI6IiIsImRpc3BsYXlMZWdlbmQiOnRydWUsImFsaWduTW9udGgiOnRydWV9" width="1500" height="645" frameBorder="0" width = 100% height =645></iframe>      

  <div class="text">Demandeurs d\'emploi inscrits à Pôle emploi Hommes</div></a>
</div>

</div>
<br>
<div>
<a class="prev" style="float:left; margin-top:-500px !important;margin-left: 100px;" onclick="plusSlides(-1)">&#10094;</a>
<a class="next" id="next" style="float:right; margin-top:-500px !important;margin-right: 100px;" onclick="plusSlides(1)">&#10095;</a>


</div>
<br>
<div style="text-align:center; margin-top: -20px !important;">
  <span class="dot" onclick="currentSlide(1)"></span> 
  <span class="dot" onclick="currentSlide(2)"></span> 
  <span class="dot" onclick="currentSlide(3)"></span> 
  <span class="dot" onclick="currentSlide(4)"></span> 
  <span class="dot" onclick="currentSlide(5)"></span> 
  <span class="dot" onclick="currentSlide(6)"></span> 
  <span class="dot" onclick="currentSlide(7)"></span> 
  <span class="dot" onclick="currentSlide(8)"></span> 
  <span class="dot" onclick="currentSlide(9)"></span> 
</div> 

    </div>

    </div>



<link rel="stylesheet" href="https://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.9.1.js"></script>
  <script src="https://code.jquery.com/ui/1.9.2/jquery-ui.js"></script> 
  <script src="http://code.jquery.com/jquery-latest.min.js"></script>
  <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery.slides.min.js"></script>


<script>




var slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
  showSlides(slideIndex += n);
}

function currentSlide(n) {
  console.log(" hh ");
  showSlides(slideIndex = n);
}

function showSlides(n) {
  var i;
  var slides = document.getElementsByClassName("mySlides");
  var dots = document.getElementsByClassName("dot");
  if (n > slides.length) {slideIndex = 1}    
  if (n < 1) {slideIndex = slides.length}
  for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";  
  }
  for (i = 0; i < dots.length; i++) {
      dots[i].className = dots[i].className.replace(" active", "");
  }
  slides[slideIndex-1].style.display = "block";  
  dots[slideIndex-1].className += " active";
}
$(document).ready(function() {
var timer = null;
timer = setInterval(function() {
  $("#next").trigger("click");
}, 2500);
  

  });

</script>
<script>
$("head").append("<link href=\"https://code.jquery.com/ui/1.9.2/themes/base/jquery-ui.css\" rel=\"stylesheet\">");
</script>

<script src="https://code.jquery.com/jquery-1.9.1.js"></script>
<script src="https://code.jquery.com/ui/1.9.2/jquery-ui.js"></script> 
<script src="http://code.jquery.com/jquery-latest.min.js"></script>
<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery.slides.min.js"></script>
            
            '
          
        ],
    );
    return $element;
    
  }

}

