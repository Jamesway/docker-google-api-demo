$(function() {
    $('.message-snippet').on('click', function () {
        
        $('#messageModal .modal-body').html($(this).data('message'));
    })
});