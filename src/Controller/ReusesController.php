<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\ckan_admin\Utils\Api;

/**
 * Provides route responses for the Example module.
 */
class ReusesController extends ControllerBase {


	public function myPage(Request $request) {


		$config = json_decode(file_get_contents(__DIR__ ."/../../config.json"));
		$api = new Api();
		
		$reuses = $api->getReuses(null, null, null, "online", 1000, 0);
		$html = "";
		foreach($reuses["reuses"] as $reu){
			$urldataset = $config->client->routing_prefix . "/visualisation/?id=" .  $reu["dataset_id"];
			$html .= '<div class="reuse col-md-3 col-sm-6 col-xs-12">
						<div class="thumbnail">
						<a href=' . $reu["url"] . ' target="_blank">
							<img src="' . $reu["image"] . '"/>
							<div class="caption">
								<h2 data-id="' . $reu["id"] .'"> ' . $reu["title"] . ' </h2>
								<p class="data-desc">' . $reu["description"] . '</p>
								<p><span class="titre">Auteur</span><span class="info">' . ($reu["author_url"] != null ? '<a href="' . $reu["author_url"] . '">' . $reu["author_name"] . '</a>' : $reu["author_name"]) . '</span></p>
								<p><span class="titre">Date de publication</span><span class="info">' . date('Y-m-d H:i:s', strtotime($reu["date"])) . '</span></p>
								<p><span class="titre">Type</span><span class="info">' . $reu["type"] . '</span></p>
								<p><span class="titre">Source</span><a href="' . $urldataset . '" target="_blank"><span class="info">' . $reu["dataset_title"] . '</span></a></p>
							</div>
							</a>
						</div>
					</div>';
		}
		
		$element = array(
				'example one' => [
						'#type' => 'inline_template',
						
						'#template' => '<div id ="main" class="widget-opendata">'.
						
		 

        /*'<div id="filter" class="col-md-2 content-body" >
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
               
        </div>'.*/

        '<div class="col-md-12" style="display: flex; flex-direction: column;" >
				<div id="reuses">
					'.$html.'
				</div>
			<div class="row-md-12">
                <nav id="pagination">
                    <ul class="pagination">
                    </ul>
                </nav>
            </div>
		</div>

        

    </div>
    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-3.2.1.js"></script>
    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/bootstrap.min.js"></script>
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="https://unpkg.com/masonry-layout@4.2.2/dist/masonry.pkgd.min.js"></script>
	<script>
			$(".main-container").removeClass("container").removeClass("main-container").css( "margin-top", "-20px" ).css( "margin-bottom", "-45px" );
			
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/bootstrap.custom.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/style.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/css/'.$config->client->css_file.'\" rel=\"stylesheet\">");
			$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.theme.min.css\">");
    		$("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_d4c/js/jquery-ui-1.12.1.custom/jquery-ui.min.css\">");
			setTimeout(function(){$("#reuses").masonry({
				itemSelector: ".reuse",
				percentPosition: true
			});}, 400);
	</script>',
						
				],
		);
		
		return $element;
	}

}

