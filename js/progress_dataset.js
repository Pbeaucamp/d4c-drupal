$ = jQuery;

function generateTaskUniqueId() {
    var uuid = uuidv4();
    $("#edit-generated-task-id").val(uuid);
}

/**
 * Generate a random UUID to retrieve the task
 */
function uuidv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
}

var showProgressBool = true;

function checkProgress() {
    var generatedId = $("#edit-generated-task-id").val();
    worker(generatedId);
}

function worker(datasetId) {
    $.ajax('/api/taskStatus/' + datasetId, {
        type: 'POST',
        dataType: 'json',

//      Result can be
//      * > UNKNOWN
//      * > CREATE_DATASET
//      * > UPDATE_DATASET
//      * > MANAGE_FILE
//      * > UPLOAD_CKAN
//      * > UPLOAD_DATASTORE
//      * > CREATE_CLUSTER
// 
//      Status can be
//      * > SUCCESS
// 	    * > ERROR
// 	    * > PENDING
        success: function(data) {
            if (showProgressBool && data.action != 'UNKNOWN') {
                showProgress();

                showProgressBool = false;
            }
            else {
                var percentile = 1;
                var message = '';
                var serverMessage = data.message;
                if (data.action == 'CREATE_DATASET') {
                    if (data.status == 'PENDING') {
                        message = 'Le jeu de données est en cours de création.';
                        percentile = 1;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Le jeu de données n\'a pas pu être créé car une erreur est survenue.';
                        percentile = 15;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Le jeu de données a été créé.';
                        percentile = 15;
                    }
                }
                else if (data.action == 'UPDATE_DATASET') {
                    if (data.status == 'PENDING') {
                        message = 'Le jeu de données est en cours de mise à jour.';
                        percentile = 1;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Le jeu de données n\'a pas pu être mis à jour car une erreur est survenue.';
                        percentile = 15;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Le jeu de données a été mis à jour.';
                        percentile = 15;
                    }
                }
                else if (data.action == 'MANAGE_FILE') {
                    if (data.status == 'PENDING') {
                        message = 'Le fichier est en cours de traitement.';
                        percentile = 15;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Le fichier n\'a pas pu être traité car une erreur est survenue.';
                        percentile = 25;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Le fichier a été traité.';
                        percentile = 25;
                    }
                }
                else if (data.action == 'UPLOAD_CKAN') {
                    if (data.status == 'PENDING') {
                        message = 'Le fichier est en cours d\'ajout sur CKAN.';
                        percentile = 25;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Le fichier n\'a pas pu être ajouté sur CKAN.';
                        percentile = 50;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Le fichier a été ajouté sur CKAN.';
                        percentile = 50;
                    }
                }
                else if (data.action == 'UPLOAD_DATASTORE') {
                    if (data.status == 'PENDING') {
                        message = 'Les données sont en cours d\'ajout dans le magasin de données. Cette opération peut prendre plusieurs minutes suivant la taille du fichier.';
                        percentile = 50;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Les données n\'ont pas pu être ajouté dans le magasin de données car une erreur est survenue.';
                        percentile = 90;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Les données ont été ajoutées dans le magasin de données.';
                        percentile = 90;
                    }
                }
                else if (data.action == 'CREATE_CLUSTER') {
                    if (data.status == 'PENDING') {
                        message = 'Les clusters de données sont en cours de création.';
                        percentile = 90;
                    }
                    else if (data.status == 'ERROR') {
                        message = 'Les clusters de données n\'ont pas pu être créé car une erreur est survenue.';
                        percentile = 100;
                    }
                    else if (data.status == 'SUCCESS') {
                        message = 'Les clusters de données ont été générés.';
                        percentile = 100;
                    }
                }
                console.log("Percentile " + percentile + " with server message = " + serverMessage + " and message = " + message);
                updateProgress(percentile, serverMessage, message);
            }
        },
        complete: function() {
            // Schedule the next request when the current one's complete
            setTimeout(worker.bind(null, datasetId), 1000);
        }
    });
}