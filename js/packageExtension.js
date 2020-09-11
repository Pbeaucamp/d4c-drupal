$ = jQuery;
function urlencode(str) {
str = str.replace(/%3A%2F%2F/g,"://");
str = str.replace(/%2F/g,"/");
return str;

}
function convert(str)
{
  str = str.replace(/&amp;/g, "&");
  str = str.replace(/>/g, "&gt;");
  str = str.replace(/</g, "&lt;");
  str = str.replace(/&quot;/g, '"');
  str = str.replace(/&euro;/g, "€");
  str = str.replace(/&oelig;/g, "oe");
  str = str.replace(/&ccedil;/g, "ç");
  str = str.replace(/&egrave;/g, "è");
  str = str.replace(/&eacute;/g, "é");
  str = str.replace(/&ecirc;/g, "ê");
  str = str.replace(/&ecirc;/g, "ê");
  str = str.replace(/&ecirc;/g, "ê");
  str = str.replace(/&euml;/g, "ë");
  str = str.replace(/&igrave;/g, "ì");
  str = str.replace(/&iacute;/g, "í");
  str = str.replace(/&icirc;/g, "î"); 
  str = str.replace(/&iuml;/g, "ï"); 
  str = str.replace(/&ograve;/g, "ò"); 
  str = str.replace(/&ocirc;/g, "ô"); 
  str = str.replace(/&ugrave;/g, "ù"); 
  str = str.replace(/&uacute;/g, "ú"); 
  str = str.replace(/&ucirc;/g, "û"); 
  str = str.replace(/&agrave;/g, "à"); 
  str = str.replace(/&aacute;/g, "á"); 
  str = str.replace(/&acirc;/g, "â"); 
  str = str.replace(/&atilde;/g, "ã"); 
  str = str.replace(/&deg;/g, "°"); 
  return str;
}
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
var tagObj=xmlDoc.getElementsByTagName("gmd:MD_Metadata");

var ressources = tagObj[0].getElementsByTagName("gmd:transferOptions")[0].getElementsByTagName("gmd:MD_DigitalTransferOptions");
for (var i = 0; i < ressources.length; i++) {
    
    var online = ressources[i].getElementsByTagName("gmd:onLine");
    for (var j = 0; j < online.length; j++) {
        
        var onlineRessource = online[j].getElementsByTagName("gmd:CI_OnlineResource")[0].getElementsByTagName("gmd:linkage")[0].getElementsByTagName("gmd:URL")[0].innerHTML;
        onlineRessource = urlencode(onlineRessource);
        online[j].getElementsByTagName("gmd:CI_OnlineResource")[0].getElementsByTagName("gmd:linkage")[0].getElementsByTagName("gmd:URL")[0].nodeValue =urlencode(onlineRessource);
        var hte = online[j].getElementsByTagName("gmd:CI_OnlineResource")[0].getElementsByTagName("gmd:linkage")[0].getElementsByTagName("gmd:URL")[0];
        hte.innerHTML = urlencode(onlineRessource);

    }
    
}

var description = tagObj[0].getElementsByTagName("gmd:identificationInfo")[0].getElementsByTagName("gmd:MD_DataIdentification")[0].getElementsByTagName("gmd:abstract")[0].getElementsByTagName("gco:CharacterString")[0].innerHTML;
description.innerHTML = convert(description);

tagObj[0].getElementsByTagName("gmd:identificationInfo")[0].getElementsByTagName("gmd:MD_DataIdentification")[0].getElementsByTagName("gmd:abstract")[0].getElementsByTagName("gco:CharacterString")[0].innerHTML=convert(description);
tagObj[0].getElementsByTagName("gmd:identificationInfo")[0].getElementsByTagName("gmd:MD_DataIdentification")[0].getElementsByTagName("gmd:abstract")[0].getElementsByTagName("gco:CharacterString")[0].nodeValue=convert(description);

var filedes = tagObj[0].getElementsByTagName("gmd:graphicOverview")[0].getElementsByTagName("gmd:MD_BrowseGraphic")[0].getElementsByTagName("gmd:fileDescription")[0].getElementsByTagName("gco:CharacterString")[0].innerHTML;
filedes = convert(filedes);
tagObj[0].getElementsByTagName("gmd:graphicOverview")[0].getElementsByTagName("gmd:MD_BrowseGraphic")[0].getElementsByTagName("gmd:fileDescription")[0].getElementsByTagName("gco:CharacterString")[0].innerHTML = filedes;
tagObj[0].getElementsByTagName("gmd:graphicOverview")[0].getElementsByTagName("gmd:MD_BrowseGraphic")[0].getElementsByTagName("gmd:fileDescription")[0].getElementsByTagName("gco:CharacterString")[0].nodeValue = filedes;
console.log(filedes);


return xmlDoc;

}


//open popup export dataset 
function openExportPopup(event,xml=false){

    //console.log(xml);
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
                console.log(xmltext);
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
