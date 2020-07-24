<?php

namespace Drupal\ckan_admin\Utils;
use Drupal\ckan_admin\Utils\Api;
use Drupal\ckan_admin\Utils\HarvestManager;
use Drupal\file\Entity\File;
use Drupal\ckan_admin\Utils\Logger;
use ZipArchive;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\Writer\Csv;


class ResourceManager {

	function createDataset($datasetName, $title, $description, $licence, $organization, $isPrivate, $tags, $extras, $resources) {
		Logger::logMessage("Create new dataset with name '" . $datasetName . "'");
	
		$urlRes = $this->urlCkan . "/dataset/" . $datasetName;
		$newData = ["name" => $datasetName,
			"title" => $title,
			"private" => $isPrivate,
			"author" => "",
			"author_email" => "",
			"maintainer" => "",
			"maintainer_email" => "",
			"license_id" => $licence,
			"notes" => $description,
			"url" => $urlRes,
			"version" => "",
			"state" => "active",
			"type" => "dataset",
			"resources" => [],
			"tags" => $tags,
			"extras" => $extras,
			"relationships_as_object" => [],
			"relationships_as_subject" => [],
			"groups" => [],
			"owner_org" => $organization,
		];
		
		$coll = array('0'=>'0', '1'=>'');
			
		$datasetId = $this->saveData($newData, $coll);
		$datasetId = $datasetId[1];

		Logger::logMessage("New dataset has been saved with id '" . $datasetId . "'");

		Logger::logMessage("Managing resources");
		if (isset($resources[0]) && !empty($resources[0])) {
			manageFile($resources[0]);
		}

		
		return $datasetId;
	}

	function manageFiles($datasetId, $resourceUrl, $filesDirectory, $encoding) {
		Logger::logMessage("Managing files in '" . $filesDirectory . "'");

		// $files = scandir($filesDirectory);
		$files = $this->getDirContents($filesDirectory);
		foreach($files as $file) {
			$csv = $this->manageFile($datasetId, $resourceUrl, $file, $encoding);
			if ($csv != null) {
				return $csv;
			}
		}
	}

	function getDirContents($dir, &$results = array()) {
		$files = scandir($dir);
	
		foreach ($files as $key => $value) {
			$path = realpath($dir . DIRECTORY_SEPARATOR . $value);
			if (!is_dir($path)) {
				$results[] = $path;
			}
			else if ($value != "." && $value != "..") {
				$this->getDirContents($path, $results);
				$results[] = $path;
			}
		}
	
		return $results;
	}

	function manageFile($datasetId, $generateColumns, $isUpdate, $resourceId, $file, $encoding) {
		$file = File::load($file);
		$file->setPermanent();
		$file->save();

		$fileName = parse_url($file->url());

		$resourceUrl = $file->url();
		$resourceUrl = str_replace('http:', 'https:', $resourceUrl);
	}

	function manageFile($datasetId, $generateColumns, $isUpdate, $resourceId, $file, $encoding) {
		$api = new Api;
		$root='/home/user-client/drupal-d4c/';

		Logger::logMessage("Manage resource file");


		// Getting filename

		// To rewrite

		$host = $fileName[host];
		$fileName = $fileName[path];
		$filePath = $fileName;

		$fileName = strtolower($fileName);
		$fileName = urldecode($fileName);
		$fileName = $this->nettoyage2($fileName);
		$fileName = explode("/", $fileName);
		$fileName = $fileName[(count($fileName)-1)];

		$filepathN = urldecode($filepath);
		$filepathN = $this->nettoyage2($filepathN);

		rename($root.''.urldecode($filepath), $root.''.$filepathN); 
		
		$filepath = $filepathN;

		try {
			$filesize = filesize($root.''.$filepath);
		} catch (Exception $e) {
			$filesize = 0;
			error_log('Unable to get file size for ' .$root.''.$filepath);
		}
		// END to rewrite




		Logger::logMessage("Managing file '" . $filePath . "'");

		try {
			$type = $this->extractFormat($filePath);
		} catch (Exception $e) {
			Logger::logMessage("Impossible de récupérer le format du fichier (" . $e->getMessage() . ")");
		}
		
		Logger::logMessage("Found format " . $type);
		if ($type == 'csv') {

			//if files > 50MB we don't do the treatments.
			if ($filesize < 50000000) {
			
				$validataCurl = array();
				array_push($validataCurl, 'https://go.validata.fr/api/v1/validate?schema=https://git.opendatafrance.net/scdl/deliberations/raw/master/schema.json&url=' . $resourceUrl);

				$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
				if ($encoding) {
					Logger::logMessage("Setting encoding to " . $encoding . "\r\n");
					$reader->setInputEncoding($encoding);
				}
				$spreadsheet = $reader->load($root.''.$filepath);
				$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
				$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'

				//We have an issue with number format. This line transform coordinate and it's not good. We comment it for now
				//Maybe we have to do the same for XLS, XLSX
				//$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');

				$existingCols = array();

				// TODO: We should save real column from the file and put it in the dictionnary
					
				if($generateColumns) {
					$spreadsheet->getActiveSheet()->insertNewRowBefore(1, 1);
				}

				$nbColumns = $this->lettersToNumber($highestColumn);
				for($i=1; $i<= $nbColumns; $i++) {
					if ($generateColumns) {
						$label = 'colonne_' . $i;
					}
					else {
						$label = $spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->getValue();
					}

					$label = $this->nettoyage($label);
					if(in_array($label, $existingCols)) {
						$label = $label . $i;
					}
					$existingCols[] = $label;
					
					$spreadsheet->getActiveSheet()->getCell($this->numberToLetters($i) . '1')->setValue($label);
				}
					
				$writer = new Csv($spreadsheet);
				if ($generateColumns) {
					$filepath = str_ireplace('.csv', '_gencol.csv', $filepath);
					$resourceUrl = 'https://' . $host . $filepath;
				}
				$writer->save($root.''.$filepath);
			}



			
			if ($isUpdate) {
	
				Logger::logMessage("Update is not null. We update " . $resourceId . " and push to datastore \r\n");
	
				$resources = [
					"id" => $resourceId,
					"url" => $urlCsv,
					"name" => $name,
					"description" => $description,
					"format" => "csv",
					"clear_upload" => true
				];
				
				$return = $api->updateResourceAndPushDatastore($resources);
				$return = json_decode($return, true);
			}
			else {
				Logger::logMessage("We update " . $datasetId . " and push to datastore \r\n");
	
				$resource = [     
					"package_id" => $datasetId,
					"url" => $urlCsv,
					"description" => '',
					"name" =>$name.".csv",
					"format"=>'csv'
				];
	
				$callUrluptres = $this->urlCkan . "/api/action/resource_create";
				$return = $api->updateRequest($callUrluptres, $resource, "POST");
				$return = json_decode($return, true);
			}

			$resourceId = $this->uploadResourceAndPushToDatastore($datasetId, $fileName, $resourceUrl);
		}
		else if ($type == 'xls' || $type == 'xlsx') {

			//if files > 50MB we don't do the treatments.
			if ($filesize < 50000000) {

				$xls_file = $root . $filepath;
				
				$reader = new Xlsx();
			
				if(explode(".", $fileName)[1]  === 'xls' ||explode(".", $fileName)[1] === 'XLS') {
					$reader = new Xls();
				}
				$reader->setReadDataOnly(true);
				$spreadsheet = $reader->load($xls_file);

				$loadedSheetNames = $spreadsheet->getSheetNames();
				$highestRow = $spreadsheet->getActiveSheet()->getHighestRow(); // e.g. 10
				$highestColumn = $spreadsheet->getActiveSheet()->getHighestColumn(); // e.g 'F'
				$spreadsheet->getActiveSheet()->getStyle('A1:' . $highestColumn . $highestRow)->getNumberFormat()->setFormatCode('###.##');
				$writer = new Csv($spreadsheet);

					
				$csvpath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $root . $filepath);
				$resourceUrl = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $resourceUrl);
				$fileName = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $fileName);
				$filepath = str_replace(array('.xlsx', '.xls', '.XLSX', '.XLS'), array('.csv', '.csv', '.csv', '.csv'), $filepath);

				foreach($loadedSheetNames as $sheetIndex => $loadedSheetName) {
					$writer->setSheetIndex($sheetIndex);
					Logger::logMessage("Saving CSV for sheet at index " . $sheetIndex . " with path '" . $csvpath . "'");
					$writer->save($csvpath);
					break;
				}
			}

			$resourceId = $this->uploadResourceAndPushToDatastore($datasetId, $fileName, $resourceUrl);
		}
		else if ($type == 'zip') {
			$this->manageZip($datasetId, $resourceUrl, $filePath, $encoding);
		}
		else if ($type == 'json' || $type == 'geojson' || $type == 'kml' || $type == 'shp') {
			$csv = $this->manageGeoFiles($type, $resourceUrl, $filePath);

			if ($csv != null) {
				$name = "csv_gen_" . $datasetId . "_" . uniqid();
				Logger::logMessage("Uploading CSV from GeoFile with name '" . $name . "'");
	
				$rootCsv = '/home/user-client/drupal-d4c/sites/default/files/dataset/' . $name . '.csv';
				// $urlCsv = 'https://'.$_SERVER['HTTP_HOST'].'/sites/default/files/dataset/' . $name . '.csv';
	
				file_put_contents($rootCsv, $csv);

				$this->manageFile($datasetId, $generateColumns, $isUpdate, $resourceId, $file, $encoding);
			}
		}
		else {
			Logger::logMessage("We do not process the file '" . $filePath . "'");
		}
	}

	function uploadResourceAndPushToDatastore($datasetId, $fileName, $resourceUrl) {
		$api = new Api();

		$resources = [    
			"package_id" => $datasetId,
			"url" => $resourceUrl,
			"description" => '',
			"name" => $fileName,
		];

		$callUrluptres = $this->urlCkan . "/api/action/resource_create";
		$return = $api->updateRequest($callUrluptres, $resources, "POST");
		$return = json_decode($return, true);      
		return $return["result"]["id"]; 
	}

	function validateData() {
		
   
			// validata
			$optionst = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					'Content-type:application/json',
					'Content-Length: ' . strlen($jsonData),
					'Authorization:  ' . $cle,
				),
			);
        
			if ($form_state->getValue('validata') != "non_valider") {
            
				for($v=0; $v < count($validataCurl); $v++ ){
                
					$curlValid = curl_init($validataCurl[$v]);
					curl_setopt_array($curlValid, $optionst);
					$valid = curl_exec($curlValid);
					curl_close($curlValid);
					$resValidata = json_decode($valid, true);
					//drupal_set_message('<pre>'. print_r($resValidata, true) .'</pre>');
                
					$errorsValid = $resValidata[report][tables][0][errors];

                
					if ($resValidata[report][valid] == false) {
						for ($i = 0; $i < count($errorsValid); $i++) {
							
							drupal_set_message(t(($i + 1) . '. Code:' . $errorsValid[$i][code] . ' | Message:' . $errorsValid[$i][message]), 'warning');
							
							if($i>5){
							   break;
							}
						}
                    } 
					else if ($resValidata[report][valid] == true) {
						drupal_set_message('Les données ont été validées');
					}
                }
			}
	}

	function updateDataset() {
		
                
		$check=false;

		foreach ($dataSet as &$value) {
			if ($value[id] == $data_id) {

				$check=true;
				$datasetName = $value[name];
				$editDataset = $value;
				$extras = array();
				$cout_extras = count($value[extras]);
				$pict = false;
				$pict2 = false;
				$dataset_lies = false;
				$them_t = false;
				$theme_label_ex = false;
				$analyse = false;
				$typeMap_ex = false;
				$overlaysMap_ex = false;
				$dnt_viz_api = false;
				$widget_ex = false;
				$visu_ex = false;
				$date_dataset_ex = false;
				$disableFieldsEmptyEx = false;
				
				if ($cout_extras != 0) {

					$url_pict = '';

					$form_file = $form_state->getValue('img_picto');

					if (isset($form_file[0]) && !empty($form_file[0])) {

						$file = File::load($form_file[0]);
						$file->setPermanent();
						$file->save();
						$url_t = parse_url($file->url());
						$url_pict = $url_t["path"];

						$url_pict = explode("/", $url_pict);
						$url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
						$url_pict = $url_pict[0];
						$url_pict = "/sites/default/files/theme_logo/".$url_pict.".svg";

					} 
					else {
						$url_pict = "d4c-".$form_state->getValue('imgBack');
					}
					
					$form_file = $form_state->getValue('img_backgr');
					if (isset($form_file[0]) && !empty($form_file[0])) {

						$file = File::load($form_file[0]);
						$file->setPermanent();
						$file->save();
						$url_t = parse_url($file->url());
						$url_pict2 = $url_t["path"];

					} 

					for ($j = 0; $j < count($value[extras]); $j++) {
					  
						//$theme_label_ex = false;
						if ($value[extras][$j]['key'] == 'Picto') {
							$pict = true;
							if ($url_pict != '') {
								$value[extras][$j]['value'] = $url_pict;
							}
						}
						
						if ($value[extras][$j]['key'] == 'img_backgr') {
							$del_img = $form_state->getValue('del_img');
							if(isset($del_img)) {
								unset($value[extras]);
								// $j--;
							}
							else {
								$pict2 = true;
								if ($url_pict2 != '') {
									$value[extras][$j]['value'] = $url_pict2;
								}
							}
						}
						

						if ($value[extras][$j]['key'] == 'LinkedDataSet') {
							$dataset_lies = true;
							$value[extras][$j]['value'] = $string_dataset_lies;
						}
						
						if ($value[extras][$j]['key'] == 'dont_visualize_tab') {
							 
							$dnt_viz_api = true;
							$value[extras][$j]['value'] = $dont_visualize_tab;

						}
														
						if ($value[extras][$j]['key'] == 'theme') {
							$them_t = true;
							$value[extras][$j]['value'] = $them;
						}
						
						 if ($value[extras][$j]['key'] == 'default_visu') {
							$visu_ex = true;
							$value[extras][$j]['value'] = $visu;
						}
						
						if ($value[extras][$j]['key'] == 'label_theme') {
							$theme_label_ex = true;
							$value[extras][$j]['value'] = $them_label;
						}
						
						if ($value[extras][$j]['key'] == 'analyse_default') {
							$analyse = true;
							$value[extras][$j]['value'] = $analyse_default;
						}
							
						if ($value[extras][$j]['key'] == 'type_map') {
							$typeMap_ex = true;
							$value[extras][$j]['value'] = $selectedTypeMap;
						}
						
						if ($value[extras][$j]['key'] == 'overlays') {
							$overlaysMap_ex = true;
							$value[extras][$j]['value'] = $selectedOverlays;
						}
						
						if ($value[extras][$j]['key'] == 'widgets') {
							$widget_ex = true;
							$value[extras][$j]['value'] = $widget;
						}
						
						if ($value[extras][$j]['key'] == 'date_dataset') {
							$date_dataset_ex = true;
							$value[extras][$j]['value'] = $dateDataset;
						}

						if ($value[extras][$j]['key'] == 'disable_fields_empty') {
							$disableFieldsEmptyEx  = true;
							$value[extras][$j]['value'] = $disableFieldsEmpty;
						}


					}

				}
			   
				if ($pict == false) {

					$value[extras][count($value[extras])]['key'] = 'Picto';
					$value[extras][count($value[extras]) - 1]['value'] = $url_pict;
				}
				
				if ($pict2 == false) {
					if($url_pict2 || $url_pict2!='' || $url_pict2!=null){
						$value[extras][count($value[extras])]['key'] = 'img_backgr';
						$value[extras][count($value[extras]) - 1]['value'] = $url_pict2;
					}
				}

				if ($dataset_lies == false) {
					$value[extras][count($value[extras])]['key'] = 'LinkedDataSet';
					$value[extras][count($value[extras]) - 1]['value'] = $string_dataset_lies;
				}
				
				if ($dnt_viz_api == false) {
					$value[extras][count($value[extras])]['key'] = 'dont_visualize_tab';
					$value[extras][count($value[extras]) - 1]['value'] = $dont_visualize_tab;
				}


			   
				

				
				if($theme_label_ex==false){
					$value[extras][count($value[extras])]['key'] = 'label_theme';
					$value[extras][count($value[extras]) - 1]['value'] = $them_label;
				}

				if ($them_t == false) {
					$value[extras][count($value[extras])]['key'] = 'theme';
					$value[extras][count($value[extras]) - 1]['value'] = $them; 
				}
				
				if ($visu_ex == false) {
					$value[extras][count($value[extras])]['key'] = 'default_visu';
					$value[extras][count($value[extras]) - 1]['value'] = $visu; 
				}
				
				if ($analyse == false && $analyse_default!='') {
					$value[extras][count($value[extras])]['key'] = 'analyse_default';
					$value[extras][count($value[extras]) - 1]['value'] = $analyse_default; 
				}
				
				if ($typeMap_ex == false && $selectedTypeMap!='') {
					$value[extras][count($value[extras])]['key'] = 'type_map';
					$value[extras][count($value[extras]) - 1]['value'] = $selectedTypeMap; 
				} 
				
				if ($overlaysMap_ex == false && $selectedOverlays!='') {
					$value[extras][count($value[extras])]['key'] = 'overlays';
					$value[extras][count($value[extras]) - 1]['value'] = $selectedOverlays; 
				} 
				
				if ($widget_ex == false && $widget!='') {
					$value[extras][count($value[extras])]['key'] = 'widgets';
					$value[extras][count($value[extras]) - 1]['value'] = $widget; 
				}

				if ($date_dataset_ex == false) {
					$value[extras][count($value[extras])]['key'] = 'date_dataset';
					$value[extras][count($value[extras]) - 1]['value'] = $dateDataset; 
				}
				if ($disableFieldsEmptyEx  == false) {
					$value[extras][count($value[extras])]['key'] = 'disable_fields_empty';
					$value[extras][count($value[extras]) - 1]['value'] = $disableFieldsEmpty; 
				}

				
				
				$value[title] = $title;
				$value[notes] = $description;
				$value[license_id] = $licence;
				$value['private'] = $private;

				//tags//
				
				$tagsFin = array();
				if($tags!=null || $tags!='') {
					$tagsFin = explode(",", $tags);
				}
				for ($j = 0; $j < count($tagsFin); $j++) {
					$tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tagsFin[$j], "name" => $tagsFin[$j], "resources" => $resources];
				} 
				if($tagsData==null){
					$tagsData=array();
				}
				$value["tags"] = $tagsData;
				
				
				//tags end//
				

				$return = $api->updateRequest($callUrl, $value, "POST");
				$return = json_decode($return);
				if ($return->success == true) {
					drupal_set_message('Les données ont été sauvegardées');
					 
				} else {
					 
					
					drupal_set_message(t('les données n`ont pas été ajoutées!'), 'error');
					drupal_set_message("Raison: " . $return->error->message);
				}
				
				$callUrluptOwner = $this->urlCkan . "/api/action/package_owner_org_update";
				$return = $api->updateRequest($callUrluptOwner, ["id" => $data_id, "organization_id" => $organization], "POST");

				
				break;
			}

		}
	
		if($check==false){
			// drupal_set_message(t('id not find'), 'error');
		
		}
	}

	/**
	 * We need to modify and clean dataset name from the user defined title
	 */
	function defineDatasetName($title) {
		$label = $title;
		if(strlen($label) > 95) {
			$label = substr($label, 0, 95);
		}
		return $this->nettoyage($label);
	}

	/**
	 * This function save an uploaded image to be use as an icon
	 */
	function definePicto($imgPicto, $imgBack) {
		if (isset($imgPicto[0]) && !empty($imgPicto[0])) {

			$file = File::load($imgPicto[0]);
			$file->setPermanent();
			$file->save();
			$url_t = parse_url($file->url());
			$url_pict = $url_t["path"];

			$url_pict = explode("/", $url_pict);
			$url_pict = explode(".", $url_pict[(count($url_pict) - 1)]);
			$url_pict = "/sites/default/files/theme_logo/".$url_pict[0].".svg";

			return $url_pict;

		} 
		else {
			return "d4c-" . $imgBack;
		}
	}

	function defineBackground($imgBackground) {
		if (isset($imgBackground[0]) && !empty($imgBackground[0])) {

			$file = File::load($imgBackground[0]);
			$file->setPermanent();
			$file->save();
			$url_t = parse_url($file->url());
			$url_pict = $url_t["path"];

			return $url_pict;
		}
		return null;
	}

	function defineWidget($widget) {
		$widget_html='';
		$hasWidget = false;
        foreach($widget as $key =>$val){
            if ($val[name] != '' && $val[widget] != ''){
				$off = '';  
				if($val[offWidjet] == 1) {
					$off = '<.off.>'; 
				}

				$hasWidget = true;
				$widget_html = $widget_html .$val[name].'<.info.>'.$val[description].'<.info.> '.$val[widget].' '.$off.'<.explode.>';            
			} 
        }
        
        
        $widget = $hasWidget ? substr($widget_html, 0, -11) : null;
	}

	function defineAnalyse($analyseDefault) {
        if (explode("=", $analyseDefault)[0] != 'dataChart') {
            $analyseDefault =  explode("&", $analyseDefault);
               
            foreach($analyseDefault as &$anal){
                if(explode("=", $anal)[0]=='dataChart'){
                    $analyseDefault_f = $anal;
                    break;
                }
                else{
                    $analyseDefault_f = "";
                }
			}  
            
			$analyseDefault =  explode('"', $analyseDefault_f);
			return $analyseDefault[0];
		}
		return $analyseDefault;
	}

	function defineLinkDatasets($linkDatasets) {
		$linkDatasetsStr = '';
		foreach ($linkDatasets as $key => &$val) {
			if ($val[dt] == 1) {
				$linkDatasetsStr = $linkDatasetsStr . ';' . $key;
			}
		}
		
		return substr($linkDatasetsStr, 1);
	}

	function defineTags($tags) {
		$tagsData = array();
		if ($tags == '') {
			$tagsData = [];
		} 
		else {
			$tags = explode(",", $tags);

			for ($j = 0; $j < count($tags); $j++) {
				$tagsData[$j] = ["vocabulary_id" => null, "state" => "active", "display_name" => $tags[$j], "name" => $tags[$j]];
			}
		}
		return $tagsData;
	}

	function defineSecurity($userId, $users) {
		$userlist = array();
		foreach($users as $user){
			$username = $user->get('name')->value;
			$uid = $user->get('uid')->value;
			$uroles = $user->getRoles();
			if($username != "" && (in_array("administrator", $uroles) || $uid == 1)){
				$userlist[] = "*".$uid."*";
			}
		}
		$userlist[] = $userId;
		$userlist = array_unique($userlist);
		if(count($userlist) == 1){
			$userlist = array($userlist);
		}
		return array("roles" => array("administrator"), "users" => $userlist);
	}
	
	function defineExtras($picto, $imgBackground, $linkDatasets, $theme, $themeLabel,
			$selectedTypeMap, $selectedOverlays, $dont_visualize_tab, $widgets, $visu, 
			$dateDataset, $disableFieldsEmpty, $security) {
		$extras = array();

		$extras[count($extras)]['key'] = 'Picto';
		$extras[(count($extras) - 1)]['value'] = $picto;

		if ($imgBackground != null) {
		$extras[count($extras)]['key'] = 'img_backgr';
		$extras[(count($extras) - 1)]['value'] = $imgBackground;
		}
					
		$extras[count($extras)]['key'] = 'LinkedDataSet';
		$extras[(count($extras) - 1)]['value'] = $linkDatasets;

		$extras[count($extras)]['key'] = 'theme';
		$extras[(count($extras) - 1)]['value'] = $theme;

		$extras[count($extras)]['key'] = 'label_theme';
		$extras[(count($extras) - 1)]['value'] = $themeLabel;

		$extras[count($extras)]['key'] = 'type_map';
		$extras[(count($extras) - 1)]['value'] = $selectedTypeMap;

		if ($selectedOverlays != ""){
		$extras[count($extras)]['key'] = 'overlays';
		$extras[(count($extras) - 1)]['value'] = $selectedOverlays;
		}

		$extras[count($extras)]['key'] = 'dont_visualize_tab';
		$extras[(count($extras) - 1)]['value'] = $dont_visualize_tab;

		$extras[count($extras)]['key'] = 'FTP_API';
		$extras[(count($extras) - 1)]['value'] = 'FTP';

		$extras[count($extras)]['key'] = 'widgets';
		$extras[(count($extras) - 1)]['value'] = $widgets;

		$extras[count($extras)]['key'] = 'default_visu';
		$extras[(count($extras) - 1)]['value'] = $visu;

		$extras[count($extras)]['key'] = 'date_dataset';
		$extras[(count($extras) - 1)]['value'] = $dateDataset;

		$extras[count($extras)]['key'] = 'disable_fields_empty';
		$extras[(count($extras) - 1)]['value'] = $disableFieldsEmpty;

		$extras[count($extras)]['key'] = 'edition_security';
		$extras[(count($extras) - 1)]['value'] = json_encode($security);
	}

	/**
	 * Extract the format of a file based on the filename
	 * and assign a type to help managing the file afterward
	 * 
	 * For now files can be of type jpg, jpeg, gif, png, txt, doc, xls, pdf, ppt, pps, odt, ods, odp, csv, json, xls, xlsx, geojson, zip
	 * 
	 */
	function extractFormat($filePath) {
		$format = pathinfo($filePath, PATHINFO_EXTENSION);
		if (strcasecmp($format , 'jpg') == 0) {
			return 'jpg';
		}
		else if (strcasecmp($format , 'jpeg') == 0) {
			return 'jpeg';
		}
		else if (strcasecmp($format , 'gif') == 0) {
			return 'gif';
		}
		else if (strcasecmp($format , 'png') == 0) {
			return 'png';
		}
		else if (strcasecmp($format , 'txt') == 0) {
			return 'txt';
		}
		else if (strcasecmp($format , 'doc') == 0) {
			return 'doc';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'pdf') == 0) {
			return 'pdf';
		}
		else if (strcasecmp($format , 'ppt') == 0) {
			return 'ppt';
		}
		else if (strcasecmp($format , 'pps') == 0) {
			return 'pps';
		}
		else if (strcasecmp($format , 'odt') == 0) {
			return 'odt';
		}
		else if (strcasecmp($format , 'ods') == 0) {
			return 'ods';
		}
		else if (strcasecmp($format , 'odp') == 0) {
			return 'odp';
		}
		else if (strcasecmp($format , 'csv') == 0) {
			return 'csv';
		}
		else if (strcasecmp($format , 'json') == 0) {
			return 'json';
		}
		else if (strcasecmp($format , 'geojson') == 0) {
			return 'geojson';
		}
		else if (strcasecmp($format , 'xls') == 0) {
			return 'xls';
		}
		else if (strcasecmp($format , 'xlsx') == 0) {
			return 'xlsx';
		}
		else if (strcasecmp($format , 'zip') == 0) {
			return 'zip';
		}
		return $format;
	}
	
	function manageZip($datasetId, $resourceUrl, $filePath, $encoding) {
		Logger::logMessage("Manage zip file");
		// $path = pathinfo(realpath($filePath), PATHINFO_DIRNAME);

		$outputDirectory = '/home/user-client/drupal-d4c/sites/default/files/dataset/zip_extraction_'.uniqid().'';

		$zip = new ZipArchive;
		$res = $zip->open($filePath);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo($outputDirectory);
			$zip->close();

			return $this->manageFiles($datasetId, $resourceUrl, $outputDirectory, $encoding);
		}
		else {
			throw new Exception('Le fichier ne peut pas être extrait.');
		}
	}

	/**
	 * Generate a geojson file (if it does not exist) and a CSV file from various type of Geo format
	 * 
	 * $type can be geojson, json, kml and shp
	 * $id of the file
	 * $url of the file
	 * 
	 */
	function manageGeoFiles($type, $resourceUrl, $filePath) {
		Logger::logMessage("Manage " . $type . " file");

		Logger::logMessage("Retrieving file '" . $resourceUrl + "'");
		$fileContent = Query::callSolrServer($resourceUrl);

		if ($type == 'geojson' || $type == 'json'){
			$csv = $this->buildCSVFromGeojson($fileContent);
		}
		else if ($type == 'json') {
			$json_match = false;
			if ($type == 'json') {
				$json = file_get_contents($resourceUrl);
				$json = json_decode($json, true);
				if (isset($json["type"]) && $json["type"] == "FeatureCollection") {
					$json_match = true;
				}
			}

			if ($json_match) {
				$csv = $this->buildCSVFromGeojson($fileContent);
			}
			else {
				Logger::logMessage("No CSV file generated from Geo File");
				$csv = null;
			}
		}
		else if ($type == 'kml' || $type == 'shp') {
			//We create a tmp file in which we write the result and an output file to convert
			// $pathInput = tempnam(sys_get_temp_dir(), 'input_convert_geo_file_');
			// $fileInput = fopen($pathInput, 'w');
			// fwrite($fileInput, $fileContent);
			// fclose($fileInput);

			$scriptPath = '/home/user-client/drupal-d4c/modules/ckan_admin/src/Utils/convert_geo_files_ogr2ogr.sh';

			$typeConvert = 'GEOJSON';
			
			$rootJson='/home/user-client/drupal-d4c/sites/default/files/dataset/gen_'.uniqid().'.geojson';
			$command = $scriptPath." 2>&1 '" . $typeConvert . "' " . $rootJson . " " . $filePath . "";
			$message = shell_exec($command);
			$json = file_get_contents ($rootJson);

			$csv = $this->buildCSVFromGeojson($json);
			unlink ($rootJson);
		}
		else {
			throw new Exception('Le type de fichier ' . $type . ' is not supported.');
		}

		return $csv;
	}
	
	function buildCSVFromGeojson($json) {
		if($json == null || count($json) == 0){
			return "";
		}
		
		Logger::logMessage("Creating CSV from GeoJson");
		
		// If passed a string, turn it into an array
		if (is_array($json) === false) {
			//$json = utf8_encode($json);
			//$json = Export::convert_bad_characters($json);
			$json = json_decode($json, true, 512, JSON_UNESCAPED_UNICODE);
			//$json = json_decode($json, true);
		}
		
		if($json["type"] != "FeatureCollection"){
			return "";
		}
		//construction du csv
		$cols = array();
		$colNames = array();
		$data_csv = array();

		//Previously we were getting only the columns for the first feature but we could miss a lot of informations
		//We now go through all features but we have to check if it not too much time consuming
		$hasShapes = false;
		$index = 0;
		foreach($json["features"] as $feat) {
			foreach($feat["properties"] as $key => $val){

				//We check if the key already exist
				if (!in_array($key, $cols)) {
					Logger::logMessage("Found column " . $key);

					$cols[] = $key;
					$colNames[] = $this->clearGeoProperties($key, $index);
					$index++;
				}
			}
			if ($feat["geometry"]["type"] != "Point") {
				$hasShapes = true;
			}
		}
		if ($hasShapes) {
			$cols[] = "coordinates";
			$cols[] = "geo_shape";
			$colNames[] = "coordinates";
			$colNames[] = "geo_shape";
		}
		else {
			$cols[] = "geo_point_2d";
			$colNames[] = "geo_point_2d";
		}
		
		$rows = array();
		$colsTypes = array();
		foreach($json["features"] as $feat){
			$row = array();
			foreach($cols as $col){
				if($col == "geo_point_2d"){
					$str = json_encode($feat["geometry"]["coordinates"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$val = '"'.$match[2] .",". $match[1].'"';
					$row[] = $val;
				} else if($col == "geo_shape") {
					$str = json_encode($feat["geometry"]);
					preg_match('/\[([-]?[\d|.]+),([-]?[\d|.]+)/i', $str, $match);
					$coord = '"' . $match[2] . "," . $match[1] . '"';

					//We replace " by "" to escape them
					$str = str_replace('"', "\"\"", $str);

					$row[] = $coord;
					$row[] = '"' . $str . '"';
				} else if($col == "coordinates"){
					continue;
				}	
				else {
					$value = $feat["properties"][$col];
					if((isset($colsTypes[$col]) && $colsTypes[$col] == "text") || !$this->isNumericColumn($json,$col)){
						//We replace " by "" to escape them
						$value = str_replace('"', "\"\"", $value);

						$row[] = '"' . $value . '"';
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "text";
						}
					} else {
						$row[] = $value;
						if(!isset($colsTypes[$col])){
							$colsTypes[$col] = "float";
						}
					}
				}
			}
			
			$rows[] = $row;
		}
		
		foreach($rows as &$row){
			if(count($row) < count($cols)){
				$row = array_pad($row, count($cols), "");
			}
			$row = implode($row, ",");
		}
		
		$data_csv = strtolower(implode($colNames, ","));
		array_unshift($rows, $data_csv);
		error_log("count ". (count($rows)));
		$res = implode($rows, "\n");
		return $res;
	}
	
	function isNumericColumn($json, $colName) {
		
		for($i=0; $i< 100; $i++){
			$val = $json["features"][$i]["properties"][$col];
			if( !is_numeric ($val)){
				return false;
			} 
		}
		return true;
	}

	function clearGeoProperties($colName, $index) {
		if(preg_match("/geo_point|coordin|coordon|geopoint|geoPoint|pav_positiont2d|geoloc|wgs84|equgpsy_x|geoban|codegeo|geometry/i",$colName)){
			return "colonne_renomme_" . $index;
		}
		else {
			return $colName;
		}
	}
    
    function saveData($newData, $data) {
        $coll = $data[0];
        
        $api = new Api;
		$callUrlNewData = $this->urlCkan . "/api/action/package_create";
		$return = $api->updateRequest($callUrlNewData, $newData, "POST");   
		$resnew = json_decode($return);

		$idNewData = $resnew->result->id;

		if ($resnew->success == true) {
			drupal_set_message('Les données ont été sauvegardées');
			$idNewData = $resnew->result->id;
		} 
		else if($resnew->error->name[0]=='Cette URL est déjà utilisée.'){
			$coll++;
			
			if($coll==1){
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10){
				$newData[name]=substr($newData[name],0, -3);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -3);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else if($coll>100){
				$newData[name]=substr($newData[name],0, -4);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -4);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>1000){
				$newData[name]=substr($newData[name],0, -5);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -5);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];    
			}
			else if($coll>10000){
				$newData[name]=substr($newData[name],0, -6);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -6);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
			else{
				$newData[name]=substr($newData[name],0, -2);
				$newData[name]=$newData[name].'_'.$coll;
				$newData[title]=substr($newData[title],0, -2);
				$newData[title]=$newData[title].' '.$coll;
				$idNewData = $this->saveData($newData,array('0'=>$coll, '1'=>$idNewData));
				$idNewData = $idNewData[1];
			}
		}
		else {
			//drupal_set_message(print_r($resnew,true));
			drupal_set_message(t('les données n`ont pas été ajoutées!'), 'error');
			drupal_set_message("Raison: " . json_encode($resnew->error->name));

		}

        return array('0'=>$coll, '1'=>$idNewData);
    }

	function deleteDataset($datasetId) {
		$callUrl = $this->urlCkan . "/api/action/package_delete";
            
		$delDataset = [
			"id" => $datasetId,
		];

        $api = new Api;
		$response = $api->updateRequest($callUrl, $delDataset, "POST");
		
		$response = json_decode($response, true);
		if ($response[success] == true) {
			$harvestManager = new HarvestManager;
			$harvestManager->deleteHarvest($datasetId);
			return true;
		}
		else {
			throw new Exception('Impossible de supprimer le dataset (' . $response . ' is not supported.');
		}
	}
	
    function nettoyage( $str, $charset='utf-8') {
		if (!mb_detect_encoding($str, 'UTF-8', true)) {
			$str = iconv("UTF-8", "Windows-1252//TRANSLIT", $str);
		}
		
		//We remove whitespaces at the beggining and end of the label
		$str = trim($str);
		
		$unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
		$str = strtr( $str, $unwanted_array );
		
		$str = str_replace("?", "", $str);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace("%", "", $str);
		$str = str_replace("(", "", $str);
		$str = str_replace(")", "", $str);
		$str = str_replace("*", "", $str);
		$str = str_replace("!", "", $str);
		$str = str_replace("@", "", $str);
		$str = str_replace("#", "", $str);
		$str = str_replace("$", "", $str);
		$str = str_replace("^", "", $str);
		$str = str_replace("&", "", $str);
		$str = str_replace("+", "", $str);
		$str = str_replace(":", "", $str);
		$str = str_replace(">", "", $str);
		$str = str_replace("<", "", $str);
		$str = str_replace('\'', "_", $str);
		$str = str_replace("/", "_", $str);
		$str = str_replace("|", "_", $str);
		$str = strtolower($str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		$str = str_replace("-", "_", $str); 
		return $str;
	}

    function nettoyage2( $str, $charset='utf-8' ) {
		$str = utf8_decode($str);
		$str = utf8_decode($str);

		$str = str_replace("?", "", $str);
		$str = str_replace("`", "_", $str);
		$str = str_replace("'", "_", $str);
		$str = str_replace("-", "_", $str);
		$str = str_replace(" ", "_", $str);
		$str = str_replace("%", "1", $str);
		$str = str_replace("(", "1", $str);
		$str = str_replace(")", "1", $str);
		$str = str_replace("*", "1", $str);
		$str = str_replace("!", "1", $str);
		$str = str_replace("@", "1", $str);
		$str = str_replace("#", "1", $str);
		$str = str_replace("$", "1", $str);
		$str = str_replace("^", "1", $str);
		$str = str_replace("&", "1", $str);
		$str = str_replace("+", "1", $str);
		$str = str_replace(":", "1", $str);
		$str = str_replace(">", "1", $str);
		$str = str_replace("<", "1", $str);
		$str = str_replace("|", "_", $str);
		$str = strtolower($str);
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		$str = str_replace("-", "_", $str);    
		return $str;
	}
	
	function numberToLetters($number) {
		$alphabet = range('A', 'Z');

		$count = count($alphabet);
        if ($number <= $count) {
            return $alphabet[$number - 1];
        }
        $alpha = '';
        while ($number > 0) {
            $modulo = ($number - 1) % $count;
            $alpha  = $alphabet[$modulo] . $alpha;
            $number = floor((($number - $modulo) / $count));
        }
        return $alpha;
	}
}