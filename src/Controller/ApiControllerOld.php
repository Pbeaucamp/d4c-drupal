<?php
namespace Drupal\ckan_admin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 
 This file uses a library under MIT Licence :

ods-widgets -- https://github.com/opendatasoft/ods-widgets
Copyright (c) 2014 - Opendatasoft

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

 */
class ApiControllerOld extends ControllerBase {

	/**
	 * Returns a simple page.
	 *
	 * @return array
	 *   A simple renderable array.
	 */
	public function myPage() {

		$config = \Drupal::service('config.factory')->getEditable('ckan_admin.organisationForm');

		$idUser = 0;
    	if(\Drupal::currentUser()->isAuthenticated()){
        		$idUser = \Drupal::currentUser()->id();
    	}

		

		$element = array(
				'example one' => [
						'#type' => 'inline_template',
						'#template' => '<body class="container-fluid">
						
<div class="d4c-box ng-scope" ng-app="bpm.explore.api.console">
                
        <div ng-controller="ConsoleCtrl" class="ng-scope">
            <div class="spinner spinnerWrapper ng-hide" ng-show="pendingRequests.length"><div class="spinner" aria-role="progressbar" style="position: relative; width: 0px; z-index: 2000000000; left: 0px; top: -6px;"><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-0-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(0deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-1-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(27deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-2-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(55deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-3-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(83deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-4-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(110deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-5-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(138deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-6-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(166deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-7-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(193deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-8-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(221deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-9-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(249deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-10-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(276deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-11-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(304deg) translate(9px, 0px); border-radius: 1px;"></div></div><div style="position: absolute; top: -1px; opacity: 0.25; animation: opacity-60-25-12-13 1s linear infinite;"><div style="position: absolute; width: 12px; height: 3px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center 0px; transform: rotate(332deg) translate(9px, 0px); border-radius: 1px;"></div></div></div>
        	</div>
            <!-- ngRepeat: service in services --><div ng-class="{\'service-box\': true, \'active\': activeBox == service}" ng-repeat="service in services1" class="ng-scope service-box">
               <div class="service-header clearfix" ng-click="toggleActiveBox(service)">
                    <span class="service-label ng-binding">Lister les connaissances</span>
                    <div class="service-techinfo">
                        <span class="service-method ng-binding">GET</span>
                        <span class="service-url ng-binding">/api/action/package_search</span>
                    </div>
                </div>
                <div class="d4c-api-console ng-isolate-scope" service="service" api="api[service.id]">
    				<div class="row">
        				<div class="col-sm-12 col-md-6">
            				<form ng-submit="sendRequest(service)" class="ng-pristine ng-valid">
                				<!-- ngRepeat: param in service.urlParameters -->

                				<!-- ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param0=service.parameters[0]">
				                    <label for="dataset_search-q" class="d4c-form__label ng-binding">
				                        q
				                    </label>
                    				<div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param0.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param0.multiple" class="d4c-form__vertical-controls ng-scope">
				                                <!-- ngSwitchWhen: true -->
				                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="dataset_search-q" delayed-apply-model="api.parameters[param0.name]" placeholder="" ng-readonly="param0.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
				                            </div><!-- end ngSwitchWhen: -->
				                        </div>
	                        			<span class="d4c-form__help-text ng-binding" ng-show="param0.helptext">Requête Solr"</span>
                    				</div>
                				</div><!-- ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param1=service.parameters[1]">
				                    <label for="dataset_search-fq" class="d4c-form__label ng-binding">
				                        fq
				                    </label>
                    				<div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param1.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param1.multiple" class="d4c-form__vertical-controls ng-scope">
				                                <!-- ngSwitchWhen: true -->
				                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="dataset_search-fq" delayed-apply-model="api.parameters[param1.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
				                            </div><!-- end ngSwitchWhen: -->
				                        </div>
	                        			<span class="d4c-form__help-text ng-binding" ng-show="param1.helptext">Tout filtre à appliquer</span>
                    				</div>
                				</div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param2=service.parameters[2]">
				                    <label for="dataset_search-sort" class="d4c-form__label ng-binding">
				                        sort
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param2.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param2.multiple" class="d4c-form__vertical-controls ng-scope">
				                                <!-- ngSwitchWhen: true -->
				                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="dataset_search-sort" delayed-apply-model="api.parameters[param2.name]" placeholder="" ng-readonly="param2.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
				                            </div><!-- end ngSwitchWhen: -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param2.helptext">Critère de tri (champ ou -champ)</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param3=service.parameters[3]">
				                    <label for="dataset_search-rows" class="d4c-form__label ng-binding">
				                        rows
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param3.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="dataset_search-rows" delayed-apply-model="api.parameters[param3.name]" placeholder="" ng-readonly="param3.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param3.helptext">Nombre de lignes de résultat</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param4=service.parameters[4]">
				                    <label for="dataset_search-start" class="d4c-form__label ng-binding">
				                        start
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param4.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="dataset_search-start" delayed-apply-model="api.parameters[param4.name]" placeholder="" ng-readonly="param4.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param4.helptext">Index du premier résultat renvoyé (utilisé pour la pagination)</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param5=service.parameters[5]">
				                    <label for="dataset_search-facet" class="d4c-form__label ng-binding">
				                        facet
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param5.type" ng-init="api.parameters[param5.name]=\'True\'">
				                            <!-- ngSwitchWhen: boolean --><label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param5.name]" value="True" ng-readonly="param5.readonly">Oui</label>
				                            <label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param5.name]" value="False" ng-readonly="param5.readonly">Non</label>
				                            <!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param5.helptext">Recherche à facette</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param6=service.parameters[6]">
				                    <label for="dataset_search-facet-mincount" class="d4c-form__label ng-binding">
				                        facet.mincount
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param6.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="dataset_search-facet-mincount" delayed-apply-model="api.parameters[param6.name]" placeholder="" ng-readonly="param6.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param6.helptext">Le nombre minimum de facettes devant être incluses dans le résultat</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param7=service.parameters[7]">
				                    <label for="dataset_search-facet-limit" class="d4c-form__label ng-binding">
				                        facet.limit
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param7.type" class="d4c-form__vertical-controls">
				                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="dataset_search-facet-limit" delayed-apply-model="api.parameters[param7.name]" placeholder="" ng-readonly="param7.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param7.helptext">Le nombre maximum de valeurs retournées par les facettes</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param8=service.parameters[8]">
				                    <label for="dataset_search-facet-fields" class="d4c-form__label ng-binding">
				                        facet.fields
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <input type="text" id="dataset_search-facet-fields" delayed-apply-model="api.parameters[param8.name]" placeholder="" ng-readonly="param2.readonly" class="d4c-form__control">
				                        <span class="d4c-form__help-text ng-binding ng-hide" ng-show="param2.helptext">Nom des facettes à activer dans les résultats</span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param9=service.parameters[9]">
				                    <label for="dataset_search-drafts" class="d4c-form__label ng-binding">
				                        include_drafts
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param9.type" ng-init="api.parameters[param9.name]=\'False\'">
				                            <!-- ngSwitchWhen: boolean --><label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param9.name]" value="True" ng-readonly="param9.readonly">Oui</label>
				                            <label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param9.name]" value="False" ng-readonly="param9.readonly">Non</label>
				                            <!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param9.helptext"></span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param10=service.parameters[10]">
				                    <label for="dataset_search-private" class="d4c-form__label ng-binding">
				                        include_private
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param10.type" ng-init="api.parameters[param10.name]=\'False\'">
				                            <!-- ngSwitchWhen: boolean --><label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param10.name]" value="True" ng-readonly="param10.readonly">Oui</label>
				                            <label ng-switch-when="boolean" class="d4c-form__control--radio ng-scope"><input type="radio" ng-model="api.parameters[param10.name]" value="False" ng-readonly="param10.readonly">Non</label>
				                            <!-- end ngSwitchWhen: -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  -->
				                        </div>
				                        <span class="d4c-form__help-text ng-binding" ng-show="param10.helptext"></span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.parameters -->

				                <datalist id="autocomplete-facet">
				                    <!-- ngRepeat: item in autocompleteFacet -->
				                </datalist>

				                <div class="d4c-form__group d4c-form__group--horizontal">
				                    <label class="d4c-form__label"></label>
				                    <button type="submit" class="d4c-button d4c-button--primary" translate=""><span class="ng-scope">Envoyer</span></button>
				                </div>
            				</form>
        				</div>
				        <div class="col-sm-12 col-md-6">
				            <div d4c-json-formatter="results" ng-show="results" class="d4c-api-console-page__service-result ng-isolate-scope ng-hide"></div>
				            <div class="d4c-message-box d4c-message-box--error ng-binding ng-hide" ng-show="errors">
				                
				            </div>
				        </div>
    				</div>

				    <code class="d4c-api-console-page__service-url">
				        <a href="/api/action/package_search" target="_blank" class="ng-binding">
				            <i class="fa fa-link"></i> /api/action/package_search
				        </a>
				    </code>
				</div>

            </div><!-- end ngRepeat: service in services --><div ng-class="{\'service-box\': true, \'active\': activeBox == service}" ng-repeat="service in services2" class="ng-scope service-box active">
            	
                <div class="service-header clearfix" ng-click="toggleActiveBox(service)">
                     <span class="service-label ng-binding">Consulter une connaissance</span>
                     <div class="service-techinfo">
                         <span class="service-method ng-binding">GET</span>
                         <span class="service-url ng-binding">' . $config->client->routing_prefix . '/d4c/api/datasets/1.0/DATASETID/</span>
                     </div>
                 </div>
                 <div class="d4c-api-console ng-isolate-scope" service="service" api="api[service.id]">
				    <div class="row">
				        <div class="col-sm-12 col-md-6">
				            <form ng-submit="sendRequest(service)" class="ng-pristine ng-valid">
				                <!-- ngRepeat: param in service.urlParameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param1=service.urlParameters[0]">
				                    <label class="d4c-form__label ng-binding" for="dataset_lookup-url-DATASETID">
				                        DATASETID
				                    </label>
				                    <div class="d4c-form__vertical-controls">
				                        <input type="text" id="dataset_lookup-url-DATASETID" delayed-apply-model="api.urlParameters[param1.name]" placeholder="" ng-readonly="param1.readonly" class="d4c-form__control">
				                        <span class="d4c-form__help-text ng-binding ng-hide" ng-show="param1.helptext"></span>
				                    </div>
				                </div><!-- end ngRepeat: param in service.urlParameters -->

				                <!-- ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" ng-init="param2=service.parameters[0]">
				                    <label for="dataset_lookup-facet" class="d4c-form__label ng-binding">
				                        facet
				                    </label>
				                    <!-- <div class="d4c-form__vertical-controls">
				                        <div ng-switch="" on="param2.type" class="d4c-form__vertical-controls"> -->
				                            <!-- ngSwitchWhen: integer -->
				                            <!-- ngSwitchWhen: hierarchical -->

				                            <!-- ngSwitchDefault:  --><!-- <div ng-switch-default="" ng-switch="" on="param2.multiple" class="d4c-form__vertical-controls ng-scope"> -->
				                                <!-- ngSwitchWhen: true --><!-- <div class="multiple-field ng-scope ng-isolate-scope ng-valid" ng-switch-when="true" id="dataset_lookup-facet" ng-model="api.parameters[param2.name]" ng-readonly="param2.readonly">    -->
				                                	<!-- ngRepeat: item in items track by $index -->   <!-- <div class="input-append">      -->  
				                                		<!-- <form class="ng-pristine ng-valid">     -->      
				                                			<!-- <div class="d4c-form__addon-wrapper">               
				                                				<input type="text" class="d4c-form__control add ng-pristine ng-untouched ng-valid ng-isolate-scope" ng-model="newItem" ng-disabled="d4cDisabled" datalist-values="values" datalist-values-language="valuesLanguage"/>               
				                                				<button class="d4c-button d4c-form__addon" disabled="">                   
				                                					<i class="icon-plus fa fa-plus" aria-hidden="true"></i>               
				                                				</button>           
				                                			</div>     -->
				                                		<!--   </form> -->   
				                                	<!-- </div>
				                                </div> --><!-- end ngSwitchWhen: -->
				                                <!-- ngSwitchDefault:  -->
				                            <!-- </div> --><!-- end ngSwitchWhen: -->
				                      <!--   </div>
				                        <span class="d4c-form__help-text ng-binding ng-hide" ng-show="param2.helptext"></span>
				                    </div> -->
				                    <div class="d4c-form__vertical-controls">
				                        <input type="text" id="dataset_lookup-url-DATASETID" delayed-apply-model="api.parameters[param2.name]" placeholder="" ng-readonly="param2.readonly" class="d4c-form__control">
				                        <span class="d4c-form__help-text ng-binding ng-hide" ng-show="param2.helptext"></span>
				                    </div>
				                </div> <!-- end ngRepeat: param in service.parameters -->

				                <datalist id="autocomplete-facet">
				                    <!-- ngRepeat: item in autocompleteFacet -->
				                </datalist>

				                <div class="d4c-form__group d4c-form__group--horizontal">
				                    <label class="d4c-form__label"></label>
				                    <button type="submit" class="d4c-button d4c-button--primary" translate=""><span class="ng-scope">Envoyer</span></button>
				                </div>
				            </form>
				        </div>
				        <div class="col-sm-12 col-md-6">
				            <div d4c-json-formatter="results" ng-show="results" class="json-result d4c-api-console-page__service-result ng-isolate-scope"></div>
				            <div class="d4c-message-box d4c-message-box--error ng-binding ng-hide" ng-show="errors">
				                
				            </div>
				        </div>
				    </div>

				    <code class="d4c-api-console-page__service-url">
				        <a href="' . $config->client->routing_prefix . '/d4c/api/datasets/1.0/emprise-batie-paris/" target="_blank" class="ng-binding">
				            <i class="fa fa-link"></i> ' . $config->client->routing_prefix . '/d4c/api/datasets/1.0/emprise-batie-paris/
				        </a>
				    </code>
				</div> 
            </div><!-- end ngRepeat: service in services --><div ng-class="{\'service-box\': true, \'active\': activeBox == service}" ng-repeat="service in services3" class="ng-scope service-box">
             	<div class="service-header clearfix" ng-click="toggleActiveBox(service)">
                 <span class="service-label ng-binding">Recherche d\'enregistrements</span>
                 <div class="service-techinfo">
                     <span class="service-method ng-binding">GET</span>
                     <span class="service-url ng-binding">' . $config->client->routing_prefix . '/d4c/api/records/1.0/search//</span>
                 </div>
             </div>
             <div class="d4c-api-console ng-isolate-scope" service="service" api="api[service.id]">
			    <div class="row">
			        <div class="col-sm-12 col-md-6">
			            <form ng-submit="sendCall()" class="ng-pristine ng-valid">
			                <!-- ngRepeat: param in service.urlParameters -->

			                <!-- ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-dataset" class="d4c-form__label ng-binding">
			                        dataset
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-dataset" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">ID de la connaissance</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-q" class="d4c-form__label ng-binding">
			                        q
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-q" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Requête en texte intégral</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-lang" class="d4c-form__label ng-binding">
			                        lang
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-lang" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Code langue de 2 lettres</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-rows" class="d4c-form__label ng-binding">
			                        rows
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="records_search-rows" delayed-apply-model="api.parameters[param.name]" placeholder="10" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Nombre de lignes de résultat (10 par défaut)</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-start" class="d4c-form__label ng-binding">
			                        start
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer --><input ng-switch-when="integer" type="number" id="records_search-start" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--small ng-scope"><!-- end ngSwitchWhen: -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Index du premier résultat renvoyé (utilisé pour la pagination)</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-sort" class="d4c-form__label ng-binding">
			                        sort
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-sort" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Critère de tri (champ ou -champ)</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-facet" class="d4c-form__label ng-binding">
			                        facet
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true --><div class="multiple-field ng-scope ng-isolate-scope ng-valid" ng-switch-when="true" id="records_search-facet" ng-model="api.parameters[param.name]" ng-readonly="param.readonly">   <!-- ngRepeat: item in items track by $index -->   <div class="input-append">       <form class="ng-pristine ng-valid">           <div class="d4c-form__addon-wrapper">               <input type="text" class="d4c-form__control add ng-pristine ng-untouched ng-valid ng-isolate-scope" ng-model="newItem" ng-disabled="d4cDisabled" datalist-values="values" datalist-values-language="valuesLanguage">               <button class="d4c-button d4c-form__addon" disabled="">                   <i class="icon-plus fa fa-plus" aria-hidden="true"></i>               </button>           </div>       </form>   </div></div><!-- end ngSwitchWhen: -->
			                                <!-- ngSwitchDefault:  -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Nom des facettes à activer dans les résultats</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-refine" class="d4c-form__label ng-binding">
			                        refine
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical --><div class="hierarchical-field d4c-form__vertical-controls ng-scope ng-isolate-scope ng-valid" ng-switch-when="hierarchical" id="records_search-refine" ng-model="api.parameters[param.name]" hierarchy="param.hierarchy" ng-readonly="param.readonly"><div class="field-group d4c-align-horizontal d4c-form__horizontal-controls"><input type="text" class="d4c-form__control ng-scope" remove-field="" placeholder="Nom de la facette" list="autocomplete-facet"><input type="text" class="d4c-form__control ng-scope" remove-field="" placeholder="Valeur"></div></div><!-- end ngSwitchWhen: -->

			                            <!-- ngSwitchDefault:  -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Refinements à prendre en compte</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-exclude" class="d4c-form__label ng-binding">
			                        exclude
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical --><div class="hierarchical-field d4c-form__vertical-controls ng-scope ng-isolate-scope ng-valid" ng-switch-when="hierarchical" id="records_search-exclude" ng-model="api.parameters[param.name]" hierarchy="param.hierarchy" ng-readonly="param.readonly"><div class="field-group d4c-align-horizontal d4c-form__horizontal-controls"><input type="text" class="d4c-form__control ng-scope" remove-field="" placeholder="Nom" list="autocomplete-facet"><input type="text" class="d4c-form__control ng-scope" remove-field="" placeholder="Valeur"></div></div><!-- end ngSwitchWhen: -->

			                            <!-- ngSwitchDefault:  -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Exclusions à prendre en compte</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-geofilter.distance" class="d4c-form__label ng-binding">
			                        geofilter.distance
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-geofilter.distance" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Un point WGS84 et une distance en mètres pour le géopositionnement</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-geofilter.polygon" class="d4c-form__label ng-binding">
			                        geofilter.polygon
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-geofilter.polygon" delayed-apply-model="api.parameters[param.name]" placeholder="" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Un polygone formé par une liste de points WGS84 (un seul polygone pour le moment)</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters --><div class="d4c-form__group d4c-form__group--horizontal ng-scope" >
			                    <label for="records_search-timezone" class="d4c-form__label ng-binding">
			                        timezone
			                    </label>
			                    <div class="d4c-form__vertical-controls">
			                        <div ng-switch="" on="param.type" class="d4c-form__vertical-controls">
			                            <!-- ngSwitchWhen: integer -->
			                            <!-- ngSwitchWhen: hierarchical -->

			                            <!-- ngSwitchDefault:  --><div ng-switch-default="" ng-switch="" on="param.multiple" class="d4c-form__vertical-controls ng-scope">
			                                <!-- ngSwitchWhen: true -->
			                                <!-- ngSwitchDefault:  --><input ng-switch-default="" type="text" id="records_search-timezone" delayed-apply-model="api.parameters[param.name]" placeholder="Europe/Berlin" ng-readonly="param.readonly" class="d4c-form__control d4c-form__control--fluid ng-scope"><!-- end ngSwitchWhen: -->
			                            </div><!-- end ngSwitchWhen: -->
			                        </div>
			                        <span class="d4c-form__help-text ng-binding" ng-show="param.helptext">Le fuseau horaire utilisé pour interpréter les dates et heures dans la requête et les données de la réponse.</span>
			                    </div>
			                </div><!-- end ngRepeat: param in service.parameters -->

			                <datalist id="autocomplete-facet">
			                    <!-- ngRepeat: item in autocompleteFacet -->
			                </datalist>

			                <div class="d4c-form__group d4c-form__group--horizontal">
			                    <label class="d4c-form__label"></label>
			                    <button type="submit" class="d4c-button d4c-button--primary" translate=""><span class="ng-scope">Envoyer</span></button>
			                </div>
			            </form>
			        </div>
			        <div class="col-sm-12 col-md-6">
			            <div d4c-json-formatter="results" ng-show="results" class="d4c-api-console-page__service-result ng-isolate-scope ng-hide"></div>
			            <div class="d4c-message-box d4c-message-box--error ng-binding ng-hide" ng-show="errors">
			                
			            </div>
			        </div>
			    </div>

			    <code class="d4c-api-console-page__service-url">
			        <a href="' . $config->client->routing_prefix . '/d4c/api/records/1.0/search//" target="_blank" class="ng-binding">
			            <i class="fa fa-link"></i> ' . $config->client->routing_prefix . '/d4c/api/records/1.0/search//
			        </a>
			    </code>
			</div>
        </div><!-- end ngRepeat: service in services -->
    </div>
                
</div>				
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/jquery-1.12.0.min.js"></script>
	<script>
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/bootstrap.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/od.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/font-awesome.min.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/anfr.css\" rel=\"stylesheet\">");
			$("head").append("<link href=\"'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/css/v.css\" rel=\"stylesheet\">");
			$("head").append("<base href=\"/anfr/carte\">");

	

	</script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/ol.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/d3.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/underscore-min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/map_bfc.js"></script>
	<script>

			loadGlobalMap("\'. $config->get(\'ckan\').\'");
	</script>
	
			
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/jquery-3.2.1.js"></script>
	<script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/angular.js"></script>

 	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/4cd679879fd2.js"></script>

    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/96e9131c3992.js"></script>

    <script type="text/javascript">
            var app = angular.module("bpm.core.config", []);

            
            app.factory("domainConfig", [function() {
                return {"languages": ["fr"], "explore.reuse": null, "explore.dataset_catalog_separate_languages": null, "explore.disable_analyze": null, "explore.enable_api_tab": false};
            }]);
            

           
        </script>

    <script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/5e09f3e0b946.js"></script>

    <script src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/supported-browsers-message.js" type="text/javascript"></script>

 	<script>
 		
			var mod = angular.module(\'bpm.explore.api.console.config\', [\'bpm.explore.api.console.services\'])
            .factory(\'ServicesDescription\', function(translate, DatasetsSearchParameters/*, DatasetsLookupUrlParameters*/, DatasetsLookupParameters,
        	RecordsSearchParameters) {
                return [
                    {
                        id: \'dataset_search\',
                        label: \'Lister les connaissances\',
                        url: \'/api/action/package_search\',
                        method: \'GET\',
                        parameters: DatasetsSearchParameters
                    },
                    {
                        id: \'dataset_lookup\',
                        label: \'Consulter une connaissance\',
                        url: \'/api/action/package_show\',
                        parameters: DatasetsLookupParameters,
                        method: \'GET\'
                    },
                    {
                        id: \'records_search\',
                        label: \'Recherche d\'enregistrements\',
                        url: fetchPrefix() + \'/d4c/api/records/1.0/search//\',
                        method: \'GET\',
                        parameters: RecordsSearchParameters
                    }
                ];
            });
        
			
 	</script>
 	<script type="text/javascript" src="'. $config->client->routing_prefix . '/sites/default/files/api/portail_anfr/js/18b1e9266e57.js"></script>			
</body>',
						
				],
		);
		return $element;
	}

}

