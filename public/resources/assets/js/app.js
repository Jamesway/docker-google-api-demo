$(function() {

    //decode gmail api message
    $('.message-snippet').on('click', function () {

        //1) gmail api encodes in base64url
        //2) unicode characters aren't handled by atob see: https://developer.mozilla.org/en-US/docs/Web/API/WindowBase64/Base64_encoding_and_decoding
        //specifically the "Unicode problem" solution 2

        //base64url to base64
        var base64_str = $(this).data('message').replace(/_/g, '/').replace(/-/g, '+');

        //byte array
        var byte_arr = base64js.toByteArray(base64_str);

        //decode to utf-8
        var msg = new TextDecoderLite('utf-8').decode(byte_arr);

        $('#messageModal .modal-body').html(msg);
    })
});
