$ = jQuery;

;(function() {
	var mod = angular.module('d4c.core.config', []);
	
	mod.factory("config", [function() {
		return {
			HOST: ''
		}
	}]);
}());

document.addEventListener('DOMContentLoaded', function () {
	var mapDiv = document.getElementById('mapemprise');

	var appElement = document.querySelector('[ng-app=d4c-widgets]');

	angular.element(appElement).ready(function () {
		var appScope = angular.element(appElement).scope();

		appScope.$on('mapEmpriseShapeChange', function (event, data) {
			mapDiv.setAttribute("map-emprise-shape",data);
			$('#map_emprise_shape').val(data);
			console.log("map-emprise-shape : ",document.getElementById('map_emprise_shape').getAttribute('value'));
		});
		appScope.$on('mapEmpriseCoordinatesChange', function (event, data) {
			mapDiv.setAttribute("map-emprise-coordinates",data);
			$('#map_emprise_coordinates').val(data);
			console.log("map-emprise-coordinates : ",document.getElementById('map_emprise_coordinates').getAttribute('value'));
		});
	});

});