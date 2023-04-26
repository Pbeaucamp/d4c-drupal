// $ = jQuery;
$j = jQuery;

function copyValue(widget) {
    var textArea = document.createElement("textarea");
    textArea.value = widget;
    textArea.style.position = "fixed"; //avoid scrolling to bottom
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        var res =  document.execCommand('copy');
    } catch(e) {
        console.error(e);
    }
    document.body.removeChild(textArea);
}

function search() {
    var url = arguments[0];

    var first = !url.includes("?");
    if (first) {
        url += "?";
    }

    for (var i = 1; i < arguments.length; i++) {
        console.log(arguments[i]);

        var value = null;
        var parameters = arguments[i];
        if (parameters[0] == 'text') {
            var elementId = parameters[1];
            value = $j('#' + elementId).val();
        }
        else if (parameters[0] == 'select') {
            var elementId = parameters[1];
            value = $j('#' + elementId).val();
        }

        if (value) {
            var queryParameterName = parameters[2];
            url += (first ? "" : "&") + queryParameterName + "=" + value;
            first = false;
        }
    }

    window.location.href = url;
}

function clearSearch() {
    var url = arguments[0];
    window.location.href = url;
}