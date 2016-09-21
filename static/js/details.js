// Common function
var service, host;


// Add a comment
function comment()
{

    // Modal content
    var mc = '<div class="modal-content">';
    mc+= '<h4>Add a comment</h4>';
    mc+= '<div><textarea required="required" class="materialize-textarea" placeholder="Your comment goes here."></textarea></div>';
    mc+= '</div>';
    mc+= '<div class="modal-footer">';
    mc+= '<a href="#!" class="btn modal-action modal-close waves-effect btn-flat">Cancel</a>';
    mc+= '<input type="submit" value="Save comment" class="btn waves-effect waves-light deep-orange lighten-1" />';
    mc+= '</div>';

    // Assign content
    $('#modal').html(mc).openModal();

}


// Force recheck
function recheck()
{

    // Lock button
    $(this).addClass('disabled');

    // Run action
    $.ajax({
        'url': '/ws/action.php',
        'method': 'POST',
        'data': {
            'action': 'recheck',
            'service': service,
            'host': host,
        },
        'context': this,
        'complete': function(xhr){
            if (xhr.status == 200) Materialize.toast('Check forced', 4000, 'green normal-text');
            else Materialize.toast('Fail to send check command', 4000, 'red normal-text');
            $(this).removeClass('disabled');
        }
    });

}


$(document).ready(function(){

    // Tooltip
    $('[data-tooltip]').tooltip();

    // Bind events
    $('[data-event="recheck"]').on('click',recheck);

    // Append modal
    $('body').append('<div id="modal" class="modal"></div>');

    // Capture host & service
    var loc = document.location.href;
    var rex = new RegExp('/status/([^/]+)/([^/#]+)');
    var m = rex.exec(loc);
    host = m[1];
    service = m[2];

});
