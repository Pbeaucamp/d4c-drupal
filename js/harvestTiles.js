/*
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
 
 $ = jQuery;
var map;
var timestamp=0;
$(document).ready(function () {
	initMap();
	
});
 
function initMap(json) {
	//alert(json);
	var resolutions = [0.05291677250021167, 0.13229193125052918, 0.19843789687579377, 0.26458386250105836, 0.5291677250021167, 1.3229193125052918, 1.9843789687579376, 2.6458386250105836, 3.3072982812632294, 3.9687579375158752, 6.614596562526459, 13.229193125052918, 19.843789687579378, 26.458386250105836, 33.0729828126323].reverse();					
	//customOptions.crs._scales = [125000, 100000, 75000, 50000, 25000, 15000, 12500, 10000, 7500, 5000, 2000, 1000, 750, 500, 200];					
	var origin = [-35597500, 48953100];	
	var bounds = L.bounds(L.point(926196.8437584117, 6838579.0594), L.point(942696.8439000174, 6853968.9932));//[926196.8437584117, 6838579.0594, 942696.8439000174, 6853968.9932];	
	var customOptions = {scrollWheelZoom: true,
						basemapsList: []
						//,crs: new L.Proj.CRS("EPSG:2154","+proj=lcc +lat_1=49 +lat_2=44 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs", { origin:origin, resolutions:resolutions, bounds:bounds})
						//,crs: L.geoportalCRS.EPSG2154
						//,crs: L.CRS.EPSG4326
						};
					
	var geocoder = L.Control.geocoder({
		placeholder: 'Trouver un lieu...',
		errorMessage: 'Aucun résultat.',
		geocoder: new L.Control.Geocoder.Nominatim({
			serviceUrl: "https://nominatim.openstreetmap.org/",
			geocodingQueryParams: {
				"accept-language": 'fr' || 'en',
				"polygon_geojson": true
			}
		})
	});
	map = new L.D4CMap($("#panel-map")[0], customOptions);
	map.addControl(geocoder);
	//var defaultLoc = MapHelper.getLocationStructure(D4CWidgetsConfig.defaultMapLocation);
	map.setView([46.53,2.80], 4);
	//map.setView([45.755657,4.831719], 11);
	return map;
}

 
 (function($, Drupal, drupalSettings) {
    Drupal.behaviors.vShop = {
        attach: function (context, settings) {

            var loadVar = function(event, request, settings) {     
				if(settings != undefined && settings.extraData != undefined && settings.extraData._triggering_element_name == "visu" && event.timeStamp != timestamp){
					$("#panel-map").css("display","block");
					var varArr = JSON.parse(drupalSettings.variable);
					timestamp = event.timeStamp;
					//console.log(varArr);
					//map.clearLayers();
					//var c = 0;
					//map.eachLayer(function(l){
					//	if("basemapId" in l){
					//		c++;
					//	}
					//});
					//if(varArr.length > 0 && c < varArr.length){
					//	
					//var all2154 = true;
					map.eachLayer(function (layer) {
						/*if(layer["crs"] != "EPSG:2154"){
							all2154 = false;
						}*/
						map.removeLayer(layer);
					});
					map._controlCorners.bottomleft.innerHTML = "";
					
					map._setTilesProvider(varArr, undefined, undefined, undefined, undefined, undefined, true);
					//}
					try{
						var bbox = JSON.parse(drupalSettings.maxBounds)
						if(bbox != null){
							var corner1 = L.latLng(bbox[0], bbox[1]),
							corner2 = L.latLng(bbox[2], bbox[3]),
							bounds = L.latLngBounds(corner1, corner2);
							map.setMaxBounds(bounds);
							map.fitBounds(bounds);
						}
					} catch(error){}
				}
                
			};

            $( document ).ready(loadVar);
            $( document ).ajaxComplete(loadVar);
        }
    };
})(jQuery, Drupal, drupalSettings);