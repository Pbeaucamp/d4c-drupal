<?php

namespace Drupal\ckan_admin\Utils;

class MapEmpriseHelper{

    const DATABASE_TABLE = 'd4c_dataset_emprise';

    public static function getDatasetEmprise($datasetid = null) {
        $query = \Drupal::database()->select(self::DATABASE_TABLE, "emprise");
		$query->fields('emprise', [
            'dataset_id',
			'shape',
            'coordinates'
		]);
        if ($datasetid != null) {
            $query->condition('dataset_id', $datasetid);
        }

		$prep = $query->execute();
        $data = $prep->fetchAll();

		if (empty($data)) {
			return null;
		}

        if ($datasetid != null) {
            $mapEmprise = [
                'datasetId' => $data[0]->dataset_id,
                'shape' => $data[0]->shape,
                'coordinates' => $data[0]->coordinates
            ];
    
            return $mapEmprise;
        }
        else {
            $mapEmprises = [];
            foreach ($data as $mapEmprise) {
                $mapEmprises[] = [
                    'datasetId' => $mapEmprise->dataset_id,
                    'shape' => $mapEmprise->shape,
                    'coordinates' => $mapEmprise->coordinates
                ];
            }
    
            return $mapEmprises;
        }
    }

    public static function setDatasetEmprise($datasetid, $mapEmprise) {
        $query = \Drupal::database()->upsert(self::DATABASE_TABLE)
            ->fields([
                'dataset_id',
                'shape',
                'coordinates'
            ])
            ->values([
                $datasetid,
                $mapEmprise['shape'],
                $mapEmprise['coordinates']
            ])
            ->key('dataset_id');
        $query->execute();
    }

    public static function deleteDatasetEmprise($datasetid){
        $query = \Drupal::database()->delete(self::DATABASE_TABLE);
		$query->condition('dataset_id', $datasetid);
        $query->execute();
    }
}