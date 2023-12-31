<?php

use Drupal\ckan_admin\Utils\Logger;

namespace Drupal\ckan_admin\Utils;

class Tools {

    // Récupérer la liste des paramètres sous forme d'un dictionnaire clé/valeur
    // Ne fonctionne pas si un paramètre existe plusieurs fois (ex.: ?param[]=1&param[]=2)
    // pour récupérer queryString à passer en paramètre de fonction: queryString = url.split("?")[1]
    static function parseQueryString($queryString) {
		$params = array();
        if (!empty($queryString)) {
            $pairs = $queryString[0] == "?" ? explode("&", substr($queryString, 1)) : explode("&", $queryString);
            foreach ($pairs as $pair) {
                $param = explode("=", $pair);
                if ($pair[1]) {
                    //To lowercase
                    $paramName = strtolower($param[0]);
                    $params[$paramName] = urldecode($param[1]);
                }
            }
        }

		return $params;
    }

    // Générer queryString à partir d'un dictionnaire clé/valeur
    // Inverse de parseQueryString()
    static function getQueryString($params) {
        $queryParams = array();
        foreach ($params as $key => $value) {
            $queryParams[] = $key . "=" . $value;
        }
        return implode("&", $queryParams);;
    }

    // Mettre à jour la valeur d'un paramètre de queryString or add it
    static function updateQueryStringParameter($querystring, $param, $value) {
        $params = Tools::parseQueryString($querystring);
        $params[$param] = $value;
        return Tools::getQueryString($params);
    }

    // Générer URL à partir de URL et dictionnaire de paramètres
    function getUrl($url, $params) {
        if (!empty($params)) {
            return $url + '?' + Tools::getQueryString($params);
        }
        return $url;
    }

    // Vérifier la valeur d'un paramètre de queryString
    // function checkQueryParam(querystring, paramName, paramValue) {
    //     const param = getUrlParameter(querystring, paramName);
    //     if (param != paramValue) {
    //         return updateQueryString(
    //             querystring,
    //             paramName,
    //             paramValue
    //         );
    //     }
    //     return querystring;
    // }

    // function checkQueryParams(querystring, newParams) {
    //     for (const paramName in newParams) {
    //         querystring = checkQueryParam(querystring, paramName, newParams[paramName])
    //     }
    //     return querystring;
    // }

    // Remove duplicate params
    // function cleanQueryString(querystring) {
    //     var params = parseQueryString(querystring);
    //     return getQueryString(params);
    // }

}