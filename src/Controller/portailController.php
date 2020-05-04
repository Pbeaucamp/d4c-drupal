<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class portailController extends ControllerBase {

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
					'#template' => '<div id ="main" class="widget-opendata">
						
		 

        <div id="filter" class="col-md-2 content-body" >
            <h1> <span id="nb_jeux">0</span> Jeux de données</h1>
			<input id="input-tag" type="text" class="hidden-filter">
                                						
			<div class="form-group">
				<label for="sel1">Trier par:</label>
				<select class="form-control" id="sel1">
					<option value="null" selected></option>
					<!-- <option value="date">Date modification</option> -->
					<option value="alpha">Ordre alphabétique</option>
					<option value="alpha_reverse">Ordre anti alphabétique</option>
					<option value="date_recent">Récemment modifiés</option>
					<option value="date_old">Anciennement modifiés</option>
					<option value="imported_recent">Récemment importés</option>
					<option value="imported_old">Anciennement importés</option>
					<option value="enregistrement_plus">Le + d\'enregistrement</option>
					<option value="enregistrement_minus">Le - d\'enregistrement</option>
					<option value="telechargement_plus">Le + de téléchargements</option>
					<option value="telechargement_minus">Le - de téléchargements</option>
					<option value="populaire_plus">Les + populaires</option>
					<option value="populaire_minus">Les - populaires</option>
					<option value="producteur">Producteur</option>
					<!-- <option value="granularite">Echelle territoriale</option>
					<option value="reutilisation">Réutilisations</option> -->
				</select>
			</div>

			<h2>Filtres actifs <span id="reset-filters">Tout effacer</span></h2>
			
			<ul class="jetons">
						

			</ul>
			
			<h2> Filtres </h2>
			
			<form id="search-form">
				<div class="input-group" id="barreRecherche">
					<input id="search_bar" type="text"  class="form-control" aria-label="recherche" placeholder="Rechercher un jeu de données...">
					<div class="input-group-btn">
						<button class="btn btn-default" type="submit">
						<i class="glyphicon glyphicon-search"></i>
						</button>
					</div>
				</div>
				
			</form> 
			<h3> Visualisations</h3>
			<ul id="list-visu" class="list-group">
			
			</ul>
			
			<h3> Producteurs</h3>
			<ul id="list-producteur" class="list-group">
			
			</ul>
			<input id="input-producteur" type="hidden" class="hidden-filter">
			<input id="input-format" type="hidden" class="hidden-filter">
			<!--<h3>Echelle territoriale</h3>
			<ul id="list-granularite" class="list-group">
			</ul>
			<input id="input-granularite" type="hidden" class="hidden-filter">-->

			<!-- <h3>Formats ressources</h3>
			<ul id="list-format" class="list-group">

			</ul>
			<input id="input-format" type="hidden" class="hidden-filter">
			-->
			<h3>Mots Clés</h3>
			<ul id="list-tag" class="list-group">

			</ul>
			
			<h3>Thèmes</h3>
			<ul id="list-theme" class="list-group">
			<input id="input-theme" type="hidden" class="hidden-filter">
			</ul>
			
			<h2>Télécharger le catalogue</h2>
			<ul id="list-cat" class="list-group">
				<li class="list-item" data-cat="csv"><i class="fa fa-file" aria-hidden="true"></i>CSV <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
				<li class="list-item" data-cat="xls"><i class="fa fa-file" aria-hidden="true"></i>XLS <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
				<li class="list-item" data-cat="json"><i class="fa fa-file" aria-hidden="true"></i>JSON <span class="number_element"><i class="fa fa-download" aria-hidden="true"></i></span></li>
			</ul>
               
        </div>

        <div class="col-md-10" style="
		display: flex;
		flex-direction: column;" >
			
				<div id="datasets">
					 
				</div>
			<div class="row-md-12">
                <nav id="pagination">
                    <ul class="pagination">
                    </ul>
                </nav>
            </div>
			<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" version="1.1" class="d4cwidget-spinner d4cwidget-spinner--svg hidden">    <rect x="0" y="0" width="30" height="30" class="d4cwidget-spinner__cell-11"></rect>    <rect x="35" y="0" width="30" height="30" class="d4cwidget-spinner__cell-12"></rect>    <rect x="70" y="0" width="30" height="30" class="d4cwidget-spinner__cell-13"></rect>    <rect x="0" y="35" width="30" height="30" class="d4cwidget-spinner__cell-21"></rect>    <rect x="35" y="35" width="30" height="30" class="d4cwidget-spinner__cell-22"></rect>    <rect x="70" y="35" width="30" height="30" class="d4cwidget-spinner__cell-23"></rect>    <rect x="0" y="70" width="30" height="30" class="d4cwidget-spinner__cell-31"></rect>    <rect x="35" y="70" width="30" height="30" class="d4cwidget-spinner__cell-32"></rect>    <rect x="70" y="70" width="30" height="30" class="d4cwidget-spinner__cell-33"></rect></svg>
        </div>

        

    </div>
    <script src="/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
    <script src="/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
	<script src="/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="/sites/default/files/api/portail_d4c/js/script_portail.js"></script>
	<script>
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
			
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\">");
    		$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\">");
            $("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"/sites/default/files/api/portail_d4c/css/font-awesome.min.css\">");
			//$("head").append("<meta http-equiv=\"Content-Security-Policy\" content=\"upgrade-insecure-requests\">");
	</script>
						
						'
					
				],
		);
		return $element;
		
	}

}

