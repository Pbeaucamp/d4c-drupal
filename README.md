# d4c-drupal CKAN_ADMIN 

---

Ceci est la partie serveur de Drupal, un module spécifique nommé ckan_admin.

Il offre plusieurs fonctionnalités : 

1. Gère une interface de configuration Drupal pour le moissonnage de jeux de données
2. Gère le routage du site
3. Gère quelques interfaces clients en tant que Controller.php
4. Gère l'API D4C

---

## Récupération du repository Git

Se positionner dans **{Durpal}/web/modules/contrib/**.

Supprimer le module ckan_admin si existant.

Executer la commande : `git clone http://gitlab.bpm-conseil.com/data4citizen/d4c-drupal.git ckan_admin`

---

## Configuration

Il faut inscrire les informations adéquates dans le fichier config.json à la racine du module.

Il est impératif d'indiquer, l'url ckan, la clé api ckan, l'url cluster et le fichier css client.

---

## Outils pour l'export de jeux de données

- Ogr2ogr (permet la conversion kml et shapefile)
	- `apt-get install gdal` (non nécessaire à priori)
	- `apt-get install gdal-bin`

- Php Zip (permet le zipage du shapefile)
	- `apt-get install php7.0-zip`
	- redémarrer nginx

- Spout (permet l'export xls)
	- se mettre dans le dossier root de Drupal `cd /opt/{drupal}/`
	- `composer require box/spout`
	
- Php SpreadSheets
	- se mettre dans le dossier root de Drupal `cd /opt/{drupal}/`
	- composer require phpoffice/phpspreadsheet
	
	Note: Il faut peut être installer composer pour pouvoir l'utiliser dans Drupal
	https://getcomposer.org/download/ (Suivre Command Line Installation)
	
---

## Configuration Recaptcha

Pour la configuration du recaptcha, il faut se rendre sur https://www.google.com/u/2/recaptcha/admin
L'adminstration pour D4C se fait avec le compte vanillaapps@gmail.com

Il faut ajouter chaque domain (ex: data4citizen.com, example.com, etc)

