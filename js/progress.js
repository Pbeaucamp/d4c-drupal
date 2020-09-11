$ = jQuery;

function showProgress() {
    $('#progress').show();
    $('#progress').append('<div id="progress-content" class="progress-modal-content"><div id="progress-bar"/><div id="progress-message"/></div>');  
}

function updateProgress(percentage, mainMessage, message) {
    updateProgessBar(percentage);
    if (mainMessage) {
        $('#progress-message').html('<p>' + mainMessage + '</p>');
    }
    else {
        $('#progress-message').html('<p>' + message + '</p>');
    }
}

function updateProgessBar(percentage) {
    $('#progress-bar').animate({
        width: percentage + "%"
    }, 2000 );
}