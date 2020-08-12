$ = jQuery;
function readXml(xmlFile){

var xmlDoc;

if(typeof window.DOMParser != "undefined") {
    xmlhttp=new XMLHttpRequest();
    xmlhttp.open("GET",xmlFile,false);
    if (xmlhttp.overrideMimeType){
        xmlhttp.overrideMimeType('text/xml');
    }
    xmlhttp.send();
    xmlDoc=xmlhttp.responseXML;
}
else{
    xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
    xmlDoc.async="false";
    xmlDoc.load(xmlFile);
}
return xmlDoc;
//console.log(xmlDoc);
/*var tagObj=xmlDoc.getElementsByTagName("marker");
var typeValue = tagObj[0].getElementsByTagName("type")[0].childNodes[0].nodeValue;
var titleValue = tagObj[0].getElementsByTagName("title")[0].childNodes[0].nodeValue;*/
}
//open popup export dataset 
function openExportPopup(event,xml=false){

    console.log(xml);
	createPopupExport($("#visibilityModalExport"));

	// get id of current dataset
	let id = event.target.dataset.id;

    console.log(id);
	//get zip file from packageManger.php
	$.ajax('/package/exportdataset/'+id, {
        type: 'GET',
        dataType: "json",
        data: { 'xml': xml },
        cache: true,
        success: function (data) {
            console.log(data);
            // get url of filename 
            let fileName = data.filename;   
            
          
            if(xml) {
                const xmlfile = fileName.split('/');
                var xmltext = readXml(fileName);
                var contentxml = new XMLSerializer().serializeToString(xmltext);
                var pom = document.createElement('a');

                var filename = xmlfile[xmlfile.length - 1];
                var pom = document.createElement('a');
                var bb = new Blob([contentxml], {type: 'text/plain'});

                pom.setAttribute('href', window.URL.createObjectURL(bb));
                pom.setAttribute('download', filename);

                pom.dataset.downloadurl = ['text/plain', pom.download, pom.href].join(':');
                pom.draggable = true; 
                pom.classList.add('dragout');

                pom.click();
            }
            else {
                window.location.href = fileName; 
            }

            updateProgressBarValue();

        },
        error: function (e) {
            console.log("ERROR: ", e);
        },

    });
}
