<?php
namespace Drupal\ckan_admin\Utils;
use Drupal\Core\Controller\ControllerBase;

   class MapManager{
	
	static function insertMap($data)
	{

	    if($data["id"] == 0){
            
            $query = \Drupal::database()->insert('anfr_map');
            $query->fields([
                'id',
                'idUser',
                'name',
                'lastDate',
                'layers'
            ]);
            $query->values([
                $data["id"],
                $data["idUser"],
                $data["name"],
                date('Y-m-d H:i:s'),
                $data["layers"]
            ]);

            $query->execute();
        } else {
            
            $query = \Drupal::database()->update('anfr_map');
            $query->fields([
                'lastDate' => date('Y-m-d H:i:s'),
                'layers' => $data["layers"]
            ]);
            $query->condition('id', $data["id"]);
            $query->execute();
        }
        

	    return "ok";		
	}

	static function getMapsbyID($data)
	{
	
	
		$query = \Drupal::database()->select('anfr_map', 'bcp');

        $query->fields('bcp', [
            'id',
            'idUser',
            'name',
            'lastDate',
            'layers'
        ]);

	
        $query->condition('idUser',$data["idUser"]);
	$prep=$query->execute();
	//$prep->setFetchMode(PDO::FETCH_OBJ);
        $res= array();
	while ($enregistrement = $prep->fetch()) {
				array_push($res, $enregistrement);
			}		
		return $res;	

	}

	static function deleteMap($data)
	{

            $query = \Drupal::database()->delete('anfr_map');
 	    $query->condition('id', $data["id"]);

            

            $query->execute();

	    return "ok";		
	}


}